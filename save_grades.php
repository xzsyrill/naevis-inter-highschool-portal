<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login('Teacher');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: teacher_dashboard.php');
  exit();
}
$lrn = $_POST['lrn'] ?? '';
$isShs = ($_POST['is_shs'] ?? '0') === '1';
$subjects = $isShs ? shs_subjects() : jhs_subjects();
$fields = [];
$values = [];
$types = '';
$total = 0;
$count = 0;
foreach ($subjects as $prefix => $label) {
  $quarters = $isShs ? 2 : 4;
  $sub = [];
  for ($q = 1; $q <= $quarters; $q++) {
    $field = ($isShs && in_array($prefix, ['applied1', 'applied2', 'spec1', 'spec2'])) ? $prefix . '_q' . $q : $prefix . $q;
    $val = ($_POST[$field] ?? '') !== '' ? (float)$_POST[$field] : null;
    $fields[] = "$field=?";
    $values[] = $val;
    $types .= 'd';
    $sub[] = $val;
  }
  $fg = final_grade($sub);
  if ($fg !== null) {
    $total += $fg;
    $count++;
  }
}
$gwa = $count > 0 ? round($total / $count, 2) : null;
$fields[] = 'gwa=?';
$values[] = $gwa;
$types .= 'd';
$values[] = $lrn;
$types .= 's';
$sql = 'UPDATE students SET ' . implode(',', $fields) . ' WHERE lrn=?';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$values);
if ($stmt->execute()) {
  $teacherId = current_user_id();
  $details = 'Updated grades. New GWA: ' . ($gwa !== null ? $gwa : 'N/A');
  $audit = $conn->prepare("INSERT INTO audit_logs (user_id, role, action, target_lrn, details) VALUES (?, 'Teacher', 'Grade Update', ?, ?)");
  if ($audit) {
    $audit->bind_param('sss', $teacherId, $lrn, $details);
    $audit->execute();
  }
  $note = 'Your grades were updated. Latest GWA: ' . ($gwa !== null ? $gwa : 'Pending');
  $notif = $conn->prepare("INSERT INTO notifications (lrn, message, link) VALUES (?, ?, 'student_dashboard.php?page=grades')");
  if ($notif) {
    $notif->bind_param('ss', $lrn, $note);
    $notif->execute();
  }
}
header('Location: grading.php?lrn=' . urlencode($lrn) . '&saved=1');
exit();
