<?php require_once __DIR__ . '/helpers.php'; ?>
<nav class="topbar">
  <a class="brand" href="<?php echo isset($_SESSION['Role']) ? ($_SESSION['Role'] === 'Admin' ? 'admin_dashboard.php' : ($_SESSION['Role'] === 'Teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php')) : 'index.php'; ?>">
    <img class="brand-logo-img" src="assets/img/naevis-logo.svg" alt="Naevis Inter High School Logo">
    <span>
      <strong><?php echo school_name(); ?></strong>
      <small>Junior & Senior High School Portal</small>
    </span>
  </a>
</nav>