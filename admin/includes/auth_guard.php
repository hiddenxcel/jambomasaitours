<?php
// Must be included AFTER config + functions
$sessionMaxAge = 3600;

if (
  empty($_SESSION['admin_id']) ||
  empty($_SESSION['admin_login_time']) ||
  (time() - $_SESSION['admin_login_time']) > $sessionMaxAge
) {
  session_destroy();
  header('Location: ' . SITE_URL . '/admin/login.php?expired=1');
  exit;
}

$_SESSION['admin_login_time'] = time();
