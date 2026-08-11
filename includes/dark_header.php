<?php
/* Dark-theme standalone header — include AFTER setting $pageTitle, $pageDescription, $currentPage */
if (!isset($logoUrl)) {
    $logoUrl   = getSetting('logo_url');
    $logoWidth = (int)(getSetting('logo_width','160') ?: 160);
    $siteName  = getSetting('site_name','Jambo Masai Tours');
    $siteTagline = getSetting('site_tagline','Tanzania Safari Experts');
}
/* desk = shorter label for desktop, mob = full label for mobile drawer */
$_navItems = [
    'home'         => ['url'=>url(),                        'desk'=>'Home',       'mob'=>'Home',             'icon'=>'fa-home'],
    'tours'        => ['url'=>url('tours'),             'desk'=>'Safaris',    'mob'=>'Safari Tours',     'icon'=>'fa-compass'],
    'trekking'     => ['url'=>url('mountain-trekking'), 'desk'=>'Trekking',   'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
    'destinations' => ['url'=>url('destinations'),      'desk'=>'Destinations','mob'=>'Destinations',   'icon'=>'fa-map-marker-alt'],
    'gallery'      => ['url'=>url('gallery'),           'desk'=>'Gallery',    'mob'=>'Gallery',          'icon'=>'fa-images'],
    'blog'         => ['url'=>url('blog'),              'desk'=>'Blog',       'mob'=>'Blog',             'icon'=>'fa-newspaper'],
    'about'        => ['url'=>url('about'),             'desk'=>'About',      'mob'=>'About Us',         'icon'=>'fa-info-circle'],
    'faq'          => ['url'=>url('faq'),               'desk'=>'FAQ',        'mob'=>'Safari FAQ',       'icon'=>'fa-question-circle'],
    'contact'      => ['url'=>url('contact'),           'desk'=>'Contact',    'mob'=>'Contact',          'icon'=>'fa-envelope'],
];

/* Featured tours for mega menu */
try {
    $_megaTours = getDB()->query("SELECT name,slug,price,tour_type,destination FROM tours WHERE featured=1 ORDER BY rating DESC LIMIT 4")->fetchAll();
} catch (\Throwable $e) { $_megaTours = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Jambo Masai Tours') ?></title>
  <meta name="description" content="<?= e($pageDescription ?? '') ?>">
  <?php
  /* Canonical: page can set $canonicalUrl before include; fallback = current URL without query string */
  if (!isset($canonicalUrl)) {
      $canonicalUrl = SITE_URL . '/' . ltrim(strtok($_SERVER['PHP_SELF'] ?? '', '?'), '/');
  }
  $ogType = $ogType ?? 'website';
  /* og:image fallback — kurasa kadhaa (tours, blog, gallery, contact,
     destinations, trekking) hazikuweka $ogImage, hivyo zilishirikishwa
     WhatsApp/Facebook bila picha kabisa. Sasa kila ukurasa una picha. */
  if (empty($ogImage)) {
      $ogImage = getSetting('og_default_image') ?: getSetting('logo_url')
               ?: (SITE_URL . '/uploads/about-main.jpg');
  }
  ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <meta property="og:title"       content="<?= e($pageTitle ?? '') ?>">
  <meta property="og:description" content="<?= e($pageDescription ?? '') ?>">
  <meta property="og:url"         content="<?= e($canonicalUrl) ?>">
  <meta property="og:type"        content="<?= e($ogType) ?>">
  <meta property="og:site_name"   content="Jambo Masai Tours">
  <?php if (!empty($ogImage)): ?><meta property="og:image:alt" content="<?= e($pageTitle ?? '') ?>"><?php endif; ?>
  <meta name="theme-color"        content="#a05e22">
  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= e($pageTitle ?? '') ?>">
  <meta name="twitter:description" content="<?= e($pageDescription ?? '') ?>">
  <?php if (!empty($ogImage)): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <?php if (!empty($metaKeywords)): ?><meta name="keywords" content="<?= e($metaKeywords) ?>"><?php endif; ?>
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <link rel="manifest" href="<?= e(SITE_URL) ?>/manifest.json">
  <?php
  $_gv = getSetting('google_site_verification');
  if ($_gv): ?><meta name="google-site-verification" content="<?= e($_gv) ?>"><?php endif; ?>
  <?php require __DIR__ . '/google-tags.php'; /* Google Ads + GA4 */ ?>
  <!-- Resource hints for faster CDN loading -->
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors:{ brand:'#a05e22', brandd:'#7d4817', safari:'#a05e22', dark:'#23362f' },
        fontFamily:{
          heading:['Nanum Myeongjo','Georgia','serif'],
          sans:   ['Inter','Poppins','sans-serif'],
          nav:    ['Montserrat','sans-serif'],
        }
      }}
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#2c463d}::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}

    /* Shared components */
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}
    .hero-grad{background:linear-gradient(135deg,#c17a3a,#7d4817,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .section-tag{display:inline-flex;align-items:center;gap:.5rem;background:rgba(160,94,34,.1);border:1px solid rgba(160,94,34,.2);border-radius:999px;padding:.3rem 1rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#c17a3a;margin-bottom:1.1rem}
    .f-input,.f-select,.f-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.8rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .25s;-webkit-appearance:none}
    .f-input:focus,.f-select:focus,.f-textarea:focus{border-color:rgba(160,94,34,.5);background:rgba(160,94,34,.04)}
    .f-textarea{resize:vertical;min-height:120px}
    .f-select option{background:#1a1a1a}
    .f-label{display:block;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:.45rem}
    .f-label span{color:#f87171}
    .btn-em{display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;padding:.85rem 2rem;border-radius:10px;transition:all .3s;cursor:pointer;border:none}
    .btn-em-primary{background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;box-shadow:0 4px 16px rgba(160,94,34,.25)}
    .btn-em-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(160,94,34,.35)}
    .btn-em-outline{background:transparent;color:rgba(255,255,255,.65);border:1px solid rgba(255,255,255,.15)}
    .btn-em-outline:hover{background:rgba(255,255,255,.06);color:#fff;transform:translateY(-2px)}
    .btn-em-wa{background:rgba(37,211,102,.1);color:#25D366;border:1px solid rgba(37,211,102,.25)}
    .btn-em-wa:hover{background:rgba(37,211,102,.2);transform:translateY(-2px)}

    /* Mobile drawer (legacy — public_navbar.php handles the active navbar) */
    #mobile-menu{position:fixed;top:0;right:0;bottom:0;width:min(300px,84vw);background:rgba(10,10,10,.98);backdrop-filter:blur(24px);z-index:1001;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(160,94,34,.1);overflow-y:auto;padding-bottom:2rem}
    #mobile-menu.open{transform:translateX(0)}
    #mobile-backdrop{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.6);display:none;opacity:0;transition:opacity .3s}
    #mobile-backdrop.show{display:block;opacity:1}

    /* Reveal */
    .reveal{opacity:0;transform:translateY(22px);transition:all .7s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}

    /* Page hero */
    .page-hero{position:relative;overflow:hidden;min-height:320px;display:flex;align-items:flex-end;padding-bottom:3rem}
    @keyframes kenBurns{0%{transform:scale(1)}50%{transform:scale(1.06)}100%{transform:scale(1)}}
    .ken-burns{animation:kenBurns 20s ease-in-out infinite}

    /* WhatsApp float */
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.35);opacity:0}}
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#a05e22;font-size:1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}

    /* Glow */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:0;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.1) 0%,transparent 70%)}
    .glow-orb-2{bottom:0;right:0;width:400px;height:400px;background:radial-gradient(circle,rgba(160,94,34,0.06) 0%,transparent 70%)}

    /* Scroll progress */
    .scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#a05e22,#7d4817);z-index:9999;width:0;transition:width .1s}

    /* Mega menu */
    .nav-mega-trigger{position:relative}
    .nav-mega-drop{position:absolute;top:100%;left:50%;transform:translateX(-50%) translateY(10px);width:580px;opacity:0;pointer-events:none;transition:all .28s cubic-bezier(.16,1,.3,1);padding-top:.5rem;z-index:200}
    .nav-mega-trigger:hover .nav-mega-drop{opacity:1;pointer-events:all;transform:translateX(-50%) translateY(0)}
    .nav-mega-card{background:rgba(14,14,14,.97);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:1.25rem;box-shadow:0 24px 60px rgba(0,0,0,.7),0 0 0 1px rgba(160,94,34,.06)}
    .mega-tour-item{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:12px;text-decoration:none;transition:all .2s;cursor:pointer}
    .mega-tour-item:hover{background:rgba(160,94,34,.08)}
    .mega-tour-item:hover .mega-tour-name{color:#c17a3a}
    /* Search overlay */
    #nav-search-overlay{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);display:flex;align-items:flex-start;justify-content:center;padding-top:100px;opacity:0;pointer-events:none;transition:opacity .3s}
    #nav-search-overlay.open{opacity:1;pointer-events:all}
    #nav-search-wrap{width:100%;max-width:600px;padding:0 1rem;transform:translateY(-16px);transition:transform .35s cubic-bezier(.16,1,.3,1)}
    #nav-search-overlay.open #nav-search-wrap{transform:translateY(0)}
    .nav-search-input{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:1rem 1rem 1rem 3.25rem;font-family:'Inter',sans-serif;font-size:1.05rem;color:#fff;outline:none;transition:border-color .2s}
    .nav-search-input:focus{border-color:rgba(160,94,34,.5);background:rgba(160,94,34,.04)}
    .nav-search-input::placeholder{color:rgba(255,255,255,.3)}
    /* Improved mobile drawer */
    #mobile-menu{position:fixed;top:0;right:0;bottom:0;width:min(320px,88vw);background:#0e0e0e;backdrop-filter:blur(24px);z-index:1001;transform:translateX(100%);transition:transform .38s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(255,255,255,.08);overflow-y:auto;padding-bottom:2rem}
    #mobile-menu.open{transform:translateX(0)}
    .mob-link{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-radius:12px;font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:500;text-decoration:none;transition:all .2s;color:rgba(255,255,255,.6)}
    .mob-link:hover{background:rgba(255,255,255,.05);color:#fff}
    .mob-link.active{background:rgba(160,94,34,.1);color:#a05e22}

    /* ===== LIGHT (cream) sections — add class "section-light" to any <section> ===== */
    .section-light{background:#f4e1c3;color:#2c463d;border-top:1px solid rgba(44,70,61,.08)}
    .section-light h1,.section-light h2,.section-light h3,.section-light h4{color:#233a32}
    .section-light .text-white{color:#233a32 !important}
    .section-light .text-white\/90{color:#2c463d !important}
    .section-light .text-white\/80,.section-light .text-white\/75,.section-light .text-white\/70{color:#3b5c51 !important}
    .section-light .text-white\/60,.section-light .text-white\/55,.section-light .text-white\/50{color:#5a6f64 !important}
    .section-light .text-white\/45,.section-light .text-white\/40,.section-light .text-white\/35,.section-light .text-white\/30{color:#7a8b80 !important}
    .section-light .gradient-text,.section-light .hero-grad{background:linear-gradient(135deg,#a05e22,#7d4817);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .section-light .text-brand,.section-light .text-emerald-400,.section-light .text-emerald-500{color:#a05e22 !important}
    .section-light .glass-card{background:#faf3e6 !important;border:1px solid rgba(44,70,61,.1) !important;box-shadow:0 10px 30px rgba(44,70,61,.1) !important;backdrop-filter:none !important}
    .section-light .bg-white\/5,.section-light .bg-white\/\[\.05\]{background:#faf3e6 !important}
    .section-light .border-white\/10,.section-light .border-white\/\[\.1\],.section-light .border-white\/5{border-color:rgba(44,70,61,.14) !important}
    .section-light .tour-card{background:#faf3e6;border-color:rgba(44,70,61,.1)}
    .section-light .section-tag{background:rgba(160,94,34,.14);border-color:rgba(160,94,34,.35);color:#7d4817}
    /* Form fields inside a cream section → readable on light bg */
    .section-light .f-label{color:#3b5c51 !important}
    .section-light .f-label span{color:#c0392b !important}
    .section-light .f-input,.section-light .f-select,.section-light .f-textarea{background:#fffdf8 !important;border:1px solid rgba(44,70,61,.18) !important;color:#233a32 !important}
    .section-light .f-input::placeholder,.section-light .f-textarea::placeholder{color:rgba(44,70,61,.45) !important}
    .section-light .f-input:focus,.section-light .f-select:focus,.section-light .f-textarea:focus{border-color:rgba(160,94,34,.55) !important;background:#fff !important}
    .section-light .f-select option{background:#fffdf8;color:#233a32}
    <?= $extraCss ?? '' ?>
  </style>
  <?= $headExtra ?? '' ?>
</head>
<body class="bg-dark text-gray-200">

<?php require_once __DIR__ . '/preloader.php'; ?>

<div class="scroll-progress" id="scroll-progress"></div>
<div class="glow-orb glow-orb-1" aria-hidden="true"></div>
<div class="glow-orb glow-orb-2" aria-hidden="true"></div>

<!-- ═══ UNIFIED NAVBAR (shared) ═══ -->
<?php require_once __DIR__ . '/public_navbar.php'; ?>

<?php require_once __DIR__ . '/booking-modal.php'; ?>




