<?php
$host = "sql105.infinityfree.com";
$user = "if0_42031254";
$pass = "Ningyizhuo29";
$dbname = "if0_42031254_schoolportal";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
require_once __DIR__ . '/feature_setup.php';
ensure_feature_tables($conn);
