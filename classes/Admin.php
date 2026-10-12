<?php
declare(strict_types=1);

final class Admin extends User
{
    public function dashboardStats(): array
    {
        $counts = [];
        foreach ([
            'students' => "SELECT COUNT(*) FROM users WHERE role='student' AND active=1",
            'courses' => 'SELECT COUNT(*) FROM courses WHERE active=1',
            'registrations' => "SELECT COUNT(*) FROM registrations WHERE term_id=(SELECT id FROM academic_terms WHERE is_current=1 LIMIT 1) AND status IN ('pending','approved')",
            'pending' => "SELECT COUNT(*) FROM registrations WHERE term_id=(SELECT id FROM academic_terms WHERE is_current=1 LIMIT 1) AND status='pending'",
        ] as $key => $sql) {
            $counts[$key] = (int)$this->db->query($sql)->fetchColumn();
        }
        $stmt = $this->db->query(
            "SELECT c.title, COUNT(r.id) AS total FROM courses c
             LEFT JOIN registrations r ON r.course_id=c.id AND r.term_id=(SELECT id FROM academic_terms WHERE is_current=1 LIMIT 1) AND r.status IN ('pending','approved')
             GROUP BY c.id ORDER BY total DESC LIMIT 8"
        );
        $counts['chart'] = $stmt->fetchAll();
        $counts['activity'] = $this->db->query(
            "SELECT r.id,r.status,r.requested_at,c.code,c.title,s.full_name
             FROM registrations r JOIN courses c ON c.id=r.course_id JOIN students s ON s.id=r.student_id
             ORDER BY r.requested_at DESC LIMIT 8"
        )->fetchAll();
        return $counts;
    }
}
