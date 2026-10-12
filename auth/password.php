<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        (new AuthService(Database::connection()))->changePassword(current_user_id(), (string)($_POST['current_password'] ?? ''), (string)($_POST['new_password'] ?? ''));
        flash('success', 'Your password has been changed.');
        redirect(($_SESSION['role'] === 'admin' ? 'admin' : 'student') . '/index.php');
    } catch (DomainException $e) { $error = $e->getMessage(); }
}
page_start('Change password');
?>
<div class="content-width"><?php page_title('Change password','Keep your account secure with a unique password.'); ?><div class="panel"><div class="row"><div class="col-lg-7">
 <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Current password</label><input class="form-control" name="current_password" type="password" required autocomplete="current-password"></div><div class="mb-4"><label class="form-label">New password (10+ characters)</label><input class="form-control" name="new_password" type="password" minlength="10" required autocomplete="new-password"></div><button class="btn btn-primary">Update password</button></form>
</div></div></div></div>
<?php page_end(); ?>
