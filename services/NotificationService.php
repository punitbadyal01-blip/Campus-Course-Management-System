<?php
declare(strict_types=1);

final class NotificationService
{
    public function __construct(private PDO $db) {}

    public function send(int $userId, string $message, ?string $path = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO notifications(user_id,message,url) VALUES(?,?,?)');
        $stmt->execute([$userId, $message, $path]);
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markRead(int $userId, int $notificationId): void
    {
        $stmt = $this->db->prepare('UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?');
        $stmt->execute([$notificationId, $userId]);
    }
}
