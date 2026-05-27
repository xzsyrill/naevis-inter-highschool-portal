<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_login('Teacher');

$teacherId = current_user_id();
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->bind_param('s', $teacherId);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

$teacherName = $teacher['name'] ?? 'Teacher';
$teacherSection = $teacher['section'] ?? '';
$teacherStrand = trim($teacher['strand'] ?? '');

preg_match('/Grade\s*(\d+)/i', $teacherSection, $matches);
$teacherGrade = isset($matches[1]) ? (int)$matches[1] : 0;

// Fallback mapping in case the account section text is missing or edited.
if ($teacherGrade === 0) {
  $gradeMap = [
    '234567' => 7,
    '345678' => 8,
    '456789' => 9,
    '567890' => 10,
    '678901' => 11,
    '789012' => 11,
    '890123' => 11,
    '901234' => 11,
  ];
  $teacherGrade = $gradeMap[(string)$teacherId] ?? 0;
}

$page = $_GET['page'] ?? 'student-list';

// Build safe filters. Values are cast/escaped before being added to SQL.
if ($teacherStrand !== '') {
  $safeStrand = $conn->real_escape_string($teacherStrand);
  $studentFilter = "WHERE glvl IN (11, 12) AND strand = '{$safeStrand}'";
  $dashboardLabel = "Senior High School - {$teacherStrand}";
} elseif ($teacherGrade > 0) {
  $studentFilter = "WHERE glvl = {$teacherGrade}";
  $dashboardLabel = "Grade {$teacherGrade}";
} else {
  // Last fallback: show all students instead of showing an empty dashboard.
  $studentFilter = "WHERE 1=1";
  $dashboardLabel = "All Assigned Students";
}

function render_empty_row($cols, $message)
{
  echo '<tr><td colspan="' . (int)$cols . '" class="empty-row">' . e($message) . '</td></tr>';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teacher Dashboard | <?php echo school_name(); ?></title>
  <link rel="stylesheet" href="assets/css/portal.css">
  <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/4.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>
  <script defer src="assets/js/portal.js"></script>
</head>

<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <div class="shell">
    <aside class="sidebar">
      <h2>Teacher Menu</h2>
      <a class="side-link <?php echo $page === 'student-list' ? 'active' : ''; ?>" href="teacher_dashboard.php?page=student-list">Student List</a>
      <a class="side-link <?php echo $page === 'class-ranking' ? 'active' : ''; ?>" href="teacher_dashboard.php?page=class-ranking">Class Ranking</a>
      <a class="side-link <?php echo $page === 'subject-ranking' ? 'active' : ''; ?>" href="teacher_dashboard.php?page=subject-ranking">Subject Ranking</a>
      <a class="side-link <?php echo $page === 'honor-list' ? 'active' : ''; ?>" href="teacher_dashboard.php?page=honor-list">Honor List</a>

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
          <h1>Hello, <?php echo e($teacherName); ?></h1>
          <p><?php echo e($dashboardLabel); ?></p>
        </div>
      </div>

      <?php if ($page === 'student-list'): ?>
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
                <th>GWA</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM students {$studentFilter} ORDER BY glvl ASC, section ASC, name ASC";
              $result = $conn->query($sql);
              $i = 1;
              if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><?php echo e($row['lrn']); ?></td>
                    <td><?php echo e($row['glvl']); ?></td>
                    <td><?php echo e($row['section']); ?></td>
                    <td><?php echo e($row['strand'] ?: 'JHS'); ?></td>
                    <td><?php echo e($row['gwa'] !== null ? $row['gwa'] : ''); ?></td>
                    <td><a class="btn small" href="grading.php?lrn=<?php echo e($row['lrn']); ?>">Encode Grades</a></td>
                  </tr>
              <?php endwhile;
              else:
                render_empty_row(8, 'No student records found. Import database/naevis_inter_high_db.sql or database/update_student_records.sql in phpMyAdmin.');
              endif;
              ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($page === 'class-ranking'): ?>
        <h2>Top Students by GWA</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Grade</th>
                <th>Section</th>
                <th>GWA</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM students {$studentFilter} AND gwa IS NOT NULL ORDER BY gwa DESC, name ASC LIMIT 10";
              $result = $conn->query($sql);
              $rank = 1;
              if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><?php echo e($row['glvl']); ?></td>
                    <td><?php echo e($row['section']); ?></td>
                    <td><?php echo e($row['gwa']); ?></td>
                  </tr>
              <?php endwhile;
              else:
                render_empty_row(5, 'No ranking records found yet.');
              endif;
              ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($page === 'subject-ranking'): ?>
        <h2>Best Students by Subject</h2>
        <?php
        $subjects = $teacherStrand ? shs_subjects() : jhs_subjects();
        foreach ($subjects as $prefix => $label):
          $col = $teacherStrand
            ? (($prefix === 'applied1' || $prefix === 'applied2' || $prefix === 'spec1' || $prefix === 'spec2') ? $prefix . '_q1' : $prefix . '1')
            : $prefix . '1';
          $sql = "SELECT name, glvl, section, {$col} AS score FROM students {$studentFilter} AND {$col} IS NOT NULL ORDER BY {$col} DESC LIMIT 5";
          $result = $conn->query($sql);
        ?>
          <div class="card" style="margin-bottom:16px">
            <h3><?php echo e($label); ?></h3>
            <table>
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Name</th>
                  <th>Grade</th>
                  <th>Section</th>
                  <th>Score</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $rank = 1;
                if ($result && $result->num_rows > 0):
                  while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo $rank++; ?></td>
                      <td><?php echo e($row['name']); ?></td>
                      <td><?php echo e($row['glvl']); ?></td>
                      <td><?php echo e($row['section']); ?></td>
                      <td><?php echo e($row['score']); ?></td>
                    </tr>
                <?php endwhile;
                else:
                  render_empty_row(5, 'No subject scores found.');
                endif;
                ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <h2>Honor List</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Grade</th>
                <th>Section</th>
                <th>GWA</th>
                <th>Award</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM students {$studentFilter} AND gwa >= 90 ORDER BY gwa DESC, name ASC";
              $result = $conn->query($sql);
              $i = 1;
              if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                  $award = $row['gwa'] >= 98 ? 'With Highest Honors' : ($row['gwa'] >= 95 ? 'With High Honors' : 'With Honors'); ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><?php echo e($row['glvl']); ?></td>
                    <td><?php echo e($row['section']); ?></td>
                    <td><?php echo e($row['gwa']); ?></td>
                    <td><?php echo e($award); ?></td>
                  </tr>
              <?php endwhile;
              else:
                render_empty_row(6, 'No honor list records found yet.');
              endif;
              ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>

</html>