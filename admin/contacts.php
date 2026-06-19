<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
  if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('Invalid token.'); }
  $id = sanitizeInt($_POST['contact_id'] ?? 0, 1, PHP_INT_MAX);
  if ($id) { $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ?")->execute([$id]); }
  redirect(url('admin/contacts.php'));
}

$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Contact Messages</h1>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($contacts as $c): ?>
          <tr style="<?= $c['status'] === 'unread' ? 'background:rgba(201,168,76,.04)' : '' ?>">
            <td><?= e($c['id']) ?></td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td><a href="mailto:<?= e($c['email']) ?>" style="color:var(--color-gold)"><?= e($c['email']) ?></a></td>
            <td><?= e($c['subject']) ?></td>
            <td style="max-width:280px;white-space:normal;font-size:.85rem;color:var(--text-muted);"><?= e(truncate($c['message'], 100)) ?></td>
            <td><span class="badge badge--<?= $c['status'] === 'unread' ? 'pending' : 'confirmed' ?>"><?= e($c['status']) ?></span></td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= e(formatDate($c['created_at'])) ?></td>
            <td>
              <?php if ($c['status'] === 'unread'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="contact_id" value="<?= e($c['id']) ?>">
                <input type="hidden" name="mark_read"  value="1">
                <button type="submit" class="btn btn--sm btn--outline">Mark Read</button>
              </form>
              <?php endif; ?>
              <a href="mailto:<?= e($c['email']) ?>?subject=Re: <?= urlencode($c['subject']) ?>" class="btn btn--sm btn--gold">Reply</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
