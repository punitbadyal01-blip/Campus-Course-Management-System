<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$db=Database::connection();$student=(new Student($db))->profile(current_user_id());
$rows=(new Registration($db))->forStudent((int)$student['id']);
page_start('My registrations','student/registrations.php');
?>
<div class="content-width"><?php page_title('My registrations','Track decisions and manage your current course requests.'); ?>
<div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Course</th><th>Term</th><th>Credits</th><th>Status</th><th>Request date</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['code']) ?></strong><div class="small text-muted"><?= e($r['title']) ?></div></td><td><?= e($r['term_name']) ?></td><td><?= (int)$r['credits'] ?></td><td><?= status_badge($r['status']) ?><?php if($r['decision_note']): ?><div class="small text-muted mt-1"><?= e($r['decision_note']) ?></div><?php endif; ?></td><td class="nowrap"><?= e(date('M j, Y',strtotime($r['requested_at']))) ?></td><td class="table-actions"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(url('student/confirmation.php?id='.(int)$r['id'])) ?>">Print</a><?php if(in_array($r['status'],['pending','approved'],true)): ?><form class="d-inline" method="post" action="<?= e(url('student/cancel.php')) ?>" data-confirm-submit="Cancel this registration?"><?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-journal-check"></i><h3>No registrations yet</h3><a href="<?= e(url('student/courses.php')) ?>">Explore courses</a></div></td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php page_end(); ?>
