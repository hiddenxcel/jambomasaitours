<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { redirect(url('tours.php')); }

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM tours WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$tour = $stmt->fetch();
if (!$tour) { http_response_code(404); redirect(url('tours.php')); }

$highlights  = $tour['highlights'] ? explode('|', $tour['highlights']) : [];
/* Same destination first, then fill from all other tours up to 10 */
$relStmt = $db->prepare("SELECT * FROM tours WHERE destination = ? AND id != ? ORDER BY featured DESC, rating DESC LIMIT 5");
$relStmt->execute([$tour['destination'], $tour['id']]);
$related = $relStmt->fetchAll();
if (count($related) < 5) {
    $exclude = array_merge([$tour['id']], array_column($related, 'id'));
    $ph      = implode(',', array_fill(0, count($exclude), '?'));
    $more    = $db->prepare("SELECT * FROM tours WHERE id NOT IN ($ph) ORDER BY featured DESC, rating DESC LIMIT ".(10 - count($related)));
    $more->execute($exclude);
    $related = array_merge($related, $more->fetchAll());
}

/* Tour photos */
try {
    $pStmt = $db->prepare("SELECT * FROM tour_photos WHERE tour_id=? ORDER BY sort_order ASC, id ASC");
    $pStmt->execute([$tour['id']]);
    $tourPhotos = $pStmt->fetchAll();
} catch (\Throwable $e) { $tourPhotos = []; }

$logoUrl     = getSetting('logo_url');
$logoWidth   = (int)(getSetting('logo_width','160') ?: 160);
$siteName    = getSetting('site_name','Jambo Masai Tours');
$siteTagline = getSetting('site_tagline','Tanzania Safari Experts');
$currentPage = 'tours';

$navItems = [
    'home'         => ['url' => url(),                   'label' => 'Home'],
    'tours'        => ['url' => url('tours.php'),        'label' => 'Safari Tours'],
    'destinations' => ['url' => url('destinations.php'), 'label' => 'Destinations'],
    'about'        => ['url' => url('about.php'),        'label' => 'About Us'],
    'gallery'      => ['url' => url('gallery.php'),      'label' => 'Gallery'],
    'blog'         => ['url' => url('blog.php'),         'label' => 'Blog'],
    'contact'      => ['url' => url('contact.php'),      'label' => 'Contact'],
];

preg_match('/\d+/', $tour['duration'] ?? '', $dm);
$days = (int)($dm[0] ?? 0);
$waMsg = urlencode('Hi! I am interested in the ' . $tour['name'] . ' tour. Please share more details.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <?php
  $tourCanonical = SITE_URL . '/tour/' . $tour['slug'];
  $tourDesc      = truncate(strip_tags($tour['description']), 160);
  $logoUrl_seo   = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
  ?>
  <title><?= e($tour['name']) ?> | Luxury Safari Tanzania — Jambo Masai Tours</title>
  <meta name="description" content="<?= e($tourDesc) ?>">
  <link rel="canonical" href="<?= e($tourCanonical) ?>">
  <meta property="og:title"       content="<?= e($tour['name']) ?> | Jambo Masai Tours">
  <meta property="og:description" content="<?= e($tourDesc) ?>">
  <meta property="og:image"       content="<?= e($tour['image']) ?>">
  <meta property="og:url"         content="<?= e($tourCanonical) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="Jambo Masai Tours">
  <script type="application/ld+json">
  [
    {
      "@context": "https://schema.org",
      "@type": "TouristTrip",
      "name": <?= json_encode(strip_tags($tour['name'])) ?>,
      "url": "<?= $tourCanonical ?>",
      "description": <?= json_encode(strip_tags($tourDesc)) ?>,
      "image": ["<?= e($tour['image']) ?>"],
      "touristType": ["Luxury Safari", "Adventure Travel", "Wildlife Safari"],
      "offers": {
        "@type": "Offer",
        "price": "<?= e($tour['price'] ?? '0') ?>",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock",
        "url": "<?= SITE_URL ?>/booking.php?tour=<?= e($tour['slug']) ?>"
      },
      "provider": {
        "@type": "TravelAgency",
        "name": "Jambo Masai Tours",
        "url": "<?= SITE_URL ?>"
      }<?php if (!empty($tour['rating'])): ?>,
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?= e($tour['rating']) ?>",
        "bestRating": "5",
        "ratingCount": "<?= (int)($tour['review_count'] ?? 25) ?>"
      }<?php endif; ?>
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home",         "item": "<?= SITE_URL ?>" },
        { "@type": "ListItem", "position": 2, "name": "Safari Tours", "item": "<?= SITE_URL ?>/tours.php" },
        { "@type": "ListItem", "position": 3, "name": <?= json_encode(strip_tags($tour['name'])) ?> }
      ]
    },
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": <?= json_encode('What is included in the ' . strip_tags($tour['name']) . '?') ?>,
          "acceptedAnswer": { "@type": "Answer", "text": "All accommodations, full board meals, national park entry fees, professional English-speaking guide, airport transfers, and game drives in a private 4WD Land Cruiser. International flights and travel insurance are not included." }
        },
        {
          "@type": "Question",
          "name": <?= json_encode('When is the best time for the ' . strip_tags($tour['name']) . '?') ?>,
          "acceptedAnswer": { "@type": "Answer", "text": "June to October is peak season with optimal wildlife viewing during the dry season. January to March offers the calving season spectacle with fewer crowds. The safari operates year-round — each season offers different highlights." }
        },
        {
          "@type": "Question",
          "name": <?= json_encode('How long does the ' . strip_tags($tour['name']) . ' last?') ?>,
          "acceptedAnswer": { "@type": "Answer", "text": <?= json_encode(strip_tags($tour['duration'] ?? 'Please contact us for duration details.') . ' Our team can customise the itinerary to suit your schedule and interests.') ?> }
        },
        {
          "@type": "Question",
          "name": <?= json_encode('Is the ' . strip_tags($tour['name']) . ' suitable for beginners?') ?>,
          "acceptedAnswer": { "@type": "Answer", "text": "Yes — this safari is suitable for all experience levels. Our KINAPA-certified guides handle all logistics. No prior safari experience is required. Children are welcome on game drives." }
        }
      ]
    }
  ]
  </script>
  <link rel="preconnect" href="https://cdn.tailwindcss.com"><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><link rel="dns-prefetch" href="https://images.unsplash.com"><script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors:{ brand:'#a05e22', safari:'#a05e22', dark:'#23362f' },
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
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <link rel="manifest" href="<?= e(SITE_URL) ?>/manifest.json">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#2c463d}::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}
    .hero-grad{background:linear-gradient(135deg,#c17a3a,#7d4817,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.35);opacity:0}}
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#a05e22;font-size:1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}
    .reveal{opacity:0;transform:translateY(20px);transition:all .6s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}
    .related-card{background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;transition:all .3s}
    .related-card:hover{transform:translateY(-4px);border-color:rgba(160,94,34,.25)}
    .related-card img{width:100%;height:180px;object-fit:cover;transition:transform .5s}
    .related-card:hover img{transform:scale(1.05)}
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:0;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.08) 0%,transparent 70%)}
  </style>

</head>
<body class="bg-dark">

<div class="glow-orb glow-orb-1" aria-hidden="true"></div>

<?php require_once 'includes/public_navbar.php'; ?>

<!-- HERO — Smart mosaic based on photo count -->
<?php
$photoCount = count($tourPhotos);
$totalAll   = $photoCount + 1; // +1 main tour image
?>
<style>
  .th-wrap{position:relative;background:#23362f}
  /* Single image (no extras) */
  .th-single{height:460px;overflow:hidden;position:relative}
  .th-single img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
  .th-single:hover img{transform:scale(1.02)}
  /* 2-col mosaic (2+ photos) */
  .th-mosaic{display:grid;height:460px;gap:4px}
  .th-mosaic.p-2{grid-template-columns:1.6fr 1fr}
  .th-mosaic.p-3{grid-template-columns:1.6fr 1fr;grid-template-rows:1fr 1fr}
  .th-mosaic.p-4,.th-mosaic.p-5plus{grid-template-columns:1.6fr 1fr;grid-template-rows:1fr 1fr}
  .th-main{overflow:hidden;position:relative;cursor:pointer}
  .th-mosaic.p-3 .th-main,.th-mosaic.p-4 .th-main,.th-mosaic.p-5plus .th-main{grid-row:1/3}
  .th-sm{overflow:hidden;position:relative;cursor:pointer}
  .th-main img,.th-sm img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.4,0,.2,1)}
  .th-main:hover img,.th-sm:hover img{transform:scale(1.05)}
  .th-overlay-dark{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,10,10,.8) 0%,transparent 55%)}
  .th-see-all{position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.4rem;transition:background .3s;backdrop-filter:blur(2px)}
  .th-see-all:hover{background:rgba(0,0,0,.7)}
  /* Photo count badge */
  .th-count-btn{position:absolute;bottom:1rem;right:1rem;z-index:10;display:flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;padding:.5rem 1rem;border-radius:10px;background:rgba(10,10,10,.75);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;cursor:pointer;transition:all .25s;text-decoration:none}
  .th-count-btn:hover{background:rgba(10,10,10,.92);border-color:rgba(255,255,255,.3)}
  @media(max-width:639px){
    .th-single{height:300px}
    .th-mosaic{height:300px}
    .th-sm{display:none}
    .th-mosaic{grid-template-columns:1fr!important;grid-template-rows:1fr!important}
    .th-mosaic .th-main{grid-row:1!important}
  }
</style>

<div class="th-wrap pt-[68px]">

<?php if ($photoCount === 0): ?>
  <!-- â”€â”€ Single hero image â”€â”€ -->
  <div class="th-single" <?= $totalAll > 1 ? 'onclick="openGallery(0)" style="cursor:pointer"' : '' ?>>
    <img src="<?= e($tour['image']) ?>" alt="<?= e($tour['name']) ?>" fetchpriority="high">
    <div class="th-overlay-dark"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,.4),transparent 60%)"></div>
  </div>

<?php else:
  /* Limit small photos shown in grid */
  $smShow   = min($photoCount, 4);
  $smPhotos = array_slice($tourPhotos, 0, $smShow);
  $pClass   = $photoCount === 1 ? 'p-2' : ($photoCount === 2 ? 'p-3' : ($photoCount === 3 ? 'p-4' : 'p-5plus'));
?>
  <!-- â”€â”€ Mosaic grid â”€â”€ -->
  <div class="th-mosaic <?= $pClass ?>">
    <!-- Main large image -->
    <div class="th-main" onclick="openGallery(0)">
      <img src="<?= e($tour['image']) ?>" alt="<?= e($tour['name']) ?>" fetchpriority="high">
      <div class="th-overlay-dark"></div>
    </div>
    <!-- Small images -->
    <?php foreach ($smPhotos as $si => $sp):
      $isLast = ($si === $smShow - 1) && ($totalAll > 5);
    ?>
    <div class="th-sm" onclick="openGallery(<?= $si + 1 ?>)">
      <img src="<?= e($sp['image']) ?>" alt="<?= e($sp['caption'] ?: '') ?>" loading="lazy">
      <?php if ($isLast): ?>
      <div class="th-see-all">
        <i class="fas fa-images" style="font-size:1.4rem;color:rgba(255,255,255,.8)"></i>
        <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:700;color:#fff">+<?= $totalAll - 5 ?> more</span>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <!-- "See all photos" button -->
  <button onclick="openGallery(0)" class="th-count-btn">
    <i class="fas fa-images" style="font-size:.8rem"></i>
    See all <?= $totalAll ?> photos
  </button>
<?php endif; ?>

  <!-- Tour info below hero -->
  <div class="max-w-7xl mx-auto px-4 lg:px-6 pt-6 pb-2">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-3">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>&#8250;</span>
      <a href="<?= url('tours.php') ?>" class="hover:text-white transition-colors">Safari Tours</a><span>&#8250;</span>
      <span class="text-white/60"><?= e($tour['name']) ?></span>
    </nav>
    <div class="flex flex-wrap gap-2 mb-3">
      <span class="font-nav text-[.62rem] font-semibold px-3 py-1 rounded-full" style="background:rgba(160,94,34,.15);color:#c17a3a;border:1px solid rgba(160,94,34,.25)"><?= e($tour['tour_type']) ?></span>
      <span class="font-nav text-[.62rem] font-semibold px-3 py-1 rounded-full" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)"><?= e($tour['destination']) ?></span>
      <span class="font-nav text-[.62rem] font-semibold px-3 py-1 rounded-full flex items-center gap-1" style="background:rgba(245,158,11,.12);color:#fbbf24">
        <i class="fas fa-star text-[.55rem]"></i><?= e($tour['rating']) ?>/5
      </span>
    </div>
    <h1 class="font-heading text-white font-bold leading-tight" style="font-size:clamp(1.9rem,4vw,3rem)">
      <?= e($tour['name']) ?>
    </h1>
  </div>
</div>

<!-- Gallery lightbox (photos) -->
<?php if ($photoCount > 0): ?>
<div id="gallery-lb" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.97);display:none;flex-direction:column;align-items:center;justify-content:center;padding:1rem">
  <button onclick="closeGallery()" style="position:absolute;top:1.2rem;right:1.2rem;background:rgba(255,255,255,.1);border:none;color:#fff;width:42px;height:42px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center" aria-label="Close"><i class="fas fa-times"></i></button>
  <button id="glb-prev" onclick="galleryNav(-1)" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.1);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center">&#8249;</button>
  <button id="glb-next" onclick="galleryNav(1)"  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.1);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center">&#8250;</button>
  <img id="glb-img" src="" alt="" style="max-width:90vw;max-height:80vh;border-radius:10px;object-fit:contain">
  <div style="margin-top:.75rem;display:flex;align-items:center;gap:.75rem">
    <span id="glb-counter" class="font-nav text-[.72rem] text-white/45"></span>
    <span id="glb-caption" class="font-nav text-[.78rem] text-white/65"></span>
  </div>
  <!-- Thumbnail strip -->
  <div id="glb-thumbs" style="display:flex;gap:.4rem;margin-top:.75rem;overflow-x:auto;max-width:90vw;padding:.2rem 0">
    <!-- Main tour image -->
    <img src="<?= e($tour['image']) ?>" onclick="galleryGoTo(0)" alt="main"
         style="width:56px;height:40px;object-fit:cover;border-radius:5px;cursor:pointer;opacity:.55;transition:opacity .2s;border:2px solid transparent;flex-shrink:0" class="glb-thumb">
    <?php foreach ($tourPhotos as $pi => $p): ?>
    <img src="<?= e($p['image']) ?>" onclick="galleryGoTo(<?= $pi+1 ?>)" alt="<?= e($p['caption']) ?>"
         style="width:56px;height:40px;object-fit:cover;border-radius:5px;cursor:pointer;opacity:.55;transition:opacity .2s;border:2px solid transparent;flex-shrink:0" class="glb-thumb">
    <?php endforeach; ?>
  </div>
</div>

<script>
var galleryImages = [
  { src: '<?= addslashes(e($tour['image'])) ?>', caption: '<?= addslashes(e($tour['name'])) ?>' },
  <?php foreach ($tourPhotos as $p): ?>
  { src: '<?= addslashes(e($p['image'])) ?>', caption: '<?= addslashes(e($p['caption'])) ?>' },
  <?php endforeach; ?>
];
var galCur = 0;
function openGallery(idx) {
  document.getElementById('gallery-lb').style.display='flex';
  document.body.style.overflow='hidden';
  galleryGoTo(idx);
}
function closeGallery() {
  document.getElementById('gallery-lb').style.display='none';
  document.body.style.overflow='';
}
function galleryGoTo(idx) {
  galCur = ((idx % galleryImages.length) + galleryImages.length) % galleryImages.length;
  document.getElementById('glb-img').src = galleryImages[galCur].src;
  document.getElementById('glb-counter').textContent = (galCur+1) + ' / ' + galleryImages.length;
  document.getElementById('glb-caption').textContent = galleryImages[galCur].caption || '';
  document.querySelectorAll('.glb-thumb').forEach((t,i) => {
    t.style.opacity = i===galCur ? '1' : '.5';
    t.style.borderColor = i===galCur ? '#a05e22' : 'transparent';
  });
}
function galleryNav(dir) { galleryGoTo(galCur + dir); }
document.getElementById('gallery-lb')?.addEventListener('click', e => { if(e.target===document.getElementById('gallery-lb')) closeGallery(); });
document.addEventListener('keydown', e => {
  if(document.getElementById('gallery-lb').style.display==='flex') {
    if(e.key==='Escape') closeGallery();
    if(e.key==='ArrowLeft') galleryNav(-1);
    if(e.key==='ArrowRight') galleryNav(1);
  }
});
</script>
<?php endif; ?>

<!-- MAIN CONTENT -->
<div class="max-w-7xl mx-auto px-4 lg:px-6 py-12">
  <div class="grid lg:grid-cols-[1fr_340px] gap-10 items-start">

    <!-- Left: Details -->
    <div>

      <!-- Quick meta bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-10 reveal">
        <?php foreach ([
          ['fa-clock',         '#c17a3a', 'Duration',    e($tour['duration'])],
          ['fa-users',         '#fbbf24', 'Group Size',  'Max '.e($tour['max_travelers'])],
          ['fa-map-marker-alt','#60a5fa', 'Destination', e($tour['destination'])],
          ['fa-compass',       '#f97316', 'Type',        e($tour['tour_type'])],
        ] as $m): ?>
        <div class="glass-card p-4 text-center">
          <i class="fas <?= $m[0] ?> text-lg mb-2" style="color:<?= $m[1] ?>"></i>
          <div class="font-nav text-[.6rem] uppercase tracking-wider text-white/30 mb-1"><?= $m[2] ?></div>
          <div class="text-white font-semibold text-[.85rem]"><?= $m[3] ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Description -->
      <div class="mb-10 reveal">
        <h2 class="font-heading text-white text-2xl font-bold mb-4">About This Safari</h2>
        <p class="text-white/60 leading-[1.9] text-[.95rem]"><?= e($tour['description']) ?></p>
      </div>

      <!-- Highlights from DB -->
      <?php if ($highlights): ?>
      <div class="mb-10 reveal">
        <h3 class="font-heading text-white text-xl font-bold mb-5">Tour Highlights</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php foreach ($highlights as $h): ?>
          <div class="flex items-start gap-3 p-4 rounded-xl" style="background:rgba(160,94,34,.06);border:1px solid rgba(160,94,34,.12)">
            <i class="fas fa-check-circle text-emerald-400 mt-0.5 flex-shrink-0 text-sm"></i>
            <span class="text-white/75 text-[.88rem]"><?= e(trim($h)) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Included / Not included — from DB or defaults -->
      <?php
      $incItems = !empty($tour['included'])
          ? array_filter(array_map('trim', explode('|', $tour['included'])))
          : ['Park entrance fees','Expert safari guide','All accommodation','All meals (full board)','4WD game vehicle','Airport transfers','Flying doctors insurance'];
      $notIncItems = !empty($tour['not_included'])
          ? array_filter(array_map('trim', explode('|', $tour['not_included'])))
          : ['International flights','Visa fees','Travel insurance','Personal expenses','Gratuities','Alcoholic beverages','Optional activities'];
      ?>
      <div class="mb-10 reveal">
        <h3 class="font-heading text-white text-xl font-bold mb-5">What's Included</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <!-- Included -->
          <div class="glass-card p-5">
            <h4 class="font-nav font-bold text-[.68rem] uppercase tracking-widest text-emerald-400 mb-4">
              <i class="fas fa-check-circle mr-2"></i>Included
            </h4>
            <?php foreach ($incItems as $inc): ?>
            <div class="flex items-center gap-3 py-2.5 border-b border-white/[.05] last:border-0">
              <i class="fas fa-check text-emerald-400 text-[.7rem] flex-shrink-0"></i>
              <span class="text-white/60 text-[.85rem]"><?= e(trim($inc)) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <!-- Not included -->
          <div class="glass-card p-5">
            <h4 class="font-nav font-bold text-[.68rem] uppercase tracking-widest text-red-400 mb-4">
              <i class="fas fa-times-circle mr-2"></i>Not Included
            </h4>
            <?php foreach ($notIncItems as $exc): ?>
            <div class="flex items-center gap-3 py-2.5 border-b border-white/[.05] last:border-0">
              <i class="fas fa-times text-red-400/70 text-[.7rem] flex-shrink-0"></i>
              <span class="text-white/60 text-[.85rem]"><?= e(trim($exc)) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- â”€â”€ Photos section â”€â”€ -->
      <?php if (!empty($tourPhotos)): ?>
      <div class="mb-10 reveal">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-heading text-white text-xl font-bold flex items-center gap-2">
            <i class="fas fa-images text-emerald-400 text-base"></i> Photos
          </h3>
          <button onclick="openGallery(0)"
                  class="inline-flex items-center gap-1.5 font-nav text-[.7rem] font-semibold text-white/50 hover:text-emerald-400 transition-colors">
            See all <?= count($tourPhotos) + 1 ?> photos <i class="fas fa-arrow-right text-xs"></i>
          </button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;border-radius:14px;overflow:hidden">
          <?php foreach (array_slice($tourPhotos,0,6) as $pi => $p): ?>
          <div style="overflow:hidden;cursor:pointer;aspect-ratio:4/3;position:relative" onclick="openGallery(<?= $pi+1 ?>)" class="group">
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['caption'] ?: $tour['name']) ?>"
                 loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.06]">
            <?php if ($p['caption']): ?>
            <div class="absolute inset-0 flex items-end p-2 opacity-0 group-hover:opacity-100 transition-opacity"
                 style="background:linear-gradient(to top,rgba(0,0,0,.7),transparent)">
              <p class="text-white text-[.65rem] font-nav line-clamp-1"><?= e($p['caption']) ?></p>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($tourPhotos) > 6): ?>
        <button onclick="openGallery(0)"
                class="mt-3 w-full py-2.5 rounded-xl font-nav font-semibold text-[.75rem] text-white/50 hover:text-white transition-all"
                style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
          <i class="fas fa-images text-emerald-400 mr-1.5 text-xs"></i>
          View all <?= count($tourPhotos) + 1 ?> photos
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- â”€â”€ Itinerary — tabbed day view â”€â”€ -->
      <?php
      try {
          $itinStmt = $db->prepare("SELECT * FROM tour_itinerary WHERE tour_id=? ORDER BY day_number ASC");
          $itinStmt->execute([$tour['id']]);
          $dbItin = $itinStmt->fetchAll();
      } catch (\Throwable $e) { $dbItin = []; }

      $dcPalette = [
          ['#a05e22','rgba(160,94,34,.18)'],['#f59e0b','rgba(245,158,11,.18)'],
          ['#3b82f6','rgba(59,130,246,.18)'],['#8b5cf6','rgba(139,92,246,.18)'],
          ['#f97316','rgba(249,115,22,.18)'],['#ec4899','rgba(236,72,153,.18)'],
          ['#06b6d4','rgba(6,182,212,.18)'],
      ];

      /* Auto-generate if no DB data */
      if (empty($dbItin) && $days > 0) {
          $dest = $tour['destination'] ?? 'Tanzania';
          $titles = ['Arrival & Transfer','Full Day Game Drive','Morning & Evening Safari','Cultural Experience','Nature Walk & Exploration','Departure Day'];
          $descs  = [
              'Welcome to Tanzania! Transfer to your lodge in '.$dest.'. Enjoy an afternoon game drive to settle in and spot the first wildlife.',
              'Full day in the '.$dest.' ecosystem. Track the Big Five with expert guides, enjoy a bush lunch and golden-hour game drive.',
              'Sunrise game drive followed by a bush breakfast. Afternoon at leisure before an evening game drive.',
              'Visit a local Maasai village — experience traditional dance, crafts and community life.',
              'Guided walking safari with a Maasai tracker. Learn to read animal tracks and discover medicinal plants.',
              'Early morning final game drive. Farewell breakfast, then transfer to the airport. Safari memories for life.',
          ];
          for ($d = 1; $d <= $days; $d++) {
              $ti = $d === 1 ? 0 : ($d === $days ? 5 : (($d - 2) % 4) + 1);
              $dbItin[] = [
                  'id' => 0, 'tour_id' => $tour['id'], 'day_number' => $d,
                  'title' => $d.' — '.$titles[$ti], 'description' => $descs[$ti],
                  'departure_location'=>'', 'arrival_location'=>'', 'distance'=>'', 'travel_time'=>'',
                  'accommodation'=>'', 'hotel_url'=>'', 'hotel_image'=>'',
                  'meals' => ($d === 1) ? 'Dinner' : ($d === $days ? 'Breakfast' : 'Breakfast, Lunch, Dinner'),
                  'highlights' => '', 'notes' => '',
              ];
          }
      }
      ?>

      <?php if (!empty($dbItin)): ?>
      <div class="mb-10 reveal" id="itinerary-section">
        <!-- Header row -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.2rem">
          <h3 class="font-heading text-white text-xl font-bold">
            <?= count($dbItin) ?>-Day Itinerary
          </h3>
          <div style="display:flex;align-items:center;gap:.6rem">
            <button id="itin-prev" onclick="itinGoTo(itinCur-1)"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white/50 hover:text-white transition-all text-xs"
                    style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">&#8249;</button>
            <span id="itin-counter" class="font-nav text-[.72rem] text-white/40"></span>
            <button id="itin-next" onclick="itinGoTo(itinCur+1)"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white/50 hover:text-white transition-all text-xs"
                    style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">&#8250;</button>
          </div>
        </div>

        <!-- Day tab bar -->
        <div style="overflow-x:auto;padding-bottom:.4rem;margin-bottom:1.2rem">
          <div style="display:flex;gap:.3rem;min-width:max-content">
            <?php foreach ($dbItin as $idx => $d):
              [$clr] = $dcPalette[($d['day_number']-1)%count($dcPalette)];
            ?>
            <button class="itin-tab font-nav font-semibold text-[.68rem] px-3.5 py-2 rounded-lg transition-all whitespace-nowrap"
                    style="background:rgba(255,255,255,.05);color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.08)"
                    data-idx="<?= $idx ?>"
                    onclick="itinGoTo(<?= $idx ?>)">
              Day <?= $d['day_number'] ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Day panels -->
        <?php foreach ($dbItin as $idx => $d):
          [$clr,$clrBg] = $dcPalette[($d['day_number']-1)%count($dcPalette)];
          $hls  = array_filter(explode('|', $d['highlights'] ?? ''));
          $meals= array_filter(array_map('trim', explode(',', $d['meals'] ?? '')));
        ?>
        <div class="itin-panel" data-idx="<?= $idx ?>" style="display:<?= $idx===0?'block':'none' ?>">

          <!-- Hotel image -->
          <?php if (!empty($d['hotel_image'])): ?>
          <div class="relative rounded-2xl overflow-hidden mb-5" style="height:260px">
            <img src="<?= e($d['hotel_image']) ?>" alt="<?= e($d['accommodation'] ?? 'Accommodation') ?>"
                 class="w-full h-full object-cover" loading="lazy">
            <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.8) 0%,transparent 55%)"></div>
            <?php if ($d['accommodation']): ?>
            <div class="absolute bottom-0 left-0 right-0 p-4 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(96,165,250,.18)">
                  <i class="fas fa-bed text-[#93c5fd] text-xs"></i>
                </div>
                <div>
                  <p class="text-white font-semibold text-sm leading-tight"><?= e($d['accommodation']) ?></p>
                  <?php if ($d['arrival_location']): ?>
                  <p class="text-white/45 text-[.68rem]"><?= e($d['arrival_location']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (!empty($d['hotel_url'])): ?>
              <a href="<?= e($d['hotel_url']) ?>" target="_blank" rel="noopener"
                 class="inline-flex items-center gap-1.5 font-nav font-semibold text-[.65rem] px-3 py-1.5 rounded-lg transition-all hover:scale-105"
                 style="background:rgba(255,255,255,.12);color:#fff;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15)">
                View Hotel <i class="fas fa-external-link-alt text-[.5rem]"></i>
              </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Route info bar -->
          <?php if ($d['departure_location'] || $d['distance'] || $d['travel_time'] || $d['arrival_location']): ?>
          <div class="flex flex-wrap items-center gap-2 mb-4 p-3 rounded-xl" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06)">
            <?php if ($d['departure_location']): ?>
            <span class="inline-flex items-center gap-1.5 text-[.72rem] font-nav font-semibold text-white/60">
              <i class="fas fa-plane-departure text-emerald-400 text-[.6rem]"></i><?= e($d['departure_location']) ?>
            </span>
            <?php endif; ?>
            <?php if ($d['distance'] || $d['travel_time']): ?>
            <div class="flex items-center gap-1.5 text-white/25">
              <div class="w-6 h-px bg-white/15"></div>
              <i class="fas fa-long-arrow-alt-right text-xs"></i>
              <?php if ($d['distance']): ?>
              <span class="inline-flex items-center gap-1 text-[.68rem] font-nav px-2 py-0.5 rounded-full" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.5)">
                <i class="fas fa-road text-[.55rem] text-emerald-400/60"></i><?= e($d['distance']) ?>
              </span>
              <?php endif; ?>
              <?php if ($d['travel_time']): ?>
              <span class="inline-flex items-center gap-1 text-[.68rem] font-nav px-2 py-0.5 rounded-full" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.5)">
                <i class="fas fa-clock text-[.55rem] text-amber-400/60"></i><?= e($d['travel_time']) ?>
              </span>
              <?php endif; ?>
              <i class="fas fa-long-arrow-alt-right text-xs"></i>
              <div class="w-6 h-px bg-white/15"></div>
            </div>
            <?php endif; ?>
            <?php if ($d['arrival_location']): ?>
            <span class="inline-flex items-center gap-1.5 text-[.72rem] font-nav font-semibold" style="color:<?= $clr ?>">
              <i class="fas fa-map-marker-alt text-[.6rem]"></i><?= e($d['arrival_location']) ?>
            </span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Day header -->
          <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-nav font-bold text-sm flex-shrink-0"
                 style="background:<?= $clrBg ?>;color:<?= $clr ?>;border:1.5px solid <?= $clr ?>30">
              <?= str_pad($d['day_number'],2,'0',STR_PAD_LEFT) ?>
            </div>
            <div>
              <h4 class="font-heading text-white font-bold text-lg leading-snug"><?= e($d['title']) ?></h4>
            </div>
          </div>

          <!-- Description -->
          <?php if ($d['description']): ?>
          <p class="text-white/55 text-[.92rem] leading-[1.85] mb-5"><?= e($d['description']) ?></p>
          <?php endif; ?>

          <!-- Meals + accommodation (if no hotel image) -->
          <?php if (!empty($meals) || (!empty($d['accommodation']) && empty($d['hotel_image']))): ?>
          <div class="flex flex-wrap gap-2 mb-4">
            <?php foreach ($meals as $m): ?>
            <span class="inline-flex items-center gap-1.5 text-[.7rem] font-nav font-semibold px-3 py-1.5 rounded-full" style="background:rgba(245,158,11,.1);color:#fbbf24;border:1px solid rgba(245,158,11,.18)">
              <i class="fas fa-utensils text-[.55rem]"></i><?= e($m) ?>
            </span>
            <?php endforeach; ?>
            <?php if (!empty($d['accommodation']) && empty($d['hotel_image'])): ?>
            <span class="inline-flex items-center gap-1.5 text-[.7rem] font-nav font-semibold px-3 py-1.5 rounded-full" style="background:rgba(96,165,250,.1);color:#93c5fd;border:1px solid rgba(96,165,250,.18)">
              <i class="fas fa-bed text-[.55rem]"></i>
              <?php if (!empty($d['hotel_url'])): ?>
              <a href="<?= e($d['hotel_url']) ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none">
                <?= e($d['accommodation']) ?> <i class="fas fa-external-link-alt" style="font-size:.45rem;opacity:.6"></i>
              </a>
              <?php else: ?>
              <?= e($d['accommodation']) ?>
              <?php endif; ?>
            </span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Activities / highlights -->
          <?php if (!empty($hls)): ?>
          <div class="mb-4">
            <p class="font-nav font-bold text-[.62rem] uppercase tracking-widest text-white/25 mb-2">Activities</p>
            <div class="flex flex-wrap gap-1.5">
              <?php foreach ($hls as $hl): ?>
              <span class="inline-flex items-center gap-1 text-[.7rem] font-nav px-2.5 py-1 rounded-full" style="background:rgba(160,94,34,.08);color:#c17a3a;border:1px solid rgba(160,94,34,.15)">
                <i class="fas fa-check text-[.5rem]"></i><?= e(trim($hl)) ?>
              </span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Notes box -->
          <?php if (!empty($d['notes'])): ?>
          <div class="flex items-start gap-3 p-4 rounded-xl" style="background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2)">
            <i class="fas fa-exclamation-triangle text-amber-400 mt-0.5 flex-shrink-0 text-sm"></i>
            <p class="text-[.84rem] leading-relaxed" style="color:rgba(245,158,11,.85)"><?= e($d['notes']) ?></p>
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
      </div>

      <style>
        .itin-tab.active{color:#fff !important;border-color:rgba(160,94,34,.5) !important;background:rgba(160,94,34,.1) !important}
        .itin-tab:hover:not(.active){color:rgba(255,255,255,.7) !important;border-color:rgba(255,255,255,.18) !important}
      </style>
      <script>
        var itinCur = 0;
        var itinTotal = <?= count($dbItin) ?>;
        function itinGoTo(idx) {
          if (idx < 0) idx = 0;
          if (idx >= itinTotal) idx = itinTotal - 1;
          document.querySelectorAll('.itin-panel').forEach((p,i) => p.style.display = i===idx?'block':'none');
          document.querySelectorAll('.itin-tab').forEach((t,i) => t.classList.toggle('active', i===idx));
          const counter = document.getElementById('itin-counter');
          if (counter) counter.textContent = 'Day '+(idx+1)+' of '+itinTotal;
          itinCur = idx;
          document.getElementById('itin-prev').style.opacity = idx===0?'.3':'1';
          document.getElementById('itin-next').style.opacity = idx===itinTotal-1?'.3':'1';
          /* Scroll tab into view */
          const tabs = document.querySelectorAll('.itin-tab');
          if (tabs[idx]) tabs[idx].scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});
        }
        document.addEventListener('DOMContentLoaded', () => itinGoTo(0));
      </script>
      <?php endif; ?>

    </div>

    <!-- Right: Booking sidebar -->
    <aside class="sticky top-24">
      <div class="glass-card p-6 mb-4">
        <!-- Price -->
        <div class="text-center pb-5 mb-5 border-b border-white/[.07]">
          <p class="font-nav text-[.62rem] uppercase tracking-widest text-white/35 mb-1">Starting from</p>
          <p class="font-heading font-bold text-white leading-none" style="font-size:2.8rem"><?= formatPrice($tour['price']) ?></p>
          <p class="text-white/35 text-xs mt-1 font-nav">per person</p>
        </div>

        <!-- Quick inclusions -->
        <div class="space-y-2.5 mb-5">
          <?php foreach ([
            ['fa-check',         '#c17a3a', 'Expert guides included'],
            ['fa-check',         '#c17a3a', 'All meals (full board)'],
            ['fa-check',         '#c17a3a', 'Park entrance fees'],
            ['fa-check',         '#c17a3a', 'Accommodation included'],
            ['fa-check',         '#c17a3a', 'Airport transfers'],
          ] as $inc): ?>
          <div class="flex items-center gap-2.5 text-[.83rem] text-white/55">
            <i class="fas <?= $inc[0] ?> text-[.65rem] flex-shrink-0" style="color:<?= $inc[1] ?>"></i>
            <?= $inc[2] ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- CTA buttons -->
        <a href="<?= url('booking.php?tour='.e($tour['slug'])) ?>"
           class="flex items-center justify-center gap-2 font-nav font-bold text-sm text-white w-full py-3.5 rounded-xl mb-3 transition-all hover:scale-[1.02] hover:shadow-lg"
           style="background:linear-gradient(135deg,#7d4817,#a05e22);box-shadow:0 4px 18px rgba(160,94,34,.25)">
          <i class="fas fa-calendar-check text-xs"></i> Book This Safari
        </a>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener"
           class="flex items-center justify-center gap-2 font-nav font-semibold text-sm w-full py-3 rounded-xl mb-3 transition-all hover:scale-[1.02]"
           style="color:#25D366;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2)">
          <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
        </a>
        <a href="<?= url('contact.php') ?>"
           class="flex items-center justify-center gap-2 font-nav text-sm text-white/50 hover:text-white w-full py-2.5 rounded-xl transition-all"
           style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
          <i class="fas fa-envelope text-xs"></i> Ask a Question
        </a>

        <p class="text-center text-[.7rem] text-white/25 font-nav mt-4">
          <i class="fas fa-shield-alt text-emerald-400/50 mr-1"></i>
          Free cancellation up to 30 days before departure
        </p>
      </div>

      <!-- Trust badges -->
      <div class="glass-card p-4">
        <div class="grid grid-cols-3 gap-2 text-center">
          <?php foreach ([
            ['fa-star',         '#fbbf24', '4.9/5',     'Rated'],
            ['fa-certificate',  '#c17a3a', 'TATO',      'Certified'],
            ['fa-shield-alt',   '#60a5fa', 'Secure',    'Booking'],
          ] as $b): ?>
          <div>
            <i class="fas <?= $b[0] ?> text-base mb-1" style="color:<?= $b[1] ?>"></i>
            <div class="text-white font-semibold text-[.78rem]"><?= $b[2] ?></div>
            <div class="text-white/30 text-[.6rem] font-nav"><?= $b[3] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

  </div>
</div>

<!-- YOU MIGHT ALSO LIKE — carousel -->
<?php if (!empty($related)): ?>
<section class="py-14 px-4 lg:px-0" style="border-top:1px solid rgba(255,255,255,.06)">
  <div class="max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-7">
      <h2 class="font-heading text-white text-2xl font-bold">
        You Might Also Like
      </h2>
      <div class="flex items-center gap-2">
        <button id="rel-prev" aria-label="Previous"
                class="w-9 h-9 rounded-full flex items-center justify-center transition-all text-white/50 hover:text-white"
                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12)">
          <i class="fas fa-chevron-left text-xs"></i>
        </button>
        <button id="rel-next" aria-label="Next"
                class="w-9 h-9 rounded-full flex items-center justify-center transition-all text-white/50 hover:text-white"
                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12)">
          <i class="fas fa-chevron-right text-xs"></i>
        </button>
      </div>
    </div>

    <!-- Carousel wrapper -->
    <div class="relative overflow-hidden">
      <div id="rel-track"
           style="display:flex;gap:14px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-webkit-overflow-scrolling:touch;scroll-snap-type:x mandatory;padding-bottom:4px">
        <?php foreach ($related as $r): ?>
        <div style="flex:0 0 220px;scroll-snap-align:start"
             class="group">
          <!-- Card -->
          <a href="<?= url('tour-detail.php?slug='.e($r['slug'])) ?>"
             class="block rounded-2xl overflow-hidden transition-all duration-300 group-hover:transform group-hover:-translate-y-1.5"
             style="background:#2c463d;border:1px solid rgba(255,255,255,.07);text-decoration:none">

            <!-- Image -->
            <div class="relative overflow-hidden" style="height:155px">
              <img src="<?= e($r['image']) ?>" alt="<?= e($r['name']) ?>" loading="lazy"
                   width="400" height="280"
                   class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.06]">
              <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.65) 0%,transparent 55%)"></div>
              <!-- Duration pill -->
              <div class="absolute bottom-2 left-2.5 flex items-center gap-1 font-nav text-[.62rem] text-white px-2 py-0.5 rounded-full"
                   style="background:rgba(0,0,0,.55);backdrop-filter:blur(6px)">
                <i class="fas fa-clock text-emerald-400" style="font-size:.5rem"></i>
                <?= e($r['duration']) ?>
              </div>
              <!-- Rating -->
              <?php if (!empty($r['rating'])): ?>
              <div class="absolute bottom-2 right-2.5 flex items-center gap-0.5 font-nav text-[.62rem] text-white px-2 py-0.5 rounded-full"
                   style="background:rgba(0,0,0,.55);backdrop-filter:blur(6px)">
                <i class="fas fa-star text-amber-400" style="font-size:.5rem"></i>
                <?= e($r['rating']) ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="p-3.5">
              <p class="font-nav text-[.58rem] font-bold uppercase tracking-widest text-emerald-400/70 mb-1"><?= e($r['destination']) ?></p>
              <h3 class="font-heading text-white font-bold leading-snug mb-2.5 group-hover:text-emerald-400 transition-colors"
                  style="font-size:.9rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                <?= e($r['name']) ?>
              </h3>
              <div class="flex items-center justify-between pt-2.5" style="border-top:1px solid rgba(255,255,255,.06)">
                <div>
                  <span class="text-white/30 text-[.62rem] font-nav">From </span>
                  <span class="font-heading font-bold text-emerald-400" style="font-size:1rem"><?= formatPrice($r['price']) ?></span>
                </div>
                <span class="font-nav text-[.6rem] font-semibold text-white/40 group-hover:text-emerald-400 transition-colors flex items-center gap-1">
                  View <i class="fas fa-arrow-right text-[.5rem]"></i>
                </span>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Fade edges -->
      <div class="absolute top-0 right-0 bottom-0 w-16 pointer-events-none"
           style="background:linear-gradient(to right,transparent,#23362f)"></div>
    </div>

  </div>
</section>

<style>
#rel-track::-webkit-scrollbar { display: none; }
</style>
<script>
(function(){
  const track = document.getElementById('rel-track');
  const prev  = document.getElementById('rel-prev');
  const next  = document.getElementById('rel-next');
  if (!track) return;
  const step = 234; /* card width + gap */
  prev && prev.addEventListener('click', () => track.scrollBy({ left: -step * 2, behavior: 'smooth' }));
  next && next.addEventListener('click', () => track.scrollBy({ left:  step * 2, behavior: 'smooth' }));
  /* Touch swipe */
  let tx = 0;
  track.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend',   e => {
    const d = tx - e.changedTouches[0].clientX;
    if (Math.abs(d) > 40) track.scrollBy({ left: d > 0 ? step : -step, behavior: 'smooth' });
  }, { passive: true });
})();
</script>
<?php endif; ?>

<!-- Footer -->
<footer class="border-t border-white/[.07] py-8 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
    <span class="font-heading text-white/40 font-bold text-sm"><?= e($siteName) ?></span>
    <p class="text-white/20 text-xs font-nav">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
    <div class="flex gap-4">
      <a href="<?= url('tours.php') ?>" class="text-white/35 hover:text-emerald-400 text-xs font-nav transition-colors">&larr; All Tours</a>
      <a href="<?= url('booking.php?tour='.e($tour['slug'])) ?>" class="text-emerald-400 text-xs font-nav font-semibold transition-colors">Book Safari &rarr;</a>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener"><i class="fab fa-whatsapp text-2xl relative z-10"></i></a>
<button id="back-top" aria-label="Back to top"><i class="fas fa-chevron-up text-sm"></i></button>

<?php require_once 'includes/chatbot-widget.php'; ?>

<script>
(function(){
  const btt=document.getElementById('back-top');
  window.addEventListener('scroll',()=>btt&&btt.classList.toggle('visible',scrollY>400),{passive:true});
  btt&&btt.addEventListener('click',()=>window.scrollTo({top:0,behavior:'smooth'}));
  const wa=document.getElementById('wa-float');
  if(wa)setTimeout(()=>wa.classList.add('visible'),2500);
  const revObs=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');revObs.unobserve(e.target);}}),{threshold:.08});
  document.querySelectorAll('.reveal').forEach(el=>revObs.observe(el));
})();
</script>
</body>
</html>








