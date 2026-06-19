<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'About Us | Maasai-Led Safari Experts — Jambo Masai Tours';
$pageDescription = 'Jambo Masai Tours is a Maasai-founded, award-winning safari operator based in Arusha, Tanzania. 15+ years of luxury safari experiences and community-first tourism.';
$currentPage     = 'about';
$ogImage         = IMG_ABOUT;
$canonicalUrl    = SITE_URL . '/about.php';
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
    "founder": { "@type": "Person", "name": "Jambo Masai Team" },
    "logo": { "@type": "ImageObject", "url": "' . $logoUrl_seo . '" },
    "telephone": "+255659667271",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Arusha",
      "addressRegion": "Arusha",
      "addressCountry": "TZ"
    },
    "areaServed": ["Tanzania", "Kenya", "East Africa"]
  }
}
</script>';
require_once 'includes/dark_header.php';
?>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_ABOUT ?>" alt="About Jambo Masai Tours" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.35">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.6),transparent 60%)"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">About Us</span>
    </nav>
    <div class="section-tag">Our Story</div>
    <h1 class="font-heading text-white font-bold leading-tight" style="font-size:clamp(2rem,5vw,3.2rem)">
      Rooted in <span class="hero-grad">Tanzania's Wild Heart</span>
    </h1>
    <p class="text-white/50 mt-3 max-w-xl text-[.95rem]">From the endless plains of the Serengeti to the snow-capped summit of Kilimanjaro — we are Tanzania, and Tanzania is us.</p>
  </div>
</div>

<!-- FOUNDER STORY -->
<section class="py-20 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-14 items-center">

      <div class="relative reveal">
        <div class="rounded-3xl overflow-hidden shadow-2xl" style="height:500px">
          <img src="<?= IMG_ABOUT ?>" alt="Jambo Masai Tours guides" loading="eager"
               class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>
        <!-- 15+ badge -->
        <div class="absolute -top-4 -left-4 lg:-left-8 rounded-2xl px-5 py-4 shadow-2xl" style="background:linear-gradient(135deg,#047857,#10b981)">
          <div class="text-3xl font-bold text-white font-heading leading-none">15+</div>
          <div class="text-emerald-100 text-[.7rem] mt-1 font-nav uppercase tracking-wider leading-tight">Years of<br>Adventure</div>
        </div>
        <!-- Floating about-small image -->
        <div class="absolute -bottom-6 -right-3 lg:-right-8 w-44 h-36 rounded-2xl overflow-hidden shadow-2xl hidden md:block" style="border:3px solid #0a0a0a">
          <img src="<?= IMG_MAASAI ?>" alt="Maasai culture" loading="lazy" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          <p class="absolute bottom-2 left-3 text-white text-[.6rem] font-nav font-semibold tracking-wider uppercase">Maasai Heritage</p>
        </div>
      </div>

      <div class="reveal" style="transition-delay:100ms">
        <div class="section-tag">Our Origin</div>
        <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold leading-snug mb-5">
          A Vision Born in <span class="hero-grad">Tanzania's Safari Circuit</span>
        </h2>
        <p class="text-white/55 leading-[1.9] mb-4 text-[.95rem]">
          Jambo Masai Tours was founded in 2010 by <strong class="text-white/80">Raphael Salewa</strong> with a passion for sharing the beauty, wildlife, and culture of Tanzania. Inspired by the remarkable landscapes of Tanzania, he envisioned a company that offers authentic safari experiences while supporting local communities and conservation efforts.
        </p>
        <p class="text-white/50 leading-[1.9] mb-4 text-[.95rem]">
          Today, our team of <strong class="text-white/70">30+ certified guides</strong>, naturalists, and travel professionals specializes in unforgettable journeys through Tanzania's Safari Circuit — including <span class="text-emerald-400/80">Serengeti National Park</span>, <span class="text-emerald-400/80">Ngorongoro Conservation Area</span>, <span class="text-emerald-400/80">Tarangire National Park</span>, Lake Manyara, Mount Kilimanjaro, and Zanzibar, among other iconic destinations.
        </p>
        <p class="text-white/45 leading-[1.9] mb-8 text-[.88rem]">
          At Jambo Masai Tours, we believe the best safaris combine <strong class="text-white/65">exceptional wildlife encounters</strong>, rich cultural experiences, and genuine local knowledge. Every journey we create is designed to showcase the true spirit of Tanzania while contributing to the preservation of its natural and cultural heritage.
        </p>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 gap-3 mb-8">
          <?php foreach ([
            ['1,200+','Happy Travellers'],['850+','Safaris Completed'],
            ['30+','Expert Guides'],['4.9/5','Average Rating'],
          ] as $v): ?>
          <div class="glass-card p-4 text-center">
            <div class="font-heading font-bold text-2xl text-emerald-400 leading-none mb-1"><?= $v[0] ?></div>
            <div class="text-white/35 text-[.72rem] font-nav uppercase tracking-wider"><?= $v[1] ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="<?= url('tours.php') ?>"   class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Browse Safaris</a>
          <a href="<?= url('contact.php') ?>" class="btn-em btn-em-outline">Contact Us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CORE VALUES -->
<section class="py-20 px-4 lg:px-0" style="background:linear-gradient(180deg,transparent,rgba(16,185,129,.03),transparent)">
  <div class="max-w-7xl mx-auto">
    <div class="text-center max-w-2xl mx-auto mb-14 reveal">
      <div class="section-tag">What Drives Us</div>
      <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1">Our Core <span class="hero-grad">Values</span></h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ([
        ['fa-leaf',        '#34d399', 'Responsible Tourism',  'Every booking contributes to local schools, anti-poaching units and wildlife corridors. We measure success in impact, not just profit.'],
        ['fa-handshake',   '#fbbf24', 'Cultural Respect',     'We are custodians of Maasai traditions. Cultural tours are designed with community elders and profits flow back to the villages.'],
        ['fa-shield-alt',  '#60a5fa', 'Conservation First',   'We follow strict no-off-road driving policies, maintain low vehicle ratios at sightings, and fund the ecosystems we operate within.'],
        ['fa-star',        '#f59e0b', 'Excellence Always',    'From camp linens to sunrise positioning, we obsess over every detail because you deserve nothing less than extraordinary.'],
        ['fa-lock',        '#a78bfa', 'Safety & Security',    'Comprehensive travel insurance, flying doctors membership, modern vehicles with emergency communication and first-aid trained guides.'],
        ['fa-globe-africa','#f97316', 'Authentic Africa',     'No staged performances. No tourist traps. Just the real, raw, magnificent Africa that our guides have lived in all their lives.'],
      ] as $i => $v): ?>
      <div class="glass-card p-6 group hover:border-emerald-500/20 transition-all reveal" style="transition-delay:<?= $i * 70 ?>ms">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-colors group-hover:scale-110 duration-300" style="background:<?= $v[1] ?>18">
          <i class="fas <?= $v[0] ?> text-lg" style="color:<?= $v[1] ?>"></i>
        </div>
        <h3 class="font-heading text-white text-lg font-bold mb-2"><?= $v[2] ?></h3>
        <p class="text-white/45 text-[.86rem] leading-relaxed"><?= $v[3] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- STATS COUNTER -->
<section class="py-16 px-4 lg:px-0" style="background:linear-gradient(135deg,#0d0d0d,#111)">
  <div class="max-w-7xl mx-auto grid grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ([
      ['fa-users',         '#34d399', 1200, '+', 'Happy Travellers'],
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

<!-- CTA -->
<section class="py-20 px-4 lg:px-0 text-center">
  <div class="max-w-xl mx-auto reveal">
    <div class="section-tag">Join Us</div>
    <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1 mb-5">
      Ready for Your <span class="hero-grad">African Adventure?</span>
    </h2>
    <p class="text-white/50 leading-relaxed mb-8">Let our expert Maasai guides take you on a journey you'll never forget.</p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="<?= url('tours.php') ?>"   class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Browse Safaris</a>
      <a href="<?= url('contact.php') ?>" class="btn-em btn-em-outline">Contact Us</a>
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