<?php
declare(strict_types=1);

final class RegistrationService
{
    public function __construct(private PDO $db) {}

    public function register(int $userId, int $courseId): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT s.* FROM students s WHERE s.user_id=? FOR UPDATE');
            $stmt->execute([$userId]);
            $student = $stmt->fetch();
            if (!$student) throw new DomainException('Student profile was not found.');
            $stmt = $this->db->query('SELECT *, (NOW() BETWEEN registration_starts_at AND registration_ends_at) AS registration_open FROM academic_terms WHERE is_current=1 LIMIT 1');
            $term = $stmt->fetch();
            if (!$term) throw new DomainException('There is no active academic term.');
            if (!(int)$term['registration_open']) {
                throw new DomainException('Course registration is not open for this academic term.');
            }
            $stmt = $this->db->prepare('SELECT * FROM courses WHERE id=? AND active=1 FOR UPDATE');
            $stmt->execute([$courseId]);
            $course = $stmt->fetch();
            if (!$course) throw new DomainException('This course is unavailable.');
            if (($course['department_id'] !== null && (int)$course['department_id'] !== (int)$student['department_id']) || (int)$course['semester'] !== (int)$student['semester']) {
                throw new DomainException('This course is not available to your department and semester.');
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM registrations WHERE student_id=? AND course_id=? AND term_id=? AND status IN ('pending','approved')");
            $stmt->execute([$student['id'], $courseId, $term['id']]);
            if ((int)$stmt->fetchColumn() > 0) throw new DomainException('You are already registered for this course.');
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM registrations WHERE course_id=? AND term_id=? AND status IN ('pending','approved')");
            $stmt->execute([$courseId, $term['id']]);
            if ((int)$stmt->fetchColumn() >= (int)$course['capacity']) throw new DomainException('This course is full; no seats are available.');
            $missing = (new PrerequisiteService($this->db))->missing((int)$student['id'], $courseId);
            if ($missing) throw new DomainException('Complete prerequisites first: ' . implode(', ', array_column($missing, 'code')) . '.');
            $conflicts = (new ScheduleService($this->db))->conflicts((int)$student['id'], $courseId, (int)$term['id']);
            if ($conflicts) throw new DomainException('Schedule overlaps with: ' . implode(', ', array_column($conflicts, 'code')) . '.');
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(c.credits),0) FROM registrations r JOIN courses c ON c.id=r.course_id WHERE r.student_id=? AND r.term_id=? AND r.status IN ('pending','approved')");
            $stmt->execute([$student['id'], $term['id']]);
            if ((int)$stmt->fetchColumn() + (int)$course['credits'] > (int)$term['credit_limit']) throw new DomainException('This course exceeds the term credit limit of ' . $term['credit_limit'] . '.');
            $stmt = $this->db->prepare("INSERT INTO registrations(student_id,course_id,term_id,status) VALUES(?,?,?,'pending')");
            $stmt->execute([$student['id'], $courseId, $term['id']]);
            $registrationId = (int)$this->db->lastInsertId();
            (new NotificationService($this->db))->send($userId, "Registration request received for {$course['code']}.", 'student/registrations.php');
            $admins = $this->db->query("SELECT id FROM users WHERE role='admin' AND active=1")->fetchAll(PDO::FETCH_COLUMN);
            $notifyAdmin = $this->db->prepare('INSERT INTO notifications(user_id,message,url) VALUES(?,?,?)');
            foreach ($admins as $adminId) {
                $notifyAdmin->execute([(int)$adminId, "New registration request for {$course['code']}.", 'admin/registrations.php?status=pending']);
            }
            $this->audit($userId, 'registration_requested', 'registration', $registrationId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException && (string)$e->getCode() === '23000') throw new DomainException('You already have a registration for this course and term.');
            throw $e;
        }
    }

    public function cancel(int $userId, int $registrationId, bool $admin = false): void
    {
        $this->db->beginTransaction();
        try {
            $query = "SELECT r.*,t.registration_ends_at,(NOW()>t.registration_ends_at) AS deadline_passed FROM registrations r JOIN academic_terms t ON t.id=r.term_id WHERE r.id=?";
            $stmt = $this->db->prepare($query . ($admin ? ' FOR UPDATE' : ' AND r.student_id=(SELECT id FROM students WHERE user_id=?) FOR UPDATE'));
            $stmt->execute($admin ? [$registrationId] : [$registrationId, $userId]);
            $registration = $stmt->fetch();
            if (!$registration) throw new DomainException('Registration was not found.');
            if (in_array($registration['status'], ['cancelled', 'rejected'], true)) throw new DomainException('This registration is already closed.');
            if (!$admin && (int)$registration['deadline_passed']) throw new DomainException('The cancellation deadline has passed.');
            $stmt = $this->db->prepare("UPDATE registrations SET status='cancelled',decided_at=NOW(),decided_by=?,decision_note=? WHERE id=?");
            $stmt->execute([$admin ? $userId : null, $admin ? 'Cancelled by administrator' : 'Cancelled by student', $registrationId]);
            $this->db->prepare('INSERT INTO notifications(user_id,message,url) SELECT user_id,?,? FROM students WHERE id=?')
                ->execute(['Registration cancelled; the seat is available again.', 'student/registrations.php', $registration['student_id']]);
            $this->audit($userId, 'registration_cancelled', 'registration', $registrationId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function decide(int $adminId, int $registrationId, string $status, string $note = ''): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) throw new DomainException('Invalid decision.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT r.*,s.user_id FROM registrations r JOIN students s ON s.id=r.student_id WHERE r.id=? FOR UPDATE');
            $stmt->execute([$registrationId]);
            $registration = $stmt->fetch();
            if (!$registration || $registration['status'] !== 'pending') throw new DomainException('Only pending registrations can be decided.');
            $stmt = $this->db->prepare('UPDATE registrations SET status=?,decided_at=NOW(),decided_by=?,decision_note=? WHERE id=?');
            $stmt->execute([$status, $adminId, trim($note) ?: null, $registrationId]);
            (new NotificationService($this->db))->send((int)$registration['user_id'], 'Your registration request was ' . $status . '.', 'student/registrations.php');
            $this->audit($adminId, 'registration_' . $status, 'registration', $registrationId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function audit(int $actor, string $action, string $type, int $id): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)');
        $stmt->execute([$actor, $action, $type, $id, $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
