<?php
require_once __DIR__ . '/includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo school_name(); ?></title>

  <link rel="stylesheet" href="assets/css/portal.css">
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800&display=swap" rel="stylesheet">
</head>

<body>

  <header class="site-header landing-header">
    <div class="brand">
      <img src="assets/img/naevis-logo.svg" alt="School Logo" class="brand-logo">
      <div>
        <h2>Naevis Inter High School</h2>
        <p>Junior & Senior High School Portal</p>
      </div>
    </div>

    <nav class="main-nav">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#portal">Portal Features</a>
      <a href="login.php" class="login-btn">Login</a>
    </nav>
  </header>

  <main>

    <!-- LANDING PAGE SLIDESHOW -->
    <section class="hero-slider" id="home">

      <div class="hero-slide active">
        <img src="assets/img/school-campus.png" alt="School Campus">
        <div class="hero-overlay">
          <span class="hero-label">Welcome to</span>
          <h1>Naevis Inter High School</h1>
          <p>A modern school portal for managing student records, grades, and academic progress.</p>
          <a href="login.php" class="hero-btn">Open Portal</a>
        </div>
      </div>

      <div class="hero-slide">
        <img src="assets/img/student-activities.png" alt="Student Activities">
        <div class="hero-overlay">
          <span class="hero-label">Student Life</span>
          <h1>Learning Beyond the Classroom</h1>
          <p>Empowering students through leadership, collaboration, activities, and academic excellence.</p>
          <a href="login.php" class="hero-btn">Access Dashboard</a>
        </div>
      </div>

      <div class="hero-slide">
        <img src="assets/img/academic-achievement.png" alt="Academic Achievement">
        <div class="hero-overlay">
          <span class="hero-label">Academic Excellence</span>
          <h1>Track Grades and Progress</h1>
          <p>Teachers, admins, and students can access organized records in one secure portal.</p>
          <a href="login.php" class="hero-btn">Get Started</a>
        </div>
      </div>

      <div class="hero-dots">
        <button type="button" class="dot active"></button>
        <button type="button" class="dot"></button>
        <button type="button" class="dot"></button>
      </div>

    </section>

    <!-- ABOUT SECTION -->
    <section class="about-section" id="about">
      <div class="section-heading-about">
        <span>About Our School</span>
        <h2>Committed to Quality Education</h2>
        <p>
          Naevis Inter High School provides a supportive learning environment for Junior High School and Senior High School learners.
        </p>
      </div>

      <div class="circle-card-grid">
        <div class="circle-card">
          <div class="circle-icon">🎯</div>
          <h3>Mission</h3>
          <p>To provide quality, inclusive, and values-centered education that prepares students for lifelong learning.</p>
        </div>

        <div class="circle-card">
          <div class="circle-icon">👁️</div>
          <h3>Vision</h3>
          <p>To become a trusted institution that develops competent, responsible, and future-ready learners.</p>
        </div>

        <div class="circle-card">
          <div class="circle-icon">⭐</div>
          <h3>Goal</h3>
          <p>To support academic excellence through organized student records, grade monitoring, and school collaboration.</p>
        </div>

        <div class="circle-card">
          <div class="circle-icon">🤝</div>
          <h3>Core Values</h3>
          <p>Integrity, excellence, respect, responsibility, collaboration, and service.</p>
        </div>
      </div>
    </section>

    <!-- PORTAL FEATURES -->
    <section class="portal-section" id="portal">
      <div class="section-heading-portal">
        <span>Portal Features</span>
        <h2>Built for Admins, Teachers, and Students</h2>
      </div>

      <div class="circle-card-grid feature-grid">
        <div class="circle-card">
          <div class="circle-icon">🧑‍💼</div>
          <h3>Admin Portal</h3>
          <p>Manage students, sections, records, rankings, and academic information.</p>
        </div>

        <div class="circle-card">
          <div class="circle-icon">👩‍🏫</div>
          <h3>Teacher Portal</h3>
          <p>Encode grades, compute averages, view class rankings, and print report cards.</p>
        </div>

        <div class="circle-card">
          <div class="circle-icon">🎓</div>
          <h3>Student Portal</h3>
          <p>View grades, academic standing, report cards, and personal dashboard updates.</p>
        </div>
      </div>
    </section>

  </main>

  <footer class="landing-footer">
    <p>Naevis Inter High School © 2025. All Rights Reserved.</p>
  </footer>

  <script src="assets/js/portal.js"></script>
</body>

</html>