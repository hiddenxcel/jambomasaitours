<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Great Migration Safari ' . date('Y') . ' | Serengeti Wildebeest & Mara River Crossings — Jambo Masai Tours';
$pageDescription = 'Witness the Great Wildebeest Migration in the Serengeti — 1.5 million wildebeest, dramatic Mara River crossings (Jul–Oct) and calving season (Jan–Mar). Book a curated Great Migration safari package with Maasai-founded, TATO-licensed guides.';
$metaKeywords    = 'great migration safari, serengeti migration, wildebeest migration tanzania, mara river crossing safari, great migration ' . date('Y') . ', serengeti wildebeest migration, calving season serengeti, migration safari packages, best time great migration, tanzania migration safari, ngorongoro serengeti migration tour';
$currentPage     = 'migration';
$ogImage         = IMG_SERENGETI;
$canonicalUrl    = SITE_URL . '/migration';

$db = getDB();

/* Great Migration packages only */
$stmt = $db->prepare("SELECT * FROM tours WHERE tour_type = ? ORDER BY featured DESC, rating DESC");
$stmt->execute(['Great Migration Safari']);
$packages = $stmt->fetchAll();

/* Itinerary preview per package (degrade gracefully if table missing) */
$itinPreview = [];
foreach ($packages as $p) {
    try {
        $iq = $db->prepare("SELECT day_number, title FROM tour_itinerary WHERE tour_id = ? ORDER BY day_number ASC LIMIT 4");
        $iq->execute([$p['id']]);
        $itinPreview[$p['id']] = $iq->fetchAll();
    } catch (\Throwable $e) {
        $itinPreview[$p['id']] = [];
    }
}

/* ── Great Migration FAQs (drive both the on-page section AND FAQPage schema) ── */
$migFaqs = [
    ['q' => 'What is the Great Migration?',
     'a' => 'The Great Migration is the largest overland animal movement on Earth — roughly 1.5 million wildebeest, 250,000 zebra and hundreds of thousands of gazelle move in a continuous clockwise loop between Tanzania\'s Serengeti and Kenya\'s Maasai Mara, following the rains and fresh grass. It happens year-round, but each season offers a different spectacle.'],
    ['q' => 'When is the best time to see the Great Migration?',
     'a' => 'It depends what you want to witness. July to October is prime time for the dramatic Mara River crossings in the northern Serengeti. January to March is the calving season in the southern Serengeti (Ndutu), when around 8,000 wildebeest are born each day. June sees the Grumeti River crossings in the Western Corridor.'],
    ['q' => 'When do the Mara River crossings happen?',
     'a' => 'The famous Mara River crossings — where wildebeest plunge into crocodile-filled waters — typically occur from July through October in the northern Serengeti. Crossings are unpredictable and can happen back and forth, so we position you in the north during these months to maximise your chances.'],
    ['q' => 'How much does a Great Migration safari cost?',
     'a' => 'Our Great Migration safari packages vary by length, season and accommodation level. Peak river-crossing months (July–October) command higher rates and sell out early. Contact us with your dates for a tailored quote — every itinerary is customised to your budget and interests.'],
    ['q' => 'Where is the migration in July and August?',
     'a' => 'In July and August the herds have reached the northern Serengeti along the Mara River, making it the best time and place to witness river crossings. This is the peak of the migration safari season, so booking 6–12 months ahead is strongly recommended.'],
    ['q' => 'How many days do I need for a Great Migration safari?',
     'a' => 'A minimum of 4–5 days lets you reach the migration zone and enjoy quality game drives, but 6–8 days is ideal to combine the migration with Ngorongoro Crater and Tarangire. We tailor the length to the season and your travel plans.'],
    ['q' => 'Will I definitely see a river crossing?',
     'a' => 'River crossings are one of nature\'s most unpredictable events — no operator can guarantee a crossing on a specific day. What we guarantee is expert positioning: our Maasai guides track the herds daily and place you at the best crossing points during peak season to give you the highest possible chance.'],
];

/* ── SEO: JSON-LD (BreadcrumbList + ItemList + FAQPage + TouristTrip) ── */
$ldItems = [];
foreach ($packages as $idx => $p) {
    $ldItems[] = [
        '@type'    => 'ListItem',
        'position' => $idx + 1,
        'item'     => [
            '@type' => 'TouristTrip',
            'name'  => $p['name'],
            'url'   => SITE_URL . '/tour/' . $p['slug'],
            'offers'=> ['@type' => 'Offer', 'price' => (string)(float)$p['price'], 'priceCurrency' => 'USD'],
        ],
    ];
}
$ldFaqs = [];
foreach ($migFaqs as $f) {
    $ldFaqs[] = ['@type' => 'Question', 'name' => $f['q'],
                 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
}
$jsonLd = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'The Great Migration', 'item' => $canonicalUrl],
    ]],
    ['@context' => 'https://schema.org', '@type' => 'TouristTrip',
        'name' => 'The Great Migration Safari — Serengeti, Tanzania',
        'url'  => $canonicalUrl,
        'description' => 'Follow the Great Wildebeest Migration across the Serengeti — Mara River crossings, calving season and Big Five game viewing, guided by Maasai experts.',
        'image' => [IMG_SERENGETI],
        'touristType' => ['Wildlife Safari', 'Great Migration Safari', 'Luxury Safari'],
        'provider' => [
            '@type' => 'TravelAgency', 'name' => 'Jambo Masai Tours',
            'url' => SITE_URL, 'telephone' => SITE_PHONE,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Arusha', 'addressCountry' => 'TZ'],
        ],
    ],
    ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Great Migration Safari Packages', 'itemListElement' => $ldItems],
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ldFaqs],
];
$headExtra = '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

/* ── Month-by-month migration calendar (general knowledge, static) ──
   phase: calving | peak | river | shoulder */
$calendar = [
    1  => ['Jan', 'Ndutu / Southern Serengeti',  'Calving season begins — thousands of wildebeest give birth.', 'calving'],
    2  => ['Feb', 'Ndutu / Southern Serengeti',  'Peak calving — ~8,000 calves a day, predators on the prowl.', 'calving'],
    3  => ['Mar', 'Southern → Central Serengeti', 'Herds begin drifting north-west as the plains dry.',          'shoulder'],
    4  => ['Apr', 'Central Serengeti',            'Long rains; columns of wildebeest march westward.',            'shoulder'],
    5  => ['May', 'Western Corridor',             'Rutting season; the Grumeti River tests the herds.',           'river'],
    6  => ['Jun', 'Western Corridor / Grumeti',   'Dramatic Grumeti River crossings begin.',                      'river'],
    7  => ['Jul', 'Northern Serengeti',           'Herds reach the north — prime Mara River crossing season.',    'peak'],
    8  => ['Aug', 'Northern Serengeti / Mara',    'Iconic Mara River crossings — crocodiles await.',              'peak'],
    9  => ['Sep', 'Northern Serengeti / Mara',    'Crossings continue back and forth across the Mara.',           'peak'],
    10 => ['Oct', 'Northern → Central Serengeti', 'Herds begin the long journey back south.',                     'river'],
    11 => ['Nov', 'Central / Eastern Serengeti',  'Short rains draw the herds toward the southern plains.',       'shoulder'],
    12 => ['Dec', 'Southern Serengeti',           'Herds gather on the short-grass plains for calving.',          'shoulder'],
];
/* [ bg-tint, border, accent, label, icon ] */
$phaseColors = [
    'calving'  => ['rgba(96,165,250,.10)',  'rgba(96,165,250,.30)',  '#60a5fa', 'Calving',        'fa-baby'],
    'peak'     => ['rgba(160,94,34,.14)',   'rgba(160,94,34,.38)',   '#c17a3a', 'Peak Crossings', 'fa-water'],
    'river'    => ['rgba(251,191,36,.10)',  'rgba(251,191,36,.32)',  '#fbbf24', 'River Crossings', 'fa-otter'],
    'shoulder' => ['rgba(255,255,255,.04)', 'rgba(255,255,255,.10)', 'rgba(255,255,255,.55)', 'On the Move', 'fa-shoe-prints'],
];
$currentMonth = (int)date('n');

require_once 'includes/dark_header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<div class="mig-hero page-hero pt-[68px]">
  <img src="<?= e(IMG_SERENGETI) ?>" alt="Wildebeest herds during the Great Migration river crossing in the Serengeti, Tanzania"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.42" fetchpriority="high">
  <!-- cinematic gradient stack -->
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.75),rgba(35,54,47,.55) 45%,rgba(35,54,47,.9) 82%,#23362f)"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.7),transparent 62%)"></div>
  <div class="mig-hero-vignette absolute inset-0"></div>
  <!-- drifting herd-dust glow -->
  <div class="mig-hero-glow" aria-hidden="true"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-4 w-full">
    <div class="mig-hero-grid">
      <!-- Left: text -->
      <div>
        <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/40 mb-5 mig-fade" style="--d:0s">
          <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
          <span class="text-white/70">The Great Migration</span>
        </nav>
        <div class="section-tag mig-badge mig-fade" style="--d:.08s">
          <span class="mig-badge-dot"></span><i class="fas fa-horse"></i> Season <?= date('Y') ?> · Serengeti · Live Now
        </div>
        <h1 class="font-heading text-white font-bold leading-tight mig-fade" style="font-size:clamp(2.2rem,5vw,3.6rem);--d:.16s">
          The <span class="hero-grad">Great Migration</span>
        </h1>
        <p class="text-white/55 mt-4 max-w-lg text-[.98rem] leading-relaxed mig-fade" style="--d:.24s">
          Follow <span class="text-white/80 font-semibold">1.5&nbsp;million wildebeest &amp; zebra</span> on the greatest
          wildlife spectacle on Earth — safaris timed to the herds, from the thunder of the Mara River crossings
          to the newborn calves of the southern plains.
        </p>
        <!-- CTAs -->
        <div class="flex flex-wrap gap-3 mt-7 mig-fade" style="--d:.32s">
          <a href="#packages" class="btn-em btn-em-primary"><i class="fas fa-horse"></i> View Packages</a>
          <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= rawurlencode('Hi! I want to plan a Great Migration safari.') ?>"
             target="_blank" rel="noopener" class="btn-em btn-em-wa"><i class="fab fa-whatsapp text-lg"></i> Ask Us</a>
        </div>
      </div>

      <!-- Right: 2×2 stat cards -->
      <div class="mig-stat-grid mig-fade" style="--d:.4s">
        <?php
        $stats = [
          ['fa-paw',    '1.5M+',    'Wildebeest & Zebra', 'The largest herd on Earth',   '#a05e22'],
          ['fa-water',  'Jun–Oct',  'River Crossings',    'Mara & Grumeti crossing window','#fbbf24'],
          ['fa-baby',   'Jan–Mar',  'Calving Season',     '~8,000 calves born a day',    '#60a5fa'],
          ['fa-crown',  'Big Five', 'Guaranteed Territory','Lion, leopard, elephant & more','#c17a3a'],
        ];
        foreach ($stats as $s): ?>
        <div class="mig-stat" style="--accent:<?= $s[4] ?>">
          <div class="mig-stat-head">
            <span class="mig-stat-ico"><i class="fas <?= $s[0] ?>"></i></span>
            <div class="mig-stat-val"><?= $s[1] ?></div>
          </div>
          <div class="mig-stat-lbl"><?= $s[2] ?></div>
          <div class="mig-stat-sub"><i class="fas fa-circle mig-stat-bullet"></i><?= $s[3] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<style>
/* ── Overflow guard: no section may push the page wider than the viewport ── */
.mig-hero,#mig-intro,#packages,.mig-hero ~ section,section.section-light{overflow-x:clip}
/* keep long uppercase labels from overflowing their cards */
.mig-stat-lbl,.mig-cal-loc span,.mig-cal-chip{overflow-wrap:anywhere}

/* ── Hero polish ── */
.mig-hero{align-items:flex-end;min-height:auto;padding-bottom:2.5rem}
@media(max-width:640px){.mig-hero{padding-bottom:2rem}}
.mig-hero-vignette{background:radial-gradient(ellipse at 50% 40%,transparent 45%,rgba(10,14,12,.55) 100%);pointer-events:none}
.mig-hero-glow{position:absolute;bottom:-10%;left:5%;width:60%;height:55%;background:radial-gradient(ellipse at bottom left,rgba(160,94,34,.18),transparent 65%);pointer-events:none;z-index:1;animation:migGlow 9s ease-in-out infinite alternate}
@keyframes migGlow{0%{opacity:.55;transform:translateX(0)}100%{opacity:.9;transform:translateX(40px)}}
/* fade-up entrance (staggered via --d) */
.mig-fade{opacity:0;transform:translateY(20px);animation:migFadeUp .9s cubic-bezier(.22,1,.36,1) forwards;animation-delay:var(--d,0s)}
@keyframes migFadeUp{to{opacity:1;transform:translateY(0)}}
/* live badge */
.mig-badge{align-items:center;flex-wrap:wrap;max-width:100%}
@media(max-width:400px){.mig-badge{font-size:.55rem;letter-spacing:.1em;padding:.3rem .75rem}}
.mig-badge-dot{width:6px;height:6px;border-radius:50%;background:#c17a3a;box-shadow:0 0 0 0 rgba(193,122,58,.7);animation:migLive 1.8s ease-out infinite}
@keyframes migLive{0%{box-shadow:0 0 0 0 rgba(193,122,58,.6)}70%,100%{box-shadow:0 0 0 7px rgba(193,122,58,0)}}
/* hero two-column grid */
.mig-hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:2.75rem;align-items:center;padding-bottom:2rem}
@media(max-width:900px){
  .mig-hero-grid{grid-template-columns:1fr;gap:1.75rem;padding-bottom:1rem;text-align:center}
  /* centre every block in the left column */
  .mig-hero-grid > div:first-child > *{margin-left:auto;margin-right:auto}
  .mig-hero-grid nav{justify-content:center}
  .mig-hero-grid .mig-badge{justify-content:center}
  .mig-hero-grid p{margin-left:auto;margin-right:auto}
  .mig-hero-grid .flex.flex-wrap{justify-content:center}
}
/* 2×2 stat cards */
.mig-stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:420px){.mig-stat-grid{grid-template-columns:1fr;gap:.65rem}}
.mig-stat{position:relative;overflow:hidden;padding:1.35rem 1.4rem;border-radius:18px;background:linear-gradient(158deg,rgba(255,255,255,.055),rgba(255,255,255,.02));border:1px solid rgba(255,255,255,.09);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:transform .45s cubic-bezier(.22,1,.36,1),border-color .45s,box-shadow .45s}
/* soft accent glow, top-right */
.mig-stat::before{content:'';position:absolute;top:-50%;right:-35%;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--accent) 26%,transparent),transparent 68%);opacity:.6;pointer-events:none;transition:opacity .45s,transform .55s cubic-bezier(.22,1,.36,1);z-index:0}
/* accent bar that grows on hover — bottom edge */
.mig-stat::after{content:'';position:absolute;left:0;bottom:0;height:3px;width:0;background:linear-gradient(90deg,var(--accent),transparent);transition:width .5s cubic-bezier(.22,1,.36,1)}
.mig-stat:hover{transform:translateY(-6px);border-color:color-mix(in srgb,var(--accent) 50%,transparent);box-shadow:0 20px 44px rgba(0,0,0,.32),0 0 0 1px color-mix(in srgb,var(--accent) 22%,transparent)}
.mig-stat:hover::before{opacity:1;transform:scale(1.35)}
.mig-stat:hover::after{width:100%}
.mig-stat > *{position:relative;z-index:1}
/* header: icon + big value on one line */
.mig-stat-head{display:flex;align-items:center;gap:.85rem;margin-bottom:.8rem}
.mig-stat-ico{width:44px;height:44px;flex-shrink:0;border-radius:12px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--accent) 16%,transparent);border:1px solid color-mix(in srgb,var(--accent) 32%,transparent);box-shadow:inset 0 0 12px color-mix(in srgb,var(--accent) 12%,transparent);transition:transform .45s cubic-bezier(.34,1.56,.64,1),background .45s,box-shadow .45s}
.mig-stat-ico i{color:var(--accent);font-size:1.05rem}
.mig-stat:hover .mig-stat-ico{transform:scale(1.1) rotate(-6deg);background:color-mix(in srgb,var(--accent) 26%,transparent);box-shadow:inset 0 0 14px color-mix(in srgb,var(--accent) 20%,transparent),0 6px 18px color-mix(in srgb,var(--accent) 30%,transparent)}
.mig-stat-val{font-family:'Nanum Myeongjo',serif;font-weight:700;font-size:1.7rem;line-height:1;color:#fff;letter-spacing:-.01em}
.mig-stat-lbl{font-family:'Montserrat',sans-serif;font-size:.63rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:color-mix(in srgb,var(--accent) 75%,#ffffff);margin-bottom:.45rem}
.mig-stat-sub{display:flex;align-items:center;gap:.5rem;font-size:.73rem;line-height:1.4;color:rgba(255,255,255,.4)}
.mig-stat-bullet{font-size:.3rem;color:var(--accent);flex-shrink:0;opacity:.8}
@media(max-width:900px){.mig-stat-head{justify-content:center}.mig-stat-lbl{text-align:center}.mig-stat-sub{justify-content:center}}
@media(max-width:640px){.mig-stat{padding:1.1rem}.mig-stat-val{font-size:1.45rem}.mig-stat-ico{width:38px;height:38px}.mig-stat-sub{display:none}}
@media(prefers-reduced-motion:reduce){.mig-fade,.mig-hero-glow,.mig-badge-dot{animation:none}.mig-fade{opacity:1;transform:none}}
</style>

<!-- ═══════════════ SEASONAL INTRO ═══════════════ -->
<section id="mig-intro" class="py-16 px-4 lg:px-0">
  <div class="max-w-4xl mx-auto text-center reveal">
    <div class="section-tag">Why the Migration Never Stops</div>
    <h2 class="font-heading text-white font-bold mb-5" style="font-size:clamp(1.6rem,3.5vw,2.4rem)">
      A year-round chase across <span class="hero-grad">the endless plains</span>
    </h2>
    <p class="text-white/55 leading-relaxed text-[.98rem]">
      The Great Migration is not a single event but a continuous, clockwise loop through the
      Serengeti–Mara ecosystem, driven by the rains and the search for fresh grass. Every month
      the herds are somewhere different — which is exactly why <strong class="text-white/80">timing your
      safari matters</strong>. Use the calendar below to see where the herds are, then choose the
      package built for that moment.
    </p>
  </div>
</section>

<!-- ═══════════════ MIGRATION CALENDAR ═══════════════ -->
<section class="pb-20 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-10 reveal">
      <div class="section-tag">Month-by-Month</div>
      <h2 class="font-heading text-white font-bold" style="font-size:clamp(1.6rem,3.5vw,2.3rem)">
        Where are the herds <span class="hero-grad">right now?</span>
      </h2>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap justify-center gap-x-5 gap-y-2 mb-10 reveal">
      <?php foreach ($phaseColors as $pc): ?>
      <div class="flex items-center gap-2">
        <span style="width:26px;height:26px;border-radius:8px;background:<?= $pc[0] ?>;border:1px solid <?= $pc[1] ?>;display:inline-flex;align-items:center;justify-content:center">
          <i class="fas <?= $pc[4] ?>" style="font-size:.6rem;color:<?= $pc[2] ?>"></i>
        </span>
        <span class="font-nav uppercase tracking-wider" style="font-size:.6rem;font-weight:600;color:<?= $pc[2] ?>"><?= $pc[3] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mig-cal-grid reveal">
      <?php foreach ($calendar as $mNum => $m):
        $pc      = $phaseColors[$m[3]];
        $isNow   = ($mNum === $currentMonth);
        $isPast  = ($mNum < $currentMonth);
      ?>
      <div class="mig-cal-card<?= $isNow ? ' is-now' : '' ?>"
           style="--accent:<?= $pc[2] ?>;--tint:<?= $pc[0] ?>;--brd:<?= $pc[1] ?>">
        <!-- top row: month + phase chip -->
        <div class="mig-cal-top">
          <div class="mig-cal-month">
            <span class="mig-cal-mn"><?= $m[0] ?></span>
            <?php if ($isNow): ?><span class="mig-cal-now"><span class="mig-cal-dot"></span>You are here</span><?php endif; ?>
          </div>
          <span class="mig-cal-chip">
            <i class="fas <?= $pc[4] ?>"></i><?= $pc[3] ?>
          </span>
        </div>
        <!-- location -->
        <div class="mig-cal-loc">
          <i class="fas fa-location-dot"></i><span><?= e($m[1]) ?></span>
        </div>
        <!-- description -->
        <p class="mig-cal-desc"><?= e($m[2]) ?></p>
        <!-- accent underline -->
        <span class="mig-cal-bar"></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<style>
.mig-cal-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
@media(max-width:1023px){.mig-cal-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:719px){.mig-cal-grid{grid-template-columns:repeat(2,1fr);gap:.75rem}}
.mig-cal-card{position:relative;border-radius:18px;padding:1.15rem 1.15rem 1.35rem;background:linear-gradient(160deg,rgba(255,255,255,.045),rgba(255,255,255,.015));border:1px solid rgba(255,255,255,.08);overflow:hidden;transition:transform .4s cubic-bezier(.22,1,.36,1),border-color .4s,box-shadow .4s;isolation:isolate}
/* tinted glow blob per phase */
.mig-cal-card::before{content:'';position:absolute;top:-40%;right:-30%;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,var(--tint),transparent 68%);opacity:.9;pointer-events:none;z-index:-1;transition:opacity .4s,transform .5s cubic-bezier(.22,1,.36,1)}
.mig-cal-card:hover{transform:translateY(-6px);border-color:var(--brd);box-shadow:0 18px 40px rgba(0,0,0,.28)}
.mig-cal-card:hover::before{opacity:1;transform:scale(1.25)}
/* current month — copper spotlight */
.mig-cal-card.is-now{background:linear-gradient(155deg,rgba(160,94,34,.26),rgba(125,72,23,.10));border-color:rgba(160,94,34,.55);box-shadow:0 14px 44px rgba(160,94,34,.28),inset 0 1px 0 rgba(255,255,255,.08)}
.mig-cal-card.is-now::before{background:radial-gradient(circle,rgba(193,122,58,.4),transparent 68%);opacity:1}
.mig-cal-card.is-now::after{content:'';position:absolute;inset:0;border-radius:18px;border:1px solid rgba(193,122,58,.5);pointer-events:none;animation:migPulseRing 2.6s ease-out infinite}
@keyframes migPulseRing{0%{opacity:.7;transform:scale(1)}70%,100%{opacity:0;transform:scale(1.04)}}
.mig-cal-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.7rem}
.mig-cal-month{display:flex;flex-direction:column;gap:.35rem}
.mig-cal-mn{font-family:'Nanum Myeongjo',serif;font-weight:700;font-size:1.45rem;line-height:1;color:rgba(255,255,255,.92)}
.mig-cal-card.is-now .mig-cal-mn{color:#fff}
.mig-cal-now{display:inline-flex;align-items:center;gap:.3rem;background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;font-family:'Montserrat',sans-serif;font-size:.46rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:.22rem .5rem;border-radius:999px;box-shadow:0 4px 14px rgba(160,94,34,.45);width:fit-content}
.mig-cal-dot{width:5px;height:5px;border-radius:50%;background:#fff;box-shadow:0 0 0 0 rgba(255,255,255,.7);animation:migDot 1.8s ease-out infinite}
@keyframes migDot{0%{box-shadow:0 0 0 0 rgba(255,255,255,.6)}70%,100%{box-shadow:0 0 0 6px rgba(255,255,255,0)}}
.mig-cal-chip{display:inline-flex;align-items:center;gap:.3rem;flex-shrink:0;font-family:'Montserrat',sans-serif;font-size:.5rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);background:var(--tint);border:1px solid var(--brd);padding:.28rem .55rem;border-radius:999px;white-space:nowrap}
.mig-cal-chip i{font-size:.55rem}
.mig-cal-loc{display:flex;align-items:center;gap:.4rem;margin-bottom:.55rem}
.mig-cal-loc i{font-size:.62rem;color:#c17a3a;flex-shrink:0}
.mig-cal-loc span{font-family:'Montserrat',sans-serif;font-size:.64rem;font-weight:500;color:rgba(255,255,255,.68)}
.mig-cal-desc{font-size:.73rem;line-height:1.5;color:rgba(255,255,255,.44);margin:0}
.mig-cal-card.is-now .mig-cal-desc{color:rgba(255,255,255,.6)}
.mig-cal-bar{position:absolute;left:0;bottom:0;height:3px;width:0;background:linear-gradient(90deg,var(--accent),transparent);transition:width .5s cubic-bezier(.22,1,.36,1)}
.mig-cal-card:hover .mig-cal-bar,.mig-cal-card.is-now .mig-cal-bar{width:100%}

/* ── Packages responsive ── */
.mig-pkg-img{min-height:340px;max-height:460px}
@media(max-width:1023px){.mig-pkg-img{min-height:300px;max-height:380px}}
@media(max-width:640px){.mig-pkg-img{min-height:220px;max-height:300px}}
@media(max-width:520px){
  .mig-pkg-cta{justify-content:flex-start}
  .mig-pkg-btns{width:100%}
  .mig-pkg-btns .btn-em{flex:1;justify-content:center;padding-left:0;padding-right:0}
}
</style>

<!-- ═══════════════ PACKAGES ═══════════════ -->
<section id="packages" class="section-light py-20 px-4 lg:px-0" style="scroll-margin-top:80px">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14 reveal">
      <div class="section-tag">Curated Migration Safaris</div>
      <h2 class="font-heading font-bold" style="font-size:clamp(1.8rem,4vw,2.6rem)">
        Choose your <span class="hero-grad">migration package</span>
      </h2>
      <p class="mt-4 max-w-2xl mx-auto text-[.98rem]" style="color:#5a6f64">
        Each itinerary is designed around the herds' position and the best chances of witnessing a crossing.
      </p>
    </div>

    <?php if (empty($packages)): ?>
    <!-- Empty state -->
    <div class="glass-card max-w-2xl mx-auto text-center reveal" style="padding:3rem 2rem">
      <i class="fas fa-binoculars" style="font-size:2.4rem;color:#a05e22;margin-bottom:1rem"></i>
      <h3 class="font-heading font-bold mb-3" style="font-size:1.4rem;color:#233a32">Migration packages coming soon</h3>
      <p style="color:#5a6f64" class="mb-6">
        We're finalising this season's migration itineraries. Tell us your travel dates and we'll craft a
        tailor-made migration safari just for you.
      </p>
      <div class="flex flex-wrap justify-center gap-3">
        <a href="<?= url('contact') ?>" class="btn-em btn-em-primary"><i class="fas fa-paper-plane"></i> Enquire Now</a>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= rawurlencode('Hi! I would like to know about your Great Migration safari packages.') ?>"
           target="_blank" rel="noopener" class="btn-em btn-em-wa"><i class="fab fa-whatsapp text-lg"></i> WhatsApp Us</a>
      </div>
    </div>
    <?php else: ?>

    <div class="space-y-16">
      <?php foreach ($packages as $i => $p):
        $flip       = ($i % 2 === 1); // zigzag
        $highlights = array_values(array_filter(array_map('trim', explode('|', $p['highlights'] ?? ''))));
        $days       = $itinPreview[$p['id']] ?? [];
      ?>
      <div class="reveal grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">

        <!-- Image -->
        <div class="<?= $flip ? 'lg:order-2' : '' ?>">
          <div style="position:relative;border-radius:22px;overflow:hidden;box-shadow:0 20px 50px rgba(44,70,61,.18)">
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="mig-pkg-img"
                 loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block">
            <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.4),transparent 55%)"></div>
            <!-- badges -->
            <div style="position:absolute;top:1rem;left:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
              <?php if ($p['featured']): ?>
              <span style="background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.35rem .8rem;border-radius:999px">★ Most Popular</span>
              <?php endif; ?>
              <span style="background:rgba(0,0,0,.55);backdrop-filter:blur(6px);color:#fff;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:600;padding:.35rem .8rem;border-radius:999px"><i class="fas fa-clock" style="margin-right:.3rem;color:#c17a3a"></i><?= e($p['duration']) ?></span>
            </div>
            <div style="position:absolute;bottom:1rem;right:1rem;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);color:#fbbf24;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:700;padding:.3rem .7rem;border-radius:999px">
              <i class="fas fa-star"></i> <?= number_format((float)$p['rating'], 1) ?>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="<?= $flip ? 'lg:order-1' : '' ?>">
          <div class="flex items-center gap-2 mb-3" style="color:#a05e22">
            <i class="fas fa-map-marker-alt" style="font-size:.8rem"></i>
            <span class="font-nav uppercase tracking-widest" style="font-size:.65rem;font-weight:700"><?= e($p['destination']) ?></span>
          </div>
          <h3 class="font-heading font-bold mb-3" style="font-size:clamp(1.5rem,3vw,2rem);color:#233a32;line-height:1.15">
            <?= e($p['name']) ?>
          </h3>
          <p class="mb-5 leading-relaxed" style="color:#5a6f64;font-size:.95rem">
            <?= e(truncate($p['description'] ?? '', 240)) ?>
          </p>

          <!-- Highlights -->
          <?php if ($highlights): ?>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-5">
            <?php foreach (array_slice($highlights, 0, 4) as $h): ?>
            <div class="flex items-start gap-2">
              <i class="fas fa-check-circle mt-0.5" style="color:#a05e22;font-size:.8rem"></i>
              <span style="color:#3b5c51;font-size:.85rem"><?= e($h) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Itinerary preview -->
          <?php if ($days): ?>
          <div class="mb-6" style="border-left:2px solid rgba(160,94,34,.3);padding-left:1rem">
            <div class="font-nav uppercase tracking-widest mb-2" style="font-size:.58rem;font-weight:700;color:#7d4817">Day-by-day preview</div>
            <?php foreach ($days as $d): ?>
            <div class="flex items-start gap-2.5 mb-1.5">
              <span style="flex-shrink:0;font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;color:#fff;background:#a05e22;border-radius:6px;padding:.1rem .45rem;margin-top:.1rem">D<?= (int)$d['day_number'] ?></span>
              <span style="color:#3b5c51;font-size:.82rem"><?= e($d['title']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Price + CTA -->
          <div class="mig-pkg-cta flex flex-wrap items-center justify-between gap-4 pt-5" style="border-top:1px solid rgba(44,70,61,.12)">
            <div>
              <span class="font-nav uppercase tracking-widest block" style="font-size:.55rem;color:#7a8b80">From / per person</span>
              <span class="font-heading font-bold" style="font-size:1.8rem;color:#a05e22"><?= formatPrice((float)$p['price']) ?></span>
            </div>
            <div class="mig-pkg-btns flex gap-2.5">
              <a href="<?= url('tour/' . e($p['slug'])) ?>" class="btn-em btn-em-outline" style="color:#5a6f64;border-color:rgba(44,70,61,.25)">Details</a>
              <a href="<?= url('booking?tour=' . e($p['slug'])) ?>" class="btn-em btn-em-primary"><i class="fas fa-calendar-check"></i> Book</a>
            </div>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════ FAQ (Great Migration) ═══════════════ -->
<section id="mig-faq" class="py-20 px-4 lg:px-0">
  <div class="max-w-3xl mx-auto">
    <div class="text-center mb-12 reveal">
      <div class="section-tag">Great Migration FAQ</div>
      <h2 class="font-heading text-white font-bold" style="font-size:clamp(1.6rem,3.5vw,2.4rem)">
        Your <span class="hero-grad">migration questions</span>, answered
      </h2>
      <p class="text-white/50 mt-3 text-[.95rem]">Everything you need to plan the perfect Serengeti wildebeest migration safari.</p>
    </div>

    <div class="space-y-3 reveal">
      <?php foreach ($migFaqs as $fi => $f): ?>
      <details class="mig-faq glass-card" <?= $fi === 0 ? 'open' : '' ?>>
        <summary class="mig-faq-q">
          <span class="font-heading text-white" style="font-size:1.02rem;font-weight:700"><?= e($f['q']) ?></span>
          <i class="fas fa-plus mig-faq-ico"></i>
        </summary>
        <div class="mig-faq-a"><?= e($f['a']) ?></div>
      </details>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-8 reveal">
      <p class="text-white/45 text-sm">Have a question we haven't covered?
        <a href="<?= url('contact') ?>" style="color:#c17a3a;text-decoration:underline">Ask our safari experts</a> or see the full
        <a href="<?= url('faq') ?>" style="color:#c17a3a;text-decoration:underline">safari FAQ</a>.
      </p>
    </div>
  </div>
</section>

<style>
.mig-faq{overflow:hidden;transition:border-color .3s}
.mig-faq[open]{border-color:rgba(160,94,34,.35)}
.mig-faq-q{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.35rem}
.mig-faq-q::-webkit-details-marker{display:none}
.mig-faq-ico{flex-shrink:0;color:#c17a3a;font-size:.85rem;transition:transform .3s cubic-bezier(.22,1,.36,1)}
.mig-faq[open] .mig-faq-ico{transform:rotate(135deg)}
.mig-faq-a{padding:0 1.35rem 1.2rem;color:rgba(255,255,255,.5);font-size:.9rem;line-height:1.65}
.mig-faq:hover{border-color:rgba(160,94,34,.25)}
</style>

<!-- ═══════════════ CLOSING CTA ═══════════════ -->
<section class="py-24 px-4 lg:px-0 relative overflow-hidden">
  <img src="<?= e(IMG_SERENGETI) ?>" alt="" class="absolute inset-0 w-full h-full object-cover" style="opacity:.18" aria-hidden="true">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,#23362f,rgba(35,54,47,.85),#23362f)"></div>
  <div class="relative z-10 max-w-3xl mx-auto text-center reveal">
    <div class="section-tag">Don't Miss the Crossing</div>
    <h2 class="font-heading text-white font-bold mb-4" style="font-size:clamp(1.8rem,4vw,2.8rem)">
      Ready to witness the <span class="hero-grad">Great Migration?</span>
    </h2>
    <p class="text-white/55 mb-8 max-w-xl mx-auto leading-relaxed">
      The best camps sell out 6–12 months ahead of the crossing season. Secure your dates today and let our
      Maasai guides put you exactly where the herds are.
    </p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="<?= url('booking') ?>" class="btn-em btn-em-primary"><i class="fas fa-calendar-check"></i> Start My Booking</a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= rawurlencode('Hi! I want to plan a Great Migration safari.') ?>"
         target="_blank" rel="noopener" class="btn-em btn-em-wa"><i class="fab fa-whatsapp text-lg"></i> Chat on WhatsApp</a>
    </div>
  </div>
</section>

<?php require_once 'includes/dark_footer.php'; ?>
