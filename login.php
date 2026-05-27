<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $role = $_POST['role'] ?? '';
  $id = trim($_POST['user_id'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($role === 'Student') {
    $sql = "SELECT lrn, name FROM students WHERE lrn = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $id, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
      $row = $result->fetch_assoc();
      $_SESSION['Login'] = 'true';
      $_SESSION['Role'] = 'Student';
      $_SESSION['UserID'] = $row['lrn'];
      $_SESSION['Name'] = $row['name'];
      header('Location: student_dashboard.php');
      exit();
    }
    $error = 'Invalid student LRN or password.';
  } elseif ($role === 'Admin' || $role === 'Teacher') {
    $sql = "SELECT id, name, role FROM accounts WHERE id = ? AND email = ? AND role = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $id, $email, $role, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
      $row = $result->fetch_assoc();
      $_SESSION['Login'] = 'true';
      $_SESSION['Role'] = $row['role'];
      $_SESSION['UserID'] = $row['id'];
      $_SESSION['Name'] = $row['name'];
      header('Location: ' . ($row['role'] === 'Admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'));
      exit();
    }
    $error = 'Invalid employee credentials.';
  } else {
    $error = 'Please select a valid portal type.';
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login | <?php echo school_name(); ?></title>
  <link rel="stylesheet" href="assets/css/portal.css">
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700,800&display=swap" rel="stylesheet">
</head>

<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="auth-page">
    <section class="auth-card">
      <div class="auth-info">
        <h1>Welcome back.</h1>
        <p>Select your portal type. Teachers and admins use employee credentials, while students use their LRN and student password.</p>
        <p><strong>Sample:</strong><br>Admin: 100000 / admin@naevis.edu.ph / admin123<br>Student: 109381 / student123</p>

        <div class="privacy-note">
          <strong>⚠ NOTE:</strong> Please be informed that all your data disclosed herein (e.g. email address, contact number) will be protected in compliance with the <strong>Data Privacy Act of 2012</strong>.
          By logging in to the system, you confirm that you fully and voluntarily give consent to the collection of such data.
        </div>
      </div>

      <div class="auth-form">
        <h2>Portal Login</h2><?php if ($error): ?>
          <div class="alert error"><?php echo e($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="field">
            <label>Login As</label>
            <select name="role" id="roleSelect" required>
              <option value="">Select portal</option>
              <option>Admin</option>
              <option>Teacher</option>
              <option>Student</option>
            </select>
          </div>
          <div class="field">
            <label>Employee No. / Student LRN</label>
            <input name="user_id" type="text" required placeholder="Enter ID or LRN">
          </div>
          <div class="field email-field">
            <label>Email Address <small>(Admin/Teacher only)</small></label>
            <input name="email" type="email" placeholder="Enter school email">
          </div>
          <div class="field">
            <label>Password</label>
            <input name="password" type="password" required placeholder="Enter password">
          </div>
          <button class="btn" type="submit">Log In</button>
        </form>
      </div>
    </section>
  </main>

  <script>
    document.getElementById('roleSelect').addEventListener('change', function() {
      document.querySelector('.email-field').style.display = this.value === 'Student' ? 'none' : 'flex';
    });
  </script>
</body>

</html>