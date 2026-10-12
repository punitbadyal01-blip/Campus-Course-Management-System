<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$profile=(new Student(Database::connection()))->profile(current_user_id());
$rows=(new Registration(Database::connection()))->forStudent((int)$profile['id']);
page_start('Registration history','student/history.php');
?>
<div class="content-width"><?php page_title('Registration history','All requests across academic terms.'); ?><div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Course</th><th>Term</th><th>Status</th><th>Requested</th><th>Decision</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['code'].' · '.$r['title']) ?></td><td><?= e($r['term_name']) ?></td><td><?= status_badge($r['status']) ?></td><td><?= e(date('M j, Y H:i',strtotime($r['requested_at']))) ?></td><td><?= e($r['decided_at']?date('M j, Y',strtotime($r['decided_at'])):'—') ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5"><div class="empty-state"><h3>No history available</h3><p>Your registration requests will appear here.</p></div></td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php page_end(); ?>
