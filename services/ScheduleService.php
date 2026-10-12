<?php
declare(strict_types=1);

final class ScheduleService
{
    public function __construct(private PDO $db) {}

    public function conflicts(int $studentId, int $courseId, int $termId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT c.code,c.title FROM registrations r
             JOIN course_schedules existing ON existing.course_id=r.course_id
             JOIN course_schedules requested ON requested.course_id=?
             JOIN courses c ON c.id=r.course_id
             WHERE r.student_id=? AND r.term_id=? AND r.status IN ('pending','approved')
             AND existing.day_of_week=requested.day_of_week
             AND existing.starts_at < requested.ends_at AND requested.starts_at < existing.ends_at"
        );
        $stmt->execute([$courseId, $studentId, $termId]);
        return $stmt->fetchAll();
    }
}
