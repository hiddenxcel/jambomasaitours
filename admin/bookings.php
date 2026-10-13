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
    <?php
    $counts = ['' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
    try { foreach ($db->query("SELECT status, COUNT(*) c FROM bookings GROUP BY status")->fetchAll() as $r) { $counts[$r['status']] = (int)$r['c']; $counts[''] += (int)$r['c']; } } catch (\Throwable $e) {}
    adm_pagehead('Bookings', 'Booking requests sent from the website. ' . count($bookings) . ' shown' . ($filter ? ' (' . e($filter) . ')' : '') . '.', [
      'icon' => 'fa-calendar-check',
      'help' => [
        'key' => 'bookings', 'title' => 'How bookings work',
        'steps' => [
          '<strong>A customer fills the booking form</strong> on the website. The request appears here as <em>Pending</em> and you receive an email (if email notifications are set up in <a href="settings.php#email">Settings</a>).',
          '<strong>Contact the customer</strong> using the email or WhatsApp buttons in the row. Confirm dates, group size and price with them.',
          '<strong>Update the status</strong> in the last column, then press <strong>Save</strong>.',
          'Use <strong>Confirmed</strong> once the customer agrees and pays the deposit, <strong>Completed</strong> after the trip, and <strong>Cancelled</strong> if it will not go ahead.',
        ],
        'tips' => [
          'Changing the status here <strong>does not send an email</strong> to the customer. Always reply to them yourself.',
          '<strong>Value</strong> is the tour\'s USD price multiplied by the number of travellers. It is an estimate, not an invoice.',
          'Only <em>Confirmed</em> and <em>Completed</em> bookings count towards "Confirmed this month" on the Dashboard.',
        ],
      ],
    ]);
    ?>

    <?php if (!empty($flash)): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i><div><?= e($flash['message']) ?></div></div>
    <?php endif; ?>

    <!-- Vichujio vyenye idadi -->
    <div style="display:flex;gap:.5rem;margin-bottom:1.1rem;flex-wrap:wrap">
      <?php foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $s => $label): ?>
      <a href="bookings.php<?= $s ? '?status=' . $s : '' ?>" class="btn btn--sm <?= $filter === $s ? 'btn--primary' : 'btn--outline' ?>">
        <?= $label ?> <span style="opacity:.7;font-weight:500"><?= (int)($counts[$s] ?? 0) ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="adm-card">
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr><th>#</th><th>Customer</th><th>Contact</th><th>Tour</th><th>Travel date</th><th>Pax</th><th>Value (USD)</th><th>Status</th><th>Update status</th></tr>
          </thead>
          <tbody>
            <?php if (!$bookings): ?>
            <tr><td colspan="9"><div class="adm-empty"><i class="fas fa-calendar-xmark"></i><strong>No bookings <?= $filter ? 'with this status' : 'yet' ?></strong>Requests from the website booking form will appear here.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($bookings as $b):
              $wa = preg_replace('/\D+/', '', (string)$b['phone']); ?>
            <tr>
              <td style="font-size:.74rem">#<?= e($b['id']) ?></td>
              <td><strong style="color:#fff"><?= e($b['first_name'] . ' ' . $b['last_name']) ?></strong><br><small style="color:var(--text-dim)"><?= e($b['country']) ?></small></td>
              <td>
                <a href="mailto:<?= e($b['email']) ?>" style="font-size:.8rem"><?= e($b['email']) ?></a><br>
                <small style="color:var(--text-dim)"><?= e($b['phone']) ?></small>
                <div style="display:flex;gap:.35rem;margin-top:.35rem">
                  <a class="btn btn--outline btn--sm" href="mailto:<?= e($b['email']) ?>" title="Send email"><i class="fas fa-envelope"></i></a>
                  <?php if ($wa): ?><a class="btn btn--outline btn--sm" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>" title="Open WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                </div>
              </td>
              <td><?= e(truncate($b['tour_name'], 30)) ?></td>
              <td><?= e($b['travel_date']) ?></td>
              <td><?= e($b['travelers']) ?></td>
              <td><?= formatPrice($b['price'] * $b['travelers']) ?></td>
              <td><span class="badge badge--<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
              <td>
                <form method="POST" style="display:flex;gap:.4rem;align-items:center">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                  <input type="hidden" name="update_status" value="1">
                  <select name="status" class="f-select" style="padding:.4rem .6rem;font-size:.78rem;min-width:115px">
                    <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn--primary btn--sm">Save</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
</body>
</html>
