<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$db = getDB();

try {
    $tours = $db->query("
        SELECT * FROM tours
        WHERE LOWER(tour_type) LIKE '%trek%'
           OR LOWER(tour_type) LIKE '%mountain%'
           OR LOWER(tour_type) LIKE '%climb%'
           OR LOWER(tour_type) LIKE '%walk%'
           OR LOWER(tour_type) LIKE '%adventure%'
        ORDER BY featured DESC, rating DESC
    ")->fetchAll();
    if (empty($tours)) {
        $tours = $db->query("SELECT * FROM tours ORDER BY featured DESC, rating DESC LIMIT 6")->fetchAll();
    }
} catch (\Throwable $e) { $tours = []; }

$prices   = array_filter(array_column($tours, 'price'));
$minPrice = $prices ? min($prices) : 0;
$pageTitle   = 'Kilimanjaro & Mt. Meru Trekking | Tanzania Routes';
$pageDesc    = 'Conquer Africa\'s highest peaks. Expert-guided Kilimanjaro climbs, walking safaris and adventure tours. KINAPA certified guides.';
$currentPage = 'trekking';

function ticon2(string $t): string {
    return match(strtolower($t)) {
        'trekking'             => 'fa-mountain',
        'walking safari'       => 'fa-hiking',
        'adventure tour'       => 'fa-bolt',
        'bird watching safari' => 'fa-feather',
        default                => 'fa-compass',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <?php require __DIR__ . '/includes/google-tags.php'; /* Google Ads + GA4 */ ?>
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <link rel="canonical" href="<?= SITE_URL ?>/mountain-trekking">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDesc) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Jambo Masai Tours">
  <meta name="theme-color" content="#a05e22">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="preconnect" href="https://cdn.tailwindcss.com"><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><link rel="dns-prefetch" href="https://images.unsplash.com"><script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#a05e22',dark:'#23362f'},fontFamily:{heading:['Nanum Myeongjo','Georgia','serif'],sans:['Inter','sans-serif'],nav:['Montserrat','sans-serif']}}}}</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#2c463d}::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}
    .hero-grad{background:linear-gradient(135deg,#c17a3a,#7d4817,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .section-tag{display:inline-flex;align-items:center;gap:.45rem;background:rgba(160,94,34,.1);border:1px solid rgba(160,94,34,.2);border-radius:999px;padding:.28rem .9rem;font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#c17a3a;margin-bottom:.85rem}
    .reveal{opacity:0;transform:translateY(18px);transition:all .65s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#a05e22;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}
    #main-nav{position:fixed;top:0;left:0;right:0;z-index:1000;transition:background .4s;background:linear-gradient(to bottom,rgba(0,0,0,.6) 0%,transparent 100%)}
    #main-nav.scrolled,#main-nav.solid{background:rgba(10,10,10,.96) !important;backdrop-filter:blur(20px);border-bottom:1px solid rgba(160,94,34,.1)}
    /* Tour cards */
    .pkg-card{background:rgba(17,17,17,.9);border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden;transition:all .35s cubic-bezier(.4,0,.2,1)}
    .pkg-card:hover{border-color:rgba(160,94,34,.3);transform:translateY(-4px);box-shadow:0 20px 50px rgba(0,0,0,.6)}
    .pkg-card:hover .pkg-img{transform:scale(1.05)}
    .pkg-img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
    /* Route cards */
    .route-card{background:rgba(17,17,17,.8);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.5rem;transition:all .3s;cursor:default}
    .route-card:hover{border-color:rgba(160,94,34,.25);background:rgba(17,17,17,.95);transform:translateY(-3px)}
    /* Why cards */
    .why-card{background:rgba(17,17,17,.8);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.5rem;transition:all .3s}
    .why-card:hover{border-color:rgba(160,94,34,.2);transform:translateY(-3px)}
  </style>
</head>
<body>
<div class="scroll-progress" id="scroll-progress" style="position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#a05e22,#7d4817);z-index:9999;width:0;transition:width .1s"></div>

<?php require_once 'includes/public_navbar.php'; ?>
<?php require_once 'includes/booking-modal.php'; ?>

<!-- ---------- HERO ---------- -->
<section style="position:relative;overflow:hidden;padding-top:68px;background:#23362f">
  <!-- Background image -->
  <div style="position:absolute;inset:0;z-index:0">
    <img src="<?= IMG_KILIMANJARO ?>" alt="Kilimanjaro"
         style="width:100%;height:100%;object-fit:cover;opacity:.25" fetchpriority="high" width="1920" height="800">
    <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,10,10,.95) 40%,rgba(10,10,10,.7) 100%)"></div>
  </div>

  <!-- Hero content -->
  <div style="position:relative;z-index:10;max-width:1280px;margin:0 auto;padding:4rem 2rem;display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center">
    <!-- Left text -->
    <div>
      <nav style="display:flex;align-items:center;gap:.45rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.35);margin-bottom:1.25rem">
        <a href="<?= url() ?>" style="color:inherit;text-decoration:none" onmouseover="this.style.color='#a05e22'" onmouseout="this.style.color='rgba(255,255,255,.35)'">Home</a>
        <span>--</span><span style="color:rgba(255,255,255,.5)">Mountain Trekking</span>
      </nav>
      <div class="section-tag reveal"><i class="fas fa-mountain" style="font-size:.52rem"></i>Tanzania's Roof</div>
      <h1 style="font-family:'Nanum Myeongjo',serif;font-size:clamp(2rem,4vw,3.2rem);font-weight:700;color:#fff;line-height:1.15;margin-bottom:1rem" class="reveal">
        Conquer <span class="hero-grad">Kilimanjaro</span><br>& Beyond
      </h1>
      <p style="font-size:.95rem;color:rgba(255,255,255,.5);line-height:1.8;margin-bottom:2rem;max-width:480px" class="reveal">
        Expert-guided treks on all major routes. KINAPA-certified guides, world-class safety standards, and an 85%+ summit success rate.
      </p>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap" class="reveal">
        <a href="#packages" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;padding:.85rem 1.75rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;text-decoration:none;box-shadow:0 4px 16px rgba(160,94,34,.3)">
          <i class="fas fa-mountain" style="font-size:.7rem"></i> View Packages
        </a>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;padding:.85rem 1.5rem;border-radius:10px;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);color:#25D366;text-decoration:none">
          <i class="fab fa-whatsapp" style="font-size:.9rem"></i> Ask Us
        </a>
      </div>
    </div>

    <!-- Right stats panel -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" class="reveal">
      <?php foreach ([
        ['fa-mountain','5,895m','Uhuru Peak','Africa\'s highest summit','#a05e22'],
        ['fa-route','6+','Major Routes','Machame, Lemosho, Rongai...','#fbbf24'],
        ['fa-chart-line','85%+','Summit Rate','Above-average success','#c17a3a'],
        ['fa-certificate','KINAPA','Certified','Official Tanzania guides','#60a5fa'],
      ] as $s): ?>
      <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:1.25rem">
        <i class="fas <?= $s[0] ?>" style="color:<?= $s[4] ?>;font-size:1.1rem;margin-bottom:.65rem;display:block"></i>
        <div style="font-family:'Nanum Myeongjo',serif;font-size:1.5rem;font-weight:700;color:#fff;line-height:1"><?= $s[1] ?></div>
        <div style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.1em;margin-top:.2rem"><?= $s[2] ?></div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.3);margin-top:.3rem"><?= $s[3] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  @media(max-width:768px){.hero-grid{grid-template-columns:1fr !important}}
</section>

<!-- ---------- WHY CHOOSE US ---------- -->
<section style="padding:5rem 2rem;background:rgba(255,255,255,.01);border-top:1px solid rgba(255,255,255,.05)">
  <div style="max-width:1280px;margin:0 auto">
    <div style="text-align:center;margin-bottom:3rem" class="reveal">
      <div class="section-tag"><i class="fas fa-shield-alt" style="font-size:.52rem"></i>Why Trek With Us</div>
      <h2 style="font-family:'Nanum Myeongjo',serif;font-size:clamp(1.7rem,3vw,2.4rem);font-weight:700;color:#fff">
        Tanzania's Most Trusted <span class="hero-grad">Trek Operator</span>
      </h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem">
      <?php foreach ([
        ['fa-certificate','#a05e22','KINAPA Certified','All guides certified by Tanzania National Parks Authority.'],
        ['fa-chart-line','#fbbf24','85%+ Summit Rate','Careful acclimatisation itineraries for maximum success.'],
        ['fa-first-aid','#f87171','Emergency Trained','First aid, oxygen, and evacuation protocols on every climb.'],
        ['fa-users','#60a5fa','Small Groups','Max 6 guests per guide for personalised, safe climbs.'],
      ] as $w): ?>
      <div class="why-card reveal">
        <div style="width:46px;height:46px;border-radius:12px;background:<?= $w[1] ?>18;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
          <i class="fas <?= $w[0] ?>" style="color:<?= $w[1] ?>;font-size:1rem"></i>
        </div>
        <div style="font-family:'Montserrat',sans-serif;font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.45rem"><?= $w[2] ?></div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.38);line-height:1.65"><?= $w[3] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ---------- PACKAGES ---------- -->
<section id="packages" style="padding:5rem 2rem;background:#23362f">
  <div style="max-width:1280px;margin:0 auto">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2.5rem;flex-wrap:wrap;gap:1rem" class="reveal">
      <div>
        <div class="section-tag"><i class="fas fa-compass" style="font-size:.52rem"></i>Our Packages</div>
        <h2 style="font-family:'Nanum Myeongjo',serif;font-size:clamp(1.6rem,2.5vw,2rem);font-weight:700;color:#fff">
          Trekking & Adventure Tours
        </h2>
      </div>
      <a href="<?= url('tours?tour_type=Trekking') ?>" style="font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;color:#a05e22;text-decoration:none;display:flex;align-items:center;gap:.35rem">
        View all tours <i class="fas fa-arrow-right" style="font-size:.6rem"></i>
      </a>
    </div>

    <?php if (!empty($tours)): ?>
    <div style="display:grid;grid-template-columns:repeat(<?= min(count($tours),3) ?>,1fr);gap:1.5rem">
      <?php foreach ($tours as $i => $tour):
        preg_match('/\d+/', $tour['duration'] ?? '', $dm); $days = (int)($dm[0] ?? 0);
        $hls = array_filter(explode('|', $tour['highlights'] ?? ''));
        $waM = urlencode('Hi! I\'m interested in '.$tour['name'].'. Please share details.');
      ?>
      <div class="pkg-card reveal" style="transition-delay:<?= $i*80 ?>ms">
        <div style="position:relative;height:220px;overflow:hidden">
          <img src="<?= e($tour['image']) ?>" alt="<?= e($tour['name']) ?>" class="pkg-img"
               loading="<?= $i<2?'eager':'lazy' ?>" width="600" height="400">
          <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(10,10,10,.9) 0%,transparent 55%)"></div>
          <?php if ($tour['featured']): ?>
          <div style="position:absolute;top:.85rem;left:.85rem;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;padding:.25rem .7rem;border-radius:999px;background:rgba(245,158,11,.9);color:#000;text-transform:uppercase;letter-spacing:.06em">
            <i class="fas fa-fire" style="font-size:.5rem"></i> Most Popular
          </div>
          <?php endif; ?>
          <div style="position:absolute;top:.85rem;right:.85rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:#fff;padding:.28rem .65rem;border-radius:999px;background:rgba(0,0,0,.55);backdrop-filter:blur(8px)">
            <i class="fas fa-clock" style="color:#a05e22;font-size:.5rem"></i> <?= e($tour['duration']) ?>
          </div>
          <div style="position:absolute;bottom:.85rem;right:.85rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:#fff;padding:.25rem .6rem;border-radius:999px;background:rgba(0,0,0,.55)">
            <i class="fas fa-star" style="color:#fbbf24;font-size:.52rem"></i> <?= e($tour['rating'] ?? '5.0') ?>
          </div>
        </div>
        <div style="padding:1.25rem;display:flex;flex-direction:column;gap:.6rem">
          <div style="display:flex;align-items:center;gap:.4rem">
            <i class="fas fa-map-marker-alt" style="color:#a05e22;font-size:.6rem"></i>
            <span style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:600;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em"><?= e($tour['destination']) ?></span>
            <span style="margin-left:auto;font-family:'Montserrat',sans-serif;font-size:.6rem;padding:.15rem .5rem;border-radius:6px;background:rgba(239,68,68,.12);color:#f87171">Challenging</span>
          </div>
          <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.05rem;font-weight:700;color:#fff;line-height:1.3"><?= e($tour['name']) ?></h3>
          <p style="font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.65"><?= e(truncate($tour['description'], 120)) ?></p>
          <?php if (!empty($hls)): ?>
          <div style="display:flex;flex-wrap:wrap;gap:.3rem">
            <?php foreach (array_slice($hls,0,3) as $hl): ?>
            <span style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:600;padding:.18rem .6rem;border-radius:999px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.45)">
              <i class="fas fa-check" style="color:#a05e22;font-size:.45rem"></i> <?= e(trim($hl)) ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:.75rem;border-top:1px solid rgba(255,255,255,.06);margin-top:.25rem">
            <div>
              <div style="font-family:'Montserrat',sans-serif;font-size:.52rem;color:rgba(255,255,255,.25);text-transform:uppercase">From</div>
              <div style="font-family:'Nanum Myeongjo',serif;font-size:1.35rem;font-weight:700;color:#a05e22"><?= formatPrice($tour['price']) ?><span style="font-family:'Montserrat',sans-serif;font-size:.52rem;font-weight:400;color:rgba(255,255,255,.3)">/person</span></div>
            </div>
            <div style="display:flex;gap:.45rem">
              <a href="<?= url('tour/'.e($tour['slug'])) ?>" style="display:inline-flex;align-items:center;gap:.35rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;padding:.55rem 1rem;border-radius:9px;background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;text-decoration:none">
                <i class="fas fa-binoculars" style="font-size:.6rem"></i> View
              </a>
              <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= $waM ?>" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2);text-decoration:none">
                <i class="fab fa-whatsapp" style="color:#25D366;font-size:.9rem"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem;background:rgba(255,255,255,.02);border-radius:16px;border:1px solid rgba(255,255,255,.06)">
      <i class="fas fa-mountain" style="font-size:2.5rem;color:rgba(255,255,255,.1);display:block;margin-bottom:1rem"></i>
      <p style="color:rgba(255,255,255,.35);font-family:'Montserrat',sans-serif;font-size:.85rem">Packages coming soon.</p>
      <a href="<?= url('contact') ?>" style="display:inline-flex;align-items:center;gap:.4rem;margin-top:1.25rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.72rem;padding:.65rem 1.5rem;border-radius:10px;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.2);color:#a05e22;text-decoration:none">
        Contact Us for Custom Trek
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ---------- ROUTES ---------- -->
<section style="padding:5rem 2rem;background:rgba(255,255,255,.015);border-top:1px solid rgba(255,255,255,.05)">
  <div style="max-width:1280px;margin:0 auto">
    <div style="text-align:center;margin-bottom:3rem" class="reveal">
      <div class="section-tag"><i class="fas fa-route" style="font-size:.52rem"></i>Kilimanjaro Routes</div>
      <h2 style="font-family:'Nanum Myeongjo',serif;font-size:clamp(1.7rem,3vw,2.4rem);font-weight:700;color:#fff">
        Choose Your Path to <span class="hero-grad">Uhuru Peak</span>
      </h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
      <?php foreach ([
        ['Machame','fa-mountain','#a05e22','6-7 days','Most Popular','High','Scenic "Whiskey Route" - best acclimatisation profile.'],
        ['Marangu','fa-home','#fbbf24','5-6 days','Hut Route','Moderate','Hut accommodation. Shorter duration, lower success rate.'],
        ['Lemosho','fa-leaf','#60a5fa','7-8 days','Most Scenic','High','Long western approach through pristine rainforest.'],
        ['Rongai','fa-compass','#a78bfa','6-7 days','Northern Approach','High','Quiet northern route. Spectacular wilderness scenery.'],
      ] as $r): ?>
      <div class="route-card reveal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
          <div style="width:42px;height:42px;border-radius:11px;background:<?= $r[2] ?>18;display:flex;align-items:center;justify-content:center">
            <i class="fas <?= $r[1] ?>" style="color:<?= $r[2] ?>;font-size:.9rem"></i>
          </div>
          <span style="font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;padding:.2rem .6rem;border-radius:999px;background:<?= $r[2] ?>20;color:<?= $r[2] ?>"><?= $r[4] ?></span>
        </div>
        <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:.35rem"><?= $r[0] ?> Route</h3>
        <div style="display:flex;gap:.75rem;margin-bottom:.75rem">
          <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:.25rem">
            <i class="fas fa-clock" style="color:<?= $r[2] ?>;font-size:.5rem"></i><?= $r[3] ?>
          </span>
          <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:.25rem">
            <i class="fas fa-chart-line" style="color:<?= $r[2] ?>;font-size:.5rem"></i><?= $r[5] ?>
          </span>
        </div>
        <p style="font-size:.75rem;color:rgba(255,255,255,.38);line-height:1.65"><?= $r[6] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ---------- BEST TIME + INCLUDED ---------- -->
<section style="padding:5rem 2rem;background:#23362f;border-top:1px solid rgba(255,255,255,.05)">
  <div style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

    <!-- Best time -->
    <div style="background:rgba(17,17,17,.8);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:2rem" class="reveal">
      <div class="section-tag"><i class="fas fa-calendar-alt" style="font-size:.52rem"></i>Best Time</div>
      <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.3rem;font-weight:700;color:#fff;margin-bottom:1.5rem">When to Climb Kilimanjaro</h3>
      <?php foreach ([
        ['Jan - Mar','#a05e22','Peak Season ?','Clear skies, excellent visibility. Best for photography.'],
        ['Apr - May','#f87171','Avoid - Long Rains','Wet & slippery trails. Not recommended.'],
        ['Jun - Oct','#a05e22','Peak Season ?','Dry & clear. Most popular. Book well in advance.'],
        ['Nov - Dec','#fbbf24','Short Rains','Light rains, moderate crowds. Some clear days.'],
      ] as $m): ?>
      <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.65rem .75rem;border-radius:10px;background:rgba(255,255,255,.03);margin-bottom:.5rem">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $m[1] ?>;margin-top:.3rem;flex-shrink:0"></div>
        <div>
          <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:.2rem">
            <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:700;color:#fff"><?= $m[0] ?></span>
            <span style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;color:<?= $m[1] ?>"><?= $m[2] ?></span>
          </div>
          <p style="font-size:.72rem;color:rgba(255,255,255,.35);line-height:1.5"><?= $m[3] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- What's included -->
    <div style="background:rgba(17,17,17,.8);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:2rem" class="reveal">
      <div class="section-tag"><i class="fas fa-check-circle" style="font-size:.52rem"></i>What's Included</div>
      <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.3rem;font-weight:700;color:#fff;margin-bottom:1.5rem">Everything You Need</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
        <?php foreach ([
          ['fa-id-card','Park & gate fees'],['fa-user-tie','Lead guide + assistants'],
          ['fa-hands','Certified porters'],['fa-tent','Tents & sleeping bags'],
          ['fa-utensils','All meals & snacks'],['fa-tint','Purified water'],
          ['fa-lungs','Emergency oxygen'],['fa-heartbeat','Pulse oximeter'],
          ['fa-car','Airport transfers'],['fa-certificate','Summit certificate'],
        ] as $inc): ?>
        <div style="display:flex;align-items:center;gap:.55rem;padding:.45rem .5rem;border-radius:8px;background:rgba(255,255,255,.03)">
          <i class="fas <?= $inc[0] ?>" style="color:#a05e22;font-size:.6rem;flex-shrink:0;width:14px;text-align:center"></i>
          <span style="font-size:.72rem;color:rgba(255,255,255,.55)"><?= $inc[1] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</section>

<!-- ---------- CTA ---------- -->
<section style="padding:5rem 2rem;background:linear-gradient(135deg,rgba(160,94,34,.06),rgba(5,150,105,.04));border-top:1px solid rgba(160,94,34,.12)">
  <div style="max-width:640px;margin:0 auto;text-align:center" class="reveal">
    <div class="section-tag"><i class="fas fa-mountain" style="font-size:.52rem"></i>Start Your Adventure</div>
    <h2 style="font-family:'Nanum Myeongjo',serif;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:700;color:#fff;margin-bottom:.75rem">
      Ready to Summit <span class="hero-grad">Kilimanjaro?</span>
    </h2>
    <p style="font-size:.9rem;color:rgba(255,255,255,.45);line-height:1.7;margin-bottom:2rem">
      Our expert guides will take you to the top safely. Contact us to plan your perfect trek - custom itineraries available.
    </p>
    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
      <a href="<?= url('booking') ?>" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;padding:.85rem 2rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;text-decoration:none;box-shadow:0 4px 16px rgba(160,94,34,.3)">
        <i class="fas fa-calendar-check" style="font-size:.72rem"></i> Book Your Trek
      </a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;padding:.85rem 1.75rem;border-radius:10px;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);color:#25D366;text-decoration:none">
        <i class="fab fa-whatsapp" style="font-size:.9rem"></i> WhatsApp Us
      </a>
    </div>
  </div>
</section>

<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener"><i class="fab fa-whatsapp text-2xl relative z-10"></i></a>
<button id="back-top" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-chevron-up text-sm"></i></button>

<style>
@media(max-width:900px){
  [style*="grid-template-columns:repeat(4"]{grid-template-columns:repeat(2,1fr) !important}
  [style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr !important}
  [style*="grid-template-columns:1fr 1fr;gap:3rem"]{grid-template-columns:1fr !important}
}
@media(max-width:640px){
  [style*="grid-template-columns:repeat(4"]{grid-template-columns:1fr !important}
  [style*="grid-template-columns:repeat(2"]{grid-template-columns:1fr !important}
}
</style>

<script>
(function(){
  var sp=document.getElementById('scroll-progress');
  var btt=document.getElementById('back-top');
  var wa=document.getElementById('wa-float');
  window.addEventListener('scroll',function(){
    var m=document.documentElement.scrollHeight-innerHeight;
    if(m>0&&sp)sp.style.width=(scrollY/m*100).toFixed(2)+'%';
    btt&&btt.classList.toggle('visible',scrollY>400);
  },{passive:true});
  if(wa)setTimeout(function(){wa.classList.add('visible');},2500);
  if('IntersectionObserver' in window){
    var ro=new IntersectionObserver(function(e){e.forEach(function(x){if(x.isIntersecting){x.target.classList.add('visible');ro.unobserve(x.target);}});},{rootMargin:'0px 0px -60px 0px',threshold:0});
    document.querySelectorAll('.reveal').forEach(function(el){ro.observe(el);});
  } else {
    document.querySelectorAll('.reveal').forEach(function(el){el.classList.add('visible');});
  }
})();
</script>

<?php require_once 'includes/dark_footer.php'; ?>
</body>
</html>

