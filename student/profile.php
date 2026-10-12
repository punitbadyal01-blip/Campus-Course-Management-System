<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$db=Database::connection();$profile=(new Student($db))->profile(current_user_id());$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$phone=post_string('phone',30);$stmt=$db->prepare('UPDATE students SET phone=? WHERE user_id=?');$stmt->execute([$phone?:null,current_user_id()]);flash('success','Profile updated.');redirect('student/profile.php');}
page_start('My profile','student/profile.php');
?>
<div class="content-width"><?php page_title('My profile','Your academic and contact information.'); ?><div class="panel"><div class="row g-4"><div class="col-lg-7"><dl class="row mb-0"><?php foreach(['full_name'=>'Full name','register_number'=>'Register number','email'=>'Email','department_name'=>'Department','semester'=>'Semester','academic_year'=>'Academic year'] as $key=>$label): ?><dt class="col-sm-4 text-muted fw-normal"><?= e($label) ?></dt><dd class="col-sm-8 fw-semibold"><?= e($profile[$key]) ?></dd><?php endforeach; ?></dl><hr><form method="post"><?= csrf_field() ?><label class="form-label">Phone</label><div class="input-group"><input class="form-control" name="phone" value="<?= e($profile['phone']??'') ?>" maxlength="30"><button class="btn btn-primary">Update phone</button></div></form></div></div></div></div>
<?php page_end(); ?>
