<?php
declare(strict_types=1);

final class Student extends User
{
    public function profile(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*,u.email,d.name AS department_name,d.code AS department_code
             FROM students s JOIN users u ON u.id=s.user_id JOIN departments d ON d.id=s.department_id
             WHERE s.user_id=?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function byId(int $studentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM students WHERE id=?');
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }
}
