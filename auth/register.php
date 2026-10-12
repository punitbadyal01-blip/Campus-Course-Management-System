<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
if (!empty($_SESSION['user_id'])) redirect('student/index.php');
$db = Database::connection();
$departments = $db->query('SELECT id,name FROM departments WHERE active=1 ORDER BY name')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $userId = (new AuthService($db))->registerStudent([
            'email'=>post_string('email',190),'password'=>(string)($_POST['password'] ?? ''),
            'register_number'=>post_string('register_number',40),'full_name'=>post_string('full_name',160),
            'department_id'=>(int)($_POST['department_id'] ?? 0),'semester'=>(int)($_POST['semester'] ?? 0),
            'academic_year'=>post_string('academic_year',20),'phone'=>post_string('phone',30),
        ]);
        if ((new AuthService($db))->login(post_string('email',190),(string)($_POST['password'] ?? ''),'student')) redirect('student/index.php');
        flash('success', 'Account created. Sign in with your email and password.');
        redirect('auth/login.php');
    } catch (Throwable $e) {
        if (!$e instanceof DomainException && !($e instanceof PDOException && (string)$e->getCode() === '23000')) error_log('Student registration error: ' . $e->getMessage());
        $error = $e instanceof DomainException ? $e->getMessage() : 'Registration could not be completed. Check the submitted details.';
    }
}
page_start('Student registration');
?>
<div class="auth-wrap"><div class="auth-card">
 <h1 class="h3 fw-bold">Create student account</h1><p class="text-muted mb-4">Use your official academic details.</p>
 <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <?php if (!$departments): ?><div class="alert alert-warning">No departments are configured. Ask the administrator to add departments first.</div><?php else: ?>
 <form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" required maxlength="160" autocomplete="name"></div>
  <div class="row g-3"><div class="col-md-6"><label class="form-label">Register number</label><input class="form-control" name="register_number" required maxlength="40"></div><div class="col-md-6"><label class="form-label">Phone (optional)</label><input class="form-control" name="phone" maxlength="30"></div></div>
  <div class="mt-3"><label class="form-label">College email</label><input class="form-control" type="email" name="email" required maxlength="190" autocomplete="email"></div>
  <div class="row g-3 mt-0"><div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id" required><option value="">Choose...</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Semester</label><select class="form-select" name="semester" required><?php for($s=1;$s<=12;$s++): ?><option value="<?= $s ?>"><?= $s ?></option><?php endfor; ?></select></div><div class="col-md-3"><label class="form-label">Academic year</label><input class="form-control" name="academic_year" value="2026-2027" required maxlength="20"></div></div>
  <div class="mt-3 mb-4"><label class="form-label">Password (10+ characters)</label><input class="form-control" type="password" name="password" required minlength="10" autocomplete="new-password"></div><button class="btn btn-primary w-100">Create account</button>
 </form><?php endif; ?>
 <p class="text-center text-muted small mt-4 mb-0">Already registered? <a href="<?= e(url('auth/login.php')) ?>">Sign in</a></p>
</div></div>
<?php page_end(); ?>
