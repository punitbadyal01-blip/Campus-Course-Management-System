<?php
require_once dirname(__DIR__) . '/middleware/bootstrap.php';
require_role('student');
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('student/registrations.php');
verify_csrf();
try{(new RegistrationController(new RegistrationService(Database::connection())))->cancel(current_user_id(),(int)($_POST['registration_id']??0));flash('success','Registration cancelled and its seat released.');}
catch(DomainException $e){flash('danger',$e->getMessage());}
catch(Throwable $e){error_log('Cancellation error: '.$e->getMessage());flash('danger','Cancellation could not be completed.');}
redirect('student/registrations.php');
