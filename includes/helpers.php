<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_URL . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $message;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        flash('warning', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
    $stmt = Database::connection()->prepare('SELECT role,active FROM users WHERE id=?');
    $stmt->execute([current_user_id()]);
    $account = $stmt->fetch();
    if (!$account || !(int)$account['active'] || $account['role'] !== ($_SESSION['role'] ?? '')) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('danger', 'Your account is unavailable. Please contact the administrator.');
        redirect('auth/login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('You are not authorized to access this page.');
    }
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function post_string(string $key, int $maxLength = 500): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return mb_substr($value, 0, $maxLength);
}
