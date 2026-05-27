<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login('Admin');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $lrn = trim($_POST['lrn'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $glvl = (int)($_POST['glvl'] ?? 0);
  $section = trim($_POST['section'] ?? 'Section A');
  $strand = trim($_POST['strand'] ?? '');
  $age = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
  $sex = trim($_POST['sex'] ?? '');
  $password = trim($_POST['password'] ?? 'student123') ?: 'student123';

  if ($action === 'add') {
    $stmt = $conn->prepare("INSERT INTO students (lrn,name,glvl,section,strand,age,sex,password) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssississ', $lrn, $name, $glvl, $section, $strand, $age, $sex, $password);
    $message = $stmt->execute() ? 'Student added successfully.' : 'Unable to add student. LRN may already exist.';
  } elseif ($action === 'edit') {
    $stmt = $conn->prepare("UPDATE students SET name=?, glvl=?, section=?, strand=?, age=?, sex=?, password=? WHERE lrn=?");
    $stmt->bind_param('sississs', $name, $glvl, $section, $strand, $age, $sex, $password, $lrn);
    $message = $stmt->execute() ? 'Student updated successfully.' : 'Unable to update student.';
  } elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM students WHERE lrn=?");
    $stmt->bind_param('s', $lrn);
    $message = $stmt->execute() ? 'Student deleted successfully.' : 'Unable to delete student.';
  }
}

$page = $_GET['page'] ?? '';
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';
$strand = $_GET['strand'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$types = '';
$params = [];

if ($grade !== '') {
  $where[] = 'glvl = ?';
  $types .= 'i';
  $params[] = (int)$grade;
}
if ($section !== '') {
  $where[] = 'section = ?';
  $types .= 's';
  $params[] = $section;
}
if ($strand !== '') {
  if ($strand === 'JHS') {
    $where[] = "(strand = '' OR strand IS NULL)";
  } else {
    $where[] = 'strand = ?';
    $types .= 's';
    $params[] = $strand;
  }
}
if ($search !== '') {
  $where[] = '(name LIKE ? OR CAST(lrn AS CHAR) LIKE ?)';
  $types .= 'ss';
  $like = '%' . $search . '%';
  $params[] = $like;
  $params[] = $like;
}

$sql = 'SELECT * FROM students' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY glvl ASC, section ASC, strand ASC, name ASC';
$stmt = $conn->prepare($sql);
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

$counts = $conn->query("SELECT COUNT(*) total, SUM(glvl BETWEEN 7 AND 10) jhs, SUM(glvl BETWEEN 11 AND 12) shs FROM students")->fetch_assoc();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard | <?php echo school_name(); ?></title>
  <link rel="stylesheet" href="assets/css/portal.css">
  <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/4.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>
  <script defer src="assets/js/portal.js"></script>
</head>

<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <div class="shell">
    <aside class="sidebar">
      <h2>Admin Menu</h2>
      <a class="side-link <?php echo $page !== 'rankings' ? 'active' : ''; ?>" href="admin_dashboard.php">Manage Students</a>
      <a class="side-link <?php echo $page === 'rankings' ? 'active' : ''; ?>" href="admin_dashboard.php?page=rankings">Rankings</a>
      <a class="side-link" href="print_class_list.php" target="_blank">Print Class List</a>

      <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
          <i class="fi fi-ss-power"></i>
          <span>Log Out</span>
        </a>
      </div>
    </aside>

    <main class="main">
      <div class="page-title">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Manage records for Grade 7–12 students by grade, section, strand, and LRN.</p>
        </div>
        <button class="btn" onclick="openModal('studentModal')">Add Student</button>
      </div>

      <?php if ($message): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>

      <div class="cards">
        <div class="card">
          <h3>Total Students</h3>
          <div class="number"><?php echo e($counts['total'] ?? 0); ?></div>
        </div>
        <div class="card">
          <h3>Junior High</h3>
          <div class="number"><?php echo e($counts['jhs'] ?? 0); ?></div>
        </div>
        <div class="card">
          <h3>Senior High</h3>
          <div class="number"><?php echo e($counts['shs'] ?? 0); ?></div>
        </div>
        <div class="card">
          <h3>Sections</h3>
          <div class="number">A/B</div>
        </div>
      </div>

      <?php if ($page === 'rankings'): ?>
        <h2>Top Students by GWA</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Strand</th>
                <th>GWA</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $r = $conn->query("SELECT * FROM students WHERE gwa IS NOT NULL ORDER BY gwa DESC, name ASC LIMIT 20");
              $rank = 1;
              if ($r && $r->num_rows > 0):
                while ($row = $r->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><?php echo e($row['glvl']); ?></td>
                    <td><?php echo e($row['section']); ?></td>
                    <td><?php echo e($row['strand'] ?: 'JHS'); ?></td>
                    <td><?php echo e($row['gwa']); ?></td>
                  </tr>
                <?php endwhile;
              else: ?>
                <tr>
                  <td colspan="6" class="empty-row">No ranking records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="toolbar">
          <form class="filters" method="GET" action="admin_dashboard.php">
            <input type="text" name="search" placeholder="Search name or LRN" value="<?php echo e($search); ?>">
            <select name="grade">
              <option value="">All Grades</option>
              <?php for ($g = 7; $g <= 12; $g++): ?>
                <option value="<?php echo $g; ?>" <?php echo (string)$grade === (string)$g ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
              <?php endfor; ?>
            </select>
            <select name="section">
              <option value="">All Sections</option>
              <option value="Section A" <?php echo $section === 'Section A' ? 'selected' : ''; ?>>Section A</option>
              <option value="Section B" <?php echo $section === 'Section B' ? 'selected' : ''; ?>>Section B</option>
            </select>
            <select name="strand">
              <option value="">All Strands</option>
              <option value="JHS" <?php echo $strand === 'JHS' ? 'selected' : ''; ?>>JHS / None</option>
              <option value="ABM" <?php echo $strand === 'ABM' ? 'selected' : ''; ?>>ABM</option>
              <option value="HUMSS" <?php echo $strand === 'HUMSS' ? 'selected' : ''; ?>>HUMSS</option>
              <option value="STEM" <?php echo $strand === 'STEM' ? 'selected' : ''; ?>>STEM</option>
              <option value="TVL" <?php echo $strand === 'TVL' ? 'selected' : ''; ?>>TVL</option>
            </select>
            <button class="btn secondary" type="submit">Apply Filter</button>
            <button class="btn secondary" type="button" onclick="resetAdminFilters()">Reset</button>
          </form>
          <a class="btn secondary" target="_blank" href="print_class_list.php">Print</a>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>LRN</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Strand</th>
                <th>Age</th>
                <th>Sex</th>
                <th>GWA</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1;
              if ($students && $students->num_rows > 0): while ($s = $students->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo e($s['name']); ?></td>
                    <td><?php echo e($s['lrn']); ?></td>
                    <td><?php echo e($s['glvl']); ?></td>
                    <td><?php echo e($s['section']); ?></td>
                    <td><?php echo e($s['strand'] ?: 'JHS'); ?></td>
                    <td><?php echo e($s['age']); ?></td>
                    <td><?php echo e($s['sex']); ?></td>
                    <td><?php echo e($s['gwa']); ?></td>
                    <td>
                      <div class="actions">
                        <button class="btn small secondary" onclick='fillEdit(<?php echo json_encode($s); ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this student?')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="lrn" value="<?php echo e($s['lrn']); ?>">
                          <button class="btn small danger">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile;
              else: ?>
                <tr>
                  <td colspan="10" class="empty-row">No records found for the selected filter. Click Reset or import the updated database SQL.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <div class="modal" id="studentModal">
    <div class="modal-content">
      <button class="close" onclick="closeModal('studentModal')">×</button>
      <h2 id="modalTitle">Add Student</h2>
      <form method="POST">
        <input type="hidden" name="action" id="formAction" value="add">
        <div class="form-grid">
          <div class="field"><label>LRN</label><input name="lrn" id="lrn" required></div>
          <div class="field"><label>Full Name</label><input name="name" id="name" required></div>
          <div class="field"><label>Grade Level</label><select name="glvl" id="glvl" required><?php for ($g = 7; $g <= 12; $g++): ?><option value="<?php echo $g; ?>">Grade <?php echo $g; ?></option><?php endfor; ?></select></div>
          <div class="field"><label>Section</label><select name="section" id="section">
              <option value="Section A">Section A</option>
              <option value="Section B">Section B</option>
            </select></div>
          <div class="field"><label>Strand</label><select name="strand" id="strand">
              <option value="">JHS / None</option>
              <option>ABM</option>
              <option>HUMSS</option>
              <option>STEM</option>
              <option>TVL</option>
            </select></div>
          <div class="field"><label>Age</label><input name="age" id="age" type="number"></div>
          <div class="field"><label>Sex</label><select name="sex" id="sex">
              <option>Male</option>
              <option>Female</option>
            </select></div>
          <div class="field"><label>Student Password</label><input name="password" id="password" value="student123"></div>
        </div>
        <button class="btn" type="submit">Save Student</button>
      </form>
    </div>
  </div>
  <script>
    function fillEdit(s) {
      openModal('studentModal');
      modalTitle.textContent = 'Edit Student';
      formAction.value = 'edit';
      lrn.value = s.lrn;
      lrn.readOnly = true;
      name.value = s.name || '';
      glvl.value = s.glvl || 7;
      section.value = s.section || 'Section A';
      strand.value = s.strand || '';
      age.value = s.age || '';
      sex.value = s.sex || 'Male';
      password.value = s.password || 'student123';
    }
  </script>
</body>

</html>