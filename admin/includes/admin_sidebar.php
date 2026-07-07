<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

/* Unread counts for badges */
try {
    $db2 = getDB();
    $unreadMsgs    = (int)$db2->query("SELECT COUNT(*) FROM contacts WHERE status='unread'")->fetchColumn();
    $pendingBooks  = (int)$db2->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
} catch (\Throwable $e) { $unreadMsgs = 0; $pendingBooks = 0; }
?>
<style>
/* Sidebar font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@500;600;700&family=Nanum+Myeongjo:wght@700&display=swap');
.adm-sidebar *{box-sizing:border-box}
</style>

<!-- Mobile top bar (always rendered, shown via CSS on mobile) -->
<div id="adm-topbar" style="display:none;position:fixed;top:0;left:0;right:0;height:56px;background:#111827;border-bottom:1px solid rgba(255,255,255,.08);z-index:210;align-items:center;padding:0 1rem;gap:.75rem">
  <button id="adm-ham" onclick="admOpenSidebar()" aria-label="Open menu"
          style="width:38px;height:38px;border-radius:9px;background:#10b981;border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;box-shadow:0 3px 12px rgba(16,185,129,.35);flex-shrink:0">
    <i class="fas fa-bars"></i>
  </button>
  <span style="font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:.95rem">
    Jambo <span style="background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Masai</span> Admin
  </span>
</div>

<!-- Overlay -->
<div id="adm-overlay" onclick="admCloseSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:205;opacity:0;transition:opacity .3s"></div>

<!-- Sidebar -->
<aside id="adm-sidebar" class="admin-sidebar" style="background:#111827;border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:208;transition:transform .35s cubic-bezier(.4,0,.2,1);overflow-y:auto;overflow-x:hidden">

  <!-- Brand -->
  <div style="padding:1.1rem 1.1rem .9rem;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between">
    <a href="<?= SITE_URL ?>/admin/index.php" style="display:flex;align-items:center;gap:.65rem;text-decoration:none">
      <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas fa-tree" style="color:#fff;font-size:.85rem"></i>
      </div>
      <div>
        <div style="font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:.92rem;line-height:1.2">Jambo <span style="background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Masai</span></div>
        <div style="font-family:'Montserrat',sans-serif;font-size:.52rem;color:#10b981;letter-spacing:.15em;text-transform:uppercase">Admin Panel</div>
      </div>
    </a>
    <button onclick="admCloseSidebar()" id="adm-close-btn" style="width:30px;height:30px;border-radius:7px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:.78rem">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- Site link -->
  <div style="padding:.6rem .75rem;border-bottom:1px solid rgba(255,255,255,.06)">
    <a href="<?= SITE_URL ?>" target="_blank" style="display:flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;color:rgba(255,255,255,.3);text-decoration:none;padding:.4rem .5rem;border-radius:7px;transition:all .2s" onmouseover="this.style.background='rgba(16,185,129,.08)';this.style.color='#10b981'" onmouseout="this.style.background='';this.style.color='rgba(255,255,255,.3)'">
      <i class="fas fa-external-link-alt" style="font-size:.6rem"></i> View Website
    </a>
  </div>

  <!-- Navigation -->
  <nav style="flex:1;padding:.6rem .75rem">

    <!-- Main section -->
    <div style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:.5rem .6rem .3rem;margin-top:.25rem">Main</div>

    <?php
    $mainNav = [
      ['index.php',   'fa-tachometer-alt', 'Dashboard',   $pendingBooks > 0 ? $pendingBooks : 0, 'amber'],
      ['bookings.php','fa-calendar-check', 'Bookings',    $pendingBooks, 'amber'],
      ['contacts.php','fa-envelope',       'Messages',    $unreadMsgs, 'amber'],
    ];
    foreach ($mainNav as $n):
      $isActive = $currentFile === $n[0];
    ?>
    <a href="<?= $n[0] ?>" class="admin-nav__item <?= $isActive ? 'is-active' : '' ?>">
      <i class="fas <?= $n[1] ?>" style="width:18px;text-align:center;font-size:.82rem;flex-shrink:0;color:<?= $isActive ? '#10b981' : 'rgba(255,255,255,.35)' ?>"></i>
      <?= $n[2] ?>
      <?php if ($n[3] > 0): ?>
      <span style="margin-left:auto;background:rgba(245,158,11,.15);color:#fbbf24;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;padding:.12rem .4rem;border-radius:999px"><?= $n[3] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>

    <!-- Content section -->
    <div style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:.5rem .6rem .3rem;margin-top:.5rem">Content</div>

    <?php
    $contentNav = [
      ['tours.php',             'fa-compass',       'Safari Tours'],
      ['destinations-manager.php','fa-map-marker-alt','Dest. Pages'],
      ['gallery.php',           'fa-images',        'Gallery'],
      ['blog.php',              'fa-newspaper',     'Blog Posts'],
    ];
    foreach ($contentNav as $n):
      $isActive = $currentFile === $n[0];
    ?>
    <a href="<?= $n[0] ?>" class="admin-nav__item <?= $isActive ? 'is-active' : '' ?>">
      <i class="fas <?= $n[1] ?>" style="width:18px;text-align:center;font-size:.82rem;flex-shrink:0;color:<?= $isActive ? '#10b981' : 'rgba(255,255,255,.35)' ?>"></i>
      <?= $n[2] ?>
    </a>
    <?php endforeach; ?>

    <!-- Tours sub-items -->
    <?php $toursActive = in_array($currentFile, ['tours.php','tour-itinerary.php','tour-photos.php']); ?>
    <div style="margin-left:1rem;padding-left:.65rem;border-left:1px solid rgba(16,185,129,.15)">
      <?php
      /* Migration shortcut: active when tours.php is filtered by the migration type */
      $isMig = ($currentFile === 'tours.php' && ($_GET['type'] ?? '') === 'Great Migration Safari');
      ?>
      <a href="tours.php?type=Great+Migration+Safari"
         class="admin-nav__item <?= $isMig ? 'is-active' : '' ?>" style="font-size:.76rem;padding:.5rem .65rem">
        <i class="fas fa-horse" style="width:16px;text-align:center;font-size:.72rem;color:<?= $isMig ? '#10b981' : 'rgba(255,255,255,.25)' ?>"></i>
        Migration Pkgs
        <span style="font-size:.55rem;color:rgba(255,255,255,.2);margin-left:.3rem">↳</span>
      </a>
      <?php foreach ([
        ['tour-itinerary.php','fa-route',  'Itinerary Editor'],
        ['tour-photos.php',  'fa-images',  'Tour Photos'],
      ] as $sub): $isSub = $currentFile === $sub[0]; ?>
      <a href="<?= $sub[0] ?><?= $isSub ? '' : '?tour_id='.($_GET['tour_id']??1) ?>"
         class="admin-nav__item <?= $isSub ? 'is-active' : '' ?>" style="font-size:.76rem;padding:.5rem .65rem">
        <i class="fas <?= $sub[1] ?>" style="width:16px;text-align:center;font-size:.72rem;color:<?= $isSub ? '#10b981' : 'rgba(255,255,255,.25)' ?>"></i>
        <?= $sub[2] ?>
        <span style="font-size:.55rem;color:rgba(255,255,255,.2);margin-left:.3rem">↳</span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Homepage / Media section -->
    <div style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:.5rem .6rem .3rem;margin-top:.5rem">Homepage Media</div>

    <?php
    $homeNav = [
      ['hero-slides.php',  'fa-film',       'Hero Slides'],
      ['hero-settings.php','fa-video',       'Hero Video'],
      ['page-intros.php',  'fa-image',       'Page Intros'],
    ];
    foreach ($homeNav as $n): $isActive = $currentFile === $n[0]; ?>
    <a href="<?= $n[0] ?>" class="admin-nav__item <?= $isActive ? 'is-active' : '' ?>">
      <i class="fas <?= $n[1] ?>" style="width:18px;text-align:center;font-size:.82rem;flex-shrink:0;color:<?= $isActive ? '#10b981' : 'rgba(255,255,255,.35)' ?>"></i>
      <?= $n[2] ?>
    </a>
    <?php endforeach; ?>

    <!-- System section -->
    <div style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:.5rem .6rem .3rem;margin-top:.5rem">System</div>

    <?php $isSettings = $currentFile === 'settings.php'; ?>
    <a href="settings.php" class="admin-nav__item <?= $isSettings ? 'is-active' : '' ?>">
      <i class="fas fa-cog" style="width:18px;text-align:center;font-size:.82rem;flex-shrink:0;color:<?= $isSettings ? '#10b981' : 'rgba(255,255,255,.35)' ?>"></i>
      Settings
    </a>

  </nav>

  <!-- Footer -->
  <div style="padding:.85rem 1.1rem;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between">
    <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.3)"><?= e($_SESSION['admin_user'] ?? 'Admin') ?></span>
    <a href="logout.php" style="display:flex;align-items:center;gap:.35rem;font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(239,68,68,.6);text-decoration:none;transition:color .2s" onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(239,68,68,.6)'">
      <i class="fas fa-power-off" style="font-size:.65rem"></i> Logout
    </a>
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

  /* ── Open sidebar ── */
  window.admOpenSidebar = function() {
    if (!sidebar) return;
    sidebar.style.transform = 'translateX(0)';
    if (overlay) { overlay.style.display = 'block'; setTimeout(function(){ overlay.style.opacity='1'; }, 10); }
    if (closeBtn) closeBtn.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    _open = true;
  };

  /* ── Close sidebar ── */
  window.admCloseSidebar = function() {
    if (!sidebar) return;
    sidebar.style.transform = 'translateX(-100%)';
    if (overlay) { overlay.style.opacity = '0'; setTimeout(function(){ overlay.style.display='none'; }, 300); }
    if (closeBtn) closeBtn.style.display = 'none';
    document.body.style.overflow = '';
    _open = false;
  };

  /* ── Responsive handler ── */
  function applyLayout() {
    var isMob = window.innerWidth < 1024;

    /* Top bar: show on mobile, hide on desktop */
    if (topbar) topbar.style.display = isMob ? 'flex' : 'none';

    /* Sidebar: always visible desktop, hidden mobile unless open */
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

    /* Main content: margin on desktop, padding-top on mobile */
    if (main) {
      main.style.marginLeft = isMob ? '0' : '260px';
      main.style.paddingTop = isMob ? '60px' : '';
    }
  }

  /* Run on load and resize */
  applyLayout();
  window.addEventListener('resize', applyLayout, { passive: true });

  /* Escape key */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && _open) window.admCloseSidebar();
  });
})();
</script>
