<?php
function page_start(string $title, string $active = ''): void
{
    $role = $_SESSION['role'] ?? '';
    $db = Database::connection();
    $unread = !empty($_SESSION['user_id']) ? (new NotificationService($db))->unreadCount(current_user_id()) : 0;
    $flash = take_flash();
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark topbar sticky-top">
 <div class="container-fluid px-3 px-lg-4">
  <a class="navbar-brand fw-semibold" href="<?= e(url($role === 'admin' ? 'admin/index.php' : ($role === 'student' ? 'student/index.php' : 'index.php'))) ?>"><i class="bi bi-mortarboard-fill me-2"></i><?= e(APP_NAME) ?></a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="topNav">
   <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
    <?php if ($role): ?>
      <span class="nav-link text-white-50 small"><?= e($_SESSION['email'] ?? '') ?></span>
      <a class="nav-link position-relative" href="<?= e(url(($role === 'admin' ? 'admin' : 'student') . '/notifications.php')) ?>"><i class="bi bi-bell"></i> Alerts<?php if ($unread): ?><span class="badge rounded-pill bg-danger ms-1"><?= $unread ?></span><?php endif; ?></a>
      <form method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-light">Sign out</button></form>
    <?php else: ?><a class="nav-link" href="<?= e(url('auth/login.php')) ?>">Sign in</a><?php endif; ?>
   </div>
  </div>
 </div>
</nav>
<div class="app-shell">
<?php if ($role): ?>
 <aside class="sidebar">
  <div class="sidebar-label"><?= $role === 'admin' ? 'ADMINISTRATION' : 'STUDENT PORTAL' ?></div>
  <?php
  $studentLinks = [
      ['student/index.php','grid-1x2','Dashboard'],['student/courses.php','book','Browse courses'],
      ['student/registrations.php','journal-check','My registrations'],['student/timetable.php','calendar-week','Weekly timetable'],
      ['student/history.php','clock-history','Registration history'],['student/notifications.php','bell','Notifications'],
      ['student/profile.php','person','My profile'],
  ];
  $adminLinks = [
      ['admin/index.php','grid-1x2','Dashboard'],['admin/courses.php','book','Manage courses'],
      ['admin/registrations.php','clipboard-check','Registrations'],['admin/students.php','people','Students'],
      ['admin/records.php','mortarboard','Academic records'],['admin/settings.php','sliders','Departments & terms'],['admin/reports.php','bar-chart','Reports'],
      ['admin/notifications.php','bell','Notifications'],
  ];
  foreach ($role === 'admin' ? $adminLinks : $studentLinks as [$href,$icon,$label]): ?>
    <a class="side-link <?= $active === $href ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><i class="bi bi-<?= e($icon) ?>"></i><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
  <div class="sidebar-bottom"><a class="side-link" href="<?= e(url('auth/password.php')) ?>"><i class="bi bi-shield-lock"></i><span>Change password</span></a></div>
 </aside>
<?php endif; ?>
 <main class="main-content <?= $role ? '' : 'main-public' ?>">
  <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php
}

function page_end(): void
{
    ?>
 </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body></html>
<?php
}

function page_title(string $title, string $subtitle = ''): void
{
    echo '<div class="page-heading"><div><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') echo '<p>' . e($subtitle) . '</p>';
    echo '</div></div>';
}

function status_badge(string $status): string
{
    $class = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','cancelled'=>'secondary'][$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
}
