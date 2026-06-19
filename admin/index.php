<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();
$stats = [
  'bookings'  => $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
  'pending'   => $db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
  'confirmed' => $db->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
  'tours'     => $db->query("SELECT COUNT(*) FROM tours")->fetchColumn(),
  'contacts'  => $db->query("SELECT COUNT(*) FROM contacts WHERE status='unread'")->fetchColumn(),
  'revenue'   => $db->query("SELECT COALESCE(SUM(t.price * b.travelers),0) FROM bookings b JOIN tours t ON b.tour_id=t.id WHERE MONTH(b.created_at)=MONTH(NOW())")->fetchColumn(),
];

$recentBookings = $db->query("SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id=t.id ORDER BY b.created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Jambo Masai Tours Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <?php include 'includes/admin_sidebar.php'; ?>

  <!-- Main -->
  <main class="admin-main">
    <!-- Header -->
    <div class="admin-header">
      <div>
        <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;color:#fff;font-weight:700">Dashboard</h1>
        <p style="color:rgba(255,255,255,.4);font-size:.82rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">Welcome back, <strong style="color:#10b981"><?= e($_SESSION['admin_user'] ?? 'Admin') ?></strong></p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= url() ?>" target="_blank" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem"><i class="fas fa-external-link-alt" style="font-size:.6rem"></i> View Site</a>
        <a href="logout.php" class="btn btn--danger btn--sm" style="display:inline-flex;align-items:center;gap:.35rem"><i class="fas fa-power-off" style="font-size:.6rem"></i> Logout</a>
      </div>
    </div>

    <!-- Quick action buttons -->
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem">
      <?php foreach ([
        ['tours.php?edit=0',    'fa-plus',       'Add Tour',     'btn--primary'],
        ['bookings.php',        'fa-list',        'Bookings',     'btn--outline'],
        ['contacts.php',        'fa-envelope',   'Messages',     'btn--outline'],
        ['gallery.php',         'fa-images',     'Gallery',      'btn--outline'],
        ['settings.php',        'fa-cog',        'Settings',     'btn--outline'],
      ] as $q): ?>
      <a href="<?= $q[0] ?>" class="btn <?= $q[3] ?> btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
        <i class="fas <?= $q[1] ?>" style="font-size:.65rem"></i><?= $q[2] ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Stat cards -->
    <div class="adm-stats">
      <?php foreach ([
        ['fa-calendar-check','rgba(16,185,129,.15)','#10b981', $stats['bookings'],  'Total Bookings'],
        ['fa-clock',         'rgba(245,158,11,.15)','#fbbf24', $stats['pending'],   'Pending'],
        ['fa-check-circle',  'rgba(52,211,153,.15)','#34d399', $stats['confirmed'], 'Confirmed'],
        ['fa-compass',       'rgba(96,165,250,.15)','#60a5fa', $stats['tours'],     'Active Tours'],
        ['fa-envelope',      'rgba(167,139,250,.15)','#a78bfa',$stats['contacts'],  'Unread Messages'],
        ['fa-dollar-sign',   'rgba(245,158,11,.15)','#fbbf24', '$'.number_format($stats['revenue']), 'Revenue (Month)'],
      ] as $w): ?>
      <div class="adm-stat">
        <div class="adm-stat-icon" style="background:<?= $w[1] ?>"><i class="fas <?= $w[0] ?>" style="color:<?= $w[2] ?>;font-size:1rem"></i></div>
        <div>
          <div class="adm-stat-val"><?= e($w[3]) ?></div>
          <div class="adm-stat-lbl"><?= e($w[4]) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Recent Bookings -->
    <div class="adm-card" style="margin-bottom:1.5rem">
      <div class="adm-card-header">
        <h2 class="adm-card-title">Recent Bookings</h2>
        <a href="bookings.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem"><i class="fas fa-list" style="font-size:.6rem"></i> View All</a>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead><tr><th>#</th><th>Client</th><th>Tour</th><th>Date</th><th>Pax</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (empty($recentBookings)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:rgba(255,255,255,.3)">No bookings yet</td></tr>
            <?php else: foreach ($recentBookings as $b): ?>
            <tr>
              <td style="color:rgba(255,255,255,.3);font-size:.72rem">#<?= e($b['id']) ?></td>
              <td>
                <div style="font-weight:600;color:rgba(255,255,255,.85);font-size:.82rem"><?= e($b['first_name'].' '.$b['last_name']) ?></div>
                <div style="font-size:.7rem;color:rgba(255,255,255,.3)"><?= e($b['country']) ?></div>
              </td>
              <td style="max-width:180px;color:rgba(255,255,255,.6);font-size:.8rem"><?= e(truncate($b['tour_name'],28)) ?></td>
              <td style="font-size:.8rem;color:rgba(255,255,255,.5)"><?= e(formatDate($b['travel_date'])) ?></td>
              <td style="text-align:center;font-weight:600;color:#fff"><?= e($b['travelers']) ?></td>
              <td><span class="badge badge--<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
              <td><a href="bookings.php" class="btn btn--outline btn--sm" style="font-size:.6rem;padding:.25rem .55rem">View</a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick links grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.85rem">
      <?php foreach ([
        ['tours.php',        'fa-compass',        '#10b981','Safari Tours',      'Manage tour packages',         $stats['tours'].' tours'],
        ['bookings.php',     'fa-calendar-check', '#fbbf24','Bookings',          'View reservations',            $stats['bookings'].' total'],
        ['contacts.php',     'fa-envelope',       '#a78bfa','Messages',          'Customer enquiries',           $stats['contacts'].' unread'],
        ['gallery.php',      'fa-images',         '#60a5fa','Gallery',           'Upload & manage photos',       ''],
        ['destinations.php', 'fa-map-marked-alt', '#f97316','Destinations',      'Destination content',          ''],
        ['settings.php',     'fa-cog',            '#34d399','Settings',          'Logo, name, contacts',         ''],
      ] as $ql): ?>
      <a href="<?= $ql[0] ?>" style="display:flex;align-items:center;gap:.75rem;padding:.9rem 1rem;background:#1a1d27;border:1px solid rgba(255,255,255,.08);border-radius:12px;text-decoration:none;transition:all .25s" onmouseover="this.style.borderColor='rgba(16,185,129,.25)';this.style.background='rgba(16,185,129,.05)'" onmouseout="this.style.borderColor='rgba(255,255,255,.08)';this.style.background='#1a1d27'">
        <div style="width:38px;height:38px;border-radius:10px;background:<?= $ql[2] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fas <?= $ql[1] ?>" style="color:<?= $ql[2] ?>;font-size:.88rem"></i>
        </div>
        <div>
          <div style="font-family:'Montserrat',sans-serif;font-weight:700;font-size:.8rem;color:#fff"><?= $ql[3] ?></div>
          <div style="font-size:.7rem;color:rgba(255,255,255,.3)"><?= $ql[4] ?></div>
          <?php if ($ql[5]): ?><div style="font-size:.62rem;color:<?= $ql[2] ?>"><?= $ql[5] ?></div><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

  </main>
</div>
</body>
</html>
