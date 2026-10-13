<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();

/* Hesabu salama: jedwali likikosekana, thamani ni 0 badala ya ukurasa kuvunjika */
$count = function (string $sql) use ($db): int {
    try { return (int)$db->query($sql)->fetchColumn(); } catch (\Throwable $e) { return 0; }
};

$stats = [
  'bookings'  => $count("SELECT COUNT(*) FROM bookings"),
  'pending'   => $count("SELECT COUNT(*) FROM bookings WHERE status='pending'"),
  'confirmed' => $count("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','completed')"),
  'tours'     => $count("SELECT COUNT(*) FROM tours"),
  'contacts'  => $count("SELECT COUNT(*) FROM contacts WHERE status='unread'"),
];
/* Mapato yanayokadiriwa: bookings zilizothibitishwa/zilizokamilika tu, mwezi na mwaka huu. Bei ya USD ya tour haibadilishwi. */
try {
    $stats['revenue'] = (float)$db->query("SELECT COALESCE(SUM(t.price * b.travelers),0) FROM bookings b JOIN tours t ON b.tour_id=t.id
        WHERE b.status IN ('confirmed','completed') AND YEAR(b.created_at)=YEAR(NOW()) AND MONTH(b.created_at)=MONTH(NOW())")->fetchColumn();
} catch (\Throwable $e) { $stats['revenue'] = 0; }

$recentBookings = [];
try { $recentBookings = $db->query("SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id=t.id ORDER BY b.created_at DESC LIMIT 8")->fetchAll(); } catch (\Throwable $e) {}

/* ── Kazi zinazosubiri ── */
/* [ikoni, idadi, umoja, wingi, kiungo, maelezo] */
$todo = [
  ['fa-clock',           $stats['pending'],  'pending booking',  'pending bookings',  'bookings.php',     'Confirm or decline them so customers get an answer.'],
  ['fa-envelope',        $stats['contacts'], 'unread message',   'unread messages',   'contacts.php',     'Reply within 24 hours; fast replies win bookings.'],
  ['fa-route',           $count("SELECT COUNT(*) FROM tours t WHERE NOT EXISTS (SELECT 1 FROM tour_itinerary i WHERE i.tour_id=t.id)"),
                                              'tour has no itinerary', 'tours have no itinerary', 'tours.php', 'Add a day-by-day itinerary so the tour page and PDF are complete.'],
  ['fa-images',          $count("SELECT COUNT(*) FROM tours t WHERE NOT EXISTS (SELECT 1 FROM tour_photos p WHERE p.tour_id=t.id)"),
                                              'tour has no extra photos', 'tours have no extra photos', 'tours.php', 'Tours with 4 or more photos convert better.'],
  ['fa-magnifying-glass', $count("SELECT COUNT(*) FROM blog_posts WHERE published=1 AND (IFNULL(seo_title,'')='' OR IFNULL(meta_description,'')='')"),
                                              'blog post is missing an SEO title or description', 'blog posts are missing an SEO title or description', 'blog.php', 'These fields control how the post looks on Google.'],
  ['fa-star',            $count("SELECT COUNT(*) FROM testimonials WHERE approved=0"),
                                              'review is hidden', 'reviews are hidden', 'testimonials.php', 'Check them and publish the genuine ones.'],
];
$todo = array_values(array_filter($todo, fn($t) => $t[1] > 0));

/* ── Orodha ya kuanzia: inasoma hali halisi ya tovuti ── */
$setup = [
  ['Upload your logo',                      getSetting('logo_url') !== '',                       'settings.php#branding'],
  ['Set up email notifications',            getSetting('smtp_host') !== '' && getSetting('smtp_user') !== '', 'settings.php#email'],
  ['Connect Google Analytics',              getSetting('ga4_measurement_id') !== '',             'settings.php#analytics'],
  ['Add your social media links',           getSetting('social_facebook') !== '' || getSetting('social_instagram') !== '', 'settings.php#social'],
  ['Add your Google review link',           getSetting('google_review_url') !== '',              'settings.php#social'],
  ['Publish at least 5 blog posts',         $count("SELECT COUNT(*) FROM blog_posts WHERE published=1") >= 5, 'blog.php'],
];
$setupDone = count(array_filter($setup, fn($s) => $s[1]));
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">

  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">

    <?php adm_pagehead('Dashboard', 'Welcome back, <strong style="color:#fff">' . e($_SESSION['admin_user'] ?? 'Admin') . '</strong>. Here is what needs your attention today.', [
        'icon'    => 'fa-gauge-high',
        'actions' => [
            ['label' => 'View website', 'href' => url(), 'icon' => 'fa-arrow-up-right-from-square', 'attrs' => 'target="_blank" rel="noopener"'],
            ['label' => 'Add tour',     'href' => 'tours.php?edit=0', 'icon' => 'fa-plus', 'kind' => 'primary'],
        ],
        'help' => [
            'key' => 'dashboard', 'title' => 'New here? How to use this admin',
            'steps' => [
                '<strong>Check "Needs attention"</strong> below first. It lists bookings, messages and content that are waiting for you.',
                '<strong>Reply to customers.</strong> Open <a href="bookings.php">Bookings</a> to confirm a request and <a href="contacts.php">Messages</a> to answer enquiries.',
                '<strong>Keep content fresh.</strong> Edit tours in <a href="tours.php">Safari Tours</a> and publish new articles in <a href="blog.php">Blog Posts</a>.',
                '<strong>Finish the setup checklist</strong> on the right so emails, analytics and your logo work.',
                'Need a how-to for anything? Open <a href="help.php">Help &amp; Guide</a>.',
            ],
            'tips' => [
                'Changes you save here go live on the website immediately.',
                'Tour prices are stored in <strong>USD</strong>. Visitors can view other currencies, but the USD price is the real price.',
                'Each page has a "How this page works" box like this one. You can hide it after reading.',
            ],
        ],
    ]); ?>

    <!-- Stat cards -->
    <div class="adm-stats">
      <?php foreach ([
        ['fa-calendar-check', 'rgba(193,122,58,.15)', '#e0a46a', $stats['bookings'],  'Total bookings'],
        ['fa-clock',          'rgba(251,191,36,.14)', '#fbbf24', $stats['pending'],   'Pending'],
        ['fa-circle-check',   'rgba(74,222,128,.13)', '#4ade80', $stats['confirmed'], 'Confirmed'],
        ['fa-compass',        'rgba(96,165,250,.14)', '#60a5fa', $stats['tours'],     'Tours online'],
        ['fa-envelope',       'rgba(167,139,250,.14)','#a78bfa', $stats['contacts'],  'Unread messages'],
        ['fa-dollar-sign',    'rgba(251,191,36,.14)', '#fbbf24', '$' . number_format($stats['revenue']), 'Confirmed this month (USD)'],
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

    <div class="adm-dash-grid">
      <div>
        <!-- Needs attention -->
        <div class="adm-card" style="margin-bottom:1.5rem">
          <div class="adm-card-header"><h2 class="adm-card-title">Needs attention</h2></div>
          <div class="adm-card-body">
            <?php if (!$todo): ?>
              <div class="adm-empty" style="padding:1.5rem"><i class="fas fa-circle-check" style="color:#4ade80"></i><strong>You are all caught up</strong>No bookings, messages or content are waiting.</div>
            <?php else: ?>
            <div class="adm-check">
              <?php foreach ($todo as $t): ?>
              <a class="adm-check__item" href="<?= $t[4] ?>">
                <span class="adm-check__dot" style="border-color:rgba(251,191,36,.5);color:#fbbf24"><i class="fas fa-exclamation"></i></span>
                <span class="adm-check__t"><strong style="color:#fff"><?= (int)$t[1] ?> <?= e($t[1] === 1 ? $t[2] : $t[3]) ?></strong><br><span style="font-size:.78rem;color:var(--text-dim)"><?= e($t[5]) ?></span></span>
                <i class="fas fa-chevron-right adm-check__go"></i>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent bookings -->
        <div class="adm-card">
          <div class="adm-card-header">
            <h2 class="adm-card-title">Recent bookings</h2>
            <a href="bookings.php" class="btn btn--outline btn--sm">View all</a>
          </div>
          <div class="adm-table-wrap">
            <table class="adm-table">
              <thead><tr><th>#</th><th>Client</th><th>Tour</th><th>Travel date</th><th>Pax</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($recentBookings)): ?>
                <tr><td colspan="7"><div class="adm-empty"><i class="fas fa-calendar-xmark"></i><strong>No bookings yet</strong>Requests from the website booking form will appear here.</div></td></tr>
                <?php else: foreach ($recentBookings as $b): ?>
                <tr>
                  <td style="font-size:.72rem">#<?= e($b['id']) ?></td>
                  <td>
                    <div style="font-weight:600;color:#fff;font-size:.82rem"><?= e($b['first_name'].' '.$b['last_name']) ?></div>
                    <div style="font-size:.7rem;color:var(--text-dim)"><?= e($b['country']) ?></div>
                  </td>
                  <td style="max-width:180px;font-size:.8rem"><?= e(truncate($b['tour_name'],28)) ?></td>
                  <td style="font-size:.8rem"><?= e(formatDate($b['travel_date'])) ?></td>
                  <td style="text-align:center;font-weight:600;color:#fff"><?= e($b['travelers']) ?></td>
                  <td><span class="badge badge--<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
                  <td><a href="bookings.php" class="btn btn--outline btn--sm">Open</a></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        <!-- Setup checklist -->
        <div class="adm-card" style="margin-bottom:1.5rem">
          <div class="adm-card-header">
            <h2 class="adm-card-title">Setup checklist</h2>
            <span class="adm-counter <?= $setupDone === count($setup) ? 'is-ok' : '' ?>"><?= $setupDone ?>/<?= count($setup) ?> done</span>
          </div>
          <div class="adm-card-body">
            <div class="adm-check">
              <?php foreach ($setup as $s): ?>
              <a class="adm-check__item <?= $s[1] ? 'is-done' : '' ?>" href="<?= $s[2] ?>">
                <span class="adm-check__dot"><i class="fas fa-check"></i></span>
                <span class="adm-check__t"><?= e($s[0]) ?></span>
                <?php if (!$s[1]): ?><i class="fas fa-chevron-right adm-check__go"></i><?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="adm-card">
          <div class="adm-card-header"><h2 class="adm-card-title">Quick actions</h2></div>
          <div class="adm-card-body">
            <div class="adm-check">
              <?php foreach ([
                ['tours.php?edit=0',      'fa-compass',   'Add a new tour'],
                ['blog.php?edit=0',        'fa-pen-nib',   'Write a blog post'],
                ['testimonials.php',      'fa-star',      'Add a guest review'],
                ['gallery.php',           'fa-images',    'Upload gallery photos'],
              ] as $q): ?>
              <a class="adm-check__item" href="<?= $q[0] ?>"><span class="adm-check__dot" style="border:0;background:rgba(193,122,58,.16);color:#e0a46a"><i class="fas <?= $q[1] ?>"></i></span><span class="adm-check__t"><?= e($q[2]) ?></span><i class="fas fa-chevron-right adm-check__go"></i></a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>
</body>
</html>
