<?php
require_once __DIR__ . '/middleware/bootstrap.php';
$db = Database::connection();
$adminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
if ($adminCount > 0) {
    page_start('Setup complete');
    echo '<div class="auth-wrap"><div class="auth-card text-center"><i class="bi bi-shield-check fs-1 text-success"></i><h1 class="h3 mt-3">Administrator already configured</h1><p class="text-muted">First-run setup is disabled because an administrator exists.</p><a class="btn btn-primary" href="' . e(url('auth/login.php')) . '">Go to sign in</a></div></div>';
    page_end();
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $setupLock = (int)$db->query("SELECT GET_LOCK('online_course_registration_first_admin',5)")->fetchColumn();
    if ($setupLock !== 1) {
        $error = 'Another first-run setup is in progress. Please retry in a moment.';
    } else {
    try {
        if ((int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0) {
            throw new DomainException('An administrator has already been created. Setup is closed.');
        }
        $email = mb_strtolower(post_string('email', 190));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $password !== $confirm) {
            throw new DomainException('Use a valid email, a 12-character password, and matching confirmation.');
        }
        $departments = $db->query('SELECT id FROM departments WHERE active=1 ORDER BY id')->fetchAll();
        if (!$departments) throw new DomainException('Import database.sql first so the sample departments and terms exist.');
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO users(email,password_hash,role) VALUES(?,?,'admin')");
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        $adminId = (int)$db->lastInsertId();
        $demoEmail = 'demo.student@college.test';
        $stmt = $db->prepare("INSERT INTO users(email,password_hash,role) VALUES(?,?,'student')");
        $stmt->execute([$demoEmail, password_hash('DemoStudent!2026', PASSWORD_DEFAULT)]);
        $demoUser = (int)$db->lastInsertId();
        $stmt = $db->prepare('INSERT INTO students(user_id,register_number,full_name,department_id,semester,academic_year) VALUES(?,?,?,?,1,?)');
        $stmt->execute([$demoUser, 'DEMO2026', 'Demo Student', (int)$departments[0]['id'], '2026-2027']);
        $stmt = $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,'first_admin_created','user',?,?)");
        $stmt->execute([$adminId, $adminId, $_SERVER['REMOTE_ADDR'] ?? null]);
        $db->commit();
        flash('success', 'Administrator created. Demo student: demo.student@college.test / DemoStudent!2026');
        redirect('auth/login.php');
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (!$e instanceof DomainException && !($e instanceof PDOException && (string)$e->getCode() === '23000')) error_log('First admin setup error: ' . $e->getMessage());
        $error = $e instanceof DomainException ? $e->getMessage() : ($e instanceof PDOException && (string)$e->getCode() === '23000' ? 'That email is already in use.' : 'Setup could not be completed. Check database configuration and try again.');
    } finally {
        $db->query("SELECT RELEASE_LOCK('online_course_registration_first_admin')");
    }
    }
}
page_start('Initial administrator setup');
?>
<div class="auth-wrap"><div class="auth-card">
 <div class="text-center mb-4"><div class="stat-icon mx-auto"><i class="bi bi-shield-lock"></i></div><h1 class="h3 mt-3">Create your administrator</h1><p class="text-muted">First-run setup is available only until the first admin account exists.</p></div>
 <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Administrator email</label><input class="form-control" name="email" type="email" required maxlength="190" autocomplete="email"></div>
 <div class="mb-3"><label class="form-label">Password (12+ characters)</label><input class="form-control" name="password" type="password" required minlength="12" autocomplete="new-password"></div>
 <div class="mb-4"><label class="form-label">Confirm password</label><input class="form-control" name="confirm_password" type="password" required minlength="12" autocomplete="new-password"></div>
 <button class="btn btn-primary w-100">Create admin & demo student</button></form>
 <div class="alert alert-info mt-4 mb-0 small">This one-time action also creates a demo student. Change the demo password after signing in.</div>
</div></div>
<?php page_end(); ?>
