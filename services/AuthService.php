<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(private PDO $db) {}

    public function login(string $email, string $password, string $role): bool
    {
        $email = mb_strtolower(trim($email));
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $lock = $this->db->prepare('SELECT 1 FROM login_attempts WHERE email=? AND ip_hash=? AND locked_until>NOW()');
        $lock->execute([$email, $ipHash]);
        if ($lock->fetchColumn()) return false;

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email=? AND role=? AND active=1 LIMIT 1');
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($email, $ipHash);
            return false;
        }
        $this->db->prepare('DELETE FROM login_attempts WHERE email=? AND ip_hash=?')->execute([$email, $ipHash]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $this->audit((int)$user['id'], 'login', 'user', (int)$user['id']);
        return true;
    }

    private function recordFailedLogin(string $email, string $ipHash): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO login_attempts(email,ip_hash,attempts,last_attempt_at) VALUES(?,?,1,NOW())
                 ON DUPLICATE KEY UPDATE
                   attempts=IF(last_attempt_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE),1,attempts+1),
                   last_attempt_at=NOW(),locked_until=NULL'
            );
            $stmt->execute([$email, $ipHash]);
            $stmt = $this->db->prepare('SELECT attempts FROM login_attempts WHERE email=? AND ip_hash=? FOR UPDATE');
            $stmt->execute([$email, $ipHash]);
            if ((int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS) {
                $this->db->prepare('UPDATE login_attempts SET locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email=? AND ip_hash=?')
                    ->execute([$email, $ipHash]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function registerStudent(array $data): int
    {
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        if (strlen($data['password'] ?? '') < 10) throw new DomainException('Password must contain at least 10 characters.');
        if (strlen($data['register_number'] ?? '') < 3 || strlen($data['full_name'] ?? '') < 2) throw new DomainException('Enter a valid name and register number.');
        if (!in_array((int)($data['semester'] ?? 0), range(1, 12), true)) throw new DomainException('Select a valid semester.');
        if (!preg_match('/^\d{4}-\d{4}$/', (string)($data['academic_year'] ?? ''))) throw new DomainException('Enter the academic year in YYYY-YYYY format.');
        if (strlen((string)$data['email']) > 190) throw new DomainException('Email address is too long.');
        if (mb_strtolower(trim((string)$data['email'])) === 'demo.student@college.test' || strtoupper(trim((string)$data['register_number'])) === 'DEMO2026') {
            throw new DomainException('That email address or register number is reserved for the local demo account.');
        }
        $stmt = $this->db->prepare('SELECT id FROM departments WHERE id=? AND active=1');
        $stmt->execute([(int)$data['department_id']]);
        if (!$stmt->fetch()) throw new DomainException('Select a valid department.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO users(email,password_hash,role) VALUES(?,?,'student')");
            $stmt->execute([mb_strtolower(trim($data['email'])), password_hash($data['password'], PASSWORD_DEFAULT)]);
            $userId = (int)$this->db->lastInsertId();
            $stmt = $this->db->prepare('INSERT INTO students(user_id,register_number,full_name,department_id,semester,academic_year,phone) VALUES(?,?,?,?,?,?,?)');
            $stmt->execute([$userId, strtoupper(trim($data['register_number'])), trim($data['full_name']), (int)$data['department_id'], (int)$data['semester'], trim($data['academic_year']), trim($data['phone'] ?? '') ?: null]);
            $this->audit($userId, 'student_registered', 'student', (int)$this->db->lastInsertId());
            $this->db->commit();
            return $userId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException && (string)$e->getCode() === '23000') throw new DomainException('That email or register number is already registered.');
            throw $e;
        }
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        if (strlen($new) < 10) throw new DomainException('New password must contain at least 10 characters.');
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) throw new DomainException('Your current password is incorrect.');
        $stmt = $this->db->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        $this->audit($userId, 'password_changed', 'user', $userId);
    }

    private function audit(int $actor, string $action, string $entity, int $id): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)');
        $stmt->execute([$actor, $action, $entity, $id, $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
