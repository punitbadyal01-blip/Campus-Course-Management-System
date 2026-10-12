<?php
declare(strict_types=1);

final class Registration
{
    public function __construct(private PDO $db) {}

    public function forStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,c.code,c.title,c.credits,c.faculty_name,t.name AS term_name,
             GROUP_CONCAT(DISTINCT CONCAT(cs.day_of_week,':',TIME_FORMAT(cs.starts_at,'%H:%i'),'-',TIME_FORMAT(cs.ends_at,'%H:%i')) ORDER BY cs.day_of_week SEPARATOR ', ') AS schedule
             FROM registrations r JOIN courses c ON c.id=r.course_id JOIN academic_terms t ON t.id=r.term_id
             LEFT JOIN course_schedules cs ON cs.course_id=c.id
             WHERE r.student_id=? GROUP BY r.id ORDER BY r.requested_at DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
}
