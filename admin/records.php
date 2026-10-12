<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('admin');
$db=Database::connection();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  if(($_POST['action']??'')==='delete'){
   $id=(int)($_POST['id']??0);$db->prepare('DELETE FROM academic_records WHERE id=?')->execute([$id]);
   $db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)')->execute([current_user_id(),'academic_record_deleted','academic_record',$id,$_SERVER['REMOTE_ADDR']??null]);
   flash('success','Academic record removed.');
  }else{
   $student=(int)($_POST['student_id']??0);$course=(int)($_POST['course_id']??0);$term=(int)($_POST['term_id']??0);$grade=strtoupper(post_string('grade',8));$date=post_string('completed_at',10);
   if(!in_array($grade,['A','B','C','D','F','P','NC','W','I'],true))throw new DomainException('Enter a valid grade (A–F, P, NC, W, or I).');
   $when=DateTimeImmutable::createFromFormat('Y-m-d',$date);if(!$when||$when->format('Y-m-d')!==$date)throw new DomainException('Select a valid completion date.');
   $stmt=$db->prepare('INSERT INTO academic_records(student_id,course_id,term_id,grade,completed_at) VALUES(?,?,?,?,?)');$stmt->execute([$student,$course,$term,$grade,$date]);$recordId=(int)$db->lastInsertId();
   $db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)')->execute([current_user_id(),'academic_record_added','academic_record',$recordId,$_SERVER['REMOTE_ADDR']??null]);
   flash('success','Academic record added.');
  }
  redirect('admin/records.php');
 }catch(Throwable $e){if(!$e instanceof DomainException&&!($e instanceof PDOException&&(string)$e->getCode()==='23000'))error_log('Academic record error: '.$e->getMessage());$error=$e instanceof DomainException?$e->getMessage():($e instanceof PDOException&&(string)$e->getCode()==='23000'?'A record for this student, course, and term already exists.':'Academic record could not be saved. Verify the selected records.');}
}
$students=$db->query('SELECT id,register_number,full_name FROM students ORDER BY full_name')->fetchAll();$courses=$db->query('SELECT id,code,title FROM courses ORDER BY code')->fetchAll();$terms=$db->query('SELECT id,name FROM academic_terms ORDER BY starts_on DESC')->fetchAll();
$records=$db->query('SELECT a.*,s.full_name,s.register_number,c.code,c.title,t.name term_name FROM academic_records a JOIN students s ON s.id=a.student_id JOIN courses c ON c.id=a.course_id JOIN academic_terms t ON t.id=a.term_id ORDER BY a.completed_at DESC LIMIT 500')->fetchAll();
page_start('Academic records','admin/records.php');
?>
<div class="content-width"><?php page_title('Academic records','Record completed courses used for prerequisite eligibility.'); ?><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="panel mb-4"><h2 class="h5 fw-bold">Add completion record</h2><?php if(!$students||!$courses||!$terms): ?><div class="alert alert-info mb-0">Create students, courses, and academic terms before recording course completions.</div><?php else: ?><form class="row g-3 align-items-end" method="post"><?= csrf_field() ?><div class="col-lg-3"><label class="form-label">Student</label><select class="form-select" name="student_id" required><option value="">Select student</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['register_number'].' · '.$s['full_name']) ?></option><?php endforeach; ?></select></div><div class="col-lg-3"><label class="form-label">Completed course</label><select class="form-select" name="course_id" required><option value="">Select course</option><?php foreach($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' · '.$c['title']) ?></option><?php endforeach; ?></select></div><div class="col-lg-2"><label class="form-label">Term</label><select class="form-select" name="term_id" required><?php foreach($terms as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div><div class="col-lg-2"><label class="form-label">Grade</label><select class="form-select" name="grade"><?php foreach(['A','B','C','D','F','P','NC','W','I'] as $grade): ?><option><?= e($grade) ?></option><?php endforeach; ?></select></div><div class="col-lg-2"><label class="form-label">Completed</label><input class="form-control" type="date" name="completed_at" required value="<?= e(date('Y-m-d')) ?>"></div><div class="col-12"><button class="btn btn-primary">Save academic record</button></div></form><?php endif; ?></div>
<div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Student</th><th>Course</th><th>Term</th><th>Grade</th><th>Completed</th><th></th></tr></thead><tbody><?php foreach($records as $r): ?><tr><td><?= e($r['full_name']) ?><div class="small text-muted"><?= e($r['register_number']) ?></div></td><td><?= e($r['code'].' · '.$r['title']) ?></td><td><?= e($r['term_name']) ?></td><td><strong><?= e($r['grade']) ?></strong></td><td><?= e(date('M j, Y',strtotime($r['completed_at']))) ?></td><td><form method="post" data-confirm-submit="Delete this academic record?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; if(!$records): ?><tr><td colspan="6" class="text-center text-muted py-4">No academic records recorded.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php page_end(); ?>
