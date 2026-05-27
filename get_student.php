<?php
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');
$lrn = $_GET['lrn'] ?? '';
$stmt = $conn->prepare('SELECT lrn,name,glvl,section,strand,age,sex,gwa FROM students WHERE lrn=?');
$stmt->bind_param('s', $lrn);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo json_encode($row ?: null);
