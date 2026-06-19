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
    'tours'        => ['url'=>url('tours.php'),             'desk'=>'Safaris',    'mob'=>'Safari Tours',     'icon'=>'fa-compass'],
    'trekking'     => ['url'=>url('mountain-trekking.php'), 'desk'=>'Trekking',   'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
    'destinations' => ['url'=>url('destinations.php'),      'desk'=>'Destinations','mob'=>'Destinations',   'icon'=>'fa-map-marker-alt'],
    'gallery'      => ['url'=>url('gallery.php'),           'desk'=>'Gallery',    'mob'=>'Gallery',          'icon'=>'fa-images'],
    'blog'         => ['url'=>url('blog.php'),              'desk'=>'Blog',       'mob'=>'Blog',             'icon'=>'fa-newspaper'],
    'about'        => ['url'=>url('about.php'),             'desk'=>'About',      'mob'=>'About Us',         'icon'=>'fa-info-circle'],
    'faq'          => ['url'=>url('faq.php'),               'desk'=>'FAQ',        'mob'=>'Safari FAQ',       'icon'=>'fa-question-circle'],
    'contact'      => ['url'=>url('contact.php'),           'desk'=>'Contact',    'mob'=>'Contact',          'icon'=>'fa-envelope'],
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
  ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <meta property="og:title"       content="<?= e($pageTitle ?? '') ?>">
  <meta property="og:description" content="<?= e($pageDescription ?? '') ?>">
  <meta property="og:url"         content="<?= e($canonicalUrl) ?>">
  <meta property="og:type"        content="<?= e($ogType) ?>">
  <meta property="og:site_name"   content="Jambo Masai Tours">
  <meta name="theme-color"        content="#10b981">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <link rel="manifest" href="<?= e(SITE_URL) ?>/manifest.json">
  <?php
  $_ga4 = getSetting('ga4_measurement_id');
  $_gv  = getSetting('google_site_verification');
  if ($_gv): ?><meta name="google-site-verification" content="<?= e($_gv) ?>"><?php endif;
  if ($_ga4): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($_ga4) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($_ga4) ?>');</script>
  <?php endif; ?>
  <!-- Resource hints for faster CDN loading -->
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors:{ brand:'#10b981', brandd:'#059669', safari:'#10b981', dark:'#0a0a0a' },
        fontFamily:{
          heading:['Playfair Display','Georgia','serif'],
          sans:   ['Inter','Poppins','sans-serif'],
          nav:    ['Montserrat','sans-serif'],
        }
      }}
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#0a0a0a;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#111}::-webkit-scrollbar-thumb{background:#10b981;border-radius:2px}

    /* Shared components */
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}
    .hero-grad{background:linear-gradient(135deg,#34d399,#059669,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .section-tag{display:inline-flex;align-items:center;gap:.5rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:999px;padding:.3rem 1rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#34d399;margin-bottom:1.1rem}
    .f-input,.f-select,.f-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.8rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .25s;-webkit-appearance:none}
    .f-input:focus,.f-select:focus,.f-textarea:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.04)}
    .f-textarea{resize:vertical;min-height:120px}
    .f-select option{background:#1a1a1a}
    .f-label{display:block;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:.45rem}
    .f-label span{color:#f87171}
    .btn-em{display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;padding:.85rem 2rem;border-radius:10px;transition:all .3s;cursor:pointer;border:none}
    .btn-em-primary{background:linear-gradient(135deg,#059669,#10b981);color:#fff;box-shadow:0 4px 16px rgba(16,185,129,.25)}
    .btn-em-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(16,185,129,.35)}
    .btn-em-outline{background:transparent;color:rgba(255,255,255,.65);border:1px solid rgba(255,255,255,.15)}
    .btn-em-outline:hover{background:rgba(255,255,255,.06);color:#fff;transform:translateY(-2px)}
    .btn-em-wa{background:rgba(37,211,102,.1);color:#25D366;border:1px solid rgba(37,211,102,.25)}
    .btn-em-wa:hover{background:rgba(37,211,102,.2);transform:translateY(-2px)}

    /* Mobile drawer (legacy — public_navbar.php handles the active navbar) */
    #mobile-menu{position:fixed;top:0;right:0;bottom:0;width:min(300px,84vw);background:rgba(10,10,10,.98);backdrop-filter:blur(24px);z-index:1001;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(16,185,129,.1);overflow-y:auto;padding-bottom:2rem}
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
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);color:#10b981;font-size:1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}

    /* Glow */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:0;width:500px;height:500px;background:radial-gradient(circle,rgba(16,185,129,0.1) 0%,transparent 70%)}
    .glow-orb-2{bottom:0;right:0;width:400px;height:400px;background:radial-gradient(circle,rgba(16,185,129,0.06) 0%,transparent 70%)}

    /* Scroll progress */
    .scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#10b981,#059669);z-index:9999;width:0;transition:width .1s}

    /* Mega menu */
    .nav-mega-trigger{position:relative}
    .nav-mega-drop{position:absolute;top:100%;left:50%;transform:translateX(-50%) translateY(10px);width:580px;opacity:0;pointer-events:none;transition:all .28s cubic-bezier(.16,1,.3,1);padding-top:.5rem;z-index:200}
    .nav-mega-trigger:hover .nav-mega-drop{opacity:1;pointer-events:all;transform:translateX(-50%) translateY(0)}
    .nav-mega-card{background:rgba(14,14,14,.97);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:1.25rem;box-shadow:0 24px 60px rgba(0,0,0,.7),0 0 0 1px rgba(16,185,129,.06)}
    .mega-tour-item{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:12px;text-decoration:none;transition:all .2s;cursor:pointer}
    .mega-tour-item:hover{background:rgba(16,185,129,.08)}
    .mega-tour-item:hover .mega-tour-name{color:#34d399}
    /* Search overlay */
    #nav-search-overlay{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);display:flex;align-items:flex-start;justify-content:center;padding-top:100px;opacity:0;pointer-events:none;transition:opacity .3s}
    #nav-search-overlay.open{opacity:1;pointer-events:all}
    #nav-search-wrap{width:100%;max-width:600px;padding:0 1rem;transform:translateY(-16px);transition:transform .35s cubic-bezier(.16,1,.3,1)}
    #nav-search-overlay.open #nav-search-wrap{transform:translateY(0)}
    .nav-search-input{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:1rem 1rem 1rem 3.25rem;font-family:'Inter',sans-serif;font-size:1.05rem;color:#fff;outline:none;transition:border-color .2s}
    .nav-search-input:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.04)}
    .nav-search-input::placeholder{color:rgba(255,255,255,.3)}
    /* Improved mobile drawer */
    #mobile-menu{position:fixed;top:0;right:0;bottom:0;width:min(320px,88vw);background:#0e0e0e;backdrop-filter:blur(24px);z-index:1001;transform:translateX(100%);transition:transform .38s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(255,255,255,.08);overflow-y:auto;padding-bottom:2rem}
    #mobile-menu.open{transform:translateX(0)}
    .mob-link{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-radius:12px;font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:500;text-decoration:none;transition:all .2s;color:rgba(255,255,255,.6)}
    .mob-link:hover{background:rgba(255,255,255,.05);color:#fff}
    .mob-link.active{background:rgba(16,185,129,.1);color:#10b981}
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




