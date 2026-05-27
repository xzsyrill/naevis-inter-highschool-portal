<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function require_login($role = null)
{
  if (!isset($_SESSION['Login']) || $_SESSION['Login'] !== 'true') {
    header('Location: login.php');
    exit();
  }
  if ($role !== null && ($_SESSION['Role'] ?? '') !== $role) {
    header('Location: login.php');
    exit();
  }
}

function current_user_id()
{
  return $_SESSION['UserID'] ?? null;
}

function current_role()
{
  return $_SESSION['Role'] ?? null;
}
