<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'About Us | Maasai-Founded Safari Experts | Jambo Masai';
$pageDescription = 'Meet the Maasai-founded team behind Jambo Masai Tours in Arusha: sustainable tourism, authentic cultural connections and tailor-made Tanzania safaris.';
$currentPage     = 'about';
$ogImage         = IMG_ABOUT;
$canonicalUrl    = SITE_URL . '/about';
$logoUrl_seo     = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$_aFav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$headExtra = '<link rel="icon" type="image/png" href="' . e($_aFav) . '">
<link rel="shortcut icon" href="' . e($_aFav) . '">
<link rel="apple-touch-icon" href="' . e($_aFav) . '">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "About Us | Jambo Masai Tours",
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
    "areaServed": ["Tanzania", "Kenya", "East Africa"],
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
?>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_ABOUT ?>" alt="About Us - Jambo Masai Tours" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.35">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.6),transparent 60%)"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">About Us</span>
    </nav>
    <div class="section-tag">About Us</div>
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
        <div class="absolute -top-4 -left-4 lg:-left-8 rounded-2xl px-5 py-4 shadow-2xl" style="background:linear-gradient(135deg,#5e3611,#a05e22)">
          <div class="text-3xl font-bold text-white font-heading leading-none">15+</div>
          <div class="text-emerald-100 text-[.7rem] mt-1 font-nav uppercase tracking-wider leading-tight">Years of<br>Adventure</div>
        </div>
        <!-- Floating about-small image -->
        <div class="absolute -bottom-6 -right-3 lg:-right-8 w-44 h-36 rounded-2xl overflow-hidden shadow-2xl hidden md:block" style="border:3px solid #23362f">
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

        <!-- License badge -->
        <a href="#licences" class="inline-flex items-center gap-3 glass-card px-4 py-3 mb-8" style="text-decoration:none">
          <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#10b98118">
            <i class="fas fa-certificate text-emerald-400 text-base"></i>
          </div>
          <div>
            <div class="text-white/80 text-[.82rem] font-semibold leading-tight">Licensed &amp; Registered Tour Operator</div>
            <div class="text-white/40 text-[.72rem] font-nav">License No. 022741 &middot; TALA No. 035421</div>
          </div>
        </a>

        <div class="flex flex-wrap gap-3">
          <a href="<?= url('tours') ?>"   class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Browse Safaris</a>
          <a href="<?= url('contact') ?>" class="btn-em btn-em-outline">Contact Us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CORE VALUES -->
<section class="section-light py-20 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="text-center max-w-2xl mx-auto mb-14 reveal">
      <div class="section-tag">What Drives Us</div>
      <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1">Our Core <span class="hero-grad">Values</span></h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ([
        ['fa-leaf',        '#c17a3a', 'Responsible Tourism',  'Every booking contributes to local schools, anti-poaching units and wildlife corridors. We measure success in impact, not just profit.'],
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

<!-- CTA -->
<section class="py-20 px-4 lg:px-0 text-center">
  <div class="max-w-xl mx-auto reveal">
    <div class="section-tag">Join Us</div>
    <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1 mb-5">
      Ready for Your <span class="hero-grad">African Adventure?</span>
    </h2>
    <p class="text-white/50 leading-relaxed mb-8">Let our expert Maasai guides take you on a journey you'll never forget.</p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="<?= url('tours') ?>"   class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Browse Safaris</a>
      <a href="<?= url('contact') ?>" class="btn-em btn-em-outline">Contact Us</a>
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