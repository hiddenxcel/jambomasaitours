<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';

// Already logged in ? dashboard
if (!empty($_SESSION['admin_id'])) { redirect(url('admin/index.php')); }

$error     = '';
$csrfToken = generateCsrfToken();

// ─── Brute-force lockout: 5 failed attempts → 15-minute cool-off ───
$LOCK_MAX     = 5;
$LOCK_WINDOW  = 900; // 15 minutes
$lp = $_SESSION['login_attempts'] ?? ['count' => 0, 'first' => time()];
if (time() - $lp['first'] > $LOCK_WINDOW) { $lp = ['count' => 0, 'first' => time()]; } // window reset
$lockedOut = $lp['count'] >= $LOCK_MAX;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($lockedOut) {
    $wait  = ceil(($LOCK_WINDOW - (time() - $lp['first'])) / 60);
    $error = "Too many failed attempts. Please try again in {$wait} minute(s).";
  } elseif (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $error = 'Security error. Please try again.';
  } else {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
      session_regenerate_id(true);
      unset($_SESSION['login_attempts']); // clear on success
      $_SESSION['admin_id']         = $admin['id'];
      $_SESSION['admin_user']       = $admin['username'];
      $_SESSION['admin_login_time'] = time();
      redirect(url('admin/index.php'));
    } else {
      sleep(1); // slow brute force
      $lp['count']++;
      $_SESSION['login_attempts'] = $lp;
      $remaining = max(0, $LOCK_MAX - $lp['count']);
      $error = 'Invalid username or password.'
             . ($remaining > 0 && $remaining <= 2 ? " {$remaining} attempt(s) left." : '');
      unset($_SESSION[CSRF_TOKEN_NAME]);
      $csrfToken = generateCsrfToken();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | <?= e(SITE_NAME) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Nanum+Myeongjo:wght@700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    body{background:#0a0a0a;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
    .login-wrap{width:100%;max-width:400px}
    .login-card{background:#1a1d27;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2.5rem 2rem;box-shadow:0 20px 60px rgba(0,0,0,.5)}
  </style>
</head>
<body>
<div class="login-wrap">
  <!-- Glow -->
  <div style="position:fixed;top:-100px;left:50%;transform:translateX(-50%);width:400px;height:300px;background:radial-gradient(ellipse,rgba(16,185,129,.08),transparent);pointer-events:none"></div>

  <div class="login-card">
    <!-- Logo -->
    <div style="text-align:center;margin-bottom:2rem">
      <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;margin:0 auto .85rem">
        <i class="fas fa-tree" style="color:#fff;font-size:1.4rem"></i>
      </div>
      <div style="font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:1.3rem">
        Jambo <span style="background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Masai</span>
      </div>
      <div style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.3);letter-spacing:.18em;text-transform:uppercase;margin-top:.2rem">Admin Panel</div>
    </div>

    <?php if (!empty($_GET['expired'])): ?>
    <div class="alert alert-warning" style="margin-bottom:1.1rem"><i class="fas fa-clock"></i> Session expired. Please log in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:1.1rem"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
      <div class="f-group">
        <label class="f-label" for="username">Username</label>
        <div style="position:relative">
          <i class="fas fa-user" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.78rem;pointer-events:none"></i>
          <input type="text" class="f-input" id="username" name="username" required autocomplete="username" placeholder="admin" style="padding-left:2.5rem">
        </div>
      </div>
      <div class="f-group">
        <label class="f-label" for="password">Password</label>
        <div style="position:relative">
          <i class="fas fa-lock" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.78rem;pointer-events:none"></i>
          <input type="password" class="f-input" id="password" name="password" required autocomplete="current-password" placeholder="————————" style="padding-left:2.5rem">
        </div>
      </div>
      <button type="submit" class="btn btn-adm btn-adm-primary w-full" style="justify-content:center;padding:.85rem;font-size:.82rem;margin-top:.5rem;border-radius:12px">
        <i class="fas fa-sign-in-alt" style="font-size:.72rem"></i> Sign In
      </button>
    </form>

    <div style="text-align:center;margin-top:1.25rem">
      <a href="<?= url() ?>" style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.3);text-decoration:none" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='rgba(255,255,255,.3)'">
        ? Back to Website
      </a>
    </div>
  </div>
</div>
</body>
</html>
