<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

/* Anwani ya zamani /about inaelekezwa (301) kwenye /about-us */
$_aboutPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('#/(about|about\.php)/?$#', $_aboutPath)) {
    header('Location: ' . SITE_URL . '/about-us', true, 301);
    exit;
}

$pageTitle       = 'About Jambo Masai Tours | Local Tanzania Safari Operator Since 2010';
$pageDescription = 'Founded in 2010 by Raphael Salewa, a local Maasai guide, Jambo Masai Tours is a Tanzania-based team of 30+ safari, Kilimanjaro and cultural specialists.';
$currentPage     = 'about';
$ogImage         = IMG_ABOUT;
$canonicalUrl    = SITE_URL . '/about-us';
$logoUrl_seo     = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$_aFav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$headExtra = '<link rel="icon" type="image/png" href="' . e($_aFav) . '">
<link rel="shortcut icon" href="' . e($_aFav) . '">
<link rel="apple-touch-icon" href="' . e($_aFav) . '">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "About Jambo Masai Tours",
  "url": "' . $canonicalUrl . '",
  "description": ' . json_encode($pageDescription) . ',
  "mainEntity": {
    "@type": "TravelAgency",
    "name": "Jambo Masai Tours",
    "url": "' . SITE_URL . '",
    "foundingDate": "2010",
    "founder": { "@type": "Person", "name": "Raphael Salewa", "jobTitle": "Founder and Maasai guide" },
    "logo": { "@type": "ImageObject", "url": "' . $logoUrl_seo . '" },
    "telephone": "+255659667271",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Arusha",
      "addressRegion": "Arusha",
      "addressCountry": "TZ"
    },
    "areaServed": ["Tanzania", "Kenya", "East Africa"],
    "numberOfEmployees": { "@type": "QuantitativeValue", "minValue": 30 },
    "memberOf": [
      { "@type": "Organization", "name": "Tanzania Association of Tour Operators (TATO)" },
      { "@type": "Organization", "name": "Kilimanjaro Porters Assistance Project (KPAP)" }
    ],
    "identifier": [
      { "@type": "PropertyValue", "name": "Licence number", "value": "022741" },
      { "@type": "PropertyValue", "name": "TALA licence number", "value": "035421" }
    ]
  }
}
</script>';
require_once 'includes/dark_header.php';

/* Maeneo tunayobobea (kila moja inaunganishwa na ukurasa wake) */
$ab_specialities = [
  ['fa-paw',            'Serengeti National Park',      'Vast golden plains, extraordinary wildlife, and the spectacular Great Migration.', url('destination/serengeti')],
  ['fa-mountain-sun',   'Ngorongoro Conservation Area', 'The world-famous Ngorongoro Crater, abundant wildlife, and Maasai heritage.', url('destination/ngorongoro')],
  ['fa-tree',           'Tarangire National Park',      'Magnificent elephants, ancient baobabs, and dramatic seasonal landscapes.', url('destination/tarangire')],
  ['fa-dove',           'Lake Manyara National Park',   'Groundwater forests, birdlife, diverse wildlife, and beautiful scenery.', url('destination/lake-manyara-national-park')],
  ['fa-mountain',       'Mount Kilimanjaro',            'Professionally guided trekking adventures to the Roof of Africa.', url('kilimanjaro')],
  ['fa-umbrella-beach', 'Zanzibar',                     'Tropical beaches, Swahili culture, historic Stone Town, and the Indian Ocean.', url('destination/zanzibar')],
  ['fa-people-group',   'Maasai cultural experiences',  'Meaningful opportunities to learn about local traditions, communities, and ways of life.', url('cultural-tours')],
  ['fa-compass',        'Hidden Tanzania',              'Lesser-known landscapes and destinations for travelers looking beyond the traditional safari routes.', url('destinations')],
];
$ab_services = [
  ['fa-binoculars',  'Tanzania Wildlife Safaris', 'Explore Tanzania\'s famous national parks in search of lions, elephants, leopards, cheetahs, buffalo, giraffes, zebras, wildebeest, and hundreds of other species. Our safari experiences can be tailored for couples, families, solo travelers, photographers, honeymooners, and private groups.', url('tours'), 'See safari tours'],
  ['fa-water',       'Great Migration Safaris', 'Experience one of Africa\'s most remarkable wildlife spectacles in the Serengeti. We create safari itineraries around the seasonal movements of wildebeest, zebras, predators, and other wildlife, helping travelers experience the Serengeti according to the time of year they visit.', url('migration'), 'Explore the Migration'],
  ['fa-mountain',    'Mount Kilimanjaro Trekking', 'Climb Africa\'s highest mountain with experienced mountain guides who understand the terrain, altitude, changing weather, and physical demands of the journey. From choosing the right Kilimanjaro route to preparing for the trek, our team helps make your mountain adventure safe, organized, and memorable.', url('kilimanjaro'), 'Plan your climb'],
  ['fa-people-group','Maasai Cultural Experiences', 'Discover another side of Tanzania through meaningful cultural experiences with local communities. Learn about traditions, knowledge, history, daily life, and the relationship between people and the land while traveling with respect for local communities and their way of life.', url('cultural-tours'), 'Discover cultural tours'],
  ['fa-tent',        'Tanzania Adventure & Nature Travel', 'Tanzania has far more to offer than its most famous destinations. We help travelers discover different landscapes, wildlife areas, walking experiences, cultural encounters, and destinations that can make a Tanzania holiday uniquely their own.', url('destinations'), 'Browse destinations'],
  ['fa-umbrella-beach','Zanzibar Holidays', 'Extend your Tanzania adventure to the Indian Ocean. Relax on Zanzibar\'s beaches, explore Stone Town, discover Swahili culture and history, enjoy the island\'s coastal landscapes, or combine Zanzibar with a Tanzania safari or Mount Kilimanjaro trek.', url('safari/zanzibar-combo'), 'Safari + Zanzibar'],
];
$ab_responsible = [
  'Respect for wildlife and natural habitats',
  'Responsible travel through protected areas',
  'Respect for local communities and cultural traditions',
  'Supporting local employment and professional guides',
  'Encouraging meaningful cultural exchange',
  'Protecting Tanzania\'s natural environment',
  'Creating travel experiences that can benefit local communities',
];
$ab_why = [
  ['Local Tanzanian Roots', 'Founded by Raphael Salewa and built in Tanzania, Jambo Masai Tours remains deeply connected to the country\'s communities, landscapes, wildlife, and culture.'],
  ['Experienced Professional Guides', 'Our team of more than 30 guides, naturalists, mountain guides, and travel specialists brings practical field experience to every safari, trek, and cultural journey.'],
  ['Tailor-Made Tanzania Safaris', 'Your Tanzania adventure should fit you, not the other way around. We can create private and tailor-made itineraries around your interests, available time, travel style, budget, and priorities.'],
  ['Authentic Experiences', 'We want travelers to experience more than famous attractions. We want you to discover the stories, people, wildlife, landscapes, and traditions behind them.'],
  ['Local Support From Start to Finish', 'From the first conversation about your Tanzania holiday to the moment your journey ends, our local team is here to help organize and support your experience.'],
];
?>
<style>
  .ab-wrap{max-width:1120px;margin:0 auto;padding:0 1.25rem}
  .ab-sec{padding:5rem 0}
  .ab-alt{background:linear-gradient(180deg,rgba(44,70,61,.55),rgba(44,70,61,.25))}
  .ab-label{display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#c17a3a;margin-bottom:.9rem}
  .ab-label::before{content:'';width:28px;height:2px;background:#c17a3a;display:inline-block}
  .ab-h2{font-family:'Nanum Myeongjo',Georgia,serif;font-weight:700;color:#fff;font-size:clamp(1.7rem,3.4vw,2.5rem);line-height:1.2;margin:0 0 1.4rem}
  .ab-p{color:rgba(255,255,255,.72);font-size:1.04rem;line-height:1.85;margin:0 0 1.15rem;max-width:68ch}
  .ab-p strong{color:#fff;font-weight:600}
  .ab-lead{font-size:1.2rem;line-height:1.75;color:rgba(255,255,255,.88)}
  .ab-quote{border-left:3px solid #c17a3a;background:rgba(193,122,58,.08);padding:1.1rem 1.4rem;border-radius:0 14px 14px 0;margin:1.6rem 0;font-family:'Nanum Myeongjo',Georgia,serif;font-size:1.25rem;line-height:1.6;color:#fff;max-width:62ch}
  .ab-callout{background:linear-gradient(135deg,rgba(160,94,34,.28),rgba(193,122,58,.12));border:1px solid rgba(193,122,58,.35);border-radius:16px;padding:1.3rem 1.5rem;margin:1.6rem 0;color:#fff;font-size:1.08rem;line-height:1.7;max-width:62ch}
  .ab-callout b{font-family:'Nanum Myeongjo',Georgia,serif;font-size:1.3rem;color:#e0a46a}
  .ab-two{display:grid;grid-template-columns:1fr;gap:2.5rem;align-items:center}
  @media(min-width:960px){.ab-two{grid-template-columns:.95fr 1.05fr;gap:4rem}.ab-two.rev{grid-template-columns:1.05fr .95fr}}
  .ab-img{position:relative;border-radius:22px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.45);aspect-ratio:4/5;max-height:560px}
  .ab-img img{width:100%;height:100%;object-fit:cover;display:block}
  .ab-badge{position:absolute;left:1rem;bottom:1rem;background:linear-gradient(135deg,#5e3611,#a05e22);color:#fff;border-radius:16px;padding:.9rem 1.2rem;box-shadow:0 10px 30px rgba(0,0,0,.4)}
  .ab-badge b{display:block;font-family:'Nanum Myeongjo',serif;font-size:2rem;line-height:1}
  .ab-badge span{font-family:'Montserrat',sans-serif;font-size:.62rem;letter-spacing:.14em;text-transform:uppercase;opacity:.9}
  .ab-chips{display:flex;flex-wrap:wrap;gap:.55rem;margin-top:1.2rem}
  .ab-chip{padding:.45rem .95rem;border-radius:999px;border:1px solid rgba(193,122,58,.4);background:rgba(193,122,58,.1);color:#f0c79b;font-size:.82rem;font-weight:600}
  .ab-grid{display:grid;grid-template-columns:1fr;gap:1rem}
  @media(min-width:640px){.ab-grid{grid-template-columns:repeat(2,1fr)}}
  @media(min-width:1000px){.ab-grid.c3{grid-template-columns:repeat(3,1fr)}.ab-grid.c4{grid-template-columns:repeat(4,1fr)}}
  .ab-card{display:flex;flex-direction:column;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:1.4rem 1.35rem;text-decoration:none;transition:transform .3s,border-color .3s,background .3s}
  a.ab-card:hover{transform:translateY(-4px);border-color:rgba(193,122,58,.55);background:rgba(193,122,58,.07)}
  .ab-ico{width:46px;height:46px;border-radius:13px;background:rgba(193,122,58,.16);color:#e0a46a;display:flex;align-items:center;justify-content:center;font-size:1.05rem;margin-bottom:1rem;flex-shrink:0}
  .ab-card h3{font-family:'Nanum Myeongjo',Georgia,serif;font-weight:700;color:#fff;font-size:1.12rem;line-height:1.3;margin:0 0 .5rem}
  .ab-card p{color:rgba(255,255,255,.66);font-size:.92rem;line-height:1.7;margin:0}
  .ab-more{margin-top:auto;padding-top:1rem;font-family:'Montserrat',sans-serif;font-size:.74rem;font-weight:700;letter-spacing:.06em;color:#e0a46a;display:inline-flex;align-items:center;gap:.4rem}
  .ab-big{font-family:'Nanum Myeongjo',Georgia,serif;font-weight:700;color:#fff;font-size:clamp(1.35rem,2.8vw,2rem);line-height:1.35;margin:0 0 .6rem}
  .ab-big em{font-style:normal;color:#e0a46a}
  .ab-home{font-family:'Nanum Myeongjo',Georgia,serif;font-size:clamp(2rem,5vw,3.4rem);font-weight:700;color:#e0a46a;margin:1.4rem 0 0.2rem}
  .ab-list{list-style:none;margin:0;padding:0;display:grid;gap:.65rem}
  .ab-list li{display:flex;gap:.8rem;align-items:flex-start;padding:.85rem 1rem;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.85);font-size:.98rem;line-height:1.5}
  .ab-list li i{color:#c17a3a;margin-top:.2rem}
  .ab-num{font-family:'Nanum Myeongjo',serif;font-weight:700;font-size:2.2rem;line-height:1;color:rgba(193,122,58,.55);margin-bottom:.7rem}
  .ab-motto{display:grid;grid-template-columns:repeat(2,1fr);gap:.8rem;margin:1.6rem 0}
  @media(min-width:760px){.ab-motto{grid-template-columns:repeat(4,1fr)}}
  .ab-motto div{text-align:center;padding:1.1rem .6rem;border-radius:16px;background:linear-gradient(160deg,rgba(160,94,34,.3),rgba(193,122,58,.1));border:1px solid rgba(193,122,58,.35);font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:1.15rem}
  .ab-motto small{display:block;font-family:'Montserrat',sans-serif;font-size:.6rem;letter-spacing:.16em;text-transform:uppercase;color:#e0a46a;margin-bottom:.25rem}
  .ab-moments{display:grid;gap:.5rem;margin:1.2rem 0;padding:0;list-style:none}
  .ab-moments li{font-family:'Nanum Myeongjo',serif;font-size:1.15rem;color:rgba(255,255,255,.85);padding-left:1.4rem;position:relative}
  .ab-moments li::before{content:'';position:absolute;left:0;top:.7rem;width:8px;height:8px;border-radius:50%;background:#c17a3a}
  .ab-welcome{font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:clamp(1.8rem,4.2vw,3rem);line-height:1.2;margin:2rem 0 1rem}
  .ab-welcome span{color:#e0a46a}
  .ab-center{text-align:center}.ab-center .ab-p{margin-left:auto;margin-right:auto}
</style>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:360px">
  <img src="<?= IMG_ABOUT ?>" alt="About Us - Jambo Masai Tours guides in Tanzania" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.35">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.6),transparent 60%)"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-12 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">About Us</span>
    </nav>
    <div class="section-tag">About Us &middot; Our Story</div>
    <h1 class="font-heading text-white font-bold leading-tight" style="font-size:clamp(1.9rem,4.6vw,3.2rem);max-width:19ch">
      Born in Tanzania. <span class="hero-grad">Guided by Experience.</span> Driven by a Love for the Wild.
    </h1>
  </div>
</div>

<!-- OUR STORY -->
<section class="ab-sec">
  <div class="ab-wrap ab-two">
    <div class="ab-img reveal">
      <img src="<?= IMG_ABOUT ?>" alt="Raphael Salewa and the Jambo Masai Tours team in Tanzania" loading="eager">
      <div class="ab-badge"><b>2010</b><span>Founded in Tanzania</span></div>
    </div>
    <div class="reveal" style="transition-delay:100ms">
      <div class="ab-label">Our Story</div>
      <h2 class="ab-h2">A local Maasai guide, a lifetime in Tanzania&rsquo;s wild places</h2>
      <p class="ab-p ab-lead">Jambo Masai Tours was founded in 2010 by <strong>Raphael Salewa</strong>, a local Maasai guide whose life has been deeply connected to the mountains, wildlife, people, and landscapes of Tanzania.</p>
      <p class="ab-p">Long before Jambo Masai Tours became a Tanzania safari and trekking company, Raphael was already spending his days in the field: guiding travelers through remote wilderness areas, climbing Tanzania&rsquo;s legendary mountains, and exploring some of East Africa&rsquo;s most remarkable national parks.</p>
      <blockquote class="ab-quote">His knowledge was not learned from a guidebook. It was built through years of experience on the ground.</blockquote>
      <p class="ab-p">Raphael came to understand Tanzania from a local perspective: the rhythm of the savannah, the behavior of wildlife, the challenges of the mountains, the traditions of Maasai communities, and the stories that make each landscape more than just a place on a map.</p>
      <div class="ab-callout">In 2010, he decided to turn that experience into something of his own.<br><b>That vision became Jambo Masai Tours.</b></div>
      <p class="ab-p">Today, the company continues to grow while remaining firmly rooted in the values on which it was founded:</p>
      <div class="ab-chips">
        <span class="ab-chip">Local knowledge</span><span class="ab-chip">Authentic experiences</span><span class="ab-chip">Professional guiding</span><span class="ab-chip">Respect for nature</span><span class="ab-chip">Genuine connections with Tanzania</span>
      </div>
    </div>
  </div>
</section>

<!-- FROM ONE GUIDE TO A TEAM -->
<section class="ab-sec ab-alt">
  <div class="ab-wrap">
    <div class="reveal" style="max-width:820px">
      <div class="ab-label">Our Team</div>
      <h2 class="ab-h2">From One Local Maasai Guide to a Team of Tanzania Specialists</h2>
      <p class="ab-p ab-lead">What started with Raphael&rsquo;s personal experience has grown into a team of <strong>30+ professional guides, naturalists, mountain guides, safari specialists, and travel professionals</strong>.</p>
      <p class="ab-p">But our growth has never been about moving away from our roots. It has been about bringing more experienced people together to help travelers discover Tanzania in a deeper and more meaningful way.</p>
      <p class="ab-p">Our team creates tailor-made journeys across Tanzania, from classic wildlife safaris and Great Migration adventures to Mount Kilimanjaro treks, cultural experiences, and Zanzibar holidays.</p>
    </div>

    <h3 class="ab-big reveal" style="margin-top:2.4rem">We specialize in</h3>
    <div class="ab-grid c4">
      <?php foreach ($ab_specialities as $i => $sp): ?>
      <a class="ab-card reveal" href="<?= e($sp[3]) ?>" style="transition-delay:<?= ($i % 4) * 70 ?>ms">
        <span class="ab-ico"><i class="fas <?= $sp[0] ?>"></i></span>
        <h3><?= e($sp[1]) ?></h3>
        <p><?= e($sp[2]) ?></p>
        <span class="ab-more">Explore <i class="fas fa-arrow-right"></i></span>
      </a>
      <?php endforeach; ?>
    </div>

    <p class="ab-p reveal" style="margin-top:2.2rem">Whether you dream of watching lions beneath the Serengeti sky, standing above the clouds on Mount Kilimanjaro, discovering the Ngorongoro Crater, or experiencing Tanzania beyond the usual tourist trail, our purpose is to make your journey <strong>personal</strong>.</p>
  </div>
</section>

<!-- TANZANIA THROUGH LOCAL EYES -->
<section class="ab-sec">
  <div class="ab-wrap">
    <div class="reveal" style="max-width:860px">
      <div class="ab-label">Our Perspective</div>
      <h2 class="ab-h2">Tanzania Through Local Eyes</h2>
      <p class="ab-big">A safari is <em>more than a list of national parks.</em></p>
      <p class="ab-big">A Kilimanjaro climb is <em>more than reaching the summit.</em></p>
      <p class="ab-big">And visiting Tanzania is <em>more than taking photographs.</em></p>
      <p class="ab-p" style="margin-top:1.6rem">At Jambo Masai Tours, we believe travel becomes truly memorable when you understand the place you are visiting.</p>
      <p class="ab-p">It means knowing where to look for wildlife, understanding changing landscapes, listening to stories behind local traditions, appreciating the importance of conservation, and giving travelers the opportunity to experience Tanzania beyond the surface.</p>
      <p class="ab-p">Our guides bring years of practical experience to every journey. They know the roads, seasons, wildlife, mountains, communities, and landscapes because Tanzania is not simply where they work.</p>
      <div class="ab-home">It is home.</div>
      <p class="ab-p" style="margin-top:1rem">That local connection allows us to create experiences that feel less like a standard tour and more like a journey shared with people who genuinely know Tanzania.</p>
    </div>
  </div>
</section>

<!-- MORE THAN A SAFARI COMPANY -->
<section class="ab-sec ab-alt">
  <div class="ab-wrap">
    <div class="reveal" style="max-width:820px">
      <div class="ab-label">What We Do</div>
      <h2 class="ab-h2">More Than a Tanzania Safari Company</h2>
      <p class="ab-p">Tanzania is one of the world&rsquo;s great wildlife destinations, but its greatest strength is not only its wildlife. It is the relationship between its landscapes, animals, people, cultures, and communities.</p>
      <p class="ab-p">From the endless plains of the Serengeti to the volcanic landscapes of Ngorongoro, from the slopes of Mount Kilimanjaro to the shores of Zanzibar, Tanzania offers an extraordinary variety of experiences. <strong>Jambo Masai Tours brings these experiences together.</strong></p>
    </div>
    <h3 class="ab-big reveal" style="margin-top:2rem">Our Tanzania travel experiences include</h3>
    <div class="ab-grid c3">
      <?php foreach ($ab_services as $i => $sv): ?>
      <a class="ab-card reveal" href="<?= e($sv[3]) ?>" style="transition-delay:<?= ($i % 3) * 70 ?>ms">
        <span class="ab-ico"><i class="fas <?= $sv[0] ?>"></i></span>
        <h3><?= e($sv[1]) ?></h3>
        <p><?= e($sv[2]) ?></p>
        <span class="ab-more"><?= e($sv[4]) ?> <i class="fas fa-arrow-right"></i></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- RESPONSIBLE TOURISM -->
<section class="ab-sec">
  <div class="ab-wrap ab-two rev">
    <div class="reveal">
      <div class="ab-label">Responsible Tourism</div>
      <h2 class="ab-h2">Responsible Tourism Is Part of Our Journey</h2>
      <p class="ab-p">We believe exploring Tanzania comes with a responsibility to help protect what makes the country extraordinary.</p>
      <p class="ab-p">Wildlife, landscapes, and local cultures are not simply attractions. They are part of Tanzania&rsquo;s heritage and future. That is why responsible tourism is an important part of the way we operate.</p>
      <blockquote class="ab-quote">For us, responsible tourism is not simply a phrase used on a website. It is part of what it means to be a Tanzania-based travel company.</blockquote>
    </div>
    <div class="reveal" style="transition-delay:100ms">
      <h3 class="ab-big" style="font-size:1.35rem">Our approach focuses on</h3>
      <ul class="ab-list">
        <?php foreach ($ab_responsible as $r): ?><li><i class="fas fa-circle-check"></i><span><?= e($r) ?></span></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<!-- WHY TRAVEL WITH US -->
<section class="ab-sec ab-alt">
  <div class="ab-wrap">
    <div class="reveal" style="max-width:760px">
      <div class="ab-label">Why Us</div>
      <h2 class="ab-h2">Why Travel With Jambo Masai Tours?</h2>
      <p class="ab-p">There are many ways to see Tanzania. We believe the experience begins with the people you choose to travel with.</p>
    </div>
    <div class="ab-grid c3" style="margin-top:1.4rem">
      <?php foreach ($ab_why as $i => $w): ?>
      <div class="ab-card reveal" style="transition-delay:<?= ($i % 3) * 70 ?>ms">
        <div class="ab-num"><?= sprintf('%02d', $i + 1) ?></div>
        <h3><?= e($w[0]) ?></h3>
        <p><?= e($w[1]) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- MEMBERSHIPS, LICENCES & REGISTRATION -->
<?php
/* Sehemu hii inaonyeshwa kwa lugha aliyochagua mteja (currentLang(): en / sw / es).
   Tovuti ni ya Kiingereza kwa sasa, kwa hiyo msingi ni Kiingereza; Kiswahili na Kihispania
   vitaonekana pale kitufe cha lugha kitakapoweka kuki jmt_lang (au ?lang=sw kwa majaribio). */
$lang = currentLang();
$L = fn(string $en, string $sw, string $es) => ['en' => $en, 'sw' => $sw, 'es' => $es][$lang];

$licCards = [
  ['fa-handshake-angle', '#c17a3a', 'TATO', 'Tanzania Association of Tour Operators',
    'We are proud, active members of TATO, the leading association of tourism professionals in Tanzania. Through TATO we make sure you receive a reliable, professional and safe experience on every wildlife safari and national park visit.',
    'Tunajivunia kuwa wanachama hai wa TATO, chama kikuu cha wataalamu wa utalii nchini. Kupitia TATO tunahakikisha unapata uzoefu wa kuaminika, wa kitaalamu na salama katika kila safari ya wanyamapori na hifadhi za taifa.',
    'Nos enorgullece ser miembros activos de TATO, la principal asociación de profesionales del turismo de Tanzania. A través de TATO garantizamos una experiencia fiable, profesional y segura en cada safari de vida salvaje y visita a parques nacionales.'],
  ['fa-id-card', '#fbbf24', 'TALA', 'Tourism Agency Licensing Authority',
    'We are proud, active members of TALA, and we operate in line with the law and the tourism licensing standards set in Tanzania, so that you enjoy a memorable and peaceful journey.',
    'Tunajivunia kuwa wanachama hai wa TALA, na tunaendesha shughuli zetu kwa mujibu wa sheria na viwango vya leseni za utalii vilivyowekwa nchini, ili kukupa safari ya kipekee na ya amani.',
    'Nos enorgullece ser miembros activos de TALA y operamos conforme a la ley y a los estándares de licencias turísticas establecidos en Tanzania, para ofrecerte un viaje memorable y tranquilo.'],
  ['fa-mountain', '#60a5fa', 'KPAP', 'Kilimanjaro Porters Assistance Project',
    'We believe in ethical, fair tourism. We stand up for the rights of our Kilimanjaro porters: fair wages, good accommodation, proper equipment, correct load weights and a safe working environment.',
    'Tunaamini katika utalii wa kimaadili na wa haki. Tunasimamia haki za wapagazi wetu wa Mlima Kilimanjaro: mishahara stahiki, malazi bora, vifaa sahihi, mizigo yenye uzito sahihi na mazingira mazuri ya kazi.',
    'Creemos en un turismo ético y justo. Defendemos los derechos de nuestros porteadores del Kilimanjaro: salarios justos, buen alojamiento, equipo adecuado, pesos de carga correctos y un entorno de trabajo seguro.'],
  ['fa-leaf', '#34d399', '', ['Community-minded tourism', 'Utalii unaojali jamii', 'Turismo comprometido con la comunidad'],
    'We believe the best tourism benefits local communities and protects our environment for future generations.',
    'Tunaamini kuwa utalii bora ni ule unaonufaisha jamii za wenyeji na kulinda mazingira yetu kwa ajili ya vizazi vijavyo.',
    'Creemos que el mejor turismo beneficia a las comunidades locales y protege nuestro entorno para las generaciones futuras.'],
];
?>
<section id="licences" class="py-20 px-4 lg:px-0" style="background:linear-gradient(180deg,rgba(160,94,34,.05),transparent)" lang="<?= e($lang) ?>">
  <style>.lic-num{background:rgba(255,255,255,.04);border:1px solid rgba(251,191,36,.25);border-radius:16px;padding:1.1rem 1.6rem;min-width:220px}</style>
  <div class="max-w-6xl mx-auto">

    <div class="text-center max-w-3xl mx-auto mb-10 reveal">
      <div class="section-tag"><i class="fas fa-award" style="font-size:.55rem"></i> <?= $L('Trust & Safety', 'Uaminifu na Usalama', 'Confianza y seguridad') ?></div>
      <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1">
        <?= $L('Our Memberships, Licences &amp; <span class="hero-grad">Registration</span>', 'Uanachama, Leseni na <span class="hero-grad">Usajili Wetu</span>', 'Nuestras membresías, licencias y <span class="hero-grad">registro</span>') ?>
      </h2>
    </div>

    <!-- BRELA -->
    <div class="glass-card p-6 lg:p-8 mb-6 reveal" style="border-color:rgba(160,94,34,.3)">
      <div class="flex flex-col sm:flex-row gap-5 items-start">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:#a05e2222">
          <i class="fas fa-building-shield text-2xl" style="color:#c17a3a"></i>
        </div>
        <div>
          <div class="font-nav text-[.65rem] uppercase tracking-[.18em] mb-1" style="color:#c17a3a">BRELA &middot; <?= $L('Company Registration', 'Usajili wa Kampuni', 'Registro de la empresa') ?></div>
          <p class="text-white/70 leading-[1.85] text-[.97rem]">
            <?= $L(
              'We are a genuine tour company in Tanzania, officially registered with the <strong class="text-white">Business Registrations and Licensing Agency (BRELA)</strong>. We follow all the laws of the country so that you receive reliable and safe service throughout your journey.',
              'Sisi ni kampuni halisi ya utalii nchini Tanzania, iliyosajiliwa rasmi na <strong class="text-white">Wakala wa Usajili wa Biashara na Leseni (BRELA)</strong>. Tunafuata kikamilifu sheria zote za nchi ili kukupa huduma za kuaminika na salama wakati wote wa safari yako.',
              'Somos una empresa turística real en Tanzania, registrada oficialmente ante la <strong class="text-white">Agencia de Registro y Licencias de Negocios (BRELA)</strong>. Cumplimos plenamente todas las leyes del país para ofrecerte un servicio fiable y seguro durante todo tu viaje.') ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Memberships -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
      <?php foreach ($licCards as $i => $c): ?>
      <div class="glass-card p-6 reveal hover:border-emerald-500/20 transition-all" style="transition-delay:<?= $i * 80 ?>ms">
        <div class="flex items-center gap-4 mb-3">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $c[1] ?>18">
            <i class="fas <?= $c[0] ?> text-lg" style="color:<?= $c[1] ?>"></i>
          </div>
          <div>
            <?php if ($c[2] !== ''): ?>
              <div class="font-heading text-white text-xl font-bold leading-none"><?= e($c[2]) ?></div>
              <div class="text-white/45 text-[.74rem] font-nav mt-1"><?= e($c[3]) ?></div>
            <?php else: ?>
              <div class="font-heading text-white text-lg font-bold leading-snug"><?= e($L(...$c[3])) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <p class="text-white/55 text-[.9rem] leading-[1.8]"><?= e($L($c[4], $c[5], $c[6])) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Licence numbers -->
    <div class="flex flex-wrap justify-center gap-4 mb-8 reveal">
      <div class="lic-num text-center">
        <div class="font-nav text-[.62rem] uppercase tracking-[.16em] text-white/40 mb-1"><?= $L('Licence Number', 'Namba ya Leseni', 'Número de licencia') ?></div>
        <div class="font-heading text-2xl font-bold" style="color:#fbbf24">022741</div>
      </div>
      <div class="lic-num text-center">
        <div class="font-nav text-[.62rem] uppercase tracking-[.16em] text-white/40 mb-1"><?= $L('TALA Number', 'Namba ya TALA', 'Número TALA') ?></div>
        <div class="font-heading text-2xl font-bold" style="color:#fbbf24">035421</div>
      </div>
    </div>

    <p class="text-center text-white/50 max-w-3xl mx-auto leading-[1.85] text-[.95rem] reveal">
      <?= $L(
        'When you travel with us, you are served by trusted professionals who care for the environment and follow the highest standards of sustainable tourism in Tanzania.',
        'Unaposafiri nasi, unapata huduma kutoka kwa wataalamu wanaoaminika, wanaojali mazingira, na wanaofuata maadili ya juu ya utalii endelevu nchini Tanzania.',
        'Al viajar con nosotros, recibes el servicio de profesionales de confianza que cuidan el medio ambiente y siguen los más altos estándares de turismo sostenible en Tanzania.') ?>
    </p>
  </div>
</section>

<!-- STATS COUNTER -->
<section class="py-16 px-4 lg:px-0" style="background:linear-gradient(135deg,#0d0d0d,#2c463d)">
  <div class="max-w-7xl mx-auto grid grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ([
      ['fa-users',         '#c17a3a', 1200, '+', 'Happy Travellers'],
      ['fa-binoculars',    '#fbbf24', 850,  '+', 'Safaris Run'],
      ['fa-calendar-alt',  '#60a5fa', 15,   '+', 'Years Experience'],
      ['fa-map-marked-alt','#f97316', 12,   '',  'Destinations'],
    ] as $i => $s): ?>
    <div class="glass-card p-6 text-center reveal" style="transition-delay:<?= $i * 90 ?>ms">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:<?= $s[1] ?>15">
        <i class="fas <?= $s[0] ?> text-lg" style="color:<?= $s[1] ?>"></i>
      </div>
      <div class="font-heading font-bold text-white text-4xl leading-none mb-1"
           data-count="<?= $s[2] ?>" data-suffix="<?= $s[3] ?>">0<?= $s[3] ?></div>
      <div class="font-nav text-[.68rem] uppercase tracking-wider text-white/35"><?= $s[4] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- OUR JOURNEY CONTINUES -->
<section class="ab-sec">
  <div class="ab-wrap ab-center reveal">
    <div class="ab-label" style="justify-content:center">Our Journey Continues</div>
    <h2 class="ab-h2">More than a decade of sharing Tanzania</h2>
    <p class="ab-p">More than a decade after Raphael Salewa founded Jambo Masai Tours, the original idea remains at the heart of everything we do.</p>
    <div class="ab-motto">
      <div><small>Know</small>Tanzania</div>
      <div><small>Respect</small>Tanzania</div>
      <div><small>Share</small>Tanzania</div>
      <div><small>Protect</small>Tanzania</div>
    </div>
    <p class="ab-p">From one local Maasai guide to a growing team of more than 30 passionate professionals, our journey has always been about creating meaningful connections between travelers and the country we call home.</p>
    <p class="ab-p">We are proud to be a Tanzania-based safari and tour company, offering wildlife safaris, Great Migration safaris, Mount Kilimanjaro trekking, Maasai cultural experiences, Zanzibar holidays, and tailor-made Tanzania adventures.</p>
    <p class="ab-p"><strong>But our story is still being written.</strong></p>
    <ul class="ab-moments" style="display:inline-grid;text-align:left">
      <li>Every safari creates a new memory.</li>
      <li>Every mountain creates a new challenge.</li>
      <li>Every traveler brings a new story.</li>
      <li>And every journey gives us another opportunity to share Tanzania.</li>
    </ul>
    <div class="ab-welcome">Welcome to Tanzania.<br><span>Welcome to Jambo Masai Tours.</span></div>
    <p class="ab-p">We don&rsquo;t simply take you to Tanzania. We help you experience it through the knowledge, stories, and perspective of people who know it, live it, and love it.</p>
    <div class="flex flex-wrap gap-3 justify-center" style="margin-top:2rem">
      <a href="<?= url('tours') ?>"   class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Browse Safaris</a>
      <a href="<?= url('contact') ?>" class="btn-em btn-em-outline">Plan My Trip</a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener" class="btn-em btn-em-wa"><i class="fab fa-whatsapp text-base"></i> WhatsApp</a>
    </div>
  </div>
</section>

<?php
$pageScript = <<<'JS'
<script>
(function(){
  const cntObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target, target = +el.dataset.count, suffix = el.dataset.suffix || '';
      const ease = t => 1 - Math.pow(1 - t, 3), start = performance.now();
      const step = now => { const p = Math.min((now - start) / 2200, 1); el.textContent = Math.floor(ease(p) * target).toLocaleString() + suffix; if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
      cntObs.unobserve(el);
    });
  }, { threshold: .3 });
  document.querySelectorAll('[data-count]').forEach(c => cntObs.observe(c));
})();
</script>
JS;
require_once 'includes/dark_footer.php';
?>