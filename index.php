<?php
require_once __DIR__ . '/middleware/bootstrap.php';
if (!empty($_SESSION['user_id'])) redirect(($_SESSION['role'] === 'admin' ? 'admin' : 'student') . '/index.php');
page_start('Course registration');
?>
<section class="hero">
 <div class="container">
  <span class="badge rounded-pill text-bg-light text-primary mb-3">COLLEGE COURSE PORTAL</span>
  <h1>Plan your semester.<br>Build your future.</h1>
  <p>Explore courses, request enrollment, and keep your academic schedule organized in one place.</p>
  <div class="d-flex gap-2 flex-wrap mt-4"><a class="btn btn-light btn-lg" href="<?= e(url('auth/login.php')) ?>">Sign in <i class="bi bi-arrow-right ms-1"></i></a><a class="btn btn-outline-light btn-lg" href="<?= e(url('auth/register.php')) ?>">Create student account</a></div>
 </div>
</section>
<section class="container py-5"><div class="row g-4">
 <div class="col-md-4"><div class="panel h-100"><i class="bi bi-search text-primary fs-3"></i><h2 class="h5 mt-3">Find the right courses</h2><p class="text-muted mb-0">Browse courses relevant to your semester, review prerequisites, and check live seat availability.</p></div></div>
 <div class="col-md-4"><div class="panel h-100"><i class="bi bi-calendar-week text-primary fs-3"></i><h2 class="h5 mt-3">Avoid schedule conflicts</h2><p class="text-muted mb-0">The registration service checks eligibility, credit limits, prerequisites, and overlapping classes.</p></div></div>
 <div class="col-md-4"><div class="panel h-100"><i class="bi bi-shield-check text-primary fs-3"></i><h2 class="h5 mt-3">Stay informed</h2><p class="text-muted mb-0">Follow request decisions, manage your registrations, and print your confirmation.</p></div></div>
</div></section>
<?php page_end(); ?>
