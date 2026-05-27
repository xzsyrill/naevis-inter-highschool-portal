<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login('Student');

$lrn = current_user_id();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'update_profile') {
    $address = trim($_POST['address'] ?? '');
    $guardian = trim($_POST['guardian'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $stmt = $conn->prepare("UPDATE students SET address=?, guardian=?, contact_no=?, email=? WHERE lrn=?");
    $stmt->bind_param('sssss', $address, $guardian, $contact, $email, $lrn);
    $message = $stmt->execute() ? 'Profile updated successfully.' : 'Unable to update profile.';
  }
  if ($action === 'change_password') {
    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $stmt = $conn->prepare("SELECT password FROM students WHERE lrn=?");
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $pw = $stmt->get_result()->fetch_assoc()['password'] ?? '';
    if ($current !== $pw) {
      $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
      $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
      $error = 'Passwords do not match.';
    } else {
      $stmt = $conn->prepare("UPDATE students SET password=? WHERE lrn=?");
      $stmt->bind_param('ss', $new, $lrn);
      $message = $stmt->execute() ? 'Password changed successfully.' : 'Unable to change password.';
    }
  }
}

$stmt = $conn->prepare('SELECT * FROM students WHERE lrn=?');
$stmt->bind_param('s', $lrn);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
  die('Student record not found.');
}

$isShs = (int)$student['glvl'] >= 11;
$subjects = $isShs ? shs_subjects() : jhs_subjects();
$quarters = $isShs ? 2 : 4;
$page = $_GET['page'] ?? 'overview';

function dash_grade_field($prefix, $q, $isShs)
{
  return ($isShs && in_array($prefix, ['applied1', 'applied2', 'spec1', 'spec2'], true)) ? $prefix . '_q' . $q : $prefix . $q;
}

$subjectRows = [];
$strongest = null;
$weakest = null;
$trendLabels = [];
$trendValues = [];
foreach ($subjects as $prefix => $label) {
  $vals = [];
  for ($q = 1; $q <= $quarters; $q++) {
    $field = dash_grade_field($prefix, $q, $isShs);
    $vals[$q] = $student[$field] ?? null;
  }
  $fg = final_grade($vals);
  $subjectRows[$prefix] = ['label' => $label, 'values' => $vals, 'final' => $fg, 'remarks' => remarks($fg)];
  if ($fg !== null) {
    if ($strongest === null || $fg > $strongest['final']) $strongest = ['label' => $label, 'final' => $fg];
    if ($weakest === null || $fg < $weakest['final']) $weakest = ['label' => $label, 'final' => $fg];
  }
}
for ($q = 1; $q <= $quarters; $q++) {
  $valid = [];
  foreach ($subjectRows as $row) {
    $v = $row['values'][$q] ?? null;
    if ($v !== null && $v !== '' && is_numeric($v)) $valid[] = (float)$v;
  }
  $trendLabels[] = 'Q' . $q;
  $trendValues[] = $valid ? round(array_sum($valid) / count($valid), 2) : 0;
}

$gwa = $student['gwa'] !== null ? (float)$student['gwa'] : null;
$honor = $gwa === null ? 'Pending' : ($gwa >= 98 ? 'With Highest Honors' : ($gwa >= 95 ? 'With High Honors' : ($gwa >= 90 ? 'With Honors' : 'No Honor Standing')));
$status = $gwa !== null ? ($gwa >= 75 ? 'Promoted' : 'Retained') : 'Pending';

$rankSection = '-';
$rankGrade = '-';
$rankStrand = '-';
$rankSql = "SELECT lrn, RANK() OVER (ORDER BY gwa DESC) AS rnk FROM students WHERE glvl=? AND section=? AND gwa IS NOT NULL";
$stmt = $conn->prepare($rankSql);
$stmt->bind_param('is', $student['glvl'], $student['section']);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  if ((string)$row['lrn'] === (string)$lrn) {
    $rankSection = '#' . $row['rnk'];
    break;
  }
}
$rankSql = "SELECT lrn, RANK() OVER (ORDER BY gwa DESC) AS rnk FROM students WHERE glvl=? AND gwa IS NOT NULL";
$stmt = $conn->prepare($rankSql);
$stmt->bind_param('i', $student['glvl']);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  if ((string)$row['lrn'] === (string)$lrn) {
    $rankGrade = '#' . $row['rnk'];
    break;
  }
}
if ($isShs && trim($student['strand']) !== '') {
  $rankSql = "SELECT lrn, RANK() OVER (ORDER BY gwa DESC) AS rnk FROM students WHERE strand=? AND gwa IS NOT NULL";
  $stmt = $conn->prepare($rankSql);
  $stmt->bind_param('s', $student['strand']);
  $stmt->execute();
  $r = $stmt->get_result();
  while ($row = $r->fetch_assoc()) {
    if ((string)$row['lrn'] === (string)$lrn) {
      $rankStrand = '#' . $row['rnk'];
      break;
    }
  }
} else {
  $rankStrand = 'JHS';
}

$att = $conn->query("SELECT status, COUNT(*) total FROM attendance WHERE lrn=" . (int)$lrn . " GROUP BY status");
$attendance = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
if ($att) {
  while ($a = $att->fetch_assoc()) $attendance[$a['status']] = (int)$a['total'];
}
$attTotal = array_sum($attendance);
$attRate = $attTotal ? round((($attendance['Present'] + $attendance['Late'] * .5) / $attTotal) * 100, 1) : 0;

$audiences = ["'All'", $isShs ? "'SHS'" : "'JHS'", "'Grade " . (int)$student['glvl'] . "'"];
$announcements = $conn->query("SELECT * FROM announcements WHERE audience IN (" . implode(',', $audiences) . ") ORDER BY created_at DESC LIMIT 6");
$calendar = $conn->query("SELECT * FROM calendar_events WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) ORDER BY event_date ASC LIMIT 8");
$activityWhere = "(grade_level IS NULL OR grade_level=" . (int)$student['glvl'] . ") AND (section='' OR section='" . $conn->real_escape_string($student['section']) . "')";
if ($isShs && trim($student['strand']) !== '') $activityWhere .= " AND (strand='' OR strand='" . $conn->real_escape_string($student['strand']) . "')";
else $activityWhere .= " AND (strand='' OR strand IS NULL)";
$activities = $conn->query("SELECT * FROM activities WHERE {$activityWhere} ORDER BY due_date ASC LIMIT 8");
$notifications = $conn->query("SELECT * FROM notifications WHERE lrn IS NULL OR lrn=" . (int)$lrn . " ORDER BY created_at DESC LIMIT 8");
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Student Dashboard | <?php echo school_name(); ?></title>
  <link rel="stylesheet" href="assets/css/portal.css">
  <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/4.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>
  <script defer src="assets/js/portal.js"></script>
</head>

<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <div class="shell">
    <aside class="sidebar student-sidebar">
      <h2>Student Menu</h2>
      <a class="side-link <?php echo $page === 'overview' ? 'active' : ''; ?>" href="student_dashboard.php">Overview</a>
      <a class="side-link <?php echo $page === 'grades' ? 'active' : ''; ?>" href="student_dashboard.php?page=grades">My Grades</a>
      <a class="side-link <?php echo $page === 'analytics' ? 'active' : ''; ?>" href="student_dashboard.php?page=analytics">Performance Analytics</a>
      <a class="side-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="student_dashboard.php?page=attendance">Attendance</a>
      <a class="side-link <?php echo $page === 'announcements' ? 'active' : ''; ?>" href="student_dashboard.php?page=announcements">Announcements</a>
      <a class="side-link <?php echo $page === 'calendar' ? 'active' : ''; ?>" href="student_dashboard.php?page=calendar">School Calendar</a>
      <a class="side-link <?php echo $page === 'activities' ? 'active' : ''; ?>" href="student_dashboard.php?page=activities">Activities</a>
      <a class="side-link <?php echo $page === 'profile' ? 'active' : ''; ?>" href="student_dashboard.php?page=profile">Profile & Security</a>

      <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
          <i class="fi fi-ss-power"></i>
          <span>Log Out</span>
        </a>
      </div>
    </aside>

    <main class="main student-main">
      <div class="page-title">
        <div>
          <h1>Welcome, <?php echo e($student['name']); ?></h1>
          <p>Grade <?php echo e($student['glvl']); ?> • <?php echo e($student['section']); ?> <?php echo e($student['strand']); ?> • LRN <?php echo e($student['lrn']); ?></p>
        </div>
        <div class="page-actions">
          <a class="btn secondary" href="student_dashboard.php?page=grades#printable-card">Print Report Card</a>
        </div>
      </div>
      <?php if ($message): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>

      <?php if ($page === 'overview'): ?>
        <div class="cards dashboard-cards">
          <div class="card">
            <h3>Program</h3>
            <div><?php echo grade_program($student['glvl']); ?></div>
          </div>
          <div class="card">
            <h3>GWA</h3>
            <div class="number"><?php echo e($gwa !== null ? number_format($gwa, 2) : 'N/A'); ?></div>
          </div>
          <div class="card">
            <h3>Status</h3>
            <div><?php echo e($status); ?></div>
          </div>
          <div class="card">
            <h3>Honor Standing</h3>
            <div><?php echo e($honor); ?></div>
          </div>
          <div class="card">
            <h3>Section Rank</h3>
            <div class="number small-number"><?php echo e($rankSection); ?></div>
          </div>
          <div class="card">
            <h3>Grade Rank</h3>
            <div class="number small-number"><?php echo e($rankGrade); ?></div>
          </div>
          <div class="card">
            <h3>Attendance Rate</h3>
            <div class="number small-number"><?php echo e($attRate); ?>%</div>
          </div>
          <div class="card">
            <h3>QR Student ID</h3>
            <div class="qr-card" aria-label="Student QR style ID"><span><?php echo e(substr((string)$student['lrn'], -4)); ?></span></div>
          </div>
        </div>
        <div class="grid-two dashboard-section-gap">
          <section class="card">
            <h3>Academic Insight</h3>
            <p class="muted">Strongest subject: <strong><?php echo e($strongest['label'] ?? 'Pending'); ?></strong> <?php echo $strongest ? '(' . e($strongest['final']) . ')' : ''; ?></p>
            <p class="muted">Needs focus: <strong><?php echo e($weakest['label'] ?? 'Pending'); ?></strong> <?php echo $weakest ? '(' . e($weakest['final']) . ')' : ''; ?></p>
            <p class="muted">Recommendation: <?php echo $weakest ? 'Review your ' . $weakest['label'] . ' activities and ask your adviser for practice work.' : 'Wait for encoded grades.'; ?></p>
          </section>
          <section class="card">
            <h3>Notifications</h3>
            <div class="mini-list"><?php if ($notifications && $notifications->num_rows): while ($n = $notifications->fetch_assoc()): ?><div><strong>•</strong> <?php echo e($n['message']); ?><small><?php echo e(date('M d', strtotime($n['created_at']))); ?></small></div><?php endwhile;
                                                                                                                                                                                                                                                                          else: ?><p class="muted">No notifications yet.</p><?php endif; ?></div>
          </section>
        </div>
      <?php elseif ($page === 'grades'): ?>
        <div class="cards">
          <div class="card">
            <h3>GWA</h3>
            <div class="number"><?php echo e($gwa !== null ? number_format($gwa, 2) : 'N/A'); ?></div>
          </div>
          <div class="card">
            <h3>Promotion</h3>
            <div><?php echo e($status); ?></div>
          </div>
          <div class="card">
            <h3>Honor</h3>
            <div><?php echo e($honor); ?></div>
          </div>
        </div>
        <br>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Learning Area</th><?php for ($q = 1; $q <= $quarters; $q++): ?><th>Quarter <?php echo $q; ?></th><?php endfor; ?><th>Final</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody><?php foreach ($subjectRows as $row): ?><tr>
                  <td><?php echo e($row['label']); ?></td><?php foreach ($row['values'] as $v): ?><td><?php echo e($v ?? ''); ?></td><?php endforeach; ?><td><?php echo e($row['final']); ?></td>
                  <td><?php echo e($row['remarks']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
        <section class="report-card print-area" id="printable-card">
          <div class="report-card-inner">
            <div class="report-header">
              <div class="report-logo">NI</div>
              <div>
                <h2>DepEd Form 138-A</h2>
                <p>Department of Education</p>
                <p>Republic of the Philippines</p>
              </div>
            </div>
            <div class="report-school"><strong><?php echo strtoupper(school_name()); ?></strong><span>Junior & Senior High School Portal</span></div>
            <h3>Report on Learning Progress and Achievement</h3>
            <table class="student-info-table">
              <tr>
                <td><strong>Name:</strong> <?php echo e($student['name']); ?></td>
                <td><strong>LRN:</strong> <?php echo e($student['lrn']); ?></td>
              </tr>
              <tr>
                <td><strong>Age:</strong> <?php echo e($student['age']); ?></td>
                <td><strong>Sex:</strong> <?php echo e($student['sex']); ?></td>
              </tr>
              <tr>
                <td><strong>Grade:</strong> <?php echo e($student['glvl']); ?></td>
                <td><strong>Section:</strong> <?php echo e($student['section']); ?></td>
              </tr>
              <tr>
                <td><strong>School Year:</strong> <?php echo date('Y') . '-' . (date('Y') + 1); ?></td>
                <td><strong>Curriculum / Strand:</strong> <?php echo e($student['strand'] ?: ($isShs ? 'SHS' : 'JHS')); ?></td>
              </tr>
            </table>
            <table class="report-grades-table">
              <thead>
                <tr>
                  <th rowspan="2">Learning Areas</th>
                  <th colspan="<?php echo $quarters; ?>">Quarter</th>
                  <th rowspan="2">Final Grade</th>
                  <th rowspan="2">Remarks</th>
                </tr>
                <tr><?php for ($q = 1; $q <= $quarters; $q++): ?><th><?php echo $q; ?></th><?php endfor; ?></tr>
              </thead>
              <tbody><?php foreach ($subjectRows as $row): ?><tr>
                    <td class="text-left"><?php echo e($row['label']); ?></td><?php foreach ($row['values'] as $v): ?><td><?php echo e($v !== null && $v !== '' ? $v : '-'); ?></td><?php endforeach; ?><td><?php echo e($row['final'] ?? '-'); ?></td>
                    <td><?php echo e($row['remarks'] ?: '-'); ?></td>
                  </tr><?php endforeach; ?><tr class="report-average-row">
                  <td colspan="<?php echo $quarters + 1; ?>" class="text-right"><strong>General Average</strong></td>
                  <td><?php echo e($gwa !== null ? number_format($gwa, 2) : '-'); ?></td>
                  <td><?php echo e($status); ?></td>
                </tr>
              </tbody>
            </table>
            <table class="grading-system-table">
              <thead>
                <tr>
                  <th colspan="3">Grading System</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>90 - 100</td>
                  <td>Outstanding</td>
                  <td>Passed</td>
                </tr>
                <tr>
                  <td>85 - 89</td>
                  <td>Very Satisfactory</td>
                  <td>Passed</td>
                </tr>
                <tr>
                  <td>80 - 84</td>
                  <td>Satisfactory</td>
                  <td>Passed</td>
                </tr>
                <tr>
                  <td>75 - 79</td>
                  <td>Fairly Satisfactory</td>
                  <td>Passed</td>
                </tr>
                <tr>
                  <td>Below 75</td>
                  <td>Did Not Meet Expectations</td>
                  <td>Failed</td>
                </tr>
              </tbody>
            </table>
            <div class="report-footer-note">
              <p><?php echo school_name(); ?> is committed to excellence, integrity, and learner-centered education.</p>
              <p class="signature-line">Adviser / Teacher Signature</p>
            </div>
          </div>
        </section>
      <?php elseif ($page === 'analytics'): ?>
        <div class="grid-two">
          <section class="card">
            <h3>Quarter Trend</h3>
            <div class="bar-chart"><?php foreach ($trendValues as $idx => $v): ?><div class="bar-row"><span><?php echo e($trendLabels[$idx]); ?></span>
                  <div><b style="width:<?php echo max(5, min(100, $v)); ?>%"></b></div><strong><?php echo e($v); ?></strong>
                </div><?php endforeach; ?></div>
          </section>
          <section class="card">
            <h3>Subject Performance</h3><?php foreach ($subjectRows as $row): $v = $row['final'] ?: 0; ?><div class="bar-row"><span><?php echo e($row['label']); ?></span>
                <div><b style="width:<?php echo max(5, min(100, $v)); ?>%"></b></div><strong><?php echo e($row['final'] ?? '-'); ?></strong>
              </div><?php endforeach; ?>
          </section>
        </div>
      <?php elseif ($page === 'attendance'): ?>
        <div class="cards">
          <div class="card">
            <h3>Attendance Rate</h3>
            <div class="number"><?php echo e($attRate); ?>%</div>
          </div><?php foreach ($attendance as $k => $v): ?><div class="card">
              <h3><?php echo e($k); ?></h3>
              <div class="number small-number"><?php echo e($v); ?></div>
            </div><?php endforeach; ?>
        </div><br>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody><?php $logs = $conn->query("SELECT * FROM attendance WHERE lrn=" . (int)$lrn . " ORDER BY school_date DESC LIMIT 30");
                    if ($logs && $logs->num_rows): while ($a = $logs->fetch_assoc()): ?><tr>
                    <td><?php echo e($a['school_date']); ?></td>
                    <td><span class="badge"><?php echo e($a['status']); ?></span></td>
                    <td><?php echo e($a['remarks']); ?></td>
                  </tr><?php endwhile;
                    else: ?><tr>
                  <td colspan="3" class="empty-row">No attendance records yet.</td>
                </tr><?php endif; ?></tbody>
          </table>
        </div>
      <?php elseif ($page === 'announcements'): ?>
        <div class="feature-list"><?php if ($announcements && $announcements->num_rows): while ($a = $announcements->fetch_assoc()): ?><article class="card">
                <h3><?php echo e($a['title']); ?></h3>
                <p><?php echo e($a['body']); ?></p><small class="muted"><?php echo e($a['audience']); ?> • <?php echo e(date('M d, Y', strtotime($a['created_at']))); ?></small>
              </article><?php endwhile;
                                  else: ?><div class="card">No announcements yet.</div><?php endif; ?></div>
      <?php elseif ($page === 'calendar'): ?>
        <div class="feature-list"><?php if ($calendar && $calendar->num_rows): while ($c = $calendar->fetch_assoc()): ?><article class="card calendar-card">
                <div class="calendar-date"><strong><?php echo e(date('d', strtotime($c['event_date']))); ?></strong><span><?php echo e(date('M', strtotime($c['event_date']))); ?></span></div>
                <div>
                  <h3><?php echo e($c['event_title']); ?></h3>
                  <p><?php echo e($c['description']); ?></p><small class="muted"><?php echo e($c['event_type']); ?></small>
                </div>
              </article><?php endwhile;
                                  else: ?><div class="card">No upcoming events.</div><?php endif; ?></div>
      <?php elseif ($page === 'activities'): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Activity</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody><?php if ($activities && $activities->num_rows): while ($a = $activities->fetch_assoc()): ?><tr>
                    <td><?php echo e($a['title']); ?></td>
                    <td><?php echo e($a['subject']); ?></td>
                    <td><?php echo e($a['description']); ?></td>
                    <td><?php echo e($a['due_date']); ?></td>
                    <td><span class="badge"><?php echo e($a['status']); ?></span></td>
                  </tr><?php endwhile;
                    else: ?><tr>
                  <td colspan="5" class="empty-row">No activities assigned yet.</td>
                </tr><?php endif; ?></tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="grid-two">
          <section class="card">
            <h3>Student Profile</h3>
            <form method="POST"><input type="hidden" name="action" value="update_profile">
              <div class="field"><label>Address</label><input name="address" value="<?php echo e($student['address'] ?? ''); ?>"></div>
              <div class="field"><label>Guardian</label><input name="guardian" value="<?php echo e($student['guardian'] ?? ''); ?>"></div>
              <div class="field"><label>Contact No.</label><input name="contact_no" value="<?php echo e($student['contact_no'] ?? ''); ?>"></div>
              <div class="field"><label>Email</label><input type="email" name="email" value="<?php echo e($student['email'] ?? ''); ?>"></div><button class="btn" type="submit">Save Profile</button>
            </form>
          </section>
          <section class="card">
            <h3>Change Password</h3>
            <form method="POST"><input type="hidden" name="action" value="change_password">
              <div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
              <div class="field"><label>New Password</label><input type="password" name="new_password" required></div>
              <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div><button class="btn" type="submit">Change Password</button>
            </form>
          </section>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>

</html>