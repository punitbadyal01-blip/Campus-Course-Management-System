<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$db = Database::connection();
$profile = (new Student($db))->profile(current_user_id());
if (!$profile) { http_response_code(403); exit('Student profile is unavailable.'); }
$term = $db->query('SELECT * FROM academic_terms WHERE is_current=1 LIMIT 1')->fetch();
$stats = ['active'=>0,'credits'=>0,'pending'=>0,'approved'=>0];
if ($term) {
    $stmt = $db->prepare("SELECT COUNT(*) total,COALESCE(SUM(c.credits),0) credits,SUM(r.status='pending') pending,SUM(r.status='approved') approved
                          FROM registrations r JOIN courses c ON c.id=r.course_id WHERE r.student_id=? AND r.term_id=? AND r.status IN ('pending','approved')");
    $stmt->execute([$profile['id'],$term['id']]);
    $stats = array_merge($stats, $stmt->fetch() ?: []);
}
$recent = (new Registration($db))->forStudent((int)$profile['id']);
page_start('Student dashboard','student/index.php');
?>
<div class="content-width">
<?php page_title('Good day, ' . $profile['full_name'], $profile['department_name'] . ' · Semester ' . $profile['semester'] . ($term ? ' · ' . $term['name'] : ' · No active term')); ?>
<div class="row g-3 mb-4">
 <?php foreach ([['Courses registered',(int)$stats['total'],'journal-check'],['Credits enrolled',(int)$stats['credits'],'award'],['Awaiting decision',(int)$stats['pending'],'hourglass-split'],['Approved',(int)$stats['approved'],'check-circle']] as [$label,$value,$icon]): ?>
 <div class="col-6 col-xl-3"><div class="stat-card d-flex justify-content-between align-items-start"><div><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div><div class="stat-icon"><i class="bi bi-<?= e($icon) ?>"></i></div></div></div>
 <?php endforeach; ?>
</div>
<div class="d-flex align-items-center justify-content-between mb-3"><h2 class="h5 fw-bold mb-0">Recent registrations</h2><a href="<?= e(url('student/registrations.php')) ?>">View all</a></div>
<div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Course</th><th>Term</th><th>Credits</th><th>Status</th><th>Requested</th></tr></thead><tbody>
<?php foreach (array_slice($recent,0,6) as $r): ?><tr><td><strong><?= e($r['code']) ?></strong> · <?= e($r['title']) ?></td><td><?= e($r['term_name']) ?></td><td><?= (int)$r['credits'] ?></td><td><?= status_badge($r['status']) ?></td><td><?= e(date('M j, Y',strtotime($r['requested_at']))) ?></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-journal-plus"></i><h3>Nothing registered yet</h3><p>Browse courses to find the right classes for this term.</p><a class="btn btn-primary btn-sm" href="<?= e(url('student/courses.php')) ?>">Browse courses</a></div></td></tr><?php endif; ?>
</tbody></table></div></div>
</div>
<?php page_end(); ?>
