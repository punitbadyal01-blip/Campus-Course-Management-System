<?php
declare(strict_types=1);

define('APP_NAME', 'CampusCourse');
define('APP_URL', rtrim(getenv('APP_URL') ?: '/online_course_registration', '/'));
define('APP_ROOT', dirname(__DIR__));
define('MAX_LOGIN_ATTEMPTS', 8);
define('LOGIN_LOCK_MINUTES', 15);
