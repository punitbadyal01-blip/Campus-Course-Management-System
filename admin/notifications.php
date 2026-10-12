<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('admin');
$db=Database::connection();$service=new NotificationService($db);
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();if(($_POST['action']??'')==='read_all'){$stmt=$db->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');$stmt->execute([current_user_id()]);}else $service->markRead(current_user_id(),(int)($_POST['id']??0));flash('success','Notification updated.');redirect('admin/notifications.php');}
$rows=$service->forUser(current_user_id());
page_start('Notifications','admin/notifications.php');
?>
<div class="content-width"><?php page_title('Notifications','New registration activity and account updates.'); ?><div class="panel p-0 overflow-hidden"><?php if($rows): ?><div class="d-flex justify-content-end p-3"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-sm btn-outline-secondary">Mark all read</button></form></div><ul class="list-group list-group-flush"><?php foreach($rows as $n): ?><li class="list-group-item p-3 <?= $n['read_at']?'':'bg-light' ?>"><div class="d-flex justify-content-between gap-3"><div><span class="fw-semibold"><?= e($n['message']) ?></span><div class="small text-muted"><?= e(date('M j, Y H:i',strtotime($n['created_at']))) ?></div><?php if($n['url']): ?><a class="small" href="<?= e(url($n['url'])) ?>">View details</a><?php endif; ?></div><?php if(!$n['read_at']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn btn-sm btn-outline-primary">Mark read</button></form><?php endif; ?></div></li><?php endforeach; ?></ul><?php else: ?><div class="empty-state"><i class="bi bi-bell"></i><h3>You're all caught up</h3><p>There are no notifications.</p></div><?php endif; ?></div></div>
<?php page_end(); ?>
