<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();

// Update booking status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('Invalid token.'); }
  $id     = sanitizeInt($_POST['booking_id'] ?? 0, 1, PHP_INT_MAX);
  $status = sanitizeInput($_POST['status'] ?? '');
  if ($id && in_array($status, ['pending','confirmed','completed','cancelled'])) {
    $stmt = $db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    setFlashMessage('success', 'Booking #' . $id . ' status updated.');
    redirect(url('admin/bookings.php'));
  }
}

$filter = sanitizeInput($_GET['status'] ?? '');
$sql  = "SELECT b.*, t.name as tour_name, t.price FROM bookings b JOIN tours t ON b.tour_id = t.id";
$params = [];
if ($filter && in_array($filter, ['pending','confirmed','completed','cancelled'])) {
  $sql .= " WHERE b.status = ?";
  $params[] = $filter;
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Bookings</h1>
        <p style="color:var(--text-muted);font-size:.9rem;"><?= count($bookings) ?> records</p>
      </div>
    </div>

    <?php if (!empty($flash)): ?>
    <div class="form-success" style="margin-bottom:var(--space-6);"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-6);flex-wrap:wrap;">
      <?php foreach (['', 'pending', 'confirmed', 'completed', 'cancelled'] as $s): ?>
      <a href="bookings.php<?= $s ? '?status=' . $s : '' ?>"
         class="btn btn--sm <?= $filter === $s ? 'btn--gold' : 'btn--outline' ?>">
        <?= $s ? ucfirst($s) : 'All' ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Tour</th><th>Travel Date</th><th>Pax</th><th>Value</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= e($b['id']) ?></td>
            <td><strong><?= e($b['first_name'] . ' ' . $b['last_name']) ?></strong><br><small style="color:var(--text-muted)"><?= e($b['country']) ?></small></td>
            <td><a href="mailto:<?= e($b['email']) ?>" style="color:var(--color-gold)"><?= e($b['email']) ?></a><br><small><?= e($b['phone']) ?></small></td>
            <td><?= e(truncate($b['tour_name'], 30)) ?></td>
            <td><?= e($b['travel_date']) ?></td>
            <td><?= e($b['travelers']) ?></td>
            <td><?= formatPrice($b['price'] * $b['travelers']) ?></td>
            <td><span class="badge badge--<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
            <td>
              <form method="POST" style="display:flex;gap:var(--space-2);align-items:center;">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                <input type="hidden" name="update_status" value="1">
                <select name="status" class="form-control" style="padding:.3rem .6rem;font-size:.78rem;min-width:110px;">
                  <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--gold btn--sm">Save</button>
              </form>
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
