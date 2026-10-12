<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
$db=Database::connection();$profile=(new Student($db))->profile(current_user_id());
$term=$db->query('SELECT * FROM academic_terms WHERE is_current=1 LIMIT 1')->fetch();$grid=array_fill(1,7,[]);
if($term){$stmt=$db->prepare("SELECT c.code,c.title,r.status,cs.day_of_week,cs.starts_at,cs.ends_at,cs.room FROM registrations r JOIN courses c ON c.id=r.course_id JOIN course_schedules cs ON cs.course_id=c.id WHERE r.student_id=? AND r.term_id=? AND r.status IN ('pending','approved') ORDER BY cs.starts_at");$stmt->execute([$profile['id'],$term['id']]);foreach($stmt as $item)$grid[(int)$item['day_of_week']][]=$item;}
$days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
page_start('Weekly timetable','student/timetable.php');
?>
<div class="content-width"><?php page_title('Weekly timetable',$term?$term['name']:'No active academic term.'); ?><div class="panel table-responsive"><table class="table timetable-grid align-top mb-0"><thead><tr><?php foreach($days as $day): ?><th><?= e($day) ?></th><?php endforeach; ?></tr></thead><tbody><tr><?php for($d=1;$d<=7;$d++): ?><td style="min-width:140px"><?php foreach($grid[$d] as $item): ?><div class="schedule-pill"><strong><?= e($item['code']) ?> <?= status_badge($item['status']) ?></strong><?= e(substr($item['starts_at'],0,5).'–'.substr($item['ends_at'],0,5)) ?><br><?= e($item['room']) ?></div><?php endforeach; if(!$grid[$d]): ?><span class="text-muted small">No classes</span><?php endif; ?></td><?php endfor; ?></tr></tbody></table></div></div>
<?php page_end(); ?>
