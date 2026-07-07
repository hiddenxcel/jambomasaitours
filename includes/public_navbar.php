<?php
/**
 * UNIFIED PUBLIC NAVBAR - included by ALL public pages
 * Requires: $currentPage (string), $logoUrl, $logoWidth, $siteName, $siteTagline
 * Optional: $idxMegaTours / $_megaTours for mega menu
 */

/* Ensure settings loaded */
if (!isset($logoUrl))    { $logoUrl    = getSetting('logo_url'); }
if (!isset($logoWidth))  { $logoWidth  = (int)(getSetting('logo_width','160') ?: 160); }
if (!isset($siteName))   { $siteName   = getSetting('site_name','Jambo Masai Tours'); }
if (!isset($siteTagline)){ $siteTagline= getSetting('site_tagline','Tanzania Safari Experts'); }

/* Canonical navItems - identical on every page */
$_pNavItems = [
    'home'         => ['url'=>url(),                     'desk'=>'Home',        'mob'=>'Home',             'icon'=>'fa-home'],
    'tours'        => ['url'=>url('tours'),              'desk'=>'Safaris',     'mob'=>'Safari Tours',     'icon'=>'fa-compass',       'mega'=>'safaris'],
    'migration'    => ['url'=>url('migration'),          'desk'=>'Migration',   'mob'=>'Great Migration', 'icon'=>'fa-horse'],
    'trekking'     => ['url'=>url('mountain-trekking'),  'desk'=>'Trekking',    'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
    'destinations' => ['url'=>url('destinations'),       'desk'=>'Destinations','mob'=>'Destinations',     'icon'=>'fa-map-marker-alt'],
    'blog'         => ['url'=>url('blog'),               'desk'=>'Blog',        'mob'=>'Blog',             'icon'=>'fa-newspaper'],
    'contact'      => ['url'=>url('contact'),            'desk'=>'Contact',     'mob'=>'Contact',          'icon'=>'fa-envelope'],
    'more'         => ['url'=>'#',                       'desk'=>'More',        'mob'=>'More',             'icon'=>'fa-ellipsis-h',    'mega'=>'more'],
];

/* Featured tours for mega menu */
if (!isset($idxMegaTours) && !isset($_megaTours)) {
    try {
        $idxMegaTours = getDB()->query("SELECT name,slug,price,destination FROM tours WHERE featured=1 ORDER BY rating DESC LIMIT 4")->fetchAll();
    } catch (\Throwable $e) { $idxMegaTours = []; }
} else {
    $idxMegaTours = $idxMegaTours ?? $_megaTours ?? [];
}

$_activePage = $currentPage ?? '';

/* Short name parts for logo text */
$_nameParts  = explode(' ', $siteName);
$_name1      = $_nameParts[0] ?? 'Jambo';
$_name2      = $_nameParts[1] ?? 'Masai';
?>
<?php require_once __DIR__ . '/preloader.php'; ?>

<!-- --- UNIFIED NAVBAR --- -->
<style>
/* -- Navbar shared styles (loaded once) -- */
#p-nav{position:fixed;top:0;left:0;right:0;z-index:1000;transition:background .4s,box-shadow .4s,border-color .4s;background:linear-gradient(to bottom,rgba(0,0,0,.55) 0%,transparent 100%)}
/* Solid navbar after scroll → FLOATING CREAM-GLASS PILL */
#p-nav{padding:0 1rem}
#p-nav .pnav-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:66px;
  border-radius:0;padding:0;background:transparent;transition:max-width .4s cubic-bezier(.22,1,.36,1),background .4s,border-radius .4s,box-shadow .4s,padding .4s,border-color .4s;border:1px solid transparent}
#p-nav.solid{background:transparent;backdrop-filter:none;-webkit-backdrop-filter:none;border-bottom:none;box-shadow:none;padding-top:.7rem;padding-bottom:.7rem}
#p-nav.solid .pnav-inner{
  max-width:1080px;height:58px;padding:0 1.4rem;border-radius:999px;
  background:rgba(244,225,195,.78);backdrop-filter:blur(20px) saturate(1.3);-webkit-backdrop-filter:blur(20px) saturate(1.3);
  border:1px solid rgba(255,255,255,.4);box-shadow:0 10px 35px rgba(44,70,61,.18),inset 0 1px 0 rgba(255,255,255,.5)}
/* Logo swap: full logo at top, compact favicon + name when scrolled (pill) */
#p-nav.solid #p-logo-full{display:none !important}
#p-nav.solid #p-logo-icon{display:block !important}
#p-nav.solid #p-logo-text{display:inline-block !important}
/* When solid: shrink link height to fit the pill */
#p-nav.solid .desk-nav a{height:58px !important}
/* When solid: flip nav text/icons to dark for readability on white */
#p-nav.solid .desk-nav a{color:#1f2937 !important}
#p-nav.solid .desk-nav a i.fa-chevron-down{color:rgba(16,68,45,.4) !important}
#p-nav.solid .desk-nav li > a:hover{color:#7d4817 !important}
/* active link stays green (already #a05e22 inline) - keep it */
#p-nav.solid .pnav-active > a{color:#7d4817 !important}
/* Search + hamburger buttons on white */
#p-nav.solid #pnav-search-btn,#p-nav.solid #p-mob-toggle{background:rgba(16,68,45,.06) !important;border-color:rgba(16,68,45,.12) !important}
#p-nav.solid #pnav-search-btn i{color:#374151 !important}
#p-nav.solid #p-mob-toggle span{background:#1f2937 !important}
/* Search button hover (works in both dark & solid states) */
#pnav-search-btn:hover{background:rgba(160,94,34,.12) !important;border-color:rgba(160,94,34,.3) !important}
#pnav-search-btn:hover i{color:#a05e22 !important}
/* Mega menu - enhanced dropdown with stagger & glassmorphism */
.pnav-mega{position:relative}
.pnav-mega-drop{position:absolute;top:100%;left:50%;transform:translateX(-50%) translateY(12px);width:540px;opacity:0;pointer-events:none;transition:all .35s cubic-bezier(.22,1,.36,1);padding-top:.5rem;z-index:200}
.pnav-mega:hover .pnav-mega-drop{opacity:1;pointer-events:all;transform:translateX(-50%) translateY(0)}
.pnav-mega:hover .pnav-mega-chevron{transform:rotate(180deg) !important;color:#a05e22 !important}
/* More mega - aligns to the right edge */
.pnav-mega-drop[style*="right:0"]{left:auto;transform:none;top:calc(100% + 2px)}
.pnav-mega:hover .pnav-mega-drop[style*="right:0"]{opacity:1;pointer-events:all;transform:none}
/* Solid cream mega card */
.pnav-mega-card{background:#f4e1c3;border:1px solid rgba(44,70,61,.12);border-radius:20px;padding:1.25rem;box-shadow:0 32px 80px rgba(44,70,61,.28);overflow:hidden;transition:border-color .4s,box-shadow .4s}
.pnav-mega:hover .pnav-mega-card{border-color:rgba(160,94,34,.3);box-shadow:0 32px 80px rgba(44,70,61,.3),0 0 60px rgba(160,94,34,.06),inset 0 1px 0 rgba(255,255,255,.5)}
.pnav-mega-card::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 30% 20%,rgba(160,94,34,.05) 0%,transparent 60%);pointer-events:none}
/* Dark text inside the cream mega card (override inline whites) */
.pnav-mega-card [style*="rgba(255,255,255"]{color:#2c463d !important}
.pnav-mega-card .pnav-m-name{color:#233a32 !important}
.pnav-mega-card p[style*="#a05e22"]{color:#7d4817 !important}
.pnav-mega-card .pnav-m-item:hover{background:rgba(160,94,34,.12) !important}
.pnav-mega-card .pnav-m-item:hover .pnav-m-name{color:#7d4817 !important}
/* icon chips inside mega → cream-tinted */
.pnav-mega-card .pnav-m-item > div[style*="rgba(255,255,255,.05)"]{background:rgba(44,70,61,.08) !important}
.pnav-mega-card .pnav-m-item > div[style*="rgba(255,255,255,.05)"] i{color:#7d4817 !important}
.pnav-mega-card div[style*="border-top"]{border-color:rgba(44,70,61,.15) !important}
/* "More" mega: brand title gradient → dark, header strip subtle on cream */
.pnav-mega-card div[style*="-webkit-background-clip:text"]{background:linear-gradient(135deg,#2c463d,#a05e22) !important;-webkit-background-clip:text !important;background-clip:text !important}
.pnav-mega-card div[style*="rgba(160,94,34,.12)"][style*="border-bottom"]{border-color:rgba(44,70,61,.12) !important}
.pnav-mega-card div[style*="background:rgba(255,255,255,.02)"]{background:rgba(44,70,61,.05) !important}
.pnav-mega-viewall{transition:all .25s cubic-bezier(.22,1,.36,1);opacity:0;transform:translateY(4px)}
.pnav-mega:hover .pnav-mega-viewall{opacity:1;transform:translateY(0);transition-delay:160ms}
.pnav-mega-viewall:hover{gap:.6rem!important}
.pnav-mega-viewall:hover .pnav-viewall-link{color:#c17a3a!important}
.pnav-mega-viewall:hover .pnav-viewall-link i{transform:translateX(3px)}
.pnav-mega-wa-link{transition:color .25s!important}
.pnav-mega-wa-link:hover{color:#25D366!important}
/* Staggered entrance animation for mega menu items */
.pnav-mega:hover .pnav-m-item{opacity:1;transform:translateY(0)}
.pnav-m-item{display:flex;align-items:center;gap:.7rem;padding:.58rem .72rem;border-radius:11px;text-decoration:none;transition:all .25s cubic-bezier(.22,1,.36,1);opacity:0;transform:translateY(6px)}
.pnav-m-item:nth-child(1){transition-delay:20ms}
.pnav-m-item:nth-child(2){transition-delay:50ms}
.pnav-m-item:nth-child(3){transition-delay:80ms}
.pnav-m-item:nth-child(4){transition-delay:110ms}
.pnav-m-item:hover{background:rgba(160,94,34,.1);transform:translateX(3px) !important}
.pnav-m-item:hover .pnav-m-name{color:#c17a3a}
.pnav-m-item:hover .pnav-m-icon-wrap{background:rgba(160,94,34,.18) !important;border-color:rgba(160,94,34,.3) !important;transform:scale(1.05)}
.pnav-m-icon-wrap{transition:all .25s cubic-bezier(.22,1,.36,1)}
/* Search overlay */
#pnav-search{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.85);backdrop-filter:blur(16px);display:flex;align-items:flex-start;justify-content:center;padding-top:100px;opacity:0;pointer-events:none;transition:opacity .3s}
#pnav-search.open{opacity:1;pointer-events:all}
#pnav-s-wrap{width:100%;max-width:600px;padding:0 1rem;transform:translateY(-16px);transition:transform .35s cubic-bezier(.16,1,.3,1)}
#pnav-search.open #pnav-s-wrap{transform:translateY(0)}
#pnav-s-input{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:1rem 1rem 1rem 3.25rem;font-family:'Inter',sans-serif;font-size:1.05rem;color:#fff;outline:none;transition:border-color .2s;box-sizing:border-box}
#pnav-s-input:focus{border-color:rgba(160,94,34,.5)}
#pnav-s-input::placeholder{color:rgba(255,255,255,.3)}
/* Mobile drawer with enhanced entrance */
#p-mob-menu{position:fixed;top:0;right:0;bottom:0;width:min(320px,88vw);background:linear-gradient(180deg,#23362f,#1c2c26);z-index:1001;transform:translateX(100%);transition:transform .42s cubic-bezier(.22,1,.36,1);border-left:1px solid rgba(160,94,34,.18);overflow-y:auto;box-shadow:-8px 0 40px rgba(0,0,0,.5)}
#p-mob-menu.open{transform:translateX(0)}
/* Staggered link entrance on drawer open */
#p-mob-menu.open .pmob-link{opacity:1;transform:translateX(0)}
.pmob-link{opacity:0;transform:translateX(12px);transition:opacity .35s cubic-bezier(.22,1,.36,1),transform .35s cubic-bezier(.22,1,.36,1),background .2s,color .2s}
#p-mob-menu.open .pmob-link:nth-child(1){transition-delay:50ms}
#p-mob-menu.open .pmob-link:nth-child(2){transition-delay:80ms}
#p-mob-menu.open .pmob-link:nth-child(3){transition-delay:110ms}
#p-mob-menu.open .pmob-link:nth-child(4){transition-delay:140ms}
#p-mob-menu.open .pmob-link:nth-child(5){transition-delay:170ms}
#p-mob-menu.open .pmob-link:nth-child(6){transition-delay:200ms}
#p-mob-menu.open .mob-search-wrap{opacity:1;transform:translateX(0)}
#p-mob-menu.open .mob-more-section{opacity:1;transform:translateY(0)}
#p-mob-menu.open .mob-footer-section{opacity:1;transform:translateY(0)}
.mob-search-wrap{opacity:0;transform:translateX(12px);transition:opacity .35s cubic-bezier(.22,1,.36,1),transform .35s cubic-bezier(.22,1,.36,1);transition-delay:40ms}
.mob-more-section{opacity:0;transform:translateY(8px);transition:opacity .35s cubic-bezier(.22,1,.36,1),transform .35s cubic-bezier(.22,1,.36,1);transition-delay:230ms}
.mob-footer-section{opacity:0;transform:translateY(8px);transition:opacity .35s cubic-bezier(.22,1,.36,1),transform .35s cubic-bezier(.22,1,.36,1);transition-delay:300ms}
#p-mob-bd{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);display:none;opacity:0;transition:opacity .35s ease}
#p-mob-bd.show{display:block;opacity:1}
.pmob-link{display:flex;align-items:center;justify-content:space-between;padding:.82rem 1rem;border-radius:12px;font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:500;text-decoration:none;transition:background .2s,color .2s;color:rgba(255,255,255,.7);margin-bottom:.18rem}
.pmob-link:hover{background:rgba(160,94,34,.12);color:#fff}
.pmob-link.act{background:rgba(160,94,34,.1);color:#a05e22}
</style>

<nav id="p-nav">
  <div class="pnav-inner">

    <!-- Logo (full logo when at top, compact favicon when scrolled/pill) -->
    <?php $_pFav = getSetting('favicon_url') ?: (SITE_URL . '/uploads/favicon-64.png'); ?>
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:.65rem;text-decoration:none;flex-shrink:0">
      <img id="p-logo-full" src="<?= e($logoUrl ?: (SITE_URL . '/uploads/logo-husika.png')) ?>" alt="<?= e($siteName) ?>"
           style="height:42px;width:auto;max-width:175px;object-fit:contain;display:block">
      <img id="p-logo-icon" src="<?= e($_pFav) ?>" alt="<?= e($siteName) ?>"
           style="height:42px;width:42px;object-fit:contain;display:none;border-radius:50%">
      <span id="p-logo-text" style="display:none;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.92rem;letter-spacing:.02em;color:#2c463d;white-space:nowrap;line-height:1"><?= e($siteName) ?></span>
    </a>

    <!-- Desktop links -->
    <ul style="display:none;align-items:center;list-style:none;margin:0;padding:0;flex:1;justify-content:center;gap:0" id="p-nav-links" class="desk-nav">
      <?php foreach ($_pNavItems as $key => $item):
        $isActive  = $_activePage === $key;
        $megaType  = $item['mega'] ?? false;
        $hasMega   = (bool)$megaType;
      ?>
      <li class="<?= $hasMega ? 'pnav-mega' : '' ?> <?= $isActive ? 'pnav-active' : '' ?>" style="position:relative">
        <a href="<?= e($item['url']) ?>"
           style="font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;padding:0 .7rem;height:66px;display:flex;align-items:center;gap:.28rem;text-decoration:none;transition:color .2s;position:relative;color:<?= $isActive?'#a05e22':'rgba(255,255,255,.6)' ?>">
          <?= e($item['desk']) ?>
          <?php if ($hasMega): ?><i class="fas fa-chevron-down pnav-mega-chevron" style="font-size:.45rem;color:rgba(255,255,255,.3);transition:transform .35s cubic-bezier(.22,1,.36,1),color .25s"></i><?php endif; ?>
          <span style="position:absolute;bottom:0;left:.7rem;right:.7rem;height:2px;border-radius:1px;background:#a05e22;transform:scaleX(<?= $isActive?'1':'0' ?>);transition:transform .25s;transform-origin:left"></span>
        </a>

        <?php if ($megaType === 'safaris'): ?>
        <!-- Safaris mega dropdown -->
        <div class="pnav-mega-drop">
          <div class="pnav-mega-card">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
              <div>
                <p style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#a05e22;margin:0 0 .65rem">
                  <i class="fas fa-fire" style="font-size:.48rem;margin-right:.3rem"></i>Featured Safaris
                </p>
                <?php if (!empty($idxMegaTours)): foreach($idxMegaTours as $mt): ?>
                <a href="<?= url('tour/'.e($mt['slug'])) ?>" class="pnav-m-item">
                  <div style="width:32px;height:32px;border-radius:8px;background:rgba(160,94,34,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-paw" style="color:#a05e22;font-size:.6rem"></i></div>
                  <div style="min-width:0">
                    <div class="pnav-m-name" style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.82);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($mt['name']) ?></div>
                    <div style="font-size:.62rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif"><?= e($mt['destination']) ?> · $<?= number_format((float)$mt['price']) ?></div>
                  </div>
                </a>
                <?php endforeach; else: ?>
                <?php foreach([
                  ['fa-paw',           'Wildlife Safari',  'tours?tour_type=Wildlife+Safari'],
                  ['fa-horse',         'Migration Safari', 'migration'],
                  ['fa-heart',         'Honeymoon',        'tours?tour_type=Honeymoon+Safari'],
                  ['fa-child',         'Family Safari',    'tours?tour_type=Family+Safari'],
                ] as $ct): ?>
                <a href="<?= url($ct[2]) ?>" class="pnav-m-item">
                  <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas <?= $ct[0] ?>" style="color:rgba(255,255,255,.4);font-size:.6rem"></i></div>
                  <div class="pnav-m-name" style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.65)"><?= $ct[1] ?></div>
                </a>
                <?php endforeach; endif; ?>
              </div>
              <div>
                <p style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#a05e22;margin:0 0 .65rem">
                  <i class="fas fa-compass" style="font-size:.48rem;margin-right:.3rem"></i>Browse by Type
                </p>
                <?php foreach([
                  ['fa-mountain',        'Trekking',       'mountain-trekking'],
                  ['fa-umbrella-beach',  'Beach Holiday',  'tours?tour_type=Beach+Holiday'],
                  ['fa-hot-air-balloon', 'Balloon Safari', 'tours?tour_type=Balloon+Safari'],
                  ['fa-camera',          'Photography',    'tours?tour_type=Photography+Safari'],
                ] as $ct): ?>
                <a href="<?= url($ct[2]) ?>" class="pnav-m-item">
                  <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas <?= $ct[0] ?>" style="color:rgba(255,255,255,.4);font-size:.6rem"></i></div>
                  <div class="pnav-m-name" style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.65)"><?= $ct[1] ?></div>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="pnav-mega-viewall" style="margin-top:.85rem;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between;align-items:center;transition:all .25s cubic-bezier(.22,1,.36,1)">
              <a href="<?= url('tours') ?>" style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:600;color:#a05e22;text-decoration:none;display:flex;align-items:center;gap:.35rem;transition:gap .25s,color .25s" class="pnav-viewall-link">View all safari tours <i class="fas fa-arrow-right" style="font-size:.5rem;transition:transform .25s"></i></a>
              <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener" class="pnav-mega-wa-link" style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.28);text-decoration:none;display:flex;align-items:center;gap:.3rem"><i class="fab fa-whatsapp" style="color:#25D366;font-size:.78rem"></i>WhatsApp</a>
            </div>
          </div>
        </div>
        <?php elseif ($megaType === 'more'): ?>
        <!-- More mega dropdown - Professional redesign -->
        <div class="pnav-mega-drop" style="width:320px;right:0;left:auto">
          <div class="pnav-mega-card" style="padding:0;overflow:hidden">

            <!-- Menu items -->
            <div style="padding:.5rem">
              <?php foreach ([
                ['fa-users',         'About Us',  'Our story, team & mission',    'about',  '#a05e22','Meet the team behind your safari'],
                ['fa-question-circle','FAQ',       'Safari questions answered',     'faq',    '#fbbf24','Visa, packing, best time & more'],
                ['fa-newspaper',     'Blog',      'Tips, guides & wildlife news',  'blog',   '#f87171','Expert safari advice & stories'],
                ['fa-envelope',      'Contact Us','Talk to our safari experts',    'contact','#60a5fa','We respond within 2 hours'],
              ] as $mi): ?>
              <a href="<?= url($mi[3]) ?>" style="display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:10px;text-decoration:none;transition:background .2s;margin-bottom:.15rem" class="more-item">
                <div class="more-icon-wrap" style="width:38px;height:38px;border-radius:10px;background:<?= $mi[4] ?>15;border:1px solid <?= $mi[4] ?>25;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .25s cubic-bezier(.22,1,.36,1)">
                  <i class="fas <?= $mi[0] ?>" style="color:<?= $mi[4] ?>;font-size:.68rem"></i>
                </div>
                <div style="min-width:0;flex:1">
                  <div style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:700;color:rgba(255,255,255,.85);margin-bottom:.12rem"><?= $mi[1] ?></div>
                  <div style="font-size:.6rem;color:rgba(255,255,255,.3);font-family:'Montserrat',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $mi[5] ?></div>
                </div>
                <i class="fas fa-chevron-right" style="font-size:.5rem;color:rgba(255,255,255,.15);flex-shrink:0;transition:all .2s" class="more-arrow"></i>
              </a>
              <?php endforeach; ?>
            </div>

            <!-- Bottom CTA with subtle glow -->
            <div style="padding:.75rem 1rem;border-top:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.02);transition:all .3s" class="more-wa-wrap">
              <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
                 style="display:flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:600;color:rgba(255,255,255,.4);text-decoration:none;transition:color .25s"
                 onmouseover="this.style.color='#25D366'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                <i class="fab fa-whatsapp" style="color:#25D366;font-size:.8rem;filter:drop-shadow(0 0 4px rgba(37,211,102,.3))"></i>
                Chat on WhatsApp — fast response
                <span style="margin-left:auto;font-size:.55rem;background:rgba(37,211,102,.1);color:#25D366;padding:.15rem .5rem;border-radius:999px;border:1px solid rgba(37,211,102,.2);transition:all .25s" class="wa-badge">Online</span>
              </a>
            </div>
          </div>
        </div>
        <style>
        .more-item{opacity:0;transform:translateY(6px);transition:all .25s cubic-bezier(.22,1,.36,1)}
        .pnav-mega:hover .more-item{opacity:1;transform:translateY(0)}
        .more-item:nth-child(1){transition-delay:20ms}
        .more-item:nth-child(2){transition-delay:50ms}
        .more-item:nth-child(3){transition-delay:80ms}
        .more-item:nth-child(4){transition-delay:110ms}
        .more-item:hover{background:rgba(255,255,255,.08)!important}
        .more-wa-wrap{opacity:0;transform:translateY(6px);transition:all .3s cubic-bezier(.22,1,.36,1);transition-delay:160ms}
        .pnav-mega:hover .more-wa-wrap{opacity:1;transform:translateY(0)}
        .more-wa-wrap:hover{background:rgba(37,211,102,.06)!important}
        .more-wa-wrap:hover .wa-badge{background:rgba(37,211,102,.18)!important;border-color:rgba(37,211,102,.35)!important}
        .more-item:hover .more-arrow{color:rgba(255,255,255,.45)!important;transform:translateX(2px)}
        .more-item:hover .more-icon-wrap{background:rgba(160,94,34,.18)!important;border-color:rgba(160,94,34,.3)!important;transform:scale(1.05)}
        </style>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>

    <!-- Right actions -->
    <div style="display:flex;align-items:center;gap:.45rem;flex-shrink:0">
      <!-- Search -->
      <button onclick="pnavOpenSearch()" aria-label="Search" id="pnav-search-btn"
              style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s">
        <i class="fas fa-search" style="font-size:.75rem"></i>
      </button>
      <!-- Book Safari - desktop only -->
      <button onclick="openBookingModal()" id="p-book-btn"
              style="display:none;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.72rem;text-white;padding:.6rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;cursor:pointer;box-shadow:0 3px 12px rgba(160,94,34,.25);transition:all .25s;white-space:nowrap"
              onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform=''">
        <i class="fas fa-compass" style="font-size:.7rem"></i> Book Safari
      </button>
      <!-- Mobile hamburger -->
      <button id="p-mob-toggle" aria-label="Menu"
              style="width:42px;height:42px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:5px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;cursor:pointer">
        <span style="width:20px;height:2px;border-radius:2px;background:#fff;display:block;transition:all .3s" id="p-tog-1"></span>
        <span style="width:20px;height:2px;border-radius:2px;background:#fff;display:block;transition:all .3s" id="p-tog-2"></span>
        <span style="width:20px;height:2px;border-radius:2px;background:#fff;display:block;transition:all .3s" id="p-tog-3"></span>
      </button>
    </div>
  </div>
</nav>

<!-- Search overlay -->
<div id="pnav-search" onclick="if(event.target===this)pnavCloseSearch()">
  <div id="pnav-s-wrap">
    <div style="position:relative;margin-bottom:.85rem">
      <i class="fas fa-search" style="position:absolute;left:1.1rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.35);pointer-events:none"></i>
      <input id="pnav-s-input" type="text" placeholder="Search safaris, destinations, mountains…"
             onkeydown="if(event.key==='Enter'){pnavDoSearch()}">
      <button onclick="pnavCloseSearch()" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.08);border:none;color:rgba(255,255,255,.5);cursor:pointer;font-size:.9rem">&times;</button>
    </div>
    <div style="background:rgba(12,12,12,.9);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:1.1rem">
      <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.25);margin:0 0 .75rem">Popular Searches</p>
      <div style="display:flex;flex-wrap:wrap;gap:.4rem">
        <?php foreach(['Kilimanjaro','Serengeti Safari','Zanzibar Beach','Ngorongoro','Maasai Culture','Day Trip'] as $q): ?>
        <button onclick="document.getElementById('pnav-s-input').value='<?= $q ?>'"
                style="font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;padding:.35rem .85rem;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);color:rgba(255,255,255,.5);cursor:pointer;transition:all .2s"
                onmouseover="this.style.background='rgba(160,94,34,.1)';this.style.color='#a05e22'"
                onmouseout="this.style.background='rgba(255,255,255,.05)';this.style.color='rgba(255,255,255,.5)'"><?= $q ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Mobile backdrop + drawer -->
<div id="p-mob-bd"></div>
<div id="p-mob-menu" role="dialog" aria-label="Navigation">
  <!-- Drag handle indicator -->
  <div style="position:absolute;top:8px;left:50%;transform:translateX(-50%);z-index:3;width:32px;height:3.5px;border-radius:3px;background:rgba(255,255,255,.12);pointer-events:none"></div>
  <div style="padding:1.25rem;border-bottom:1px solid rgba(160,94,34,.18);display:flex;align-items:center;justify-content:space-between;background:#23362f;position:sticky;top:0;z-index:2">
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:.65rem;text-decoration:none">
      <img src="<?= e($logoUrl ?: (SITE_URL . '/uploads/logo-husika.png')) ?>" alt="<?= e($siteName) ?>"
           style="height:38px;width:auto;max-width:160px;object-fit:contain;display:block">
    </a>
    <button id="p-mob-close" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.5);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;line-height:1">&times;</button>
  </div>
  <!-- Search in drawer -->
  <div class="mob-search-wrap" style="padding:.85rem 1.25rem .6rem">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.22);font-size:.72rem;pointer-events:none"></i>
      <input type="text" placeholder="Search tours…" id="mob-search-in"
             onkeydown="if(event.key==='Enter'){window.location='<?= url('tours') ?>?search='+encodeURIComponent(this.value)}"
             style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:10px;padding:.6rem .9rem .6rem 2.2rem;font-family:'Inter',sans-serif;font-size:.82rem;color:#fff;outline:none;box-sizing:border-box">
    </div>
  </div>
  <!-- Nav links -->
  <div style="padding:.2rem 1rem">
    <?php foreach ($_pNavItems as $key => $item):
      $isA = $_activePage===$key;
      if ($key === 'more') continue; // More is desktop-only mega menu
    ?>
    <a href="<?= e($item['url']) ?>" class="pmob-link <?= $isA?'act':'' ?>" onclick="pnavCloseDrawer()">
      <span style="display:flex;align-items:center;gap:.7rem">
        <span style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $isA?'rgba(160,94,34,.15)':'rgba(255,255,255,.04)' ?>">
          <i class="fas <?= $item['icon']??'fa-circle' ?>" style="font-size:.7rem;color:<?= $isA?'#a05e22':'rgba(255,255,255,.32)' ?>"></i>
        </span>
        <?= e($item['mob']) ?>
      </span>
      <?php if($isA): ?><span style="width:6px;height:6px;border-radius:50%;background:#a05e22;flex-shrink:0"></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
    <!-- Mobile More section - show as links directly -->
    <div class="mob-more-section" style="border-top:1px solid rgba(255,255,255,.05);margin-top:.5rem;padding-top:.5rem">
      <p style="font-family:'Montserrat',sans-serif;font-size:.55rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:.25rem .5rem .4rem">More</p>
      <?php foreach ([
        ['fa-images',        url('gallery'),             'Gallery',  'rgba(249,115,22,.15)', '#f97316'],
        ['fa-question-circle',url('faq'),                'Safari FAQ','rgba(251,191,36,.15)','#fbbf24'],
        ['fa-info-circle',   url('about'),               'About Us', 'rgba(160,94,34,.15)', '#a05e22'],
      ] as $ml): ?>
      <a href="<?= $ml[1] ?>" class="pmob-link" onclick="pnavCloseDrawer()">
        <span style="display:flex;align-items:center;gap:.7rem">
          <span style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $ml[4] ?>18">
            <i class="fas <?= $ml[0] ?>" style="font-size:.7rem;color:<?= $ml[4] ?>"></i>
          </span>
          <?= $ml[2] ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- Quick actions -->
  <div class="mob-footer-section" style="padding:.5rem 1.25rem 1.5rem;margin-top:.5rem;border-top:1px solid rgba(255,255,255,.05)">
    <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
       style="display:flex;align-items:center;gap:.75rem;padding:.7rem 1rem;border-radius:12px;text-decoration:none;color:rgba(255,255,255,.5);font-family:'Montserrat',sans-serif;font-size:.8rem;font-weight:500;margin-bottom:.45rem"
       onclick="pnavCloseDrawer()">
      <span style="width:32px;height:32px;border-radius:9px;background:rgba(37,211,102,.1);display:flex;align-items:center;justify-content:center"><i class="fab fa-whatsapp" style="color:#25D366;font-size:.85rem"></i></span>
      Chat on WhatsApp
    </a>
    <button onclick="pnavCloseDrawer();openBookingModal()"
            style="width:100%;padding:.85rem;border-radius:12px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.82rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem">
      <i class="fas fa-compass" style="font-size:.7rem"></i> Book Safari
    </button>
    <div style="display:flex;flex-direction:column;gap:.28rem;margin-top:.65rem;font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.2)">
      <div style="display:flex;align-items:center;gap:.4rem"><i class="fas fa-phone" style="color:rgba(160,94,34,.4)"></i><?= e(SITE_PHONE) ?></div>
      <div style="display:flex;align-items:center;gap:.4rem"><i class="fas fa-envelope" style="color:rgba(160,94,34,.4)"></i><?= e(SITE_EMAIL) ?></div>
    </div>
  </div>
</div>

<style>
/* Responsive: show desktop nav on large screens */
@media(min-width:1024px){
  #p-nav-links{display:flex!important}
  #p-book-btn{display:inline-flex!important}
  #p-mob-toggle{display:none!important}
  .hidden-mobile{display:block!important}
}
@media(max-width:1023px){
  .hidden-mobile{display:none}
  /* On mobile: when scrolled (pill), show only the logo/icon — hide the brand name */
  #p-nav.solid #p-logo-text{display:none !important}
}
</style>

<script>
(function(){
  /* Scroll progress */
  var _spp = document.createElement('div');
  _spp.id='p-scroll-prog';
  _spp.style.cssText='position:fixed;top:0;left:0;height:2px;width:0;background:linear-gradient(90deg,#a05e22,#c17a3a);z-index:9999;transition:width .1s;pointer-events:none';
  document.body.appendChild(_spp);
  window.addEventListener('scroll',function(){var m=document.documentElement.scrollHeight-innerHeight;if(m>0)_spp.style.width=(scrollY/m*100).toFixed(2)+'%';},{passive:true});

  /* Navbar solid on scroll */
  var nav=document.getElementById('p-nav');
  window.addEventListener('scroll',function(){nav&&nav.classList.toggle('solid',scrollY>60);},{passive:true});
  if(nav)nav.classList.toggle('solid',scrollY>60);

  /* Mobile drawer with drag-to-close gesture */
  var tog=document.getElementById('p-mob-toggle'),
      mm=document.getElementById('p-mob-menu'),
      bd=document.getElementById('p-mob-bd'),
      cls=document.getElementById('p-mob-close'),
      t1=document.getElementById('p-tog-1'),t2=document.getElementById('p-tog-2'),t3=document.getElementById('p-tog-3');
  var _mo=false, _dragStartX=0, _dragCurrentX=0, _isDragging=false;
  function pnavOpenDrawer(){if(!mm)return;mm.classList.add('open');bd.classList.add('show');document.body.style.overflow='hidden';_mo=true;if(t1){t1.style.transform='rotate(45deg) translate(5px,5px)';t2.style.opacity='0';t3.style.transform='rotate(-45deg) translate(5px,-5px)';}}
  function pnavCloseDrawer(){if(!mm)return;mm.classList.remove('open');bd.classList.remove('show');document.body.style.overflow='';_mo=false;if(t1){t1.style.transform='';t2.style.opacity='';t3.style.transform='';}if(mm){mm.style.transform='';}}
  window.pnavCloseDrawer=pnavCloseDrawer;
  tog&&tog.addEventListener('click',function(){_mo?pnavCloseDrawer():pnavOpenDrawer();});
  cls&&cls.addEventListener('click',pnavCloseDrawer);
  bd&&bd.addEventListener('click',pnavCloseDrawer);
  mm&&mm.querySelectorAll('a').forEach(function(a){a.addEventListener('click',pnavCloseDrawer);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){pnavCloseDrawer();pnavCloseSearch();}});
  window.addEventListener('resize',function(){if(innerWidth>=1024)pnavCloseDrawer();},{passive:true});
  /* Drag-to-close on mobile drawer */
  if(mm){mm.addEventListener('touchstart',function(e){_dragStartX=e.touches[0].clientX;_isDragging=true;},{passive:true});
  mm.addEventListener('touchmove',function(e){if(!_isDragging||!mm.classList.contains('open'))return;var dx=e.touches[0].clientX-_dragStartX;if(dx<0)dx=0;mm.style.transform='translateX('+dx+'px)';mm.style.transition='none';},{passive:true});
  mm.addEventListener('touchend',function(e){_isDragging=false;if(!mm.classList.contains('open'))return;var dx=parseFloat(mm.style.transform.replace('translateX(','').replace('px)',''))||0;if(dx>80){pnavCloseDrawer();}else{mm.style.transform='';mm.style.transition='transform .38s cubic-bezier(.4,0,.2,1)';}},{passive:true});}

  /* Search overlay */
  var sOv=document.getElementById('pnav-search'),sWr=document.getElementById('pnav-s-wrap'),sIn=document.getElementById('pnav-s-input');
  function pnavOpenSearch(){sOv.style.opacity='1';sOv.style.pointerEvents='all';if(sWr)sWr.style.transform='translateY(0)';document.body.style.overflow='hidden';setTimeout(function(){sIn&&sIn.focus();},250);}
  function pnavCloseSearch(){sOv.style.opacity='0';sOv.style.pointerEvents='none';if(sWr)sWr.style.transform='translateY(-16px)';document.body.style.overflow='';}
  function pnavDoSearch(){var q=sIn?sIn.value.trim():'';if(q)window.location='<?= url('tours') ?>?search='+encodeURIComponent(q);}
  window.pnavOpenSearch=pnavOpenSearch;window.pnavCloseSearch=pnavCloseSearch;window.pnavDoSearch=pnavDoSearch;

  /* Scroll reveal (shared) */
  if('IntersectionObserver' in window){
    var ro=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');ro.unobserve(e.target);}});},{threshold:.07});
    document.querySelectorAll('.reveal').forEach(function(el){ro.observe(el);});
  }
})();
window.SITE_URL='<?= SITE_URL ?>';
</script>

