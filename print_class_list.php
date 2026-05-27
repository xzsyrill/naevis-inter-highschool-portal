<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$grade = $_GET['grade'] ?? '';

$sql = 'SELECT * FROM students ' .
  ($grade !== '' ? 'WHERE glvl=' . (int)$grade : '') .
  ' ORDER BY glvl, strand, section, name';

$rs = $conn->query($sql);

$current = '';
?>

<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Class List</title>
  <link rel="stylesheet" href="assets/css/portal.css">
</head>

<body>

  <main class="main">
    <div class="page-title">
      <div>
        <h1><?php echo school_name(); ?></h1>
        <p>
          Official Class List • Printed
          <?php echo date('F j, Y'); ?>
        </p>
      </div>

      <button class="btn" onclick="window.print()">
        Print
      </button>
    </div>

    <?php while ($s = $rs->fetch_assoc()):

      $group = 'Grade ' . $s['glvl'] . ' ' .
        ($s['strand'] ? $s['strand'] . ' ' : '') .
        $s['section'];

      if ($group !== $current):

        if ($current !== '') {
          echo '</tbody></table></div><br>';
        }

        $current = $group;
    ?>

        <h2><?php echo e($group); ?></h2>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>LRN</th>
                <th>Age</th>
                <th>Sex</th>
                <th>GWA</th>
              </tr>
            </thead>

            <tbody>

              <?php $i = 1; ?>

            <?php endif; ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo e($s['name']); ?></td>
              <td><?php echo e($s['lrn']); ?></td>
              <td><?php echo e($s['age']); ?></td>
              <td><?php echo e($s['sex']); ?></td>
              <td><?php echo e($s['gwa']); ?></td>
            </tr>

          <?php endwhile; ?>

          <?php
          if ($current !== '') {
            echo '</tbody></table></div>';
          }
          ?>

  </main>

</body>

</html>