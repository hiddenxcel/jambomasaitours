<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Luxury Safari Tours Tanzania & Kenya | Packages & Prices — Jambo Masai Tours';
$pageDescription = 'Browse all our luxury safari tours in Tanzania. Serengeti migration, Ngorongoro Crater, Kilimanjaro trekking, Zanzibar beach and cultural tours.';
$currentPage     = 'tours';

$db        = getDB();
$tours     = $db->query("SELECT * FROM tours ORDER BY featured DESC, rating DESC")->fetchAll();
$tourTypes = $db->query("SELECT DISTINCT tour_type FROM tours ORDER BY tour_type")->fetchAll(PDO::FETCH_COLUMN);

/* Stats for hero */
$minPrice = PHP_INT_MAX; $maxPrice = 0; $minDur = PHP_INT_MAX; $maxDur = 0;
foreach ($tours as $t) {
    $p = (float)$t['price'];
    preg_match('/\d+/', $t['duration'] ?? '', $m); $d = (int)($m[0] ?? 0);
    if ($p < $minPrice) $minPrice = $p;
    if ($p > $maxPrice) $maxPrice = $p;
    if ($d && $d < $minDur) $minDur = $d;
    if ($d > $maxDur) $maxDur = $d;
}
if ($minPrice === PHP_INT_MAX) $minPrice = 0;
if ($minDur   === PHP_INT_MAX) $minDur   = 1;

$logoUrl     = getSetting('logo_url');
$logoWidth   = (int)(getSetting('logo_width', '160') ?: 160);
$siteName    = getSetting('site_name', 'Jambo Masai Tours');
$siteTagline = getSetting('site_tagline', 'Tanzania Safari Experts');

$navItems = [
    'home'         => ['url'=>url(),                        'desk'=>'Home',        'mob'=>'Home',             'icon'=>'fa-home'],
    'tours'        => ['url'=>url('tours'),             'desk'=>'Safaris',     'mob'=>'Safari Tours',     'icon'=>'fa-compass'],
    'trekking'     => ['url'=>url('mountain-trekking'), 'desk'=>'Trekking',    'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
    'destinations' => ['url'=>url('destinations'),      'desk'=>'Destinations','mob'=>'Destinations',     'icon'=>'fa-map-marker-alt'],
    'gallery'      => ['url'=>url('gallery'),           'desk'=>'Gallery',     'mob'=>'Gallery',          'icon'=>'fa-images'],
    'blog'         => ['url'=>url('blog'),              'desk'=>'Blog',        'mob'=>'Blog',             'icon'=>'fa-newspaper'],
    'about'        => ['url'=>url('about'),             'desk'=>'About',       'mob'=>'About Us',         'icon'=>'fa-info-circle'],
    'contact'      => ['url'=>url('contact'),           'desk'=>'Contact',     'mob'=>'Contact',          'icon'=>'fa-envelope'],
];

function tdiff(string $type): array {
    return match(strtolower($type)) {
        'trekking'               => ['Challenging', 'rgba(239,68,68,.15)',    '#f87171'],
        'adventure tour'         => ['Challenging', 'rgba(239,68,68,.15)',    '#f87171'],
        'walking safari'         => ['Moderate',    'rgba(245,158,11,.15)',   '#fbbf24'],
        'bird watching safari'   => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'beach holiday'          => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'cultural tour'          => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'honeymoon safari'       => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'family safari'          => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'balloon safari'         => ['Easy',        'rgba(160,94,34,.15)',   '#c17a3a'],
        'day trip'               => ['Easy',        'rgba(52,211,153,.15)',   '#c17a3a'],
        'photography safari'     => ['Moderate',    'rgba(96,165,250,.15)',   '#60a5fa'],
        'luxury safari'          => ['Easy',        'rgba(251,191,36,.15)',   '#fbbf24'],
        'budget safari'          => ['Easy',        'rgba(245,158,11,.15)',   '#fbbf24'],
        'camping safari'         => ['Moderate',    'rgba(245,158,11,.15)',   '#fbbf24'],
        'group safari'           => ['Moderate',    'rgba(245,158,11,.15)',   '#fbbf24'],
        'great migration safari' => ['Moderate',    'rgba(160,94,34,.15)',   '#c17a3a'],
        default                  => ['Moderate',    'rgba(245,158,11,.15)',   '#fbbf24'],
    };
}

function ticon(string $type): string {
    return match(strtolower($type)) {
        'wildlife safari'        => 'fa-paw',
        'great migration safari' => 'fa-horse',
        'trekking'               => 'fa-mountain',
        'beach holiday'          => 'fa-umbrella-beach',
        'cultural tour'          => 'fa-users',
        'honeymoon safari'       => 'fa-heart',
        'family safari'          => 'fa-child',
        'balloon safari'         => 'fa-hot-air-balloon',
        'walking safari'         => 'fa-hiking',
        'bird watching safari'   => 'fa-feather',
        'photography safari'     => 'fa-camera',
        'adventure tour'         => 'fa-bolt',
        'camping safari'         => 'fa-campground',
        'luxury safari'          => 'fa-star',
        'budget safari'          => 'fa-tag',
        'group safari'           => 'fa-user-friends',
        'day trip'               => 'fa-sun',
        default                  => 'fa-compass',
    };
}

function cardBadge(array $tour, int $idx): ?array {
    if ($idx === 0 || $tour['featured'])            return ['Most Popular', '#f59e0b', 'rgba(245,158,11,.9)', '#000', 'fa-fire'];
    if (strtolower($tour['tour_type']) === 'trekking') return ['Adventure',    '#8b5cf6', 'rgba(139,92,246,.85)', '#fff', 'fa-mountain'];
    if (strtolower($tour['tour_type']) === 'beach holiday') return ['Beach Retreat','#3b82f6','rgba(59,130,246,.85)','#fff','fa-umbrella-beach'];
    if ((float)$tour['price'] === (float)min(array_column([$tour], 'price'))) return null;
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require __DIR__ . '/includes/google-tags.php'; /* Google Ads + GA4 */ ?>
  <?php $toursCanon = SITE_URL . '/tours'; ?>
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="canonical" href="<?= e($toursCanon) ?>">
  <meta property="og:title"       content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url"         content="<?= e($toursCanon) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="Jambo Masai Tours">
  <?php if (!empty($tours)): ?>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Safari Tours Tanzania",
    "description": <?= json_encode($pageDescription) ?>,
    "url": "<?= $toursCanon ?>",
    "numberOfItems": <?= count($tours) ?>,
    "itemListElement": [
      <?php foreach (array_slice($tours, 0, 10) as $i => $t): ?>
      {
        "@type": "ListItem",
        "position": <?= $i + 1 ?>,
        "url": "<?= SITE_URL . '/tour/' . e($t['slug']) ?>",
        "name": <?= json_encode(strip_tags($t['name'])) ?>
      }<?= $i < min(9, count($tours)-1) ? ',' : '' ?>
      <?php endforeach; ?>
    ]
  }
  </script>
  <?php endif; ?>
  <link rel="preconnect" href="https://cdn.tailwindcss.com"><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><link rel="dns-prefetch" href="https://images.unsplash.com"><script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: { brand:'#a05e22', brandd:'#7d4817', safari:'#a05e22', dark:'#23362f' },
        fontFamily: {
          heading: ['Nanum Myeongjo','Georgia','serif'],
          sans:    ['Inter','Poppins','sans-serif'],
          nav:     ['Montserrat','sans-serif'],
        }
      }}
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#2c463d}::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}

    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}
    .hero-grad{background:linear-gradient(135deg,#c17a3a,#7d4817,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* Navbar */

    /* Mobile drawer */

    /* Hero */
    @keyframes kenBurns{0%{transform:scale(1)}50%{transform:scale(1.06)}100%{transform:scale(1)}}
    .ken-burns{animation:kenBurns 20s ease-in-out infinite}
    @keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .anim-up{opacity:0;animation:fadeInUp .8s cubic-bezier(.16,1,.3,1) forwards}

    /* Cards */
    .safari-card{background:rgba(23,23,23,.85);border:1px solid rgba(255,255,255,.06);border-radius:24px;overflow:hidden;transition:all .4s cubic-bezier(.4,0,.2,1)}
    .safari-card:hover{transform:translateY(-5px);box-shadow:0 24px 60px rgba(0,0,0,.6);border-color:rgba(160,94,34,.25)}
    .card-img{transition:transform .65s cubic-bezier(.4,0,.2,1);width:100%;height:100%;object-fit:cover}
    .safari-card:hover .card-img{transform:scale(1.06)}
    .img-overlay{position:relative;overflow:hidden}
    .img-overlay::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(10,10,10,.8) 0%,transparent 55%)}

    /* Filter buttons */
    .filter-btn{transition:all .3s cubic-bezier(.4,0,.2,1);cursor:pointer}
    .filter-btn.active{background:linear-gradient(135deg,#5e3611,#a05e22) !important;color:#fff !important;border-color:transparent !important;box-shadow:0 4px 15px rgba(160,94,34,.3)}

    /* Itinerary toggle */
    .safari-detail{max-height:0;overflow:hidden;transition:max-height .5s cubic-bezier(.16,1,.3,1)}
    .safari-detail.expanded{max-height:800px}
    .itinerary-line{position:relative;padding-left:2rem;padding-bottom:1rem}
    .itinerary-line::before{content:'';position:absolute;left:.69rem;top:1.6rem;bottom:0;width:1.5px;background:rgba(160,94,34,.2)}
    .itinerary-line:last-child::before{display:none}

    /* WhatsApp float */
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.35);opacity:0}}

    /* Back to top */
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#a05e22;font-size:1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}

    /* Reveal animation */
    .reveal{opacity:0;transform:translateY(24px);transition:all .7s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}

    /* Form inputs */
    .search-input,.search-select{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.7rem 1rem;font-family:'Inter',sans-serif;font-size:.85rem;width:100%;outline:none;transition:border-color .2s;-webkit-appearance:none}
    .search-input:focus,.search-select:focus{border-color:rgba(160,94,34,.5)}
    .search-select option{background:#1a1a1a}

    /* Glow orbs */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:0;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.1) 0%,transparent 70%)}
    .glow-orb-2{bottom:-5%;right:0;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.06) 0%,transparent 70%)}

    /* Scroll progress */
    .scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#a05e22,#7d4817);z-index:9999;width:0;transition:width .1s}

    /* Badge highlight */
    .highlight-box{background:rgba(255,255,255,.03);border-radius:12px;padding:.75rem;text-align:center;border:1px solid rgba(255,255,255,.05)}

    /* Empty state */
    #empty-state{display:none}
  </style>
</head>
<body class="bg-dark text-gray-200">

<div class="scroll-progress" id="scroll-progress"></div>
<div class="glow-orb glow-orb-1" aria-hidden="true"></div>
<div class="glow-orb glow-orb-2" aria-hidden="true"></div>

<?php require_once 'includes/public_navbar.php'; ?>

<!-- --------------- HERO --------------- -->
<section class="relative overflow-hidden" style="min-height:320px;display:flex;align-items:center">
  <div class="absolute inset-0 z-0">
    <img src="<?= IMG_SERENGETI ?>" alt="Safari Tours Tanzania" fetchpriority="high"
         class="w-full h-full object-cover ken-burns" style="opacity:.35">
    <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.9) 0%,rgba(10,10,10,.55) 50%,rgba(10,10,10,1) 100%)"></div>
    <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.9) 0%,transparent 60%)"></div>
  </div>

  <div class="relative z-10 w-full max-w-7xl mx-auto px-4 lg:px-6 pt-28 pb-16">
    <div class="max-w-2xl">
      <div class="anim-up" style="animation-delay:.1s">
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-6">
          <i class="fas fa-compass text-emerald-400 text-xs"></i>
          <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.18em] uppercase font-nav">
            <?= $totalTours = count($tours) ?> Curated Safari Experiences
          </span>
        </div>
      </div>

      <h1 class="anim-up font-heading text-white leading-[1.06] tracking-tight mb-5" style="font-size:clamp(2.2rem,6vw,4rem);animation-delay:.2s">
        Find Your Perfect<br><span class="hero-grad">Safari Adventure</span>
      </h1>

      <p class="anim-up text-white/55 text-[1rem] leading-relaxed mb-6 max-w-lg" style="animation-delay:.3s">
        From thrilling game drives across the Serengeti to cultural immersions with the Maasai — every journey is crafted to leave you breathless.
      </p>

      <!-- Migration season banner (internal link) -->
      <a href="<?= url('migration') ?>" class="anim-up mig-promo" style="animation-delay:.35s">
        <span class="mig-promo-dot"></span>
        <i class="fas fa-horse"></i>
        <span class="mig-promo-txt"><strong>It's Great Migration season</strong> — see our Serengeti migration safaris</span>
        <i class="fas fa-arrow-right mig-promo-arrow"></i>
      </a>
      <style>
      .mig-promo{display:inline-flex;align-items:center;gap:.6rem;margin-bottom:1.75rem;padding:.6rem 1.1rem;border-radius:999px;background:linear-gradient(135deg,rgba(160,94,34,.16),rgba(125,72,23,.08));border:1px solid rgba(160,94,34,.35);text-decoration:none;transition:all .3s cubic-bezier(.22,1,.36,1)}
      .mig-promo:hover{border-color:rgba(160,94,34,.6);background:linear-gradient(135deg,rgba(160,94,34,.24),rgba(125,72,23,.14));transform:translateY(-2px)}
      .mig-promo i.fa-horse{color:#c17a3a;font-size:.8rem}
      .mig-promo-dot{width:7px;height:7px;border-radius:50%;background:#c17a3a;box-shadow:0 0 0 0 rgba(193,122,58,.7);animation:migPromoLive 1.8s ease-out infinite}
      @keyframes migPromoLive{0%{box-shadow:0 0 0 0 rgba(193,122,58,.6)}70%,100%{box-shadow:0 0 0 8px rgba(193,122,58,0)}}
      .mig-promo-txt{font-family:'Inter',sans-serif;font-size:.8rem;color:rgba(255,255,255,.7)}
      .mig-promo-txt strong{color:#fff;font-weight:600}
      .mig-promo-arrow{color:#c17a3a;font-size:.7rem;transition:transform .3s}
      .mig-promo:hover .mig-promo-arrow{transform:translateX(3px)}
      @media(max-width:520px){.mig-promo-txt{font-size:.72rem}}
      </style>

      <div class="anim-up flex flex-wrap gap-6" style="animation-delay:.4s">
        <?php foreach ([
          ['fa-route',        'emerald', $totalTours . ' Safaris',    'To Choose From'],
          ['fa-calendar-alt', 'amber',   $minDur . '–' . $maxDur . ' Days', 'Trip Duration'],
          ['fa-dollar-sign',  'blue',    '$' . number_format($minPrice) . '–$' . number_format($maxPrice), 'Price Range'],
        ] as $st): ?>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(<?= $st[1]==='emerald'?'16,185,129':($st[1]==='amber'?'245,158,11':'59,130,246') ?>,.1)">
            <i class="fas <?= $st[0] ?> text-sm" style="color:<?= $st[1]==='emerald'?'#c17a3a':($st[1]==='amber'?'#fbbf24':'#60a5fa') ?>"></i>
          </div>
          <div>
            <div class="text-sm font-semibold text-white"><?= $st[2] ?></div>
            <div class="text-[.65rem] text-white/35 font-nav uppercase tracking-wider"><?= $st[3] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- --------------- FILTER BAR --------------- -->
<section class="relative z-20 -mt-6 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="glass-card p-4 lg:p-5">
      <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">

        <!-- Search input -->
        <div class="relative flex-1 w-full">
          <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
          <input id="search-input" type="text" placeholder="Search safari tours..."
                 class="search-input pl-11" oninput="applyFilters()">
        </div>

        <!-- Category filter buttons -->
        <div class="flex flex-wrap gap-2">
          <button class="filter-btn active font-nav text-[.68rem] font-semibold px-3.5 py-2 rounded-xl border border-white/10 text-white/70 hover:text-white" style="background:rgba(255,255,255,.05)" data-filter="all" onclick="setFilter(this,'all')">
            All Tours
          </button>
          <?php
          $filterMap = [
            'Wildlife Safari'        => ['fa-paw',             'Wildlife'],
            'Great Migration Safari' => ['fa-horse',           'Migration'],
            'Trekking'               => ['fa-mountain',        'Trekking'],
            'Beach Holiday'          => ['fa-umbrella-beach',  'Beach'],
            'Cultural Tour'          => ['fa-users',           'Cultural'],
            'Honeymoon Safari'       => ['fa-heart',           'Honeymoon'],
            'Family Safari'          => ['fa-child',           'Family'],
            'Balloon Safari'         => ['fa-hot-air-balloon', 'Balloon'],
            'Walking Safari'         => ['fa-hiking',          'Walking'],
            'Bird Watching Safari'   => ['fa-feather',         'Birding'],
            'Photography Safari'     => ['fa-camera',          'Photo'],
            'Adventure Tour'         => ['fa-bolt',            'Adventure'],
            'Camping Safari'         => ['fa-campground',      'Camping'],
            'Luxury Safari'          => ['fa-star',            'Luxury'],
            'Budget Safari'          => ['fa-tag',             'Budget'],
            'Group Safari'           => ['fa-user-friends',    'Group'],
            'Day Trip'               => ['fa-sun',             'Day Trip'],
          ];
          foreach ($filterMap as $type => $meta):
            if (!in_array($type, $tourTypes)) continue;
          ?>
          <button class="filter-btn font-nav text-[.68rem] font-semibold px-3.5 py-2 rounded-xl border border-white/10 text-white/70 hover:text-white" style="background:rgba(255,255,255,.05)"
                  data-filter="<?= e(strtolower($type)) ?>" onclick="setFilter(this,'<?= e(strtolower($type)) ?>')">
            <i class="fas <?= $meta[0] ?> mr-1 text-[.6rem]"></i><?= $meta[1] ?>
          </button>
          <?php endforeach; ?>
        </div>

        <!-- Sort -->
        <select id="sort-select" class="search-select" style="width:auto;min-width:160px" onchange="applyFilters()">
          <option value="popular">Most Popular</option>
          <option value="price-low">Price: Low &rarr; High</option>
          <option value="price-high">Price: High &rarr; Low</option>
          <option value="duration-short">Duration: Shortest</option>
          <option value="duration-long">Duration: Longest</option>
        </select>
      </div>

      <div class="mt-3 pt-3 border-t border-white/[.05] flex items-center justify-between">
        <span id="result-count" class="text-[.72rem] text-white/35 font-nav">Showing <?= $totalTours ?> safaris</span>
        <button onclick="resetFilters()" class="text-[.72rem] text-emerald-400 hover:text-emerald-300 transition-colors font-nav">
          <i class="fas fa-rotate-left text-[.6rem] mr-1"></i>Reset
        </button>
      </div>
    </div>
  </div>
</section>

<!-- --------------- TOUR CARDS - 3-Column Grid --------------- -->
<section class="relative z-10 py-12 px-4 xl:px-8">

  <div id="tours-list" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;max-width:1600px;margin:0 auto">

    <?php foreach ($tours as $i => $tour):
      [$diff, $diffBg, $diffColor] = tdiff($tour['tour_type'] ?? '');
      $badge = cardBadge($tour, $i);
      preg_match('/\d+/', $tour['duration'] ?? '', $dm);
      $days = (int)($dm[0] ?? 0);
      $slug  = e($tour['slug']);
      $waMsg = urlencode('Hi! I\'m interested in ' . $tour['name'] . '. Please share more details.');
      $hls   = array_filter(explode('|', $tour['highlights'] ?? ''));
    ?>

    <!-- TOUR CARD -->
    <div class="safari-card-v reveal"
         data-name="<?= e(strtolower($tour['name'])) ?>"
         data-type="<?= e(strtolower($tour['tour_type'] ?? '')) ?>"
         data-price="<?= (int)$tour['price'] ?>"
         data-duration="<?= $days ?>"
         data-rating="<?= (float)$tour['rating'] ?>"
         style="transition-delay:<?= ($i % 3) * 80 ?>ms;background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;transition:all .4s cubic-bezier(.4,0,.2,1)">

      <!-- -- IMAGE -- -->
      <div style="position:relative;height:230px;overflow:hidden;flex-shrink:0">
        <img src="<?= e($tour['image']) ?>" alt="<?= e($tour['name']) ?>"
             loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" width="600" height="400"
             style="width:100%;height:100%;object-fit:cover;transition:transform .6s cubic-bezier(.4,0,.2,1)" class="tc-img">

        <!-- Dark gradient overlay -->
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(10,10,10,.85) 0%,rgba(10,10,10,.1) 55%,transparent 100%)"></div>

        <!-- Badge top-left -->
        <?php if ($badge): ?>
        <div style="position:absolute;top:.85rem;left:.85rem;z-index:2;display:flex;align-items:center;gap:.3rem;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:.25rem .7rem;border-radius:999px;background:<?= $badge[2] ?>;color:<?= $badge[3] ?>">
          <i class="fas <?= $badge[4] ?>" style="font-size:.5rem"></i><?= $badge[0] ?>
        </div>
        <?php endif; ?>

        <!-- Duration pill top-right -->
        <div style="position:absolute;top:.85rem;right:.85rem;z-index:2;display:flex;align-items:center;gap:.35rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:#fff;padding:.3rem .7rem;border-radius:999px;background:rgba(0,0,0,.55);backdrop-filter:blur(8px)">
          <i class="fas fa-clock" style="color:#a05e22;font-size:.52rem"></i><?= e($tour['duration']) ?>
        </div>

        <!-- Rating bottom-right on image -->
        <div style="position:absolute;bottom:.85rem;right:.85rem;z-index:2;display:flex;align-items:center;gap:.3rem;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;color:#fff;padding:.28rem .65rem;border-radius:999px;background:rgba(0,0,0,.55);backdrop-filter:blur(8px)">
          <i class="fas fa-star" style="color:#fbbf24;font-size:.55rem"></i><?= e($tour['rating'] ?? '5.0') ?>
        </div>

        <!-- Tour type bottom-left on image -->
        <div style="position:absolute;bottom:.85rem;left:.85rem;z-index:2">
          <span style="display:inline-flex;align-items:center;gap:.3rem;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#c17a3a">
            <i class="fas <?= ticon($tour['tour_type'] ?? '') ?>" style="font-size:.5rem"></i>
            <?= e($tour['tour_type'] ?? 'Safari') ?>
          </span>
        </div>
      </div>

      <!-- -- CONTENT -- -->
      <div style="padding:1.25rem;display:flex;flex-direction:column;flex:1">

        <!-- Destination -->
        <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.6rem">
          <i class="fas fa-map-marker-alt" style="color:#a05e22;font-size:.6rem"></i>
          <span style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:600;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em"><?= e($tour['destination']) ?></span>
          <span style="margin-left:auto;font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.25);padding:.15rem .5rem;border-radius:6px;background:<?= $diffBg ?>;color:<?= $diffColor ?>"><?= $diff ?></span>
        </div>

        <!-- Title -->
        <h2 style="font-family:'Nanum Myeongjo',Georgia,serif;font-size:1.08rem;font-weight:700;color:#fff;line-height:1.3;margin-bottom:.65rem">
          <?= e($tour['name']) ?>
        </h2>

        <!-- Description -->
        <p style="font-size:.8rem;color:rgba(255,255,255,.42);line-height:1.65;margin-bottom:.85rem;flex:1">
          <?= e(truncate($tour['description'], 130)) ?>
        </p>

        <!-- Highlights -->
        <?php if (!empty($hls)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.9rem">
          <?php foreach (array_slice($hls, 0, 3) as $hl): ?>
          <span style="display:inline-flex;align-items:center;gap:.25rem;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:600;padding:.18rem .6rem;border-radius:999px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.45)">
            <i class="fas fa-check" style="color:#a05e22;font-size:.45rem"></i><?= e(trim($hl)) ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick info row -->
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;padding:.65rem .75rem;background:rgba(255,255,255,.03);border-radius:10px;border:1px solid rgba(255,255,255,.05)">
          <?php foreach ([
            ['fa-users','Max '.$tour['max_travelers'].' pax'],
            ['fa-utensils','Full board'],
            ['fa-car','4WD vehicle'],
          ] as $qi): ?>
          <div style="display:flex;align-items:center;gap:.35rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.35);flex:1">
            <i class="fas <?= $qi[0] ?>" style="color:#a05e22;font-size:.55rem;flex-shrink:0"></i><?= $qi[1] ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Price + Actions -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.06)">
          <div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.55rem;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:.08em">From</div>
            <div style="font-family:'Nanum Myeongjo',serif;font-size:1.45rem;font-weight:700;color:#a05e22;line-height:1.1">
              <?= formatPrice($tour['price']) ?>
              <span style="font-family:'Montserrat',sans-serif;font-size:.55rem;font-weight:400;color:rgba(255,255,255,.3)">/person</span>
            </div>
          </div>
          <div style="display:flex;gap:.5rem">
            <a href="<?= url('tour/' . $slug) ?>"
               style="display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;padding:.6rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;text-decoration:none;transition:all .25s;box-shadow:0 4px 14px rgba(160,94,34,.2)"
               class="tc-btn-view">
              <i class="fas fa-binoculars" style="font-size:.6rem"></i> View
            </a>
            <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener"
               style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2);text-decoration:none;transition:all .25s"
               class="tc-btn-wa">
              <i class="fab fa-whatsapp" style="color:#25D366;font-size:.95rem"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Empty state -->
  <div id="empty-state" class="text-center py-20" style="max-width:1600px;margin:0 auto">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(160,94,34,.1)">
      <i class="fas fa-search text-emerald-400 text-xl"></i>
    </div>
    <h3 class="font-heading text-white text-xl mb-2">No safaris found</h3>
    <p class="text-white/40 text-sm mb-5">Try a different search or filter</p>
    <button onclick="resetFilters()" class="inline-flex items-center gap-2 font-nav font-semibold text-sm text-white px-6 py-2.5 rounded-xl" style="background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.2)">
      <i class="fas fa-rotate-left text-xs text-emerald-400"></i> Show All Safaris
    </button>
  </div>

</section>

<style>
.safari-card-v:hover{border-color:rgba(160,94,34,.25)!important;transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.5)}
.safari-card-v:hover .tc-img{transform:scale(1.06)}
.tc-btn-view:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(160,94,34,.3)!important}
.tc-btn-wa:hover{background:rgba(37,211,102,.2)!important;transform:translateY(-1px)}
@media(max-width:1100px){#tours-list{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:640px){#tours-list{grid-template-columns:1fr!important}}
</style>

<!-- --------------- FAQ --------------- -->
<section class="py-20 px-4 lg:px-0">
  <div class="max-w-3xl mx-auto">
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-5">
        <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.2em] uppercase font-nav">FAQ</span>
      </div>
      <h2 class="font-heading text-white font-bold" style="font-size:clamp(1.8rem,4vw,2.6rem)">
        Common <span class="hero-grad">Questions</span>
      </h2>
    </div>

    <div class="space-y-3" id="faq-list">
      <?php foreach ([
        ['What is the best time to go on safari in Tanzania?',
         'The best time is during the dry seasons: June–October and January–February. The Great Migration river crossings happen July–October in the Serengeti. However, Tanzania offers excellent wildlife viewing year-round, with the "green season" (November–May) offering lush scenery and fewer crowds at lower prices.'],
        ['Are your safaris safe for children?',
         'Absolutely. We run family-friendly safaris with experienced guides trained in first aid. Children aged 5 and above are welcome on most game drives. We provide appropriate vehicles, child-sized equipment, and flexible itineraries tailored to family needs. All our camps meet international safety standards.'],
        ['What should I pack for my safari?',
         'We recommend neutral-coloured, lightweight clothing (khaki, olive, beige), a wide-brim hat, sunscreen, insect repellent, binoculars, a camera with zoom lens, and comfortable walking shoes. A light jacket for early morning drives and evenings is essential. We send a detailed packing guide upon booking.'],
        ['Is the Great Migration guaranteed?',
         'While wildlife sightings can never be fully guaranteed, our expert guides know the Serengeti ecosystem intimately and position you at the best crossing points during peak season (July–October). We have a very high success rate. If timing is critical, our team will advise the optimal dates for your visit.'],
        ['Do you offer custom or private safaris?',
         'Yes — all our safaris can be fully customised. We offer private vehicles, bespoke itineraries, special occasion add-ons (honeymoon, birthdays), photography-focused drives, and fly-in options. Contact us via the enquiry form or WhatsApp and we\'ll design the perfect safari just for you.'],
        ['What is included in the safari price?',
         'Our prices include park entrance fees, expert guide, accommodation, all meals (full board), 4WD game vehicle, airport transfers, and flying doctors emergency insurance. International flights, visas, travel insurance, alcoholic beverages, and gratuities are not included.'],
      ] as $i => [$q, $a]): ?>
      <div class="faq-item glass-card overflow-hidden transition-all duration-300">
        <button class="faq-btn w-full flex items-center justify-between gap-4 p-5 text-left" onclick="toggleFaq(this)">
          <span class="font-nav font-semibold text-[.9rem] text-white/85"><?= $q ?></span>
          <span class="faq-icon w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-emerald-400 font-bold text-lg transition-all duration-300"
                style="background:rgba(160,94,34,.12);border:1px solid rgba(160,94,34,.2)">+</span>
        </button>
        <div class="faq-answer" style="max-height:0;overflow:hidden;transition:max-height .4s cubic-bezier(.16,1,.3,1)">
          <p class="px-5 pb-5 text-white/50 text-[.88rem] leading-relaxed"><?= $a ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- --------------- CTA BANNER --------------- -->
<section class="py-6 px-4 lg:px-6">
  <div class="max-w-7xl mx-auto">
    <div class="relative rounded-3xl overflow-hidden" style="min-height:220px">
      <!-- Background image -->
      <img src="<?= IMG_SERENGETI ?>" alt="Safari Tanzania"
           class="absolute inset-0 w-full h-full object-cover" style="opacity:.35">
      <div class="absolute inset-0 rounded-3xl" style="background:linear-gradient(135deg,rgba(5,150,105,.25) 0%,rgba(10,10,10,.85) 60%)"></div>
      <!-- Content -->
      <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 p-8 lg:p-10">
        <div class="max-w-lg">
          <h2 class="font-heading text-white font-bold text-2xl lg:text-3xl leading-snug mb-2">
            Can't Decide? <span class="hero-grad">Let Us Help!</span>
          </h2>
          <p class="text-white/55 text-[.9rem] leading-relaxed">
            Tell us your interests, budget, and dates — and we'll craft the perfect safari just for you.
          </p>
        </div>
        <div class="flex flex-wrap gap-3 flex-shrink-0">
          <a href="<?= url('contact') ?>"
             class="inline-flex items-center gap-2 font-nav font-bold text-sm text-white px-6 py-3 rounded-xl transition-all hover:scale-105 hover:shadow-lg"
             style="background:linear-gradient(135deg,#7d4817,#a05e22);box-shadow:0 4px 16px rgba(160,94,34,.3)">
            <i class="fas fa-compass text-xs"></i> Get Custom Quote
          </a>
          <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= urlencode('Hi! I need help choosing the right safari for me.') ?>"
             target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 font-nav font-bold text-sm px-6 py-3 rounded-xl transition-all hover:scale-105"
             style="color:#25D366;background:rgba(37,211,102,.12);border:1px solid rgba(37,211,102,.3)">
            <i class="fab fa-whatsapp text-base"></i> WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- --------------- FOOTER --------------- -->
<footer class="border-t border-white/[.07] py-14 px-4 lg:px-0 mt-4">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
      <!-- Brand -->
      <div>
        <?php if ($logoUrl): ?>
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" style="width:110px" class="object-contain mb-4 opacity-80">
        <?php else: ?>
          <div class="font-heading font-bold text-white text-lg mb-4"><?= e($siteName) ?></div>
        <?php endif; ?>
        <p class="text-white/40 text-sm leading-relaxed mb-4">Tanzania's premier safari operator crafting extraordinary wildlife adventures since 2009.</p>
        <div class="flex gap-2.5">
          <?php foreach ([['fab fa-facebook-f','#'],['fab fa-instagram','#'],['fab fa-twitter','#'],['fab fa-youtube','#']] as $s): ?>
          <a href="<?= $s[1] ?>" class="w-9 h-9 rounded-full flex items-center justify-center text-white/40 hover:text-emerald-400 transition-all text-sm" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
            <i class="<?= $s[0] ?>"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Quick links -->
      <div>
        <h4 class="font-nav font-bold text-white text-sm uppercase tracking-widest mb-4">Quick Links</h4>
        <ul class="space-y-2.5">
          <?php foreach ($navItems as $key => $item): ?>
          <li><a href="<?= e($item['url']) ?>" class="text-white/45 hover:text-emerald-400 text-sm transition-colors"><?= e($item['mob'] ?? $item['desk'] ?? '') ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <!-- Tour types -->
      <div>
        <h4 class="font-nav font-bold text-white text-sm uppercase tracking-widest mb-4">Safari Types</h4>
        <ul class="space-y-2.5">
          <?php foreach (['Wildlife Safari','Cultural Tour','Trekking','Beach Holiday','Honeymoon Safari','Family Safari'] as $t): ?>
          <li><a href="<?= url('tours?tour_type='.urlencode($t)) ?>" class="text-white/45 hover:text-emerald-400 text-sm transition-colors"><?= $t ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <!-- Contact -->
      <div>
        <h4 class="font-nav font-bold text-white text-sm uppercase tracking-widest mb-4">Contact Us</h4>
        <ul class="space-y-3">
          <li class="flex items-start gap-3 text-white/45 text-sm"><i class="fas fa-map-marker-alt text-emerald-400 mt-0.5 text-xs"></i>Arusha, Tanzania</li>
          <li><a href="tel:<?= e(SITE_PHONE) ?>" class="flex items-start gap-3 text-white/45 hover:text-emerald-400 text-sm transition-colors"><i class="fas fa-phone text-emerald-400 mt-0.5 text-xs"></i><?= e(SITE_PHONE) ?></a></li>
          <li><a href="mailto:<?= e(SITE_EMAIL) ?>" class="flex items-start gap-3 text-white/45 hover:text-emerald-400 text-sm transition-colors"><i class="fas fa-envelope text-emerald-400 mt-0.5 text-xs"></i><?= e(SITE_EMAIL) ?></a></li>
        </ul>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 mt-4 font-nav font-semibold text-xs px-4 py-2 rounded-lg transition-all hover:scale-105"
           style="color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2)">
          <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
        </a>
      </div>
    </div>
    <div class="border-t border-white/[.07] pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-white/25 text-xs font-nav">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
      <div class="flex items-center gap-1.5">
        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
        <p class="text-white/25 text-xs font-nav">Secure booking · SSL encrypted · TATO Licensed</p>
      </div>
    </div>
  </div>
</footer>

<?php require_once 'includes/booking-modal.php'; ?>

<!-- WhatsApp float -->
<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener" aria-label="WhatsApp">
  <i class="fab fa-whatsapp text-2xl relative z-10"></i>
</a>
<button id="back-top" aria-label="Back to top"><i class="fas fa-chevron-up text-sm"></i></button>

<?php require_once 'includes/chatbot-widget.php'; ?>

<script>
(function(){
  'use strict';

  /* Scroll progress (new bar id) */
  const sprg = document.getElementById('scroll-prog');
  window.addEventListener('scroll', () => {
    const maxS = document.documentElement.scrollHeight - innerHeight;
    if (sprg && maxS > 0) sprg.style.width = (scrollY / maxS * 100).toFixed(2) + '%';
  }, { passive: true });
  /* Navbar solid on scroll */
  const navEl = document.getElementById('main-nav');
  window.addEventListener('scroll', () => {
    if (!navEl) return;
    navEl.style.background = scrollY > 60 ? 'rgba(10,10,10,.96)' : '';
    navEl.style.backdropFilter = scrollY > 60 ? 'blur(20px)' : '';
    navEl.style.borderBottom = scrollY > 60 ? '1px solid rgba(160,94,34,.1)' : '';
    navEl.style.boxShadow = scrollY > 60 ? '0 4px 24px rgba(0,0,0,.4)' : '';
  }, { passive: true });
  /* Search overlay */
  const sOv = document.getElementById('nav-search-overlay');
  const sWr = document.getElementById('nav-search-wrap');
  window.openNavSearch  = () => { sOv.style.opacity='1';sOv.style.pointerEvents='all';sWr.style.transform='translateY(0)';document.body.style.overflow='hidden';setTimeout(()=>document.getElementById('nav-search-input')?.focus(),250); };
  window.closeNavSearch = () => { sOv.style.opacity='0';sOv.style.pointerEvents='none';sWr.style.transform='translateY(-16px)';document.body.style.overflow=''; };
  window.doNavSearch    = () => { const q=document.getElementById('nav-search-input')?.value?.trim(); if(q) window.location=(window.SITE_URL||'')+'/tours?search='+encodeURIComponent(q); };
  sOv?.addEventListener('click', e => { if(e.target===sOv) closeNavSearch(); });
  /* Improved mobile menu */
  document.getElementById('mob-close-btn')?.addEventListener('click', closeM);

  const prog = document.getElementById('scroll-progress'); /* legacy compat */
  window.addEventListener('scroll', () => {
    const max = document.documentElement.scrollHeight - innerHeight;
    if (prog && max > 0) prog.style.width = (scrollY / max * 100).toFixed(2) + '%';
  }, { passive: true });

  /* Navbar */
  const nav = document.getElementById('main-nav');
  const updNav = () => nav && nav.classList.toggle('scrolled', scrollY > 60);
  window.addEventListener('scroll', updNav, { passive: true });
  updNav();

  /* Mobile menu */
  const toggle    = document.getElementById('menu-toggle');
  const mobileMenu= document.getElementById('mobile-menu');
  const backdrop  = document.getElementById('mobile-backdrop');
  const t1=document.getElementById('tog-1'), t2=document.getElementById('tog-2'), t3=document.getElementById('tog-3');

  let _mOpen=false;
  function openM() {
    if(!mobileMenu)return;mobileMenu.style.transform='translateX(0)'; backdrop.classList.add('show'); document.body.style.overflow='hidden'; _mOpen=true;
    if(t1){t1.style.transform='rotate(45deg) translate(5px,5px)';t2.style.opacity='0';t3.style.transform='rotate(-45deg) translate(5px,-5px)';}
  }
  function closeM() {
    if(!mobileMenu)return;mobileMenu.style.transform='translateX(100%)'; backdrop.classList.remove('show'); document.body.style.overflow=''; _mOpen=false;
    if(t1){t1.style.transform='';t2.style.opacity='';t3.style.transform='';}
  }
  toggle   && toggle.addEventListener('click', () => _mOpen ? closeM() : openM());
  backdrop && backdrop.addEventListener('click', closeM);
  mobileMenu && mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeM));
  document.addEventListener('keydown', e => { if(e.key==='Escape') closeM(); });
  window.addEventListener('resize', () => { if(innerWidth>=992) closeM(); }, { passive:true });

  /* Back to top */
  const btt = document.getElementById('back-top');
  window.addEventListener('scroll', () => btt && btt.classList.toggle('visible', scrollY > 400), { passive:true });
  btt && btt.addEventListener('click', () => window.scrollTo({ top:0, behavior:'smooth' }));

  /* WhatsApp float */
  const wa = document.getElementById('wa-float');
  if (wa) setTimeout(() => wa.classList.add('visible'), 2500);

  /* Reveal on scroll */
  const revObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('visible'); revObs.unobserve(e.target); } });
  }, { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(el => revObs.observe(el));

  /* -- Filtering & sorting -- */
  let activeFilter = 'all';
  const cards  = Array.from(document.querySelectorAll('.safari-card-v'));
  const countEl= document.getElementById('result-count');
  const emptyEl= document.getElementById('empty-state');

  window.setFilter = function(btn, filter) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = filter;
    applyFilters();
  };

  window.applyFilters = function() {
    const q    = (document.getElementById('search-input').value || '').toLowerCase().trim();
    const sort = document.getElementById('sort-select').value;

    let visible = cards.filter(c => {
      const nameMatch = c.dataset.name.includes(q);
      const typeMatch = activeFilter === 'all' || c.dataset.type === activeFilter;
      return nameMatch && typeMatch;
    });

    /* Sort */
    visible.sort((a, b) => {
      switch(sort) {
        case 'price-low':      return +a.dataset.price    - +b.dataset.price;
        case 'price-high':     return +b.dataset.price    - +a.dataset.price;
        case 'duration-short': return +a.dataset.duration - +b.dataset.duration;
        case 'duration-long':  return +b.dataset.duration - +a.dataset.duration;
        default:               return +b.dataset.rating   - +a.dataset.rating;
      }
    });

    /* Show/hide */
    cards.forEach(c => c.style.display = 'none');
    visible.forEach((c, i) => { c.style.display = ''; c.style.transitionDelay = (i * 50) + 'ms'; });

    if (countEl) countEl.textContent = 'Showing ' + visible.length + ' safari' + (visible.length !== 1 ? 's' : '');
    if (emptyEl)  emptyEl.style.display = visible.length === 0 ? 'block' : 'none';
  };

  window.resetFilters = function() {
    document.getElementById('search-input').value = '';
    document.getElementById('sort-select').value  = 'popular';
    document.querySelectorAll('.filter-btn').forEach((b, i) => { if(i===0) b.classList.add('active'); else b.classList.remove('active'); });
    activeFilter = 'all';
    applyFilters();
  };

  /* FAQ accordion */
  window.toggleFaq = function(btn) {
    const item   = btn.closest('.faq-item');
    const answer = item.querySelector('.faq-answer');
    const icon   = item.querySelector('.faq-icon');
    const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';
    /* Close all */
    document.querySelectorAll('.faq-item').forEach(el => {
      el.querySelector('.faq-answer').style.maxHeight = '0px';
      el.querySelector('.faq-icon').textContent = '+';
      el.querySelector('.faq-icon').style.transform = '';
    });
    if (!isOpen) {
      answer.style.maxHeight = answer.scrollHeight + 'px';
      icon.textContent = '-';
      icon.style.transform = 'rotate(45deg)';
    }
  };

  /* Itinerary toggle */
  window.toggleItinerary = function(btn) {
    const detail = btn.nextElementSibling;
    const chev   = btn.querySelector('.fa-chevron-down');
    const open   = detail.classList.toggle('expanded');
    if (chev) chev.style.transform = open ? 'rotate(180deg)' : '';
  };

})();
</script>

<!-- Lenis Smooth Scroll -->
<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
<script>
(function(){
  const lenis = new Lenis({ duration:1.3, easing:function(t){return Math.min(1,1.001-Math.pow(2,-10*t));}, smooth:true, mouseMultiplier:0.8, smoothTouch:false });
  function raf(t){ lenis.raf(t); requestAnimationFrame(raf); }
  requestAnimationFrame(raf);
  window._lenis = lenis;
})();
</script>
</body>
</html>








