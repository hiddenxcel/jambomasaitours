<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$db           = getDB();
$destinations = $db->query("SELECT * FROM destination_pages_full WHERE active=1 ORDER BY sort_order ASC, id ASC")->fetchAll();

/* Normalize fields from destination_pages_full to match what the template expects */
foreach ($destinations as &$d) {
    /* image: use image_url field (destination_pages_full uses image_url not image) */
    $d['image'] = !empty($d['image_url']) ? $d['image_url'] : '';

    /* JSON highlights → pipe-separated string of titles */
    if (!empty($d['highlights']) && $d['highlights'][0] === '[') {
        $hArr = json_decode($d['highlights'], true) ?: [];
        $d['highlights'] = implode('|', array_column($hArr, 'title'));
    }
    /* Use intro as description fallback */
    if (empty($d['description']) && !empty($d['intro'])) {
        $d['description'] = $d['intro'];
    }
    /* Ensure country field exists */
    if (empty($d['country'])) $d['country'] = 'Tanzania';
}
unset($d);

$allTours     = $db->query("SELECT * FROM tours ORDER BY featured DESC, rating DESC")->fetchAll();

/* Group tours by destination */
$toursByDest = [];
foreach ($allTours as $t) {
    $key = strtolower(trim($t['destination']));
    $toursByDest[$key][] = $t;
}

/* Enquiry form */
$formSuccess = false; $formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enquiry_submit'])) {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $_POST[CSRF_TOKEN_NAME])) {
        $formErrors[] = 'Security token expired.';
    } else {
        $enqName = trim($_POST['enq_name'] ?? '');
        $enqEmail= trim($_POST['enq_email'] ?? '');
        $enqDest = trim($_POST['enq_destination'] ?? '');
        $enqDate = trim($_POST['enq_date'] ?? '');
        $enqMsg  = trim($_POST['enq_message'] ?? '');
        if (empty($enqName))  $formErrors[] = 'Name is required.';
        if (!filter_var($enqEmail, FILTER_VALIDATE_EMAIL)) $formErrors[] = 'Valid email required.';
        if (empty($enqDest))  $formErrors[] = 'Please select a destination.';
        if (empty($formErrors)) {
            $subject = 'Destination Enquiry: ' . $enqDest;
            $fullMsg = "Destination: $enqDest\nTravel Date: $enqDate\n\n$enqMsg";
            $db->prepare("INSERT INTO contacts (name,email,subject,message,status,created_at) VALUES (?,?,?,?,'unread',NOW())")->execute([$enqName,$enqEmail,$subject,$fullMsg]);
            unset($_SESSION[CSRF_TOKEN_NAME]);
            $formSuccess = true;
        }
    }
}
$csrf = generateCsrfToken();

/* Fallback destinations if DB empty */
if (empty($destinations)) {
    $destinations = [
        ['id'=>1,'title'=>'Serengeti',       'slug'=>'serengeti',  'region'=>'Northern Tanzania',    'country'=>'Tanzania','description'=>'The Serengeti is Tanzania\'s most iconic wildlife destination, home to the world-famous Great Migration. Over 1.5 million wildebeest, 200,000 zebras and 350,000 gazelles traverse its vast plains in an endless cycle of life.','highlights'=>'Great Migration|Big Five|Hot Air Balloon|Endless Plains','best_season'=>'Jun – Oct','image'=>IMG_SERENGETI,'active'=>1,'sort_order'=>1],
        ['id'=>2,'title'=>'Ngorongoro',      'slug'=>'ngorongoro', 'region'=>'Crater Highlands',     'country'=>'Tanzania','description'=>'The Ngorongoro Crater is a UNESCO World Heritage Site and one of Africa\'s most spectacular natural wonders. The world\'s largest intact volcanic caldera shelters a self-contained ecosystem with extraordinary wildlife density.','highlights'=>'UNESCO World Heritage|Big Five|Ancient Caldera|Rhino Refuge','best_season'=>'Year-round','image'=>IMG_NGORONGORO,'active'=>1,'sort_order'=>2],
        ['id'=>3,'title'=>'Kilimanjaro',     'slug'=>'kilimanjaro','region'=>'Kilimanjaro Region',   'country'=>'Tanzania','description'=>'Mount Kilimanjaro is Africa\'s highest peak at 5,895m and one of the world\'s most accessible high-altitude treks. The mountain offers six trekking routes, diverse ecosystems from tropical rainforest to arctic summit, and unforgettable views.','highlights'=>'Highest Peak in Africa|Multiple Trek Routes|5 Climate Zones|Snow Cap','best_season'=>'Jan–Mar, Jun–Oct','image'=>IMG_KILIMANJARO,'active'=>1,'sort_order'=>3],
        ['id'=>4,'title'=>'Zanzibar',        'slug'=>'zanzibar',   'region'=>'Zanzibar Archipelago', 'country'=>'Tanzania','description'=>'Zanzibar is a tropical paradise of white sand beaches, turquoise waters and rich Swahili culture. From UNESCO-listed Stone Town to pristine coral reefs, this spice island offers the perfect safari-beach combination.','highlights'=>'Spice Island|White Sand Beaches|UNESCO Stone Town|Coral Reefs','best_season'=>'Jun–Mar','image'=>IMG_ZANZIBAR,'active'=>1,'sort_order'=>4],
        ['id'=>5,'title'=>'Tarangire',       'slug'=>'tarangire',  'region'=>'Manyara Region',       'country'=>'Tanzania','description'=>'Tarangire National Park is famous for its ancient baobab trees and massive elephant herds — often 300+ strong. The Tarangire River sustains extraordinary wildlife concentrations during the dry season.','highlights'=>'Elephant Sanctuary|Ancient Baobabs|Tarangire River|Bird Watching','best_season'=>'Jun – Oct','image'=>IMG_TARANGIRE,'active'=>1,'sort_order'=>5],
        ['id'=>6,'title'=>'Maasai Heartland','slug'=>'maasai',     'region'=>'Arusha & Manyara',     'country'=>'Tanzania','description'=>'The Maasai Heartland offers authentic cultural immersion with one of Africa\'s most iconic peoples. Visit traditional villages (bomas), participate in ceremonies, and learn the ancient ways of the Maasai warrior — an experience that touches the soul.','highlights'=>'Cultural Immersion|Traditional Bomas|Warrior Ceremonies|Local Crafts','best_season'=>'Year-round','image'=>IMG_MAASAI,'active'=>1,'sort_order'=>6],
    ];
}

/* Destination-specific data */
$destData = [
    'serengeti' => [
        'badge'   => ['Flagship',    '#f59e0b','rgba(245,158,11,.9)','#000','fa-crown'],
        'tagline' => 'Africa\'s Greatest Wildlife Stage',
        'region'  => 'Northern Tanzania • 14,750 km²',
        'wildlife'=> [['🦁','Lion','Abundant'],['🐘','Elephant','Common'],['🐆','Leopard','Possible'],['🐃','Buffalo','Abundant'],['🦏','Rhino','Rare'],['🐆','Cheetah','Common'],['🦒','Giraffe','Abundant'],['🦓','Zebra','Abundant'],['🐊','Crocodile','Mara River'],['🦅','500+ Birds','Year-round']],
        'activities'=> [['fa-car','emerald','Game Drives'],['fa-hot-air-balloon','amber','Balloon Safari'],['fa-walking','blue','Bush Walks'],['fa-users','purple','Maasai Village'],['fa-camera','rose','Photography'],['fa-moon','yellow','Night Drive']],
        'seasons' => [['Jan–Mar',75,'Great'],['Apr–Jun',45,'Moderate'],['Jul–Oct',100,'Peak ⭐'],['Nov–Dec',65,'Good']],
        'facts'   => [['Size','14,750 km²'],['Altitude','920–1,850m'],['From Arusha','335 km · 6 hrs'],['Fly-in','1 hr'],['Best For','Migration (Jul–Oct)']],
        'accom'   => [['amber','Luxury Tented Camps','$400+'],['emerald','Mid-range Lodges','$150–400'],['blue','Budget Camps','$60–150']],
        'map'     => [130, 130],
    ],
    'ngorongoro' => [
        'badge'   => ['UNESCO Heritage','#8b5cf6','rgba(139,92,246,.85)','#fff','fa-award'],
        'tagline' => 'The Eighth Wonder of the World',
        'region'  => 'Crater Highlands • 8,288 km²',
        'wildlife'=> [['🦁','Lion','Abundant'],['🐘','Elephant','Common'],['🦏','Black Rhino','Rare ★'],['🦬','Wildebeest','250,000+'],['🐃','Buffalo','Abundant'],['🐆','Leopard','Possible'],['🦛','Hippo','Crater Lake'],['🦩','Flamingos','Seasonal'],['🦒','Giraffe','Common'],['🦅','500+ Birds','Year-round']],
        'activities'=> [['fa-car','emerald','Crater Drive'],['fa-binoculars','blue','Rhino Tracking'],['fa-walking','amber','Highland Walks'],['fa-users','purple','Maasai Culture'],['fa-camera','rose','Photography']],
        'seasons' => [['Jan–Mar',90,'Peak ⭐'],['Apr–Jun',55,'Good'],['Jul–Oct',95,'Peak ⭐'],['Nov–Dec',80,'Very Good']],
        'facts'   => [['Crater Depth','600m'],['Crater Width','19km'],['From Arusha','180 km · 3 hrs'],['Altitude','2,285m rim'],['Entry Fee','$70/person']],
        'accom'   => [['amber','Rim Luxury Lodges','$500+'],['emerald','Crater Camps','$200–500'],['blue','Karatu Lodges','$80–200']],
        'map'     => [175, 165],
    ],
    'kilimanjaro' => [
        'badge'   => ['Adventure',   '#3b82f6','rgba(59,130,246,.85)','#fff','fa-mountain'],
        'tagline' => 'Roof of Africa — 5,895m',
        'region'  => 'Kilimanjaro Region • Multiple Routes',
        'wildlife'=> [['🐘','Elephant','Forest Zone'],['🦍','Colobus Monkey','Rainforest'],['🦜','Hartlaub\'s Turaco','Rainforest'],['🌿','Moorland Flora','Heath Zone'],['❄️','Arctic Summit','5,895m'],['🦅','Eagles','Alpine Zone'],['🌺','Giant Lobelia','Moorland'],['🌳','Ancient Forest','Lower Zone']],
        'activities'=> [['fa-hiking','emerald','Summit Trek'],['fa-route','blue','6 Routes'],['fa-campsite','amber','Camp Nights'],['fa-binoculars','purple','Wildlife Walks'],['fa-camera','rose','Summit Sunrise']],
        'seasons' => [['Jan–Mar',90,'Best ⭐'],['Apr–May',30,'Avoid (Rain)'],['Jun–Oct',85,'Excellent'],['Nov–Dec',70,'Good']],
        'facts'   => [['Summit','5,895m Uhuru Peak'],['Routes','Marangu, Machame, Lemosho+'],['Duration','5–9 Days'],['From Arusha','85 km · 1.5 hrs'],['Success Rate','~65% average']],
        'accom'   => [['amber','Mountain Lodges','$150+'],['emerald','Tented Camps','$80–200'],['blue','Mountain Huts','$40–80']],
        'map'     => [265, 145],
    ],
    'zanzibar' => [
        'badge'   => ['Beach Paradise','#06b6d4','rgba(6,182,212,.85)','#fff','fa-umbrella-beach'],
        'tagline' => 'Spice Island Paradise',
        'region'  => 'Zanzibar Archipelago • Indian Ocean',
        'wildlife'=> [['🐠','Tropical Fish','Reef'],['🐢','Sea Turtles','Nesting'],['🦈','Whale Shark','Seasonal'],['🐬','Dolphins','Year-round'],['🦜','Zanzibar Red Colobus','Unique to Zanzibar'],['🦀','Coconut Crab','Beaches'],['🪸','Coral Reefs','Diving'],['🐙','Octopus','Snorkeling']],
        'activities'=> [['fa-snorkeling','blue','Snorkeling & Diving'],['fa-ship','cyan','Dhow Cruise'],['fa-city','amber','Stone Town Tour'],['fa-pepper','emerald','Spice Farm'],['fa-umbrella-beach','rose','Beach Relaxation'],['fa-dolphin','blue','Dolphin Tour']],
        'seasons' => [['Jun–Oct',100,'Peak ⭐'],['Nov–Dec',75,'Good'],['Jan–Feb',85,'Great'],['Mar–May',30,'Rainy Season']],
        'facts'   => [['Location','Indian Ocean · 40km off coast'],['Size','1,651 km²'],['UNESCO','Stone Town Heritage Site'],['Flight from Dar','25 minutes'],['Best Beach','Nungwi, Kendwa, Paje']],
        'accom'   => [['amber','Boutique Beach Resorts','$300+'],['emerald','Mid-range Hotels','$100–300'],['blue','Budget Guesthouses','$30–100']],
        'map'     => [305, 268],
    ],
    'tarangire' => [
        'badge'   => ['Hidden Gem',  '#f97316','rgba(249,115,22,.85)','#fff','fa-gem'],
        'tagline' => 'Land of Giants & Ancient Trees',
        'region'  => 'Manyara Region • 2,850 km²',
        'wildlife'=> [['🐘','Elephant','Herds of 300+'],['🦁','Lion','Tree-climbing'],['🐃','Buffalo','Large Herds'],['🦒','Giraffe','Masai Giraffe'],['🦓','Zebra','Abundant'],['🐃','Wildebeest','Seasonal'],['🦢','Stork','Baobab Trees'],['🦅','550+ Birds','Highest in TZ']],
        'activities'=> [['fa-car','emerald','Game Drives'],['fa-campsite','amber','Bush Camping'],['fa-walking','blue','Walking Safaris'],['fa-binoculars','purple','Birding'],['fa-camera','rose','Baobab Photography']],
        'seasons' => [['Jan–Mar',70,'Good'],['Apr–Jun',40,'Green Season'],['Jul–Oct',100,'Peak ⭐'],['Nov–Dec',65,'Good']],
        'facts'   => [['Size','2,850 km²'],['Famous For','Elephant herds, Baobabs'],['From Arusha','120 km · 2 hrs'],['Best Months','July–October'],['Bird Species','550+']],
        'accom'   => [['amber','Luxury Tented Camps','$350+'],['emerald','Mid-range Lodges','$120–350'],['blue','Budget Campsites','$40–120']],
        'map'     => [200, 195],
    ],
    'maasai' => [
        'badge'   => ['Cultural',    '#10b981','rgba(16,185,129,.85)','#fff','fa-users'],
        'tagline' => 'Soul of Africa — Living Culture',
        'region'  => 'Arusha, Manyara & Kilimanjaro Regions',
        'wildlife'=> [['🦁','Lions','Maasai Land'],['🦒','Giraffe','Savannahs'],['🦓','Zebra','Open Plains'],['🐘','Elephant','Corridors'],['🐃','Wildebeest','Migration Route'],['🐦','Maasai Ostrich','Grasslands'],['🐄','Maasai Cattle','Cultural'],['🌿','Medicinal Plants','Traditional']],
        'activities'=> [['fa-users','emerald','Village Visits'],['fa-music','amber','Traditional Dance'],['fa-shield-alt','purple','Warrior Training'],['fa-leaf','green','Herbal Medicine'],['fa-drum','rose','Cultural Ceremony'],['fa-walking','blue','Guided Walks']],
        'seasons' => [['Jan–Mar',85,'Great'],['Apr–Jun',65,'Good'],['Jul–Oct',90,'Peak'],['Nov–Dec',80,'Great']],
        'facts'   => [['Best For','Cultural experiences'],['Location','Around Arusha & Ngorongoro'],['Duration','1–3 days'],['Language','Maa (Maasai)'],['Recommended','With all safaris']],
        'accom'   => [['amber','Cultural Camps','$200+'],['emerald','Community Lodges','$80–200'],['blue','Boma Stays','$40–80']],
        'map'     => [175, 230],
    ],
];

/* Map any destination title/slug → destination-detail.php slug */
function destDetailSlug(string $title, string $slug = ''): string {
    $s = strtolower($title . ' ' . $slug);
    if (str_contains($s, 'serengeti'))                          return 'serengeti';
    if (str_contains($s, 'ngorongoro'))                         return 'ngorongoro';
    if (str_contains($s, 'kilimanjaro') || str_contains($s,'kili')) return 'kilimanjaro';
    if (str_contains($s, 'zanzibar'))                           return 'zanzibar';
    if (str_contains($s, 'tarangire'))                          return 'tarangire';
    if (str_contains($s, 'maasai') || str_contains($s,'masai')) return 'maasai-heartland';
    return '';
}

$pageTitle    = 'Tanzania Destinations | Serengeti, Zanzibar & More — Jambo Masai Tours';
$pageDescription = 'Explore Tanzania\'s most breathtaking destinations: Serengeti, Ngorongoro Crater, Kilimanjaro, Zanzibar and more.';
$currentPage  = 'destinations';
$canonicalUrl = SITE_URL . '/destinations.php';

$extraCss = '
  /* Destination cards */
  .dest-card-main{background:rgba(17,17,17,.9);border:1px solid rgba(255,255,255,.06);border-radius:24px;overflow:hidden;transition:all .4s}
  .dest-card-main:hover{border-color:rgba(16,185,129,.2);box-shadow:0 30px 80px rgba(0,0,0,.5)}
  .dest-hero-link{display:block;cursor:pointer}
  .dest-hero-link:hover .dest-hero-img{transform:scale(1.04)}
  .dest-hero-img{transition:transform .6s cubic-bezier(.4,0,.2,1)}
  /* Wildlife cards */
  .wl-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:.75rem .5rem;text-align:center;transition:all .3s}
  .wl-card:hover{transform:scale(1.06);border-color:rgba(16,185,129,.2)}
  /* Activity tags */
  .act-tag{display:inline-flex;align-items:center;gap:.5rem;font-size:.72rem;font-family:"Montserrat",sans-serif;font-weight:600;padding:.5rem 1rem;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);color:rgba(255,255,255,.6);cursor:default;transition:all .25s}
  .act-tag:hover{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);color:rgba(255,255,255,.85)}
  /* Season bars */
  .s-bar{height:7px;border-radius:4px;background:rgba(255,255,255,.05);overflow:hidden;margin-top:.35rem}
  .s-fill{height:100%;border-radius:4px;transition:width 1.2s cubic-bezier(.4,0,.2,1)}
  /* Gallery grid */
  .gal-img{overflow:hidden;border-radius:0;cursor:pointer}
  .gal-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.4,0,.2,1)}
  .gal-img:hover img{transform:scale(1.1)}
  /* Lightbox */
  #lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.96);display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity .3s}
  #lightbox.open{opacity:1;pointer-events:all}
  #lightbox img{max-width:100%;max-height:90vh;border-radius:12px;object-fit:contain}
  #lb-close{position:absolute;top:1.5rem;right:1.5rem;color:#fff;background:rgba(255,255,255,.1);width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;font-size:1.2rem}
  #lb-close:hover{background:rgba(255,255,255,.2)}
  /* Dest tab */
  .dtab{display:inline-flex;align-items:center;gap:.4rem;font-size:.68rem;font-family:"Montserrat",sans-serif;font-weight:600;padding:.55rem 1.1rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:rgba(255,255,255,.5);cursor:pointer;white-space:nowrap;transition:all .25s}
  .dtab:hover{background:rgba(255,255,255,.08);color:#fff}
  .dtab.active{background:linear-gradient(135deg,#047857,#10b981);color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(16,185,129,.3)}
  /* SVG map dot */
  .mdot{cursor:pointer}
  .mdot .mdot-main{transition:r .3s,filter .3s}
  .mdot:hover .mdot-main{r:11}
  .mdot text{transition:fill .25s}
  .mdot:hover text{fill:#fff}
  /* Scroll fade right */
  .sfade-r{position:relative}
  .sfade-r::after{content:"";position:absolute;top:0;right:0;bottom:0;width:40px;background:linear-gradient(to right,transparent,#0a0a0a);pointer-events:none}
  /* FAQ filter */
  .f-input,.f-select,.f-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.75rem 1rem;font-family:"Inter",sans-serif;font-size:.88rem;outline:none;transition:border-color .2s;-webkit-appearance:none}
  .f-input:focus,.f-select:focus,.f-textarea:focus{border-color:rgba(16,185,129,.5)}
  .f-textarea{resize:vertical;min-height:100px}
  .f-select option{background:#1a1a1a}
  .f-label{display:block;font-family:"Montserrat",sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.38);margin-bottom:.4rem}
  .f-label span{color:#f87171}
';
require_once 'includes/dark_header.php';
?>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_SERENGETI ?>" alt="Tanzania Destinations" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.38">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.75),transparent 65%)"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">Destinations</span>
    </nav>
    <div class="anim-up" style="animation-delay:.1s">
      <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fas fa-map-marker-alt text-emerald-400 text-xs"></i>
        <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.18em] uppercase font-nav"><?= count($destinations) ?> Incredible Destinations</span>
      </div>
    </div>
    <h1 class="anim-up font-heading text-white font-bold leading-tight" style="font-size:clamp(2rem,5vw,3.2rem);animation-delay:.2s">
      Explore Tanzania's <span class="hero-grad">Iconic</span> Wild Places
    </h1>
    <p class="anim-up text-white/55 mt-3 max-w-xl text-[.95rem] leading-relaxed" style="animation-delay:.3s">
      From the legendary plains of the Serengeti to Zanzibar's turquoise shores — every destination tells a different story of Africa's magic.
    </p>
  </div>
</div>

<!-- TANZANIA MAP + STATS -->
<section class="py-10 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="glass-card p-6 lg:p-8 reveal">
      <div class="flex flex-col lg:flex-row items-start gap-8">

        <!-- Tanzania SVG Map -->
        <div class="flex-1 w-full">
          <p class="font-nav text-[.6rem] font-bold uppercase tracking-widest text-white/25 mb-4">
            <i class="fas fa-globe-africa text-emerald-400 mr-1.5"></i> Tanzania Destination Map
          </p>
          <div class="relative rounded-2xl overflow-hidden" style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);aspect-ratio:16/10">
            <svg viewBox="0 0 600 380" class="w-full h-full p-6" fill="none">
              <defs>
                <filter id="mglow" x="-60%" y="-60%" width="220%" height="220%">
                  <feGaussianBlur stdDeviation="3" result="blur"/>
                  <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
              </defs>
              <!-- Tanzania outline (simplified) -->
              <path d="M100,60 L190,40 L310,35 L400,50 L460,85 L480,130 L470,180 L490,240 L465,295 L430,335 L370,358 L290,362 L210,348 L150,310 L110,260 L90,200 L88,140 Z"
                    fill="rgba(16,185,129,.04)" stroke="rgba(16,185,129,.25)" stroke-width="1.5" stroke-dasharray="5,4"/>
              <!-- Zanzibar island -->
              <ellipse cx="498" cy="272" rx="16" ry="22" fill="rgba(6,182,212,.06)" stroke="rgba(6,182,212,.3)" stroke-width="1.2"/>

              <!-- Serengeti -->
              <g class="mdot" onclick="scrollToDest('serengeti')">
                <circle cx="155" cy="138" r="3" fill="#10b981" opacity=".6">
                  <animate attributeName="r" values="3;13" dur="2.6s" begin="0s" repeatCount="indefinite"/>
                  <animate attributeName="opacity" values=".6;0" dur="2.6s" begin="0s" repeatCount="indefinite"/>
                </circle>
                <circle class="mdot-main" cx="155" cy="138" r="8" fill="rgba(16,185,129,.12)" stroke="#10b981" stroke-width="1.5" filter="url(#mglow)"/>
                <circle cx="155" cy="138" r="3.5" fill="#fff"/>
                <text x="170" y="143" fill="#d4d4d4" font-size="10" font-family="Inter,sans-serif">Serengeti</text>
              </g>
              <!-- Ngorongoro -->
              <g class="mdot" onclick="scrollToDest('ngorongoro')">
                <circle cx="195" cy="168" r="3" fill="#10b981" opacity=".6">
                  <animate attributeName="r" values="3;12" dur="2.6s" begin=".6s" repeatCount="indefinite"/>
                  <animate attributeName="opacity" values=".6;0" dur="2.6s" begin=".6s" repeatCount="indefinite"/>
                </circle>
                <circle class="mdot-main" cx="195" cy="168" r="7" fill="rgba(16,185,129,.12)" stroke="#10b981" stroke-width="1.5" filter="url(#mglow)"/>
                <circle cx="195" cy="168" r="3" fill="#fff"/>
                <text x="210" y="173" fill="#d4d4d4" font-size="10" font-family="Inter,sans-serif">Ngorongoro</text>
              </g>
              <!-- Kilimanjaro -->
              <g class="mdot" onclick="scrollToDest('kilimanjaro')">
                <circle cx="280" cy="145" r="3" fill="#3b82f6" opacity=".6">
                  <animate attributeName="r" values="3;12" dur="2.6s" begin="1.2s" repeatCount="indefinite"/>
                  <animate attributeName="opacity" values=".6;0" dur="2.6s" begin="1.2s" repeatCount="indefinite"/>
                </circle>
                <circle class="mdot-main" cx="280" cy="145" r="7" fill="rgba(59,130,246,.12)" stroke="#3b82f6" stroke-width="1.5" filter="url(#mglow)"/>
                <circle cx="280" cy="145" r="3" fill="#fff"/>
                <text x="295" y="150" fill="#d4d4d4" font-size="10" font-family="Inter,sans-serif">Kilimanjaro</text>
              </g>
              <!-- Zanzibar -->
              <g class="mdot" onclick="scrollToDest('zanzibar')">
                <circle cx="498" cy="272" r="4" fill="#06b6d4" opacity=".6">
                  <animate attributeName="r" values="4;16" dur="3s" begin="0s" repeatCount="indefinite"/>
                  <animate attributeName="opacity" values=".6;0" dur="3s" begin="0s" repeatCount="indefinite"/>
                </circle>
                <circle cx="498" cy="272" r="13" fill="none" stroke="rgba(6,182,212,.35)" stroke-width="1"/>
                <circle class="mdot-main" cx="498" cy="272" r="8" fill="rgba(6,182,212,.15)" stroke="#06b6d4" stroke-width="1.5" filter="url(#mglow)"/>
                <circle cx="498" cy="272" r="3" fill="#fff"/>
                <text x="455" y="255" fill="#d4d4d4" font-size="10" font-family="Inter,sans-serif">Zanzibar</text>
              </g>
              <!-- Tarangire -->
              <g class="mdot" onclick="scrollToDest('tarangire')">
                <circle cx="218" cy="200" r="3" fill="#f97316" opacity=".6">
                  <animate attributeName="r" values="3;11" dur="2.6s" begin="1.8s" repeatCount="indefinite"/>
                  <animate attributeName="opacity" values=".6;0" dur="2.6s" begin="1.8s" repeatCount="indefinite"/>
                </circle>
                <circle class="mdot-main" cx="218" cy="200" r="6.5" fill="rgba(249,115,22,.12)" stroke="#f97316" stroke-width="1.5" filter="url(#mglow)"/>
                <circle cx="218" cy="200" r="3" fill="#fff"/>
                <text x="233" y="205" fill="#d4d4d4" font-size="10" font-family="Inter,sans-serif">Tarangire</text>
              </g>
              <!-- Maasai Heartland -->
              <g class="mdot" onclick="scrollToDest('maasai')">
                <circle class="mdot-main" cx="185" cy="235" r="5.5" fill="#10b981" filter="url(#mglow)"/>
                <text x="130" y="252" fill="#d4d4d4" font-size="9" font-family="Inter,sans-serif">Maasai Heartland</text>
              </g>
              <!-- Arusha (hub) -->
              <g>
                <circle cx="225" cy="152" r="5" fill="#fbbf24" filter="url(#mglow)"/>
                <text x="235" y="150" fill="#fbbf24" font-size="9" font-family="Inter,sans-serif" font-weight="700">Arusha ✈</text>
              </g>
              <!-- Dar es Salaam -->
              <g>
                <circle cx="400" cy="295" r="4" fill="#94a3b8" opacity=".7"/>
                <text x="408" y="299" fill="#94a3b8" font-size="8" font-family="Inter,sans-serif">Dar es Salaam ✈</text>
              </g>
            </svg>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="lg:w-64 space-y-3 w-full">
          <p class="font-nav text-[.6rem] font-bold uppercase tracking-widest text-white/25 mb-3">At a Glance</p>
          <?php foreach ([
            ['fa-map','emerald',       count($destinations).' Parks','Incredible Destinations'],
            ['fa-paw','amber',         'Big Five','In 3+ Locations'],
            ['fa-mountain','blue',     '5,895m','Kilimanjaro Summit'],
            ['fa-umbrella-beach','cyan','Zanzibar','Indian Ocean Paradise'],
          ] as $s): ?>
          <div class="flex items-center gap-3 rounded-xl p-3.5" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06)">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(<?= $s[1]==='emerald'?'16,185,129':($s[1]==='amber'?'245,158,11':($s[1]==='blue'?'59,130,246':'6,182,212')) ?>,.12)">
              <i class="fas <?= $s[0] ?>" style="color:<?= $s[1]==='emerald'?'#34d399':($s[1]==='amber'?'#fbbf24':($s[1]==='blue'?'#60a5fa':'#22d3ee')) ?>"></i>
            </div>
            <div>
              <div class="text-base font-bold text-white leading-tight"><?= $s[2] ?></div>
              <div class="text-[.68rem] text-white/35 font-nav"><?= $s[3] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <a href="<?= url('booking.php') ?>" class="btn-em btn-em-primary w-full justify-center mt-1 text-xs">
            <i class="fas fa-compass text-xs"></i> Plan My Trip
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- QUICK NAV TABS -->
<section class="py-4 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="sfade-r overflow-x-auto pb-1">
      <div style="display:flex;gap:.5rem;min-width:max-content">
        <?php foreach ($destinations as $i => $d):
          $slug = $d['slug'] ?? strtolower(str_replace(' ','-',$d['title']));
          $dd   = $destData[$slug] ?? $destData['serengeti'];
          $clr  = ['#10b981','#f59e0b','#3b82f6','#06b6d4','#f97316','#8b5cf6'][$i % 6];
        ?>
        <button class="dtab <?= $i===0?'active':'' ?>" onclick="scrollToDest('<?= e($slug) ?>')" id="tab-<?= e($slug) ?>">
          <span style="width:6px;height:6px;border-radius:50%;background:<?= $clr ?>;flex-shrink:0"></span>
          <?= e($d['title']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- DESTINATION SECTIONS -->
<?php foreach ($destinations as $idx => $d):
  $slug       = $d['slug'] ?? strtolower(str_replace(' ','-',$d['title']));
  $dd         = $destData[$slug] ?? null;
  $badge      = $dd['badge']     ?? ['Featured','#10b981','rgba(16,185,129,.85)','#fff','fa-star'];
  $dTours     = $toursByDest[strtolower($d['title'])] ?? $toursByDest[$slug] ?? [];
  $hls        = array_filter(explode('|', $d['highlights'] ?? ''));
  $img        = $d['image'] ?: IMG_SERENGETI;
  $detailSlug = destDetailSlug($d['title'], $slug);
  $detailUrl  = $detailSlug ? url('destination/' . $detailSlug) : '';
?>
<section id="<?= e($slug) ?>" class="py-8 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="dest-card-main reveal">

      <!-- Hero image band — links to detail page if available -->
      <?php $heroTag = $detailUrl ? 'a href="'.e($detailUrl).'"' : 'div'; $heroClose = $detailUrl ? 'a' : 'div'; ?>
      <<?= $heroTag ?> class="relative overflow-hidden<?= $detailUrl ? ' dest-hero-link' : '' ?>" style="height:300px;display:block">
        <img src="<?= e($img) ?>" alt="<?= e($d['title']) ?> Tanzania Safari"
             class="w-full h-full object-cover dest-hero-img" loading="<?= $idx<2?'eager':'lazy' ?>"
             style="transition:transform .6s cubic-bezier(.4,0,.2,1)">
        <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(17,17,17,1) 0%,rgba(0,0,0,.2) 55%,transparent 100%)"></div>
        <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(0,0,0,.5),transparent 60%)"></div>

        <!-- Badge -->
        <div class="absolute top-5 left-5 z-10">
          <span class="inline-flex items-center gap-1.5 font-nav font-bold text-[.62rem] uppercase tracking-wider px-3 py-1 rounded-full"
                style="background:<?= $badge[2] ?>;color:<?= $badge[3] ?>">
            <i class="fas <?= $badge[4] ?> text-[.5rem]"></i><?= $badge[0] ?>
          </span>
        </div>

        <?php if ($detailUrl): ?>
        <!-- "Learn More" chip -->
        <div class="absolute top-5 right-5 z-10">
          <span class="inline-flex items-center gap-1.5 font-nav font-bold text-[.6rem] uppercase tracking-wider px-2.5 py-1 rounded-full"
                style="background:rgba(0,0,0,.55);backdrop-filter:blur(8px);color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.15)">
            <i class="fas fa-arrow-right text-[.5rem]"></i>Learn More
          </span>
        </div>
        <?php endif; ?>

        <!-- Bottom info -->
        <div class="absolute bottom-0 left-0 right-0 p-6 z-10">
          <div class="flex items-center gap-2 mb-1.5">
            <i class="fas fa-map-marker-alt text-emerald-400 text-xs"></i>
            <span class="text-emerald-400 text-[.72rem] font-nav font-semibold"><?= e($dd['region'] ?? ($d['region'].', '.$d['country'])) ?></span>
          </div>
          <h2 class="font-heading text-white font-bold leading-tight" style="font-size:clamp(1.8rem,4vw,3rem)"><?= e($d['title']) ?></h2>
          <?php if ($dd): ?>
          <p class="text-white/50 text-sm mt-0.5 font-nav"><?= $dd['tagline'] ?></p>
          <?php endif; ?>
        </div>
      </<?= $heroClose ?>>

      <!-- Content grid -->
      <div class="p-6 lg:p-8">
        <div class="grid lg:grid-cols-3 gap-10">

          <!-- Left column (2/3) -->
          <div class="lg:col-span-2 space-y-8">

            <!-- Description -->
            <div>
              <p class="text-white/55 leading-[1.85] text-[.93rem]"><?= e($d['description']) ?></p>
              <?php if (!empty($hls)): ?>
              <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach($hls as $hl): ?>
                <span class="inline-flex items-center gap-1 text-[.68rem] font-nav font-semibold px-3 py-1 rounded-full" style="background:rgba(16,185,129,.08);color:#34d399;border:1px solid rgba(16,185,129,.15)">
                  <i class="fas fa-check text-[.5rem]"></i><?= e(trim($hl)) ?>
                </span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Wildlife -->
            <?php if ($dd && !empty($dd['wildlife'])): ?>
            <div>
              <h3 class="text-[.82rem] font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-paw text-emerald-400 text-xs"></i>
                <span class="text-white/80">Wildlife You'll Encounter</span>
              </h3>
              <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                <?php foreach($dd['wildlife'] as $w): ?>
                <div class="wl-card">
                  <div class="text-2xl mb-1"><?= $w[0] ?></div>
                  <div style="font-size:.62rem;color:rgba(255,255,255,.45)"><?= $w[1] ?></div>
                  <div style="font-size:.58rem;font-family:'Montserrat',sans-serif;font-weight:700;margin-top:.2rem;color:<?= in_array($w[2],['Abundant','Year-round','Common']) ? '#34d399' : (str_contains($w[2],'Rare')||str_contains($w[2],'Unique') ? '#a78bfa' : '#fbbf24') ?>"><?= $w[2] ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Activities -->
            <?php if ($dd && !empty($dd['activities'])): ?>
            <div>
              <h3 class="text-[.82rem] font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-binoculars text-emerald-400 text-xs"></i>
                <span class="text-white/80">Things to Do</span>
              </h3>
              <div class="flex flex-wrap gap-2">
                <?php foreach($dd['activities'] as $a):
                  $aclr = ['emerald'=>'#34d399','amber'=>'#fbbf24','blue'=>'#60a5fa','purple'=>'#a78bfa','rose'=>'#fb7185','cyan'=>'#22d3ee','yellow'=>'#fde047','green'=>'#4ade80','pink'=>'#f472b6'][$a[1]] ?? '#34d399';
                ?>
                <span class="act-tag"><i class="fas <?= $a[0] ?> text-xs" style="color:<?= $aclr ?>"></i><?= $a[2] ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Tours for this destination -->
            <?php if (!empty($dTours)): ?>
            <div>
              <h3 class="text-[.82rem] font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-route text-emerald-400 text-xs"></i>
                <span class="text-white/80">Available Safari Packages</span>
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach(array_slice($dTours,0,4) as $t): ?>
                <a href="<?= url('tour/'.e($t['slug'])) ?>"
                   class="flex items-center gap-3 p-3.5 rounded-xl transition-all group"
                   style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07)">
                  <?php if ($t['image']): ?>
                  <img src="<?= e($t['image']) ?>" alt="" class="w-14 h-12 rounded-lg object-cover flex-shrink-0">
                  <?php endif; ?>
                  <div class="min-w-0 flex-1">
                    <div class="text-white text-[.82rem] font-semibold leading-snug truncate group-hover:text-emerald-400 transition-colors"><?= e($t['name']) ?></div>
                    <div class="flex items-center gap-2 mt-0.5">
                      <span class="text-white/35 text-[.65rem] font-nav"><?= e($t['duration']) ?></span>
                      <span class="text-emerald-400 text-[.65rem] font-nav font-semibold"><?= formatPrice($t['price']) ?></span>
                    </div>
                  </div>
                  <i class="fas fa-arrow-right text-white/20 group-hover:text-emerald-400 text-xs transition-colors flex-shrink-0"></i>
                </a>
                <?php endforeach; ?>
              </div>
              <?php if (count($dTours) > 4): ?>
              <div class="mt-3">
                <a href="<?= $detailUrl ?: url('tours.php?destination='.urlencode($d['title'])) ?>" class="text-emerald-400 hover:text-emerald-300 text-[.72rem] font-nav font-semibold transition-colors">
                  View all <?= count($dTours) ?> <?= e($d['title']) ?> safaris →
                </a>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

          </div>

          <!-- Right sidebar (1/3) -->
          <div class="space-y-4">

            <!-- Best time to visit -->
            <?php if ($dd && !empty($dd['seasons'])): ?>
            <div class="glass-card p-5">
              <h4 class="font-nav font-bold text-[.62rem] uppercase tracking-widest text-white/35 mb-3.5 flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-emerald-400"></i> Best Time to Visit
              </h4>
              <div class="space-y-3">
                <?php foreach($dd['seasons'] as $s):
                  $ispeak = str_contains($s[2],'Peak') || str_contains($s[2],'Best');
                  $clr = $ispeak ? '#10b981' : (str_contains($s[2],'Avoid')||str_contains($s[2],'Rain') ? '#f87171' : '#f59e0b');
                  $bg  = $ispeak ? 'rgba(16,185,129,.3)' : (str_contains($s[2],'Avoid') ? 'rgba(248,113,113,.3)' : 'rgba(245,158,11,.3)');
                ?>
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <span class="font-nav text-[.68rem] text-white/50"><?= $s[0] ?></span>
                    <span class="font-nav text-[.62rem] font-bold" style="color:<?= $clr ?>"><?= $s[2] ?></span>
                  </div>
                  <div class="s-bar"><div class="s-fill" style="width:<?= $s[1] ?>%;background:<?= $bg ?>"></div></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Quick facts -->
            <?php if ($dd && !empty($dd['facts'])): ?>
            <div class="glass-card p-5">
              <h4 class="font-nav font-bold text-[.62rem] uppercase tracking-widest text-white/35 mb-3.5 flex items-center gap-1.5">
                <i class="fas fa-info-circle text-emerald-400"></i> Quick Facts
              </h4>
              <div class="space-y-2.5">
                <?php foreach($dd['facts'] as $f): ?>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-white/35 font-nav text-[.75rem]"><?= $f[0] ?></span>
                  <span class="text-white/75 font-semibold text-[.78rem]"><?= $f[1] ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Accommodation tiers -->
            <?php if ($dd && !empty($dd['accom'])): ?>
            <div class="glass-card p-5">
              <h4 class="font-nav font-bold text-[.62rem] uppercase tracking-widest text-white/35 mb-3.5 flex items-center gap-1.5">
                <i class="fas fa-bed text-emerald-400"></i> Accommodation
              </h4>
              <div class="space-y-2.5">
                <?php foreach($dd['accom'] as $a):
                  $ac = ['amber'=>'#fbbf24','emerald'=>'#34d399','blue'=>'#60a5fa'][$a[0]] ?? '#34d399';
                ?>
                <div class="flex items-center gap-2 text-xs">
                  <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= $ac ?>"></div>
                  <span class="text-white/60 flex-1"><?= $a[1] ?></span>
                  <span class="font-semibold text-white/40 font-nav"><?= $a[2] ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- CTA -->
            <?php if ($detailUrl): ?>
            <a href="<?= e($detailUrl) ?>"
               class="btn-em btn-em-primary w-full justify-center text-xs">
              <i class="fas fa-compass text-xs"></i> Explore <?= e($d['title']) ?>
            </a>
            <?php else: ?>
            <a href="<?= url('tours.php?destination='.urlencode($d['title'])) ?>"
               class="btn-em btn-em-primary w-full justify-center text-xs">
              <i class="fas fa-compass text-xs"></i> Explore <?= e($d['title']) ?> Safaris
            </a>
            <?php endif; ?>
            <a href="<?= url('booking.php') ?>"
               class="btn-em btn-em-outline w-full justify-center text-xs">
              <i class="fas fa-calendar-check text-xs text-emerald-400"></i> Book Custom Trip
            </a>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<!-- BEST TIME CALENDAR -->
<section class="py-14 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="text-center max-w-2xl mx-auto mb-10 reveal">
      <div class="section-tag">Plan Your Safari</div>
      <h2 class="font-heading text-white text-3xl font-bold mt-1">Best Time to Visit <span class="hero-grad">Tanzania</span></h2>
      <p class="text-white/45 mt-3 text-[.92rem]">Use this guide to find your ideal travel window for each activity.</p>
    </div>
    <div class="glass-card p-5 lg:p-6 overflow-x-auto reveal">
      <table style="width:100%;border-collapse:collapse;font-size:.75rem">
        <thead>
          <tr>
            <th style="text-align:left;padding:.5rem 1rem .5rem 0;font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.3);min-width:140px">Activity</th>
            <?php foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m): ?>
            <th style="padding:.5rem .3rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:600;color:rgba(255,255,255,.3)"><?= $m ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ([
          ['🦁 Wildlife Safari',   'Serengeti & Ngorongoro',['ok','ok','ok','no','no','best','best','best','best','ok','ok','ok']],
          ['🦬 Great Migration',   'Serengeti crossing',   ['ok','ok','no','no','no','ok','best','best','best','ok','no','ok']],
          ['🏔️ Kilimanjaro Trek',  'Summit routes',        ['best','best','ok','no','no','ok','best','best','ok','best','no','best']],
          ['🏖️ Zanzibar Beach',    'Beaches & diving',     ['best','best','ok','no','no','ok','best','best','best','ok','no','best']],
          ['🐦 Birdwatching',      'All parks & reserves', ['ok','ok','ok','ok','ok','ok','ok','ok','ok','ok','best','best']],
          ['💰 Budget Safari',     'Green season value',   ['ok','ok','ok','best','best','ok','no','no','no','ok','best','ok']],
        ] as $row): ?>
        <tr style="border-bottom:1px solid rgba(255,255,255,.05)">
          <td style="padding:.65rem 1rem .65rem 0">
            <div style="font-size:.8rem;color:rgba(255,255,255,.75);font-weight:500"><?= $row[0] ?></div>
            <div style="font-size:.65rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif"><?= $row[1] ?></div>
          </td>
          <?php foreach($row[2] as $c): ?>
          <td style="padding:.65rem .3rem;text-align:center">
            <?php if($c==='best'): ?>
            <span style="width:11px;height:11px;border-radius:50%;background:#10b981;display:inline-block"></span>
            <?php elseif($c==='ok'): ?>
            <span style="width:11px;height:11px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
            <?php else: ?>
            <span style="width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.12);display:inline-block;margin:2px"></span>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div class="flex flex-wrap items-center gap-5 mt-5 pt-4" style="border-top:1px solid rgba(255,255,255,.06)">
        <span class="flex items-center gap-2 text-white/40 text-xs font-nav"><span style="width:11px;height:11px;border-radius:50%;background:#10b981;display:inline-block"></span> Peak / Excellent</span>
        <span class="flex items-center gap-2 text-white/40 text-xs font-nav"><span style="width:11px;height:11px;border-radius:50%;background:#f59e0b;display:inline-block"></span> Good</span>
        <span class="flex items-center gap-2 text-white/40 text-xs font-nav"><span style="width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.15);display:inline-block"></span> Not Recommended</span>
      </div>
    </div>
  </div>
</section>

<!-- QUICK ENQUIRY -->
<section class="py-14 px-4 lg:px-0" id="enquiry">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-[1fr_1.3fr] gap-12 items-start">
      <div class="reveal">
        <div class="section-tag">Start Planning</div>
        <h2 class="font-heading text-white text-3xl font-bold mt-1 mb-5">Ready to Explore <span class="hero-grad">Tanzania?</span></h2>
        <p class="text-white/50 leading-relaxed mb-7 text-[.93rem]">Tell us your dream destination and travel dates. Our experts will craft a tailor-made itinerary — no obligation.</p>
        <div class="space-y-3 mb-7">
          <?php foreach(['Free personalised itinerary','Response within 24 hours','No booking fees','Expert local knowledge'] as $f): ?>
          <div class="flex items-center gap-3 text-white/55 text-[.88rem]"><i class="fas fa-check-circle text-emerald-400 text-sm flex-shrink-0"></i><?= $f ?></div>
          <?php endforeach; ?>
        </div>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= urlencode("Hello! I'd like to enquire about Tanzania destinations.") ?>" target="_blank" rel="noopener" class="btn-em btn-em-wa">
          <i class="fab fa-whatsapp text-lg"></i> Chat on WhatsApp
        </a>
      </div>

      <div class="glass-card p-6 reveal" style="transition-delay:100ms">
        <?php if ($formSuccess): ?>
        <div class="text-center py-8">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(16,185,129,.12)">
            <i class="fas fa-check text-emerald-400 text-xl"></i>
          </div>
          <h3 class="font-heading text-white text-xl font-bold mb-2">Enquiry Sent!</h3>
          <p class="text-white/45 text-sm mb-5">Our team will be in touch within 24 hours.</p>
          <a href="<?= url('destinations.php') ?>" class="btn-em btn-em-primary text-xs"><i class="fas fa-map text-xs"></i> Explore More</a>
        </div>
        <?php else: ?>
        <?php if (!empty($formErrors)): ?>
        <div class="rounded-xl p-4 mb-4 flex items-start gap-3" style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2)">
          <i class="fas fa-exclamation-circle text-red-400 mt-0.5"></i>
          <ul class="space-y-0.5"><?php foreach($formErrors as $err): ?><li class="text-red-300/80 text-[.83rem]">· <?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <h3 class="font-heading text-white text-xl font-bold mb-5">Send an Enquiry</h3>
        <form method="POST" novalidate>
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
          <input type="hidden" name="enquiry_submit" value="1">
          <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div><label class="f-label">Full Name <span>*</span></label><input type="text" name="enq_name" class="f-input" required placeholder="John Smith" value="<?= e($_POST['enq_name']??'') ?>"></div>
            <div><label class="f-label">Email <span>*</span></label><input type="email" name="enq_email" class="f-input" required placeholder="john@example.com" value="<?= e($_POST['enq_email']??'') ?>"></div>
          </div>
          <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="f-label">Preferred Destination <span>*</span></label>
              <select name="enq_destination" class="f-select" required>
                <option value="">Select destination…</option>
                <?php foreach($destinations as $d): ?><option value="<?= e($d['title']) ?>" <?= ($_POST['enq_destination']??'') === $d['title'] ? 'selected' : '' ?>><?= e($d['title']) ?></option><?php endforeach; ?>
                <option value="Multiple Destinations">Multiple Destinations</option>
              </select>
            </div>
            <div><label class="f-label">Travel Date</label><input type="month" name="enq_date" class="f-input" value="<?= e($_POST['enq_date']??'') ?>"></div>
          </div>
          <div class="mb-5"><label class="f-label">Tell Us About Your Dream Trip</label>
            <textarea name="enq_message" class="f-textarea" placeholder="Number of travellers, special interests, accommodation preferences…"><?= e($_POST['enq_message']??'') ?></textarea>
          </div>
          <button type="submit" class="btn-em btn-em-primary w-full justify-center"><i class="fas fa-paper-plane text-xs"></i> Send My Enquiry</button>
          <p class="text-center text-white/20 text-[.68rem] font-nav mt-3">We reply within 24 hours · No spam ever</p>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox"><button id="lb-close" onclick="closeLb()"><i class="fas fa-times"></i></button><img id="lb-img" src="" alt=""></div>

<?php
$pageScript = <<<'JS'
<script>
/* Scroll to destination */
window.scrollToDest = function(slug) {
  const el = document.getElementById(slug);
  if (!el) return;
  const offset = 80;
  window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
  /* Update active tab */
  document.querySelectorAll('.dtab').forEach(b => b.classList.toggle('active', b.getAttribute('onclick').includes("'"+slug+"'")));
};

/* Lightbox */
window.openLb = (src, alt) => {
  const lb = document.getElementById('lightbox'), img = document.getElementById('lb-img');
  if (!lb||!img) return;
  img.src = src; img.alt = alt||'';
  lb.classList.add('open'); document.body.style.overflow='hidden';
};
window.closeLb = () => { document.getElementById('lightbox')?.classList.remove('open'); document.body.style.overflow=''; };
document.getElementById('lightbox')?.addEventListener('click', e => { if(e.target===document.getElementById('lightbox')) closeLb(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLb(); });

/* Highlight active tab on scroll */
const sections = document.querySelectorAll('section[id]');
window.addEventListener('scroll', () => {
  let cur = '';
  sections.forEach(s => { if (s.getBoundingClientRect().top < 200) cur = s.id; });
  if (cur) document.querySelectorAll('.dtab').forEach(b => {
    b.classList.toggle('active', b.getAttribute('onclick')?.includes("'"+cur+"'"));
  });
}, { passive: true });

/* Animate season bars on reveal */
const barObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.style.width = e.target.dataset.w||e.target.style.width; });
}, { threshold: .3 });
document.querySelectorAll('.s-fill').forEach(el => barObs.observe(el));
</script>
JS;
require_once 'includes/dark_footer.php';
?>
