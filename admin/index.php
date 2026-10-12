<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('admin');
$stats=(new Admin(Database::connection()))->dashboardStats();
page_start('Administrator dashboard','admin/index.php');
?>
<div class="content-width"><?php page_title('Administrator dashboard','Course registration overview and recent activity.'); ?>
<div class="row g-3 mb-4"><?php foreach([['Active students',$stats['students'],'people'],['Active courses',$stats['courses'],'book'],['Current enrollments',$stats['registrations'],'journal-check'],['Pending requests',$stats['pending'],'hourglass-split']] as [$label,$value,$icon]): ?><div class="col-6 col-xl-3"><div class="stat-card d-flex justify-content-between align-items-start"><div><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= (int)$value ?></div></div><div class="stat-icon"><i class="bi bi-<?= e($icon) ?>"></i></div></div></div><?php endforeach; ?></div>
<div class="row g-4"><div class="col-lg-7"><div class="panel"><h2 class="h5 fw-bold mb-3">Enrollment by course</h2><canvas id="enrollmentChart" height="120"></canvas></div></div><div class="col-lg-5"><div class="panel h-100"><div class="d-flex justify-content-between mb-3"><h2 class="h5 fw-bold mb-0">Recent activity</h2><a href="<?= e(url('admin/registrations.php')) ?>">All requests</a></div>
<?php foreach($stats['activity'] as $a): ?><div class="d-flex justify-content-between align-items-start border-bottom py-3 gap-2"><div><strong><?= e($a['code']) ?></strong><div class="small text-muted"><?= e($a['full_name']) ?> · <?= e(date('M j, H:i',strtotime($a['requested_at']))) ?></div></div><?= status_badge($a['status']) ?></div><?php endforeach; if(!$stats['activity']): ?><div class="empty-state"><i class="bi bi-activity"></i><h3>No activity yet</h3></div><?php endif; ?>
</div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script><script>
new Chart(document.getElementById('enrollmentChart'),{type:'bar',data:{labels:<?= json_encode(array_column($stats['chart'],'title'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,datasets:[{label:'Active enrollments',data:<?= json_encode(array_map('intval',array_column($stats['chart'],'total'))) ?>,backgroundColor:'#3972e6',borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
</script>
<?php page_end(); ?>
