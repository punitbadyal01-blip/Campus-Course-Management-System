<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    page_start('Sign out');
    echo '<div class="auth-wrap"><div class="auth-card"><h1 class="h4">Sign out?</h1><form method="post">' . csrf_field() . '<button class="btn btn-danger">Sign out</button> <a class="btn btn-light" href="' . e(url('index.php')) . '">Cancel</a></form></div></div>';
    page_end();
    exit;
}
verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: ' . url('index.php'));
exit;
