<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
if($_SERVER['REQUEST_METHOD']!=='POST'){redirect('student/courses.php');}
verify_csrf();
try{(new RegistrationController(new RegistrationService(Database::connection())))->register(current_user_id(),(int)($_POST['course_id']??0));flash('success','Your course request is submitted. It will reserve a seat while pending.');}
catch(DomainException $e){flash('danger',$e->getMessage());}
catch(Throwable $e){error_log('Registration error: '.$e->getMessage());flash('danger','Registration could not be completed. Please try again or contact the administrator.');}
redirect('student/registrations.php');
