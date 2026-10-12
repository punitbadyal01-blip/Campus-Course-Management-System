<?php
declare(strict_types=1);

final class PrerequisiteService
{
    public function __construct(private PDO $db) {}

    public function missing(int $studentId, int $courseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.code,c.title FROM course_prerequisites p JOIN courses c ON c.id=p.prerequisite_course_id
             WHERE p.course_id=? AND NOT EXISTS (
               SELECT 1 FROM academic_records a WHERE a.student_id=? AND a.course_id=p.prerequisite_course_id
               AND a.completed_at<=CURRENT_DATE() AND a.grade NOT IN ('F','NC','W','I'))"
        );
        $stmt->execute([$courseId, $studentId]);
        return $stmt->fetchAll();
    }
}
