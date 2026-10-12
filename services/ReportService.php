<?php
declare(strict_types=1);

final class ReportService
{
    public function __construct(private PDO $db) {}

    public function registrations(array $filters = []): array
    {
        $sql = 'SELECT r.id,r.status,r.requested_at,r.decision_note,c.code,c.title,c.credits,s.register_number,s.full_name,d.name AS department,t.name AS term_name
                FROM registrations r JOIN students s ON s.id=r.student_id JOIN departments d ON d.id=s.department_id
                JOIN courses c ON c.id=r.course_id JOIN academic_terms t ON t.id=r.term_id WHERE 1=1';
        $params = [];
        foreach (['status' => 'r.status', 'course' => 'c.id', 'term' => 'r.term_id'] as $filter => $column) {
            if (($filters[$filter] ?? '') !== '') {
                $sql .= " AND {$column}=?";
                $params[] = $filters[$filter];
            }
        }
        $sql .= ' ORDER BY r.requested_at DESC';
        if (empty($filters['export'])) $sql .= ' LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
