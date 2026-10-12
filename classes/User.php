<?php
declare(strict_types=1);

class User
{
    public function __construct(protected PDO $db) {}

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id,email,role,active,created_at FROM users WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
