<?php
declare(strict_types=1);

final class RegistrationController
{
    public function __construct(private RegistrationService $registrations) {}

    public function register(int $userId, int $courseId): void
    {
        $this->registrations->register($userId, $courseId);
    }

    public function cancel(int $userId, int $registrationId, bool $admin = false): void
    {
        $this->registrations->cancel($userId, $registrationId, $admin);
    }

    public function decide(int $adminId, int $registrationId, string $status, string $note = ''): void
    {
        $this->registrations->decide($adminId, $registrationId, $status, $note);
    }
}
