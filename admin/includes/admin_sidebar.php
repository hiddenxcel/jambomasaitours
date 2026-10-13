<?php
require_once __DIR__ . '/ui.php';
$currentFile = basename($_SERVER['PHP_SELF']);

/* Idadi za arifa */
try {
    $db2 = getDB();
    $unreadMsgs   = (int)$db2->query("SELECT COUNT(*) FROM contacts WHERE status='unread'")->fetchColumn();
    $pendingBooks = (int)$db2->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
} catch (\Throwable $e) { $unreadMsgs = 0; $pendingBooks = 0; }

/* Muundo wa menyu: [faili, ikoni, jina, idadi ya arifa] */
$_nav = [
  'Overview' => [
    ['index.php',    'fa-gauge-high',     'Dashboard', 0],
    ['bookings.php', 'fa-calendar-check', 'Bookings',  $pendingBooks],
    ['contacts.php', 'fa-envelope',       'Messages',  $unreadMsgs],
  ],
  'Content' => [
    ['tours.php',                'fa-compass',       'Safari Tours', 0],
    ['blog.php',                 'fa-newspaper',     'Blog Posts', 0],
    ['testimonials.php',         'fa-star',          'Guest Reviews', 0],
    ['destinations-manager.php', 'fa-map-location-dot', 'Destination Pages', 0],
    ['gallery.php',              'fa-images',        'Gallery', 0],
    ['image-library.php',        'fa-photo-film',    'Image Library', 0],
  ],
  'Homepage' => [
    ['hero-slides.php',   'fa-film',  'Hero Slides', 0],
    ['hero-settings.php', 'fa-video', 'Hero Video', 0],
    ['page-intros.php',   'fa-image', 'Page Intros', 0],
  ],
  'System' => [
    ['settings.php', 'fa-gear',        'Settings', 0],
    ['help.php',     'fa-circle-question', 'Help & Guide', 0],
  ],
];
$_tourSubs = ['tour-itinerary.php' => ['fa-route', 'Itinerary Editor'], 'tour-photos.php' => ['fa-images', 'Tour Photos']];
$_inTours  = in_array($currentFile, ['tours.php', 'tour-itinerary.php', 'tour-photos.php'], true);
?>
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin-v2.css?v=<?= @filemtime(__DIR__ . '/../assets/admin-v2.css') ?>">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
.adm-side *{box-sizing:border-box}
</style>

<!-- Mobile top bar -->
<div id="adm-topbar" style="display:none;position:fixed;top:0;left:0;right:0;height:56px;z-index:210;align-items:center;padding:0 1rem;gap:.75rem">
  <button id="adm-ham" onclick="admOpenSidebar()" aria-label="Open menu"
          style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#a05e22,#c17a3a);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0">
    <i class="fas fa-bars"></i>
  </button>
  <span style="font-weight:700;color:#fff;font-size:.95rem">Jambo Masai <span style="color:#e0a46a">Admin</span></span>
</div>

<div id="adm-overlay" onclick="admCloseSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:205;opacity:0;transition:opacity .3s"></div>

<aside id="adm-sidebar" class="admin-sidebar adm-side" aria-label="Admin navigation">
  <div class="adm-side__brand">
    <a href="<?= SITE_URL ?>/admin/index.php" class="adm-side__logo">
      <span class="adm-side__mark"><i class="fas fa-mountain-sun"></i></span>
      <span class="adm-side__name">Jambo Masai<small>Admin</small></span>
    </a>
    <button onclick="admCloseSidebar()" id="adm-close-btn" aria-label="Close menu" style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.5);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:.78rem">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <a class="adm-side__site" href="<?= SITE_URL ?>" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View website</a>

  <nav class="adm-side__nav">
    <?php foreach ($_nav as $label => $items): ?>
      <div class="adm-side__label"><?= e($label) ?></div>
      <?php foreach ($items as $n):
        $active = $currentFile === $n[0] || ($n[0] === 'tours.php' && $_inTours && ($currentFile === 'tours.php')); ?>
        <a href="<?= $n[0] ?>" class="adm-side__item <?= $active ? 'is-active' : '' ?>">
          <i class="fas <?= $n[1] ?>"></i><?= e($n[2]) ?>
          <?php if ($n[3] > 0): ?><span class="adm-side__badge"><?= (int)$n[3] ?></span><?php endif; ?>
        </a>
        <?php if ($n[0] === 'tours.php'): ?>
        <div class="adm-side__sub">
          <?php foreach ($_tourSubs as $file => $meta): $isSub = $currentFile === $file; ?>
            <a href="<?= $file ?><?= $isSub ? '' : '?tour_id=' . (int)($_GET['tour_id'] ?? 1) ?>" class="adm-side__item <?= $isSub ? 'is-active' : '' ?>">
              <i class="fas <?= $meta[0] ?>"></i><?= e($meta[1]) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="adm-side__foot">
    <span class="adm-side__user"><i class="fas fa-circle-user"></i><?= e($_SESSION['admin_user'] ?? 'Admin') ?></span>
    <a href="logout.php" class="adm-side__logout"><i class="fas fa-right-from-bracket"></i> Log out</a>
  </div>
</aside>

<script>
(function(){
  var _open = false;
  var sidebar = document.getElementById('adm-sidebar');
  var overlay = document.getElementById('adm-overlay');
  var topbar  = document.getElementById('adm-topbar');
  var closeBtn= document.getElementById('adm-close-btn');
  var main    = document.querySelector('.admin-main');

  window.admOpenSidebar = function() {
    if (!sidebar) return;
    sidebar.style.transform = 'translateX(0)';
    if (overlay) { overlay.style.display = 'block'; setTimeout(function(){ overlay.style.opacity='1'; }, 10); }
    if (closeBtn) closeBtn.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    _open = true;
  };
  window.admCloseSidebar = function() {
    if (!sidebar) return;
    sidebar.style.transform = 'translateX(-100%)';
    if (overlay) { overlay.style.opacity = '0'; setTimeout(function(){ overlay.style.display='none'; }, 300); }
    if (closeBtn) closeBtn.style.display = 'none';
    document.body.style.overflow = '';
    _open = false;
  };
  function applyLayout() {
    var isMob = window.innerWidth < 1024;
    if (topbar) topbar.style.display = isMob ? 'flex' : 'none';
    if (sidebar) {
      if (!isMob) {
        sidebar.style.transform = 'translateX(0)';
        if (overlay) { overlay.style.display = 'none'; overlay.style.opacity = '0'; }
        document.body.style.overflow = '';
        _open = false;
        if (closeBtn) closeBtn.style.display = 'none';
      } else if (!_open) {
        sidebar.style.transform = 'translateX(-100%)';
      }
    }
    if (main) {
      main.style.marginLeft = isMob ? '0' : '260px';
      main.style.paddingTop = isMob ? '60px' : '';
    }
  }
  applyLayout();
  window.addEventListener('resize', applyLayout, { passive: true });
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && _open) window.admCloseSidebar(); });
})();
</script>
