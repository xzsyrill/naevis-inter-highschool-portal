<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login('Teacher');

$lrn = $_GET['lrn'] ?? '';
$stmt = $conn->prepare('SELECT * FROM students WHERE lrn=?');
$stmt->bind_param('s', $lrn);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
  die('Student not found.');
}

$isShs = (int)$student['glvl'] >= 11;
$subjects = $isShs ? shs_subjects() : jhs_subjects();
$quarters = $isShs ? 2 : 4;
$schoolYear = date('Y') . '-' . (date('Y') + 1);

function grade_field_name($prefix, $q, $isShs)
{
  return ($isShs && in_array($prefix, ['applied1', 'applied2', 'spec1', 'spec2'], true)) ? $prefix . '_q' . $q : $prefix . $q;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Encode Grades | <?php echo school_name(); ?></title>
  <link rel="stylesheet" href="assets/css/portal.css">
  <script defer src="assets/js/portal.js"></script>
</head>

<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <div class="shell grading-shell">
    <aside class="sidebar no-print">
      <h2>Grades</h2>
      <a class="side-link" href="teacher_dashboard.php">Back to Dashboard</a>
      <a class="side-link active" href="#">Encode Grades</a>
    </aside>

    <main class="main grading-main">
      <div class="page-title no-print">
        <div>
          <h1>Encode Grades</h1>
          <p><?php echo e($student['name']); ?> | LRN: <?php echo e($student['lrn']); ?> | Grade <?php echo e($student['glvl']); ?> <?php echo e($student['section']); ?> <?php echo e($student['strand']); ?></p>
        </div>
        <button class="btn secondary" type="button" onclick="printReportCard()">Print Report Card</button>
      </div>

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert success no-print">Grades saved successfully.</div>
      <?php endif; ?>

      <form method="POST" action="save_grades.php" class="grade-form no-print">
        <input type="hidden" name="lrn" value="<?php echo e($student['lrn']); ?>">
        <input type="hidden" name="is_shs" value="<?php echo $isShs ? 1 : 0; ?>">
        <div class="table-wrap grade-table-wrap">
          <table class="grade-encode-table">
            <thead>
              <tr>
                <th>Learning Area</th>
                <?php for ($q = 1; $q <= $quarters; $q++): ?>
                  <th>Quarter <?php echo $q; ?></th>
                <?php endfor; ?>
                <th>Final Grade</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subjects as $prefix => $label): ?>
                <?php
                $values = [];
                for ($q = 1; $q <= $quarters; $q++) {
                  $field = grade_field_name($prefix, $q, $isShs);
                  $values[] = $student[$field] ?? null;
                }
                $fg = final_grade($values);
                ?>
                <tr class="grade-row" data-subject="<?php echo e($prefix); ?>">
                  <td class="subject-name"><?php echo e($label); ?></td>
                  <?php for ($q = 1; $q <= $quarters; $q++): ?>
                    <?php $field = grade_field_name($prefix, $q, $isShs); ?>
                    <td>
                      <input class="grade-input" type="number" min="0" max="100" step="0.01" name="<?php echo e($field); ?>" value="<?php echo e($student[$field] ?? ''); ?>" placeholder="0-100">
                    </td>
                  <?php endfor; ?>
                  <td><input class="final-grade" type="text" readonly value="<?php echo e($fg); ?>"></td>
                  <td><input class="remarks" type="text" readonly value="<?php echo e(remarks($fg)); ?>"></td>
                </tr>
              <?php endforeach; ?>
              <tr class="gwa-row">
                <td colspan="<?php echo $quarters + 1; ?>" class="text-right"><strong>General Average</strong></td>
                <td><input id="general_average" name="gwa" type="text" readonly value="<?php echo e($student['gwa']); ?>"></td>
                <td><input id="promotion_status" type="text" readonly value="<?php echo $student['gwa'] !== null ? ((float)$student['gwa'] >= 75 ? 'Promoted' : 'Retained') : ''; ?>"></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="form-actions-bar">
          <button class="btn" type="submit">Save Grades</button>
          <button class="btn secondary" type="button" onclick="printReportCard()">Print Report Card</button>
        </div>
      </form>

      <section class="report-card print-area" id="reportCard">
        <div class="report-card-inner">
          <div class="report-header">
            <div class="report-logo">NI</div>
            <div>
              <h2>DepEd Form 138-A</h2>
              <p>Department of Education</p>
              <p>Republic of the Philippines</p>
            </div>
          </div>
          <div class="report-school">
            <strong><?php echo strtoupper(school_name()); ?></strong>
            <span>Junior & Senior High School Portal</span>
          </div>
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
              <td><strong>School Year:</strong> <?php echo e($schoolYear); ?></td>
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
              <tr>
                <?php for ($q = 1; $q <= $quarters; $q++): ?><th><?php echo $q; ?></th><?php endfor; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subjects as $prefix => $label): ?>
                <?php
                $values = [];
                for ($q = 1; $q <= $quarters; $q++) {
                  $field = grade_field_name($prefix, $q, $isShs);
                  $values[$q] = $student[$field] ?? null;
                }
                $fg = final_grade($values);
                ?>
                <tr>
                  <td class="text-left"><?php echo e($label); ?></td>
                  <?php for ($q = 1; $q <= $quarters; $q++): ?>
                    <td data-print="<?php echo e($prefix); ?>-q<?php echo $q; ?>"><?php echo e($values[$q] !== null && $values[$q] !== '' ? $values[$q] : '-'); ?></td>
                  <?php endfor; ?>
                  <td data-print="<?php echo e($prefix); ?>-final"><?php echo e($fg !== null ? $fg : '-'); ?></td>
                  <td data-print="<?php echo e($prefix); ?>-remarks"><?php echo e(remarks($fg) ?: '-'); ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="report-average-row">
                <td colspan="<?php echo $quarters + 1; ?>" class="text-right"><strong>General Average</strong></td>
                <td data-print="gwa"><?php echo e($student['gwa'] !== null ? $student['gwa'] : '-'); ?></td>
                <td data-print="promotion"><?php echo $student['gwa'] !== null ? ((float)$student['gwa'] >= 75 ? 'Promoted' : 'Retained') : '-'; ?></td>
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
    </main>
  </div>
</body>

</html>