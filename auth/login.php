<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
if (!empty($_SESSION['user_id'])) redirect($_SESSION['role'] === 'admin' ? 'admin/index.php' : 'student/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $now = time();
    $attempts = $_SESSION['_login_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, static fn($time) => $time > $now - LOGIN_LOCK_MINUTES * 60));
    if (count($attempts) >= MAX_LOGIN_ATTEMPTS) {
        $error = 'Too many sign-in attempts. Try again in 15 minutes.';
    } elseif ((new AuthService(Database::connection()))->login(post_string('email', 190), (string)($_POST['password'] ?? ''), ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'student')) {
        unset($_SESSION['_login_attempts']);
        redirect($_SESSION['role'] === 'admin' ? 'admin/index.php' : 'student/index.php');
    } else {
        $attempts[] = $now;
        $_SESSION['_login_attempts'] = $attempts;
        $error = 'Email, password, or account type is incorrect.';
    }
}
page_start('Sign in');
?>
<div class="auth-wrap"><div class="auth-card">
 <div class="text-center mb-4"><div class="stat-icon mx-auto"><i class="bi bi-mortarboard"></i></div><h1 class="h3 mt-3">Welcome back</h1><p class="text-muted">Sign in to continue to the course portal.</p></div>
 <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Account type</label><select class="form-select" name="role"><option value="student">Student</option><option value="admin">Administrator</option></select></div>
 <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required autocomplete="username"></div>
 <div class="mb-4"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required autocomplete="current-password"></div>
 <button class="btn btn-primary w-100">Sign in</button></form>
 <p class="text-center text-muted small mt-4 mb-0">New student? <a href="<?= e(url('auth/register.php')) ?>">Create an account</a></p>
</div></div>
<?php page_end(); ?>
