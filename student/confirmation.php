<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$db=Database::connection();$profile=(new Student($db))->profile(current_user_id());$registrationId=(int)($_GET['id']??0);
$stmt=$db->prepare('SELECT r.*,c.code,c.title,c.credits,c.faculty_name,t.name term_name FROM registrations r JOIN courses c ON c.id=r.course_id JOIN academic_terms t ON t.id=r.term_id WHERE r.id=? AND r.student_id=?');
$stmt->execute([$registrationId,$profile['id']]);$r=$stmt->fetch();
if(!$r){http_response_code(404);exit('Registration not found.');}
page_start('Registration confirmation');
?>
<div class="content-width"><?php page_title('Registration confirmation','Keep this confirmation for your records.'); ?><div class="panel">
<div class="text-end no-print"><button class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div><div class="text-center py-4"><i class="bi bi-mortarboard-fill fs-1 text-primary"></i><h2 class="h3 mt-2">Course Registration</h2><p class="text-muted"><?= e($r['term_name']) ?></p></div>
<div class="row g-3"><div class="col-md-6"><small class="text-muted">Student</small><div class="fw-semibold"><?= e($profile['full_name']) ?></div></div><div class="col-md-6"><small class="text-muted">Register number</small><div class="fw-semibold"><?= e($profile['register_number']) ?></div></div><div class="col-md-6"><small class="text-muted">Department / Semester</small><div class="fw-semibold"><?= e($profile['department_name']) ?> / <?= (int)$profile['semester'] ?></div></div><div class="col-md-6"><small class="text-muted">Course</small><div class="fw-semibold"><?= e($r['code'].' · '.$r['title']) ?></div></div><div class="col-md-6"><small class="text-muted">Credits / Faculty</small><div class="fw-semibold"><?= (int)$r['credits'] ?> credits · <?= e($r['faculty_name']) ?></div></div><div class="col-md-6"><small class="text-muted">Status / Requested</small><div class="fw-semibold"><?= status_badge($r['status']) ?> · <?= e(date('M j, Y',strtotime($r['requested_at']))) ?></div></div></div><hr><p class="small text-muted mb-0">Reference #<?= (int)$r['id'] ?>. This request is subject to the college registration policy.</p>
</div></div>
<style>@media print{.topbar,.sidebar,.page-heading,.no-print{display:none!important}.main-content{padding:0!important}.panel{box-shadow:none!important;border:0!important}}</style>
<?php page_end(); ?>
