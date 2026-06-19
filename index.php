<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Jambo Masai Tours | Luxury Safari Tours Tanzania & East Africa';
$pageDescription = 'Award-winning luxury safari tours in Tanzania. Witness the Great Migration, explore Ngorongoro Crater, climb Kilimanjaro & relax in Zanzibar. Expert local guides.';
$currentPage     = 'home';

$db = getDB();
$featuredTours = $db->query("SELECT * FROM tours WHERE featured = 1 ORDER BY rating DESC LIMIT 6")->fetchAll();
$testimonials  = $db->query("SELECT * FROM testimonials WHERE approved = 1 ORDER BY created_at DESC LIMIT 6")->fetchAll();
$galleryImages = $db->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 12")->fetchAll();
$blogPosts     = $db->query("SELECT id,title,slug,excerpt,image,author,created_at FROM blog_posts WHERE published=1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$heroVideo     = $db->query("SELECT * FROM hero_video LIMIT 1")->fetch();
$heroSlides    = $db->query("SELECT * FROM hero_slides WHERE active=1 ORDER BY sort_order ASC")->fetchAll();
if (empty($heroSlides)) {
    $heroSlides = [['id'=>0,'headline'=>'Experience Tanzania Like Never Before','subtitle'=>'Luxury Safaris, Maasai Culture & Unforgettable Adventures','btn1_text'=>'Explore Safaris','btn1_link'=>'tours.php','btn2_text'=>'Book Safari','btn2_link'=>'booking.php']];
}

/* Destinations + CSRF for the inline hero booking form (reuses ajax/book.php) */
try { $heroDests = $db->query("SELECT DISTINCT destination FROM tours WHERE destination<>'' ORDER BY destination")->fetchAll(PDO::FETCH_COLUMN); }
catch (\Throwable $e) { $heroDests = []; }
if (empty($heroDests)) $heroDests = ['Serengeti','Ngorongoro','Kilimanjaro','Zanzibar','Tarangire','Maasai Heartland'];
$heroCsrf = generateCsrfToken();

/* Destinations from DB */
$destinations = $db->query("SELECT * FROM destination_pages_full WHERE active=1 ORDER BY sort_order ASC LIMIT 6")->fetchAll();
foreach ($destinations as &$_d) {
    $_d['image']      = !empty($_d['image_url']) ? $_d['image_url'] : '';
    $_d['best_season']= $_d['best_time'] ?? 'Year-round';
    if (!empty($_d['highlights']) && $_d['highlights'][0] === '[') {
        $hArr = json_decode($_d['highlights'], true) ?: [];
        $_d['highlights'] = implode('|', array_column($hArr, 'title'));
    }
    if (empty($_d['country'])) $_d['country'] = 'Tanzania';
} unset($_d);

/* Tour count per destination name (for labels) */
$toursByDest = [];
foreach ($db->query("SELECT destination, COUNT(*) cnt FROM tours GROUP BY destination")->fetchAll() as $r) {
    $toursByDest[strtolower(trim($r['destination']))] = (int)$r['cnt'];
}

/* Static fallback if DB table empty */
if (empty($destinations)) {
    $destinations = [
        ['title'=>'Serengeti',       'slug'=>'serengeti',  'region'=>'Northern Tanzania',    'highlights'=>'Great Migration Country|Big Five|Hot Air Balloon', 'best_season'=>'Jun – Oct', 'image'=>IMG_SERENGETI],
        ['title'=>'Ngorongoro',      'slug'=>'ngorongoro', 'region'=>'Crater Highlands',     'highlights'=>'UNESCO World Heritage|Big Five|Ancient Caldera',   'best_season'=>'Year-round','image'=>IMG_NGORONGORO],
        ['title'=>'Kilimanjaro',     'slug'=>'kilimanjaro','region'=>'Kilimanjaro Region',   'highlights'=>'Roof of Africa · 5,895m|Trekking Routes|Snow Cap',  'best_season'=>'Jan – Mar', 'image'=>IMG_KILIMANJARO],
        ['title'=>'Zanzibar',        'slug'=>'zanzibar',   'region'=>'Zanzibar Archipelago', 'highlights'=>'Spice Island Paradise|White Sand Beaches|Snorkeling','best_season'=>'Jun – Mar', 'image'=>IMG_ZANZIBAR],
        ['title'=>'Tarangire',       'slug'=>'tarangire',  'region'=>'Manyara Region',       'highlights'=>'Elephant Sanctuary|Ancient Baobabs|Bird Watching',  'best_season'=>'Jun – Oct', 'image'=>IMG_TARANGIRE],
        ['title'=>'Maasai Heartland','slug'=>'maasai',     'region'=>'Arusha & Manyara',     'highlights'=>'Cultural Immersion|Traditional Villages|Warrior Life','best_season'=>'Year-round','image'=>IMG_MAASAI],
    ];
}

$logoUrl     = getSetting('logo_url');
$logoWidth   = (int)(getSetting('logo_width', '160') ?: 160);
$siteName    = getSetting('site_name', 'Jambo Masai Tours');
$siteTagline = getSetting('site_tagline', 'Tanzania Safari Experts');

if (empty($galleryImages)) {
    $galleryImages = [
        ['image'=>IMG_SERENGETI,   'title'=>'Serengeti Plains',     'category'=>'wildlife'],
        ['image'=>IMG_NGORONGORO,  'title'=>'Ngorongoro Crater',    'category'=>'landscapes'],
        ['image'=>IMG_KILIMANJARO, 'title'=>'Mount Kilimanjaro',    'category'=>'landscapes'],
        ['image'=>IMG_ZANZIBAR,    'title'=>'Zanzibar Beach',       'category'=>'zanzibar'],
        ['image'=>IMG_TARANGIRE,   'title'=>'Tarangire Elephants',  'category'=>'wildlife'],
        ['image'=>IMG_MAASAI,      'title'=>'Maasai Culture',       'category'=>'culture'],
        ['image'=>IMG_ABOUT,       'title'=>'Safari Guides',        'category'=>'safari-life'],
        ['image'=>IMG_HERO,        'title'=>'Tanzania Sunset',      'category'=>'landscapes'],
    ];
}
if (empty($testimonials)) {
    $testimonials = [
        ['customer_name'=>'Sarah M.','country'=>'United Kingdom','rating'=>5,'review'=>'An absolutely life-changing experience. Our guide Joseph knew every animal by name. The Serengeti at sunrise is something I will never forget.','photo'=>''],
        ['customer_name'=>'David K.','country'=>'United States', 'rating'=>5,'review'=>'Jambo Masai exceeded every expectation. The camp was luxurious, the food incredible, and the wildlife encounters were beyond anything I imagined.','photo'=>''],
        ['customer_name'=>'Lena H.','country'=>'Germany',        'rating'=>5,'review'=>'From booking to farewell, everything was flawless. The team truly cares about giving you the best possible experience.','photo'=>''],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="canonical" href="<?= e(SITE_URL) ?>/">
  <meta property="og:title"       content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:image"       content="<?= e(SITE_URL) ?>/assets/images/hero.jpg">
  <meta property="og:url"         content="<?= e(SITE_URL) ?>/">
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="Jambo Masai Tours">
  <meta name="theme-color"        content="#10b981">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <link rel="manifest" href="<?= e(SITE_URL) ?>/manifest.json">
  <?php $logoUrl_seo = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <script type="application/ld+json">
  [
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Jambo Masai Tours",
      "url": "<?= SITE_URL ?>",
      "description": <?= json_encode($pageDescription) ?>,
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "<?= SITE_URL ?>/tours.php?q={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@context": "https://schema.org",
      "@type": "TravelAgency",
      "name": "Jambo Masai Tours",
      "url": "<?= SITE_URL ?>",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= e($logoUrl_seo) ?>"
      },
      "image": "<?= SITE_URL ?>/assets/images/hero.jpg",
      "description": "<?= addslashes($pageDescription) ?>",
      "telephone": "+255659667271",
      "priceRange": "$$$$",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Arusha City Centre",
        "addressLocality": "Arusha",
        "addressRegion": "Arusha",
        "postalCode": "00000",
        "addressCountry": "TZ"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": -3.3988608,
        "longitude": 36.6715112
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "07:00",
        "closes": "20:00"
      },
      "areaServed": ["Tanzania","Kenya","East Africa"],
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "bestRating": "5",
        "ratingCount": "150"
      },
      "sameAs": [
        "https://www.facebook.com/jambomasaitours",
        "https://www.instagram.com/jambomasaitours",
        "https://www.tripadvisor.com/Attraction_Review-Tanzania-Jambo_Masai_Tours"
      ]<?php if (!empty($testimonials)): ?>,
      "review": [
        <?php foreach (array_slice($testimonials, 0, 5) as $ti => $tv): ?>
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": <?= json_encode($tv['customer_name']) ?> },
          "reviewRating": { "@type": "Rating", "ratingValue": "<?= (int)$tv['rating'] ?>", "bestRating": "5" },
          "reviewBody": <?= json_encode(mb_substr(strip_tags($tv['review']), 0, 500)) ?>,
          "datePublished": "<?= date('Y-m-d', strtotime($tv['created_at'] ?? 'now')) ?>"
        }<?= $ti < min(4, count($testimonials)-1) ? ',' : '' ?>
        <?php endforeach; ?>
      ]<?php endif; ?>
    }
  ]
  </script>

  <!-- Tailwind CSS CDN -->
  <link rel="preconnect" href="https://cdn.tailwindcss.com"><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><link rel="dns-prefetch" href="https://images.unsplash.com"><script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand:  '#10b981',
            brandd: '#059669',
            safari: '#10b981',
            dark:   '#0a0a0a',
            card:   '#111111',
            glass:  'rgba(255,255,255,0.04)',
          },
          fontFamily: {
            heading: ['Playfair Display','Georgia','serif'],
            sans:    ['Inter','Poppins','sans-serif'],
            nav:     ['Montserrat','sans-serif'],
          },
        }
      }
    }
  </script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- AOS -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#0a0a0a;color:#e5e7eb;font-family:'Inter','Poppins',sans-serif;line-height:1.6;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}
    ::-webkit-scrollbar-track{background:#111}
    ::-webkit-scrollbar-thumb{background:#10b981;border-radius:2px}

    /* Ambient glow */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:-5%;width:500px;height:500px;background:radial-gradient(circle,rgba(16,185,129,0.12) 0%,transparent 70%)}
    .glow-orb-2{bottom:-10%;right:-5%;width:600px;height:600px;background:radial-gradient(circle,rgba(16,185,129,0.08) 0%,transparent 70%)}

    /* Glass card */
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}

    /* Gradient text */
    .gradient-text{background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* Section tag */
    .section-tag{display:inline-block;background:rgba(16,185,129,.12);color:#10b981;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;padding:.35rem 1rem;border-radius:999px;border:1px solid rgba(16,185,129,.2);margin-bottom:1.2rem}

    /* Buttons */
    .btn-gold{display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer}
    .btn-gold:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(16,185,129,.4)}
    .btn-outline{display:inline-flex;align-items:center;gap:.5rem;background:transparent;color:#10b981;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;border:1px solid rgba(16,185,129,.4);transition:all .3s;cursor:pointer}
    .btn-outline:hover{background:rgba(16,185,129,.08);border-color:#10b981;transform:translateY(-2px)}
    .btn-wa{display:inline-flex;align-items:center;gap:.5rem;background:#25D366;color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;transition:all .3s}
    .btn-wa:hover{background:#1da851;transform:translateY(-2px)}
    .btn-outline-white{display:inline-flex;align-items:center;gap:.5rem;background:transparent;color:rgba(255,255,255,.8);font-family:'Montserrat',sans-serif;font-weight:600;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;border:1px solid rgba(255,255,255,.35);transition:all .3s;cursor:pointer}
    .btn-outline-white:hover{background:rgba(255,255,255,.08);color:#fff;transform:translateY(-2px)}

    /* Hero */
    .hero-section{position:relative;height:100dvh;min-height:600px;display:flex;align-items:center;overflow:hidden}
    .hero-media{position:absolute;inset:0;z-index:0}
    .hero-media img,.hero-media video{width:100%;height:100%;object-fit:cover}
    .hero-overlay{position:absolute;inset:0;z-index:1;background:linear-gradient(to right,rgba(0,0,0,.88) 0%,rgba(0,0,0,.6) 50%,rgba(0,0,0,.25) 100%),linear-gradient(to top,rgba(0,0,0,.6) 0%,transparent 50%)}

    /* Scroll progress */
    .scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#10b981,#059669);z-index:9999;width:0%;transition:width .1s}

    /* Back to top */
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);color:#10b981;font-size:1.1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}
    #back-top:hover{background:rgba(16,185,129,.25)}

    /* WhatsApp float */
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(0.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float:hover{background:#1da851;transform:scale(1.1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.35);opacity:0}}

    /* Nav */

    /* Tour card */
    .tour-card{background:#111;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;transition:all .4s;position:relative}
    .tour-card:hover{transform:translateY(-6px);border-color:rgba(16,185,129,.3);box-shadow:0 20px 60px rgba(0,0,0,.5)}
    .tour-card img{width:100%;height:220px;object-fit:cover;transition:transform .6s}
    .tour-card:hover img{transform:scale(1.05)}

    /* Destination card */
    .dest-card{position:relative;border-radius:16px;overflow:hidden;cursor:pointer;transition:transform .4s}
    .dest-card:hover{transform:translateY(-4px)}
    .dest-card img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
    .dest-card:hover img{transform:scale(1.08)}
    .dest-card-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.85) 0%,rgba(0,0,0,.2) 60%,transparent 100%)}

    /* Gallery */
    .gallery-grid{columns:3;column-gap:12px}
    .gallery-item{break-inside:avoid;margin-bottom:12px;border-radius:10px;overflow:hidden;cursor:pointer;position:relative}
    .gallery-item img{width:100%;display:block;transition:transform .4s}
    .gallery-item:hover img{transform:scale(1.04)}
    .gallery-item-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;font-size:1.5rem}
    .gallery-item:hover .gallery-item-overlay{opacity:1}

    /* Lightbox */
    #lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.95);display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity .3s}
    #lightbox.open{opacity:1;pointer-events:all}
    #lightbox img{max-width:100%;max-height:90vh;border-radius:8px;object-fit:contain}
    #lightbox-close{position:absolute;top:1.5rem;right:1.5rem;color:#fff;font-size:1.8rem;cursor:pointer;background:rgba(255,255,255,.1);width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:none;transition:background .2s}
    #lightbox-close:hover{background:rgba(255,255,255,.2)}

    /* Testimonial */
    .testi-track{display:flex;transition:transform .5s cubic-bezier(.4,0,.2,1);will-change:transform}
    .testi-card{flex:0 0 calc(50% - 8px);margin-right:16px}
    @media(max-width:767px){.testi-card{flex:0 0 calc(100% - 8px)}}

    /* Slider dots */
    .slider-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.2);border:none;cursor:pointer;transition:all .3s;padding:0}
    .slider-dot.active{background:#10b981;width:24px;border-radius:4px}

    /* Star rating */
    .stars{color:#10b981;letter-spacing:.1em;font-size:.85rem}

    /* Mobile nav */

    /* Animate on scroll */
    .reveal{opacity:0;transform:translateY(30px);transition:all .7s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}

    /* Section divider */
    .section-divider{height:1px;background:linear-gradient(to right,transparent,rgba(16,185,129,.2),transparent)}

    /* Progress bar in search form */
    .search-select,.search-input{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:8px;padding:.75rem 1rem;font-family:'Inter',sans-serif;font-size:.85rem;width:100%;outline:none;transition:border-color .2s;-webkit-appearance:none}
    .search-select:focus,.search-input:focus{border-color:rgba(16,185,129,.5)}
    .search-label{display:block;font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:.4rem;font-family:'Montserrat',sans-serif}

    @media(max-width:767px){
      .gallery-grid{columns:2}
    }
    @media(max-width:480px){
      .gallery-grid{columns:1}
    }
    /* Ken Burns */
    @keyframes kenBurns{0%{transform:scale(1)}50%{transform:scale(1.08)}100%{transform:scale(1)}}
    .ken-burns{animation:kenBurns 20s ease-in-out infinite}
    /* Hero staggered entrance */
    @keyframes fadeInUp2{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
    .anim-up{opacity:0;animation:fadeInUp2 .8s cubic-bezier(.16,1,.3,1) forwards}
    /* Floating card */
    @keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
    .anim-float{animation:floatY 6s ease-in-out infinite}
    /* Hero emerald gradient text */
    .hero-grad{background:linear-gradient(135deg,#34d399,#059669,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    /* Animated shimmering gradient headline */
    .hero-grad-anim{background:linear-gradient(120deg,#6ee7b7,#10b981,#34d399,#059669,#6ee7b7);background-size:250% 100%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:gradShift 6s ease infinite}
    @keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
    /* Hero badge with subtle glow */
    .hero-badge{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);box-shadow:0 0 30px rgba(16,185,129,.12),inset 0 1px 0 rgba(255,255,255,.05);backdrop-filter:blur(8px)}
    /* Stat numbers — gradient with glow */
    .hero-stat{color:#fff;text-shadow:0 0 24px rgba(16,185,129,.25)}
    /* Primary button shine sweep */
    .btn-shine{position:relative;overflow:hidden}
    .btn-shine::after{content:'';position:absolute;top:0;left:-120%;width:60%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.35),transparent);transform:skewX(-20deg);transition:left .7s ease}
    .btn-shine:hover::after{left:140%}
    /* Hero floating card subtle 3D tilt on hover */
    .hero-card-tilt{transition:transform .5s cubic-bezier(.16,1,.3,1)}
    .hero-card-tilt:hover{transform:perspective(1000px) rotateY(-4deg) rotateX(2deg)}
    @media(prefers-reduced-motion:reduce){.hero-grad-anim,.anim-float{animation:none}.hero-card-tilt:hover{transform:none}}
    /* Partners strip */
    .partners-strip{border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05)}

    /* Scroll-down indicator (mouse + pulsing chevrons) */
    .scrolldown{--color:#34d399;width:28px;height:46px;border:3px solid var(--color);border-radius:50px;box-sizing:border-box;position:relative;cursor:pointer;transition:border-color .3s,box-shadow .3s}
    .scrolldown:hover{border-color:#10b981;box-shadow:0 0 18px rgba(16,185,129,.35)}
    .scrolldown::before{content:'';position:absolute;top:6px;left:50%;width:6px;height:6px;margin-left:-3px;background:var(--color);border-radius:50%;box-shadow:0 0 6px rgba(16,185,129,.7);animation:scrolldownDot 2s infinite}
    @keyframes scrolldownDot{0%{opacity:0;height:6px}35%{opacity:1;height:9px}70%{transform:translateY(20px);height:9px;opacity:1}100%{transform:translateY(22px);height:4px;opacity:0}}
    .chevrons{display:flex;flex-direction:column;align-items:center;gap:4px}
    .chevrondown{width:9px;height:9px;border:solid var(--color);border-width:0 2px 2px 0;transform:rotate(45deg)}
    .chevrondown:nth-child(1){animation:chevronPulse .6s ease infinite alternate}
    .chevrondown:nth-child(2){animation:chevronPulse .6s ease infinite alternate .3s}
    @keyframes chevronPulse{from{opacity:.15}to{opacity:.85}}
  </style>
</head>
<body class="bg-dark text-gray-200">

<!-- Scroll progress -->
<div class="scroll-progress" id="scroll-progress"></div>

<!-- Ambient glow orbs -->
<div class="glow-orb glow-orb-1" aria-hidden="true"></div>
<div class="glow-orb glow-orb-2" aria-hidden="true"></div>

<?php
/* Nav items — shorter labels for desktop, full for mobile */
$navItems = [
  'home'         => ['url'=>url(),                        'desk'=>'Home',       'mob'=>'Home',             'icon'=>'fa-home'],
  'tours'        => ['url'=>url('tours.php'),             'desk'=>'Safaris',    'mob'=>'Safari Tours',     'icon'=>'fa-compass'],
  'trekking'     => ['url'=>url('mountain-trekking.php'), 'desk'=>'Trekking',   'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
  'destinations' => ['url'=>url('destinations.php'),      'desk'=>'Destinations','mob'=>'Destinations',    'icon'=>'fa-map-marker-alt'],
  'gallery'      => ['url'=>url('gallery.php'),           'desk'=>'Gallery',    'mob'=>'Gallery',          'icon'=>'fa-images'],
  'blog'         => ['url'=>url('blog.php'),              'desk'=>'Blog',       'mob'=>'Blog',             'icon'=>'fa-newspaper'],
  'about'        => ['url'=>url('about.php'),             'desk'=>'About',      'mob'=>'About Us',         'icon'=>'fa-info-circle'],
  'contact'      => ['url'=>url('contact.php'),           'desk'=>'Contact',    'mob'=>'Contact',          'icon'=>'fa-envelope'],
];
/* Fetch featured tours for mega menu */
try {
  $idxMegaTours = getDB()->query("SELECT name,slug,price,destination FROM tours WHERE featured=1 ORDER BY rating DESC LIMIT 4")->fetchAll();
} catch (\Throwable $e) { $idxMegaTours = []; }
?>

<?php require_once 'includes/public_navbar.php'; ?>

<!-- ══════════════════════════════════════
     HERO — 2-column with floating card
══════════════════════════════════════ -->
<section class="relative flex items-center overflow-hidden lg:min-h-screen" id="hero">

  <!-- Ken Burns background -->
  <div class="absolute inset-0 z-0">
    <?php if (!empty($heroVideo['video_url'])): ?>
    <video autoplay muted loop playsinline class="w-full h-full object-cover opacity-50" aria-hidden="true">
      <source src="<?= e($heroVideo['video_url']) ?>" type="video/mp4">
    </video>
    <?php else: ?>
    <img src="<?= e($heroVideo['fallback_image'] ?? IMG_HERO) ?>"
         alt="Tanzania Safari" width="1920" height="1080" fetchpriority="high"
         class="w-full h-full object-cover ken-burns" style="opacity:.42">
    <?php endif; ?>
    <!-- Overlays -->
    <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.82) 0%,rgba(10,10,10,.35) 45%,rgba(10,10,10,.8) 100%)"></div>
    <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.8) 0%,rgba(10,10,10,.3) 60%,rgba(10,10,10,0) 100%)"></div>
    <!-- Emerald glow accents -->
    <div class="absolute -left-32 top-1/4 w-[480px] h-[480px] rounded-full pointer-events-none" style="background:radial-gradient(circle,rgba(16,185,129,.16) 0%,transparent 70%);filter:blur(20px)"></div>
    <div class="absolute right-0 bottom-0 w-[420px] h-[420px] rounded-full pointer-events-none" style="background:radial-gradient(circle,rgba(5,150,105,.12) 0%,transparent 70%);filter:blur(20px)"></div>
    <!-- Subtle noise/vignette -->
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at center,transparent 55%,rgba(0,0,0,.45) 100%)"></div>
  </div>

  <!-- Content -->
  <div class="relative z-10 w-full max-w-7xl mx-auto px-4 lg:px-6 pt-24 pb-16 lg:pt-36 lg:pb-24">
    <div class="grid md:grid-cols-2 gap-10 lg:gap-16 items-center">

      <!-- ── Left: Slide text ── -->
      <div id="hero-left" class="max-w-xl md:max-w-none">
        <?php foreach ($heroSlides as $i => $slide):
          $words = explode(' ', trim($slide['headline']));
          $mid   = (int)ceil(count($words) / 2);
          $line1 = implode(' ', array_slice($words, 0, $mid));
          $line2 = implode(' ', array_slice($words, $mid));
        ?>
        <div class="hero-slide-content <?= $i > 0 ? 'hidden' : '' ?>" data-slide="<?= $i ?>">

          <!-- Animated badge -->
          <div class="anim-up" style="animation-delay:.1s">
            <div class="hero-badge inline-flex items-start sm:items-center gap-2 rounded-full px-3 sm:px-4 py-1.5 mb-6 max-w-full">
              <span class="relative flex h-2 w-2 mt-1 sm:mt-0 flex-shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
              </span>
              <span class="text-emerald-300 text-[.58rem] sm:text-[.63rem] font-semibold tracking-[.12em] sm:tracking-[.18em] uppercase font-nav leading-relaxed"><?= e($siteName) ?> — Tanzania Safari Experts</span>
            </div>
          </div>

          <!-- Heading: line 1 white, line 2 emerald gradient -->
          <h1 class="anim-up font-heading text-white leading-[1.04] tracking-tight mb-6"
              style="font-size:clamp(2.6rem,6.8vw,4.8rem);animation-delay:.2s;text-shadow:0 2px 30px rgba(0,0,0,.5)">
            <?= e($line1) ?><br>
            <span class="hero-grad hero-grad-anim"><?= e($line2) ?></span>
          </h1>

          <!-- Subtitle -->
          <?php if (!empty($slide['subtitle'])): ?>
          <p class="anim-up text-white/65 text-[1.05rem] leading-relaxed max-w-lg mb-8" style="animation-delay:.3s">
            <?= e($slide['subtitle']) ?>
          </p>
          <?php endif; ?>

          <!-- CTA buttons -->
          <div class="anim-up grid grid-cols-1 sm:flex sm:flex-wrap gap-3 mb-10" style="animation-delay:.4s">
            <?php if (!empty($slide['btn1_text'])): ?>
            <a href="<?= e(strpos($slide['btn1_link'],'http')===0 ? $slide['btn1_link'] : url($slide['btn1_link'])) ?>"
               class="btn-shine group inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] text-white px-7 py-3.5 rounded-xl transition-all hover:scale-105 w-full sm:w-auto"
               style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 8px 30px rgba(16,185,129,.35)">
              <i class="fas fa-compass text-xs"></i>
              <?= e($slide['btn1_text']) ?>
              <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
            </a>
            <?php endif; ?>
            <div class="grid grid-cols-2 gap-3 sm:contents">
              <?php if (!empty($slide['btn2_text'])): ?>
              <a href="<?= e(strpos($slide['btn2_link'],'http')===0 ? $slide['btn2_link'] : url($slide['btn2_link'])) ?>"
                 class="inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] text-white px-7 py-3.5 rounded-xl transition-all hover:bg-white/10 w-full sm:w-auto"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)">
                <i class="fas fa-play text-emerald-400 text-xs"></i>
                <?= e($slide['btn2_text']) ?>
              </a>
              <?php endif; ?>
              <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
                 class="inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] px-6 py-3.5 rounded-xl transition-all w-full sm:w-auto"
                 style="color:#25D366;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2)">
                <i class="fab fa-whatsapp text-base"></i> WhatsApp
              </a>
            </div>
          </div>

          <!-- Inline stats row -->
          <div class="anim-up flex items-center justify-between sm:justify-start gap-2 sm:gap-8" style="animation-delay:.5s">
            <?php foreach ([['15+','Years Experience'],['1,200+','Travellers'],['4.9 ★','Avg Rating']] as $idx => $st): ?>
            <div <?= $idx > 0 ? 'class="pl-3 sm:pl-8 border-l border-white/[.12]"' : '' ?>>
              <div class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading hero-stat"><?= $st[0] ?></div>
              <div class="text-[.6rem] sm:text-[.65rem] text-white/40 mt-0.5 uppercase tracking-wider font-nav whitespace-nowrap"><?= $st[1] ?></div>
            </div>
            <?php endforeach; ?>
          </div>

        </div>
        <?php endforeach; ?>

        <!-- Scroll indicator (mobile only — in normal flow) -->
        <div class="flex lg:hidden justify-center mt-12">
          <a href="#" onclick="event.preventDefault();window.scrollBy({top:window.innerHeight*0.85,behavior:'smooth'})" class="flex flex-col items-center gap-2" aria-label="Scroll down">
            <div class="scrolldown"></div>
            <div class="chevrons">
              <div class="chevrondown"></div>
              <div class="chevrondown"></div>
            </div>
          </a>
        </div>
      </div>

      <!-- ── Right: Inline "Plan Your Safari" quote form ── -->
      <div class="hidden md:flex items-center justify-center relative anim-up" style="animation-delay:.35s">
        <!-- Glow behind form -->
        <div class="absolute inset-0 m-auto w-[380px] h-[380px] rounded-full pointer-events-none" style="background:radial-gradient(circle,rgba(16,185,129,.18) 0%,transparent 70%);filter:blur(45px)"></div>

        <div class="relative w-full max-w-[420px] ml-auto">

          <!-- Floating trust chip: rating (top) -->
          <div class="glass-card absolute -top-5 -right-5 z-20 px-3.5 py-2.5 anim-float hidden lg:flex items-center gap-2.5" style="animation-delay:1s">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,.15)">
              <i class="fas fa-star text-amber-400 text-sm"></i>
            </div>
            <div>
              <div class="text-[.82rem] font-semibold text-white leading-none">4.9/5</div>
              <div class="text-[.6rem] text-white/40 mt-0.5">2,400+ Reviews</div>
            </div>
          </div>

          <!-- Floating trust chip: verified (bottom) -->
          <div class="glass-card absolute -bottom-5 -left-6 z-20 px-3.5 py-2.5 anim-float hidden lg:flex items-center gap-2.5" style="animation-delay:2s">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(16,185,129,.15)">
              <i class="fas fa-shield-alt text-emerald-400 text-sm"></i>
            </div>
            <div>
              <div class="text-[.82rem] font-semibold text-white leading-none">Verified</div>
              <div class="text-[.6rem] text-white/40 mt-0.5">TATO Licensed</div>
            </div>
          </div>

          <!-- Form card -->
          <div class="glass-card relative overflow-hidden p-6 lg:p-7" style="box-shadow:0 30px 80px rgba(0,0,0,.55)">
            <!-- Top accent bar -->
            <div class="absolute inset-x-0 top-0 h-1" style="background:linear-gradient(90deg,#34d399,#10b981,#059669)"></div>

            <!-- Header -->
            <div class="mb-5">
              <h2 class="font-heading text-white text-2xl leading-tight">Plan Your <span class="hero-grad">Safari</span></h2>
              <p class="text-white/50 text-[.82rem] mt-1">Free quote · We reply within 24 hours.</p>
            </div>

            <!-- Form -->
            <form id="hero-bk-form" novalidate>
              <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" id="hero-bk-csrf" value="<?= e($heroCsrf) ?>">

              <div class="mb-3">
                <label class="bm-label" for="hero-bk-name">Full Name <span style="color:#f87171">*</span></label>
                <input type="text" id="hero-bk-name" name="name" class="bm-input" required placeholder="John Smith" autocomplete="name">
              </div>

              <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                  <label class="bm-label" for="hero-bk-email">Email <span style="color:#f87171">*</span></label>
                  <input type="email" id="hero-bk-email" name="email" class="bm-input" required placeholder="you@email.com" autocomplete="email">
                </div>
                <div>
                  <label class="bm-label" for="hero-bk-phone">Phone</label>
                  <input type="tel" id="hero-bk-phone" name="phone" class="bm-input" placeholder="+255 700 000 000" autocomplete="tel">
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                  <label class="bm-label" for="hero-bk-dest">Destination</label>
                  <div class="relative">
                    <select id="hero-bk-dest" name="destination" class="bm-input" style="cursor:pointer">
                      <option value="">Choose…</option>
                      <?php foreach ($heroDests as $d): ?>
                      <option value="<?= e($d) ?>"><?= e($d) ?></option>
                      <?php endforeach; ?>
                      <option value="Multiple Destinations">Multiple</option>
                      <option value="Undecided - Need Advice">Need Advice</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-[.6rem] pointer-events-none"></i>
                  </div>
                </div>
                <div>
                  <label class="bm-label" for="hero-bk-travelers">Travelers</label>
                  <input type="number" id="hero-bk-travelers" name="travelers" class="bm-input" value="2" min="1" max="50" autocomplete="off">
                </div>
              </div>

              <div class="mb-4">
                <label class="bm-label" for="hero-bk-date">Preferred Travel Date</label>
                <input type="date" id="hero-bk-date" name="travel_date" class="bm-input" min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
              </div>

              <!-- Error -->
              <div id="hero-bk-error" class="hidden mb-3 rounded-lg px-3 py-2.5 text-[.8rem]" style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#f87171"></div>

              <!-- Submit -->
              <button type="submit" id="hero-bk-submit"
                      class="btn-shine w-full inline-flex items-center justify-center gap-2 font-nav font-bold text-[.82rem] text-white uppercase tracking-wide px-6 py-3.5 rounded-xl transition-all"
                      style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 8px 28px rgba(16,185,129,.32)">
                <i class="fas fa-compass text-xs"></i> Request Free Quote
              </button>

              <p class="text-center text-[.68rem] text-white/30 mt-3 font-nav">
                <i class="fas fa-lock text-emerald-400 mr-1 text-[.6rem]"></i> No payment required · Free cancellation
              </p>
            </form>

            <!-- Success state -->
            <div id="hero-bk-success" class="hidden text-center py-8">
              <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(16,185,129,.12)">
                <i class="fas fa-check text-2xl text-emerald-400"></i>
              </div>
              <h3 class="font-heading text-white text-xl mb-2">Request Sent!</h3>
              <p class="text-white/55 text-[.85rem] leading-relaxed mb-5">Our safari specialists will contact you within <strong class="text-white/80">24 hours</strong> to craft your perfect adventure.</p>
              <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
                 class="inline-flex items-center gap-2 font-nav font-bold text-[.78rem] px-5 py-2.5 rounded-lg transition-all"
                 style="color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2)">
                <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Slide prev/next (only when multiple slides, desktop only) -->
  <?php if (count($heroSlides) > 1): ?>
  <div class="hidden lg:flex absolute bottom-10 right-6 z-20 gap-2">
    <button id="hero-prev" class="w-10 h-10 rounded-full text-white flex items-center justify-center transition-all"
            style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)" aria-label="Prev slide">‹</button>
    <button id="hero-next" class="w-10 h-10 rounded-full text-white flex items-center justify-center transition-all"
            style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)" aria-label="Next slide">›</button>
  </div>
  <?php endif; ?>

  <!-- Scroll indicator (desktop only — absolute over full-height hero) -->
  <a href="#" onclick="event.preventDefault();window.scrollBy({top:window.innerHeight*0.85,behavior:'smooth'})" class="hidden lg:flex absolute bottom-16 left-1/2 -translate-x-1/2 z-10 flex-col items-center gap-2" aria-label="Scroll down">
    <div class="scrolldown"></div>
    <div class="chevrons">
      <div class="chevrondown"></div>
      <div class="chevrondown"></div>
    </div>
  </a>

  <!-- Curved wave divider — flows into the section below -->
  <div class="absolute bottom-0 inset-x-0 z-[5] pointer-events-none leading-[0]" aria-hidden="true">
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none" class="w-full h-[50px] lg:h-[70px]">
      <path d="M0,40 C360,90 1080,0 1440,40 L1440,80 L0,80 Z" fill="#0a0a0a"></path>
    </svg>
  </div>

</section>

<!-- ══════════════════════════════════════
     TRUSTED PARTNERS / CERTIFICATIONS
══════════════════════════════════════ -->
<section class="partners-strip relative z-10 py-10">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-7">
      <div class="flex-1 h-px" style="background:linear-gradient(to right,transparent,rgba(255,255,255,.07))"></div>
      <span class="font-nav text-[.6rem] font-700 uppercase tracking-[.22em] text-white/25 flex-shrink-0">
        Certified &amp; Featured On
      </span>
      <div class="flex-1 h-px" style="background:linear-gradient(to left,transparent,rgba(255,255,255,.07))"></div>
    </div>

    <!-- Partner badges — infinite scroll carousel -->
    <?php
    $partners = [
      ['name' => 'SafariBookings',  'short' => 'Safari Bookings', 'icon' => 'fa-binoculars',    'color' => '#f59e0b', 'url' => 'https://www.safaribookings.com/p6419'],
      ['name' => 'TATO',            'short' => 'TATO',            'icon' => 'fa-certificate',   'color' => '#10b981', 'url' => 'https://www.tatotz.org/'],
      ['name' => 'TourHQ',          'short' => 'TourHQ',          'icon' => 'fa-globe',         'color' => '#3b82f6', 'url' => 'https://www.tourhq.com/'],
      ['name' => 'Trustpilot',      'short' => 'Trustpilot',      'icon' => 'fa-star',          'color' => '#00b67a', 'url' => 'https://www.trustpilot.com/'],
      ['name' => 'KPAP',            'short' => 'KPAP',            'icon' => 'fa-mountain',      'color' => '#8b5cf6', 'url' => 'https://www.kpap.org/'],
      ['name' => 'TripAdvisor',     'short' => 'TripAdvisor',     'icon' => 'fa-map-marked-alt','color' => '#34d399', 'url' => 'https://www.tripadvisor.com/'],
      ['name' => 'SafariOptions',   'short' => 'Safari Options',  'icon' => 'fa-paw',           'color' => '#f97316', 'url' => 'https://www.safarioptions.com/'],
      ['name' => 'SafariGo',        'short' => 'SafariGo',        'icon' => 'fa-compass',       'color' => '#10b981', 'url' => 'https://www.safarigo.com/'],
      ['name' => 'GetYourGuide',    'short' => 'GetYourGuide',    'icon' => 'fa-ticket-alt',    'color' => '#ff6b35', 'url' => 'https://www.getyourguide.com/'],
      ['name' => 'SafariPicked',    'short' => 'SafariPicked',    'icon' => 'fa-check-circle',  'color' => '#a78bfa', 'url' => 'https://www.safaripicked.com/'],
      ['name' => 'BRELA',           'short' => 'BRELA',           'icon' => 'fa-building',      'color' => '#60a5fa', 'url' => 'https://ors.brela.go.tz/ors/start?returnUrl=%2fbrela%2fprod%2fors'],
    ];
    ?>
  </div>

  <!-- Full-width scroll track (outside max-w container) -->
  <div class="partner-track-wrap" id="partner-wrap">
    <div class="partner-track" id="partner-track">
      <?php
      // Render partners TWICE for seamless loop
      for ($loop = 0; $loop < 2; $loop++):
        foreach ($partners as $p): ?>
      <a href="<?= $p['url'] ?>" target="_blank" rel="noopener noreferrer"
         class="partner-badge"
         aria-label="<?= e($p['name']) ?>">
        <div class="partner-icon" style="background:<?= $p['color'] ?>18">
          <i class="fas <?= $p['icon'] ?>" style="color:<?= $p['color'] ?>"></i>
        </div>
        <span class="partner-name"><?= e($p['short']) ?></span>
      </a>
      <?php endforeach; endfor; ?>
    </div>
  </div>

</section>

<style>
/* Carousel wrapper */
.partner-track-wrap {
  overflow: hidden;
  width: 100%;
  cursor: default;
  padding: .5rem 0;
  /* Fade edges */
  -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
  mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
}

/* Scrolling track */
.partner-track {
  display: flex;
  gap: .75rem;
  width: max-content;
  animation: partnerScroll 28s linear infinite;
}
.partner-track-wrap:hover .partner-track {
  animation-play-state: paused;
}

@keyframes partnerScroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* Individual badge */
.partner-badge {
  display: inline-flex;
  align-items: center;
  gap: .65rem;
  padding: .65rem 1.1rem;
  border-radius: 14px;
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.08);
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
  transition: background .25s, border-color .25s, transform .25s;
}
.partner-badge:hover {
  background: rgba(255,255,255,.07) !important;
  border-color: rgba(16,185,129,.25) !important;
  transform: translateY(-2px);
}
.partner-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.partner-icon i { font-size: .85rem; }
.partner-name {
  font-family: 'Montserrat', sans-serif;
  font-size: .72rem;
  font-weight: 600;
  color: rgba(255,255,255,.5);
  transition: color .25s;
}
.partner-badge:hover .partner-name { color: rgba(255,255,255,.85); }

/* About section — subtle dot grid background */
.about-dots{
  background-image:radial-gradient(rgba(16,185,129,.12) 1px,transparent 1px);
  background-size:26px 26px;
  -webkit-mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 80%);
  mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 80%);
}
</style>

<!-- ══════════════════════════════════════
     ABOUT
══════════════════════════════════════ -->
<section class="about-section relative py-20 lg:py-28 px-4 lg:px-0 overflow-hidden" id="about">
  <!-- Background decoration -->
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
    <!-- Soft top/bottom fade so section blends with neighbours -->
    <div class="absolute inset-0" style="background:linear-gradient(180deg,#0a0a0a 0%,#0d1311 40%,#0d1311 60%,#0a0a0a 100%)"></div>
    <!-- Emerald glow — left -->
    <div class="absolute -left-40 top-10 w-[520px] h-[520px] rounded-full" style="background:radial-gradient(circle,rgba(16,185,129,.14) 0%,transparent 70%);filter:blur(30px)"></div>
    <!-- Emerald glow — right -->
    <div class="absolute -right-40 bottom-0 w-[460px] h-[460px] rounded-full" style="background:radial-gradient(circle,rgba(5,150,105,.1) 0%,transparent 70%);filter:blur(30px)"></div>
    <!-- Subtle dot grid -->
    <div class="absolute inset-0 about-dots opacity-[.4]"></div>
    <!-- Top & bottom hairline -->
    <div class="absolute top-0 inset-x-0 h-px" style="background:linear-gradient(to right,transparent,rgba(16,185,129,.25),transparent)"></div>
    <div class="absolute bottom-0 inset-x-0 h-px" style="background:linear-gradient(to right,transparent,rgba(16,185,129,.15),transparent)"></div>
  </div>
  <div class="relative z-10 max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-[5fr_6fr] gap-14 lg:gap-20 items-center">

      <!-- ── Left: Image composition ── -->
      <div class="relative reveal">

        <!-- Main image -->
        <div class="rounded-3xl overflow-hidden shadow-2xl" style="height:520px">
          <img src="<?= IMG_ABOUT ?>" alt="Jambo Masai Tours safari experience"
               loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>

        <!-- Secondary inset image — bottom right -->
        <div class="absolute -bottom-7 -right-3 lg:-right-8 w-40 h-32 lg:w-52 lg:h-40 rounded-2xl overflow-hidden shadow-2xl"
             style="border:3px solid #0a0a0a">
          <img src="<?= IMG_MAASAI ?>" alt="Maasai culture Tanzania"
               loading="lazy" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          <p class="absolute bottom-2 left-3 text-white text-[.6rem] font-nav font-semibold tracking-wider uppercase">Jambo Masai Tours</p>
        </div>

        <!-- Experience badge — top left -->
        <div class="absolute -top-4 -left-3 lg:-left-6 rounded-2xl px-5 py-4 shadow-2xl"
             style="background:linear-gradient(135deg,#047857,#10b981)">
          <div class="text-3xl font-bold text-white font-heading leading-none">15+</div>
          <div class="text-emerald-100 text-[.72rem] mt-1 leading-tight font-nav uppercase tracking-wider">Years of<br>Adventure</div>
        </div>

        <!-- Floating cert card — middle right -->
        <div class="glass-card absolute top-1/2 -translate-y-1/2 -right-3 lg:-right-10 p-4 space-y-2.5 hidden lg:block">
          <?php foreach ([
            ['fa-star',       '#f59e0b', 'Top Rated',     '4.9 · TripAdvisor'],
            ['fa-certificate','#10b981', 'TATO Certified','Licensed Operator'],
            ['fa-shield-alt', '#8b5cf6', 'KPAP Member',   'Responsible Tourism'],
          ] as $c): ?>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background:<?= $c[1] ?>18">
              <i class="fas <?= $c[0] ?> text-xs" style="color:<?= $c[1] ?>"></i>
            </div>
            <div>
              <div class="text-white font-semibold text-[.78rem] leading-tight"><?= $c[2] ?></div>
              <div class="text-white/35 text-[.62rem]"><?= $c[3] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>

      <!-- ── Right: Text content ── -->
      <div class="reveal" style="transition-delay:120ms">

        <!-- Section pill -->
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-5">
          <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
          <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.18em] uppercase font-nav">About Us</span>
        </div>

        <!-- Heading -->
        <h2 class="font-heading text-white leading-[1.08] mb-2" style="font-size:clamp(2rem,4vw,3.1rem)">
          Tanzania's Most <span class="hero-grad">Trusted</span><br>Safari Experience
        </h2>

        <!-- Location tag -->
        <p class="text-emerald-500 font-nav text-[.78rem] font-semibold mb-5 tracking-wide">
          <i class="fas fa-map-marker-alt text-xs mr-1"></i> Premier Safari Provider · Heart of Arusha, Tanzania
        </p>

        <!-- Body copy -->
        <p class="text-white/60 leading-[1.85] mb-4 text-[.94rem]">
          At <strong class="text-white/90 font-semibold">Jambo Masai Tours</strong>, we invite you to explore the wonders of Tanzania — from the majestic heights of
          <strong class="text-emerald-400">Kilimanjaro</strong> to the pristine shores of <strong class="text-emerald-400">Zanzibar</strong>.
        </p>
        <p class="text-white/50 leading-[1.85] mb-7 text-[.94rem]">
          We specialise in crafting <strong class="text-white/75 font-medium">tailor-made experiences</strong> that cater to your every desire — whether you seek the thrill of mountain climbing, the serenity of a beach retreat, or the excitement of encountering Africa's iconic wildlife. Our expert team is here to make your dreams a reality.
        </p>

        <!-- Feature grid 2×3 -->
        <div class="grid grid-cols-2 gap-x-4 gap-y-3 mb-8">
          <?php foreach ([
            ['fa-compass',       '#10b981', 'Expert Maasai Guides',   'Born & raised in Tanzania'],
            ['fa-mountain',      '#8b5cf6', 'Kilimanjaro Expeditions','All routes & skill levels'],
            ['fa-water',         '#3b82f6', 'Zanzibar Retreats',      'Beach & island packages'],
            ['fa-paw',           '#f59e0b', 'Wildlife Safaris',       'Big Five guaranteed'],
            ['fa-leaf',          '#34d399', 'Eco-Responsible',        'Carbon-neutral by 2027'],
            ['fa-headset',       '#f97316', '24/7 Support',           'Always here for you'],
          ] as $f): ?>
          <div class="flex items-start gap-3 group py-1">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110"
                 style="background:<?= $f[1] ?>15">
              <i class="fas <?= $f[0] ?> text-xs" style="color:<?= $f[1] ?>"></i>
            </div>
            <div>
              <div class="text-white/80 text-[.8rem] font-semibold leading-snug"><?= $f[2] ?></div>
              <div class="text-white/30 text-[.68rem] mt-0.5"><?= $f[3] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- CTA row -->
        <div class="flex flex-wrap gap-3 items-center">
          <a href="<?= url('about.php') ?>"
             class="group inline-flex items-center gap-2 font-nav font-semibold text-[.78rem] text-white px-6 py-3 rounded-xl transition-all hover:scale-105 hover:shadow-lg"
             style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 4px 18px rgba(16,185,129,.2)">
            <i class="fas fa-compass text-xs"></i>
            Our Full Story
            <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
          </a>
          <a href="<?= url('booking.php') ?>"
             class="inline-flex items-center gap-2 font-nav font-semibold text-[.78rem] text-white/75 px-6 py-3 rounded-xl transition-all hover:text-white hover:bg-white/10"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
            <i class="fas fa-calendar-check text-xs text-emerald-400"></i>
            Book Your Safari
          </a>
          <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 font-nav font-semibold text-[.78rem] px-5 py-3 rounded-xl transition-all hover:scale-105"
             style="color:#25D366;background:rgba(37,211,102,.07);border:1px solid rgba(37,211,102,.18)">
            <i class="fab fa-whatsapp text-base"></i>
            Chat With Us
          </a>
        </div>

      </div>
    </div>
  </div>
</section>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     SEARCH BAR
══════════════════════════════════════ -->
<section class="relative z-20 -mt-px">
  <div class="max-w-6xl mx-auto px-4 lg:px-6 py-6">
    <form action="<?= url('tours.php') ?>" method="GET"
          class="glass-card p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
      <div>
        <label class="search-label" for="s-dest">Destination</label>
        <select class="search-select" id="s-dest" name="destination">
          <option value="">All Destinations</option>
          <option>Serengeti</option><option>Ngorongoro</option><option>Kilimanjaro</option>
          <option>Zanzibar</option><option>Tarangire</option><option>Lake Manyara</option>
        </select>
      </div>
      <div>
        <label class="search-label" for="s-type">Tour Type</label>
        <select class="search-select" id="s-type" name="tour_type">
          <option value="">All Types</option>
          <option>Wildlife Safari</option><option>Trekking</option>
          <option>Beach Holiday</option><option>Cultural Tour</option>
        </select>
      </div>
      <div>
        <label class="search-label" for="s-trav">Travellers</label>
        <select class="search-select" id="s-trav" name="travelers">
          <option>1 Person</option><option>2 People</option>
          <option>3-5 People</option><option>6-10 People</option><option>10+ People</option>
        </select>
      </div>
      <div>
        <label class="search-label" for="s-date">Travel Date</label>
        <input type="date" class="search-input" id="s-date" name="date"
               min="<?= date('Y-m-d',strtotime('+1 day')) ?>">
      </div>
      <button type="submit" class="btn-gold justify-center h-[46px]">
        <i class="fas fa-search text-xs"></i> Search
      </button>
    </form>
  </div>
</section>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     FEATURED TOURS
══════════════════════════════════════ -->
<section class="py-20 lg:py-24 px-4 lg:px-0" id="tours">
  <div class="max-w-7xl mx-auto">

    <!-- Section header — centered -->
    <div class="text-center max-w-2xl mx-auto mb-14 reveal">
      <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-5">
        <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.2em] uppercase font-nav">Our Safaris</span>
      </div>
      <h2 class="font-heading text-white leading-tight mb-4" style="font-size:clamp(2rem,4.5vw,3.2rem)">
        Unforgettable <span class="hero-grad">Safari</span> Packages
      </h2>
      <p class="text-white/50 leading-relaxed text-[.94rem]">
        Choose from our carefully curated safari experiences, each designed to give you the most authentic and thrilling African adventure.
      </p>
    </div>

    <!-- Tour cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($featuredTours as $i => $tour):
        /* Top badge for first (Most Popular) and last (Premium) */
        $topBadge = match(true) {
          $i === 0 => ['MOST POPULAR', '#f59e0b', 'rgba(245,158,11,.9)', '#000'],
          $i === count($featuredTours) - 1 => ['PREMIUM', '#3b82f6', 'rgba(59,130,246,.85)', '#fff'],
          default  => null,
        };
      ?>
      <article class="tour-card reveal group" style="transition-delay:<?= $i * 70 ?>ms">

        <!-- Image -->
        <div class="relative overflow-hidden" style="height:230px">
          <img src="<?= e($tour['image']) ?>" alt="<?= e($tour['name']) ?>"
               loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" width="800" height="500"
               class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.06]">

          <!-- Top badge -->
          <?php if ($topBadge): ?>
          <span class="absolute top-3 left-3 font-nav font-bold text-[.6rem] uppercase tracking-wider px-3 py-1 rounded-full flex items-center gap-1.5"
                style="background:<?= $topBadge[2] ?>;color:<?= $topBadge[3] ?>">
            <i class="fas fa-<?= $i === 0 ? 'fire' : 'gem' ?> text-[.55rem]"></i>
            <?= $topBadge[0] ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="p-5">

          <!-- Meta pills -->
          <div class="flex items-center gap-2 mb-3">
            <span class="font-nav text-[.65rem] text-emerald-400 font-semibold px-2.5 py-0.5 rounded-full"
                  style="background:rgba(16,185,129,.1)">
              <?= e($tour['duration']) ?>
            </span>
            <span class="font-nav text-[.65rem] text-white/50 px-2.5 py-0.5 rounded-full"
                  style="background:rgba(255,255,255,.06)">
              <?= e($tour['destination']) ?>
            </span>
          </div>

          <!-- Title -->
          <h3 class="font-heading text-white text-[1.18rem] font-bold leading-snug mb-2">
            <?= e($tour['name']) ?>
          </h3>

          <!-- Description -->
          <p class="text-white/45 text-sm leading-relaxed" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= e($tour['description']) ?>
          </p>

          <!-- Footer -->
          <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/[.07]">
            <div>
              <span class="text-white/40 text-xs font-nav">From </span>
              <span class="text-white font-bold text-xl font-heading"><?= formatPrice($tour['price']) ?></span>
              <span class="text-white/35 text-xs font-nav"> /person</span>
            </div>
            <a href="<?= url('tour-detail.php?slug=' . e($tour['slug'])) ?>"
               class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110 hover:shadow-lg flex-shrink-0"
               style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 3px 12px rgba(16,185,129,.3)"
               aria-label="View details for <?= e($tour['name']) ?>">
              <i class="fas fa-arrow-right text-white text-sm"></i>
            </a>
          </div>

        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <!-- View all -->
    <div class="text-center mt-10 reveal">
      <a href="<?= url('tours.php') ?>"
         class="inline-flex items-center gap-2 font-nav font-semibold text-[.78rem] text-white/55 hover:text-white transition-colors group">
        View all safari packages
        <i class="fas fa-arrow-right text-xs text-emerald-400 group-hover:translate-x-1 transition-transform"></i>
      </a>
    </div>

  </div>
</section>

<style>
/* Destinations bento grid */
.dest-bento{display:grid;gap:12px;grid-template-columns:repeat(3,1fr);grid-template-rows:246px 246px 260px}
@media(max-width:1023px){.dest-bento{grid-template-columns:repeat(2,1fr);grid-template-rows:auto}}
@media(max-width:639px){.dest-bento{grid-template-columns:1fr}}
.dest-bento-large{grid-column:span 2;grid-row:span 2}
@media(max-width:1023px){.dest-bento-large{grid-column:span 1;grid-row:span 1}}

.dest-bento-card{position:relative;border-radius:20px;overflow:hidden;cursor:pointer;display:block;text-decoration:none}
@media(max-width:1023px){.dest-bento-card{height:260px}}
@media(max-width:639px){.dest-bento-card{height:240px}}
.dest-bento-card img{width:100%;height:100%;object-fit:cover;transition:transform .65s cubic-bezier(.4,0,.2,1)}
.dest-bento-card:hover img{transform:scale(1.07)}
.dest-bento-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.88) 0%,rgba(0,0,0,.25) 55%,rgba(0,0,0,.05) 100%);transition:background .4s}
.dest-bento-card:hover .dest-bento-overlay{background:linear-gradient(to top,rgba(0,0,0,.94) 0%,rgba(0,0,0,.45) 65%,rgba(0,0,0,.12) 100%)}
.dest-bento-extra{opacity:0;transform:translateY(8px);transition:opacity .35s .04s,transform .35s .04s}
.dest-bento-card:hover .dest-bento-extra{opacity:1;transform:translateY(0)}
.dest-bento-arrow{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;transition:all .3s;flex-shrink:0}
.dest-bento-card:hover .dest-bento-arrow{background:linear-gradient(135deg,#059669,#10b981);border-color:transparent;transform:rotate(-30deg)}
</style>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     POPULAR DESTINATIONS
══════════════════════════════════════ -->
<section class="py-20 lg:py-24 px-4 lg:px-0" id="destinations">
  <div class="max-w-7xl mx-auto">

    <!-- Header — centered -->
    <div class="text-center max-w-2xl mx-auto mb-12 reveal">
      <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-5">
        <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.2em] uppercase font-nav">Explore Africa</span>
      </div>
      <h2 class="font-heading text-white leading-tight mb-4" style="font-size:clamp(2rem,4.5vw,3.2rem)">
        Popular <span class="hero-grad">Destinations</span>
      </h2>
      <p class="text-white/50 leading-relaxed text-[.94rem]">
        Tanzania's most breathtaking parks, peaks and shores — explore the wonders that await you.
      </p>
      <div class="mt-5">
        <a href="<?= url('destinations.php') ?>"
           class="inline-flex items-center gap-2 font-nav font-semibold text-[.75rem] text-white/45 hover:text-emerald-400 transition-colors group">
          View all destinations
          <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform text-emerald-400"></i>
        </a>
      </div>
    </div>

    <!-- Bento grid — DB driven -->
    <div class="dest-bento reveal">
      <?php foreach ($destinations as $idx => $dest):
        $isLarge   = $idx === 0;
        $num       = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
        $tag       = explode('|', $dest['highlights'] ?? '')[0] ?? '';
        $season    = $dest['best_season'] ?? 'Year-round';
        $region    = $dest['region'] ?? $dest['country'] ?? '';
        $slug      = $dest['slug'] ?? '';
        $img       = $dest['image'] ?? IMG_SERENGETI;
        $titleKey  = strtolower(trim($dest['title']));
        $cnt       = $toursByDest[$titleKey] ?? 0;
        $countLabel = $cnt > 0 ? $cnt . ' ' . ($cnt === 1 ? 'Tour' : 'Tours') : 'Explore';
      ?>
      <a href="<?= url('destinations.php?d=' . e($slug)) ?>"
         class="dest-bento-card <?= $isLarge ? 'dest-bento-large' : '' ?>">

        <img src="<?= e($img) ?>" alt="<?= e($dest['title']) ?>" loading="<?= $idx < 2 ? 'eager' : 'lazy' ?>">

        <div class="dest-bento-overlay"></div>

        <!-- Number — top left -->
        <div class="absolute top-4 left-4 z-10">
          <span class="font-nav font-bold text-[.58rem] tracking-[.2em] text-white/30"><?= $num ?></span>
        </div>

        <!-- Region — top right, on hover -->
        <div class="absolute top-4 right-4 z-10 dest-bento-extra">
          <span class="font-nav text-[.6rem] text-white/75 px-2.5 py-1 rounded-full"
                style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px)">
            <?= e($region) ?>
          </span>
        </div>

        <!-- Bottom info -->
        <div class="absolute bottom-0 left-0 right-0 z-10 p-5">

          <!-- Tag + season — on hover -->
          <div class="dest-bento-extra flex flex-wrap items-center gap-2 mb-2.5">
            <?php if ($tag): ?>
            <span class="font-nav text-[.6rem] text-emerald-400 font-semibold px-2 py-0.5 rounded-full"
                  style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.2)">
              <?= e($tag) ?>
            </span>
            <?php endif; ?>
            <span class="font-nav text-[.6rem] text-white/40 flex items-center gap-1">
              <i class="fas fa-sun text-amber-400 text-[.55rem]"></i><?= e($season) ?>
            </span>
          </div>

          <!-- Name + count + arrow — always visible -->
          <div class="flex items-end justify-between gap-3">
            <div>
              <h3 class="font-heading text-white font-bold leading-tight <?= $isLarge ? 'text-3xl' : 'text-xl' ?>">
                <?= e($dest['title']) ?>
              </h3>
              <p class="font-nav text-[.7rem] text-white/45 mt-1">
                <i class="fas fa-compass text-emerald-400 text-[.6rem] mr-1"></i><?= e($countLabel) ?>
              </p>
            </div>
            <div class="dest-bento-arrow flex-shrink-0">
              <i class="fas fa-arrow-right"></i>
            </div>
          </div>

        </div>
      </a>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     STATS
══════════════════════════════════════ -->
<section class="py-16 px-4 lg:px-0" id="stats" style="background:linear-gradient(135deg,#0d0d0d,#111)">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ([
        ['num'=>1200,'suffix'=>'+','label'=>'Happy Travellers','icon'=>'fa-users'],
        ['num'=>850, 'suffix'=>'+','label'=>'Safari Tours',    'icon'=>'fa-binoculars'],
        ['num'=>15,  'suffix'=>'+','label'=>'Years Experience','icon'=>'fa-calendar'],
        ['num'=>12,  'suffix'=>'', 'label'=>'Destinations',   'icon'=>'fa-map-marked-alt'],
      ] as $i => $s): ?>
      <div class="glass-card p-6 text-center reveal" style="transition-delay:<?= $i * 100 ?>ms">
        <div class="w-12 h-12 rounded-xl bg-brand/10 flex items-center justify-center mx-auto mb-4">
          <i class="fas <?= $s['icon'] ?> text-brand text-lg"></i>
        </div>
        <div class="font-heading text-4xl font-bold text-white mb-1"
             data-count="<?= $s['num'] ?>" data-suffix="<?= $s['suffix'] ?>">0<?= $s['suffix'] ?></div>
        <div class="font-nav text-xs uppercase tracking-wider text-white/40"><?= $s['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     WHY CHOOSE US
══════════════════════════════════════ -->
<section class="py-20 px-4 lg:px-0" id="why-us">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14 reveal">
      <span class="section-tag">Why Choose Us</span>
      <h2 class="font-heading text-4xl lg:text-5xl text-white mt-2">The <em class="gradient-text not-italic">Jambo Masai Tours</em> Difference</h2>
      <p class="text-white/60 mt-4 max-w-xl mx-auto">
        More than a tour operator — passionate storytellers of Africa, committed to transformative experiences.
      </p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ([
        ['icon'=>'fa-compass',      'title'=>'Local Safari Experts',     'desc'=>'Born and raised in Tanzania, our guides hold TALA certifications and know every corner of the ecosystems we operate in.'],
        ['icon'=>'fa-graduation-cap','title'=>'Professional Guides',     'desc'=>'Wildlife biologists and naturalists who transform game drives into immersive learning experiences.'],
        ['icon'=>'fa-gem',          'title'=>'Affordable Luxury',         'desc'=>'Premium camps, private vehicles and gourmet meals at prices that respect your investment.'],
        ['icon'=>'fa-shield-alt',   'title'=>'Safe & Comfortable',        'desc'=>'Modern Land Cruisers, comprehensive insurance, 24/7 emergency support and all park fees included.'],
        ['icon'=>'fa-headset',      'title'=>'24/7 Customer Support',     'desc'=>'Our team is always available — before, during and after your safari — for complete peace of mind.'],
        ['icon'=>'fa-magic',        'title'=>'Personalised Adventures',   'desc'=>'No two safaris are alike. We tailor every detail to your interests, pace and travel style.'],
      ] as $i => $f): ?>
      <div class="glass-card p-6 group hover:border-brand/30 transition-all reveal" style="transition-delay:<?= $i * 80 ?>ms">
        <div class="w-12 h-12 rounded-xl bg-brand/10 flex items-center justify-center mb-4 group-hover:bg-brand/20 transition-colors">
          <i class="fas <?= $f['icon'] ?> text-brand text-lg"></i>
        </div>
        <h3 class="font-heading text-white text-lg mb-2"><?= $f['title'] ?></h3>
        <p class="text-white/50 text-sm leading-relaxed"><?= $f['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     GALLERY
══════════════════════════════════════ -->
<section class="py-20 px-4 lg:px-0" id="gallery">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-10 reveal">
      <span class="section-tag">Gallery</span>
      <h2 class="font-heading text-4xl lg:text-5xl text-white mt-2">Wildlife &amp; <em class="gradient-text not-italic">Wonders</em></h2>
    </div>

    <!-- Filter tabs -->
    <div class="flex flex-wrap gap-2 justify-center mb-8 reveal">
      <?php foreach (['all'=>'All','wildlife'=>'Wildlife','landscapes'=>'Landscapes','culture'=>'Culture','safari-life'=>'Safari Life','zanzibar'=>'Zanzibar'] as $cat => $label): ?>
      <button class="gallery-filter-btn font-nav text-[.7rem] font-600 uppercase tracking-wider px-4 py-2 rounded-full border transition-all
                     <?= $cat==='all' ? 'bg-brand/20 border-brand/50 text-brand' : 'bg-white/5 border-white/10 text-white/60 hover:border-brand/30 hover:text-white' ?>"
              data-cat="<?= $cat ?>"
              onclick="filterGallery(this,'<?= $cat ?>')">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <div class="gallery-grid reveal">
      <?php foreach ($galleryImages as $g): ?>
      <div class="gallery-item" data-category="<?= e($g['category']) ?>"
           onclick="openLightbox('<?= addslashes(e($g['image'])) ?>','<?= addslashes(e($g['title'])) ?>')">
        <img src="<?= e($g['image']) ?>" alt="<?= e($g['title']) ?>" loading="lazy" width="600" height="400">
        <div class="gallery-item-overlay"><i class="fas fa-search-plus text-white/80"></i></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-10 reveal">
      <a href="<?= url('gallery.php') ?>" class="btn-outline">View Full Gallery</a>
    </div>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox">
  <button id="lightbox-close" onclick="closeLightbox()" aria-label="Close"><i class="fas fa-times"></i></button>
  <img id="lightbox-img" src="" alt="">
</div>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- ══════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════ -->
<section class="py-20 px-4 lg:px-0" id="testimonials">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14 reveal">
      <span class="section-tag">What Travellers Say</span>
      <h2 class="font-heading text-4xl lg:text-5xl text-white mt-2">Stories from the <em class="gradient-text not-italic">Wild</em></h2>
    </div>

    <div class="relative overflow-hidden reveal">
      <div id="testi-track" class="testi-track">
        <?php foreach ($testimonials as $t): ?>
        <div class="testi-card flex-shrink-0">
          <div class="glass-card p-6 h-full">
            <div class="text-brand text-5xl font-heading leading-none mb-3 opacity-60">"</div>
            <p class="text-white/70 text-sm leading-relaxed mb-6"><?= e($t['review']) ?></p>
            <div class="flex items-center gap-4">
              <img src="<?= e($t['photo'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($t['customer_name']) . '&background=10b981&color=fff') ?>"
                   alt="<?= e($t['customer_name']) ?>" loading="lazy"
                   class="w-12 h-12 rounded-full object-cover border-2 border-brand/30">
              <div>
                <div class="text-white font-semibold text-sm"><?= e($t['customer_name']) ?></div>
                <div class="text-white/40 text-xs"><?= e($t['country']) ?></div>
                <div class="stars text-xs mt-0.5"><?= str_repeat('★',(int)$t['rating']) ?></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="flex items-center justify-center gap-4 mt-8">
        <button id="testi-prev" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center transition hover:bg-brand/20 hover:border-brand" aria-label="Prev">‹</button>
        <div id="testi-dots" class="flex items-center gap-2"></div>
        <button id="testi-next" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center transition hover:bg-brand/20 hover:border-brand" aria-label="Next">›</button>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════
     BLOG
══════════════════════════════════════ -->
<?php if (!empty($blogPosts)): ?>
<div class="section-divider max-w-7xl mx-auto px-4"></div>
<section class="py-20 px-4 lg:px-0" id="blog">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14 reveal">
      <span class="section-tag">Travel Tips & Guides</span>
      <h2 class="font-heading text-4xl lg:text-5xl text-white mt-2">From Our <em class="gradient-text not-italic">Safari Experts</em></h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($blogPosts as $i => $post): ?>
      <article class="tour-card reveal" style="transition-delay:<?= $i * 100 ?>ms">
        <img src="<?= e($post['image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="800" height="500" class="w-full h-48 object-cover">
        <div class="p-5">
          <div class="flex items-center gap-2 text-white/40 text-xs font-nav mb-3">
            <span><?= e($post['author']) ?></span>
            <span>·</span>
            <span><?= formatDate($post['created_at']) ?></span>
          </div>
          <h3 class="font-heading text-white text-lg leading-snug mb-2"><?= e($post['title']) ?></h3>
          <p class="text-white/50 text-sm leading-relaxed mb-4"><?= e(truncate($post['excerpt'], 110)) ?></p>
          <a href="<?= url('blog-single.php?slug=' . e($post['slug'])) ?>"
             class="text-brand font-nav text-[.72rem] font-600 uppercase tracking-wider hover:text-brand/80 transition-colors">
            Read More →
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-10 reveal">
      <a href="<?= url('blog.php') ?>" class="btn-outline">All Travel Articles</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     CTA BANNER
══════════════════════════════════════ -->
<section class="py-20 px-4 lg:px-0 relative overflow-hidden"
         style="background:linear-gradient(135deg,rgba(16,185,129,.12) 0%,rgba(10,10,10,0) 60%),linear-gradient(to bottom right,#0d0d0d,#111)">
  <div class="max-w-3xl mx-auto text-center relative z-10 reveal">
    <span class="section-tag">Ready to Go?</span>
    <h2 class="font-heading text-4xl lg:text-5xl text-white mt-3 leading-snug">
      Your <em class="gradient-text not-italic">Dream Safari</em> Awaits
    </h2>
    <p class="text-white/60 mt-5 text-lg leading-relaxed">
      Contact us today and let our experts design the perfect African adventure just for you.
    </p>
    <div class="flex flex-wrap gap-3 justify-center mt-10">
      <a href="<?= url('booking.php') ?>" class="btn-gold btn-lg">
        <i class="fas fa-calendar-check text-sm"></i> Start Booking
      </a>
      <a href="<?= url('contact.php') ?>" class="btn-outline">
        Ask a Question
      </a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" class="btn-wa" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp text-lg"></i> WhatsApp Us
      </a>
    </div>
  </div>
  <!-- Decorative circles -->
  <div class="absolute top-10 left-10 w-32 h-32 rounded-full border border-brand/10" aria-hidden="true"></div>
  <div class="absolute bottom-10 right-10 w-48 h-48 rounded-full border border-brand/[.07]" aria-hidden="true"></div>
</section>

<!-- ══════════════════════════════════════
     MEGA FOOTER
══════════════════════════════════════ -->
<style>
  .ftr-link{transition:all .25s;display:flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.8rem;color:rgba(255,255,255,.45);text-decoration:none}
  .ftr-link:hover{color:#34d399;transform:translateX(4px)}
  .ftr-social{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;transition:all .3s;text-decoration:none;flex-shrink:0}
  .ftr-social:hover{transform:translateY(-3px) scale(1.08)}
  .trust-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.5rem .75rem;transition:all .3s;cursor:default}
  .trust-badge:hover{border-color:rgba(16,185,129,.25);background:rgba(16,185,129,.04)}
  .pay-icon{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:.4rem .65rem;display:flex;align-items:center;justify-content:center;transition:all .25s}
  .pay-icon:hover{border-color:rgba(255,255,255,.18);transform:scale(1.08)}
  .safari-mini{position:relative;border-radius:16px;overflow:hidden;height:130px;border:1px solid rgba(255,255,255,.06);transition:all .35s;text-decoration:none;display:block}
  .safari-mini:hover{transform:translateY(-4px);border-color:rgba(16,185,129,.2);box-shadow:0 12px 32px rgba(0,0,0,.5)}
  .safari-mini img{width:100%;height:100%;object-fit:cover;opacity:.5;transition:transform .6s,opacity .3s}
  .safari-mini:hover img{transform:scale(1.08);opacity:.65}
  .nl-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:12px;padding:.8rem 1rem .8rem 2.75rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;box-sizing:border-box}
  .nl-input:focus{border-color:rgba(16,185,129,.5);box-shadow:0 0 0 3px rgba(16,185,129,.1)}
  .nl-input::placeholder{color:rgba(255,255,255,.25)}
  .footer-glow{position:absolute;top:-80px;left:50%;transform:translateX(-50%);width:600px;height:160px;background:radial-gradient(ellipse,rgba(16,185,129,.07) 0%,transparent 70%);pointer-events:none}
</style>

<footer id="site-footer" class="relative border-t border-white/[.06] pt-20 pb-0 overflow-hidden">
  <div class="footer-glow"></div>

  <!-- ── Newsletter CTA ── -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:3.5rem">
    <div class="relative overflow-hidden" style="background:rgba(18,18,18,.8);backdrop-filter:blur(20px);border-top:1px solid rgba(16,185,129,.1);border-bottom:1px solid rgba(255,255,255,.06)">
      <div class="absolute inset-0 pointer-events-none" style="background:linear-gradient(to right,rgba(16,185,129,.12),transparent 50%,rgba(201,168,76,.06))"></div>
      <div class="relative p-8 lg:p-10 flex flex-col lg:flex-row items-center justify-between gap-8">
        <div class="flex-1 text-center lg:text-left">
          <h3 class="font-heading text-white font-bold text-2xl lg:text-3xl mb-2">Stay Wild, Stay Updated</h3>
          <p class="text-white/45 text-[.88rem] max-w-md">Exclusive safari deals, Kilimanjaro tips and wildlife stories — straight to your inbox. No spam, just Africa.</p>
        </div>
        <div class="w-full lg:w-auto lg:min-w-[420px]">
          <form id="footer-nl-form" onsubmit="handleFooterNl(event)" class="flex flex-col sm:flex-row gap-3">
            <div style="position:relative;flex:1">
              <i class="fas fa-envelope" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.82rem;pointer-events:none"></i>
              <input type="email" required placeholder="Enter your email address" class="nl-input">
            </div>
            <button type="submit" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;padding:.8rem 1.6rem;border-radius:12px;border:none;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:.5rem;transition:all .25s;box-shadow:0 4px 16px rgba(16,185,129,.3)" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform=''">
              <i class="fas fa-paper-plane" style="font-size:.72rem"></i> Subscribe
            </button>
          </form>
          <p style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.2);margin-top:.5rem;text-align:center">Join 5,000+ adventurers. Unsubscribe anytime.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Main Grid ── -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:3rem">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-8 lg:gap-5">

      <!-- Brand (4 cols) -->
      <div class="col-span-2 md:col-span-3 lg:col-span-4">
        <a href="<?= url() ?>" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;margin-bottom:1.25rem">
          <img src="<?= SITE_URL ?>/uploads/logo-husika.png" alt="<?= e($siteName) ?>"
               style="height:48px;width:auto;max-width:160px;object-fit:contain;display:block">
          <?php if (false): ?>
            <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas fa-tree" style="color:#fff;font-size:1rem"></i>
            </div>
            <div>
              <div style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;font-size:1.15rem;line-height:1.2">
                <?= e(explode(' ',$siteName)[0]??'Jambo') ?>
                <span style="background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"> <?= e(explode(' ',$siteName)[1]??'Masai') ?></span>
              </div>
              <div style="font-family:'Montserrat',sans-serif;font-size:.55rem;color:rgba(255,255,255,.28);letter-spacing:.18em;text-transform:uppercase"><?= e($siteTagline) ?></div>
            </div>
          <?php endif; ?>
        </a>
        <p style="font-size:.84rem;color:rgba(255,255,255,.42);line-height:1.8;margin-bottom:1.25rem;max-width:280px">
          Authentic Maasai-guided safaris and mountain expeditions across Tanzania's most spectacular landscapes. We don't just show you Africa — we let you live it.
        </p>
        <!-- Socials -->
        <div style="display:flex;gap:.5rem;margin-bottom:1.25rem">
          <?php foreach ([
            ['fab fa-facebook-f',getSetting('social_facebook','#'),'rgba(59,130,246,.12)','#60a5fa'],
            ['fab fa-instagram',getSetting('social_instagram','#'),'rgba(236,72,153,.12)','#f472b6'],
            ['fab fa-twitter',getSetting('social_twitter','#'),'rgba(14,165,233,.12)','#38bdf8'],
            ['fab fa-youtube',getSetting('social_youtube','#'),'rgba(239,68,68,.12)','#f87171'],
            ['fab fa-tiktok',getSetting('social_tiktok','#'),'rgba(16,185,129,.12)','#34d399'],
          ] as $s): ?>
          <a href="<?= e($s[1]) ?>" target="_blank" rel="noopener" class="ftr-social" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)"
             onmouseover="this.style.background='<?= $s[2] ?>';this.querySelector('i').style.color='<?= $s[3] ?>'"
             onmouseout="this.style.background='rgba(255,255,255,.05)';this.querySelector('i').style.color='rgba(255,255,255,.45)'">
            <i class="<?= $s[0] ?>" style="font-size:.82rem;color:rgba(255,255,255,.45)"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <!-- TripAdvisor -->
        <div class="trust-badge">
          <div style="display:flex;gap:2px">
            <?php for($i=0;$i<5;$i++): ?><i class="fas fa-circle" style="color:#34d399;font-size:.5rem"></i><?php endfor; ?>
          </div>
          <div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:700;color:rgba(255,255,255,.8)">TripAdvisor</div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.58rem;color:rgba(255,255,255,.3)">4.9 / 5 · 2,400+ Reviews</div>
          </div>
        </div>
      </div>

      <!-- Safaris (2 cols) -->
      <div class="col-span-1 lg:col-span-2">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.65);margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem">
          <span style="width:3px;height:14px;background:#10b981;border-radius:2px;display:inline-block"></span> Safaris
        </h4>
        <ul style="space-y:0">
          <?php foreach ([
            ['Serengeti Migration', 'tours.php?destination=Serengeti'],
            ['Ngorongoro Crater',   'tours.php?destination=Ngorongoro'],
            ['Zanzibar & Beach',    'tours.php?tour_type=Beach+Holiday'],
            ['Wildlife Big Five',   'tours.php?tour_type=Wildlife+Safari'],
            ['Cultural Maasai',     'tours.php?tour_type=Cultural+Tour'],
            ['Family Safari',       'tours.php?tour_type=Family+Safari'],
          ] as $l): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url($l[1]) ?>" class="ftr-link">
              <i class="fas fa-chevron-right" style="font-size:.45rem;color:rgba(16,185,129,.4);flex-shrink:0"></i><?= $l[0] ?>
            </a>
          </li>
          <?php endforeach; ?>
          <li style="margin-top:.85rem">
            <a href="<?= url('tours.php') ?>" style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:700;color:#10b981;text-decoration:none;display:flex;align-items:center;gap:.4rem">View All Safaris <i class="fas fa-arrow-right" style="font-size:.55rem"></i></a>
          </li>
        </ul>
      </div>

      <!-- Destinations (2 cols) -->
      <div class="col-span-1 lg:col-span-2">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.65);margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem">
          <span style="width:3px;height:14px;background:#f59e0b;border-radius:2px;display:inline-block"></span> Destinations
        </h4>
        <ul>
          <?php foreach ([
            ['🦁','Serengeti',   'serengeti'],
            ['🌋','Ngorongoro',  'ngorongoro'],
            ['🏔️','Kilimanjaro', 'kilimanjaro'],
            ['🏖️','Zanzibar',    'zanzibar'],
            ['🐘','Tarangire',   'tarangire'],
            ['🤝','Maasai Heartland','maasai'],
          ] as $d): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url('destinations.php?d='.$d[2]) ?>" class="ftr-link"><?= $d[0] ?> <?= $d[1] ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Trekking (2 cols) -->
      <div class="col-span-1 lg:col-span-2">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.65);margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem">
          <span style="width:3px;height:14px;background:#3b82f6;border-radius:2px;display:inline-block"></span> Trekking
        </h4>
        <ul>
          <?php foreach ([
            ['🏔️','Machame Route',  'mountain-trekking.php'],
            ['🌿','Marangu Route',  'mountain-trekking.php'],
            ['🗺️','Lemosho Route',  'mountain-trekking.php'],
            ['⛺','Rongai Route',   'mountain-trekking.php'],
            ['🌋','Mt. Meru',       'mountain-trekking.php'],
            ['🎒','Gear Checklist', 'mountain-trekking.php#gear'],
          ] as $t): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url($t[2]) ?>" class="ftr-link"><?= $t[0] ?> <?= $t[1] ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Contact (2 cols) -->
      <div class="col-span-2 md:col-span-3 lg:col-span-2">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.65);margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem">
          <span style="width:3px;height:14px;background:#8b5cf6;border-radius:2px;display:inline-block"></span> Contact
        </h4>
        <ul style="list-style:none">
          <?php foreach ([
            ['fa-map-marker-alt','#10b981','<a href="https://maps.app.goo.gl/cqcERfdGpABg9xo49" target="_blank" rel="noopener" style="color:rgba(255,255,255,.42);text-decoration:none">Arusha, Tanzania 12105</a>'],
            ['fa-phone',         '#10b981','<a href="tel:'.e(SITE_PHONE).'" style="color:rgba(255,255,255,.42);text-decoration:none">'.e(SITE_PHONE).'</a>'],
            ['fa-envelope',      '#10b981','<a href="mailto:'.e(SITE_EMAIL).'" style="color:rgba(255,255,255,.42);text-decoration:none">'.e(SITE_EMAIL).'</a>'],
            ['fab fa-whatsapp',  '#25D366','<a href="https://wa.me/'.e(WHATSAPP_NUMBER).'" target="_blank" rel="noopener" style="color:#25D366;font-weight:600;text-decoration:none">Chat on WhatsApp</a>'],
          ] as $c): ?>
          <li style="display:flex;align-items:flex-start;gap:.65rem;margin-bottom:.85rem">
            <div style="width:30px;height:30px;background:rgba(255,255,255,.05);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="<?= $c[0] ?>" style="color:<?= $c[1] ?>;font-size:.65rem"></i>
            </div>
            <span style="font-family:'Inter',sans-serif;font-size:.82rem;color:rgba(255,255,255,.42);line-height:1.5;margin-top:.2rem"><?= $c[2] ?></span>
          </li>
          <?php endforeach; ?>
          <!-- Hours -->
          <li style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.65rem .85rem;margin-top:.65rem">
            <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#10b981;margin-bottom:.3rem">Office Hours</p>
            <p style="font-family:'Inter',sans-serif;font-size:.72rem;color:rgba(255,255,255,.38)">Mon–Fri: 8am – 6pm EAT</p>
            <p style="font-family:'Inter',sans-serif;font-size:.72rem;color:rgba(255,255,255,.38)">Sat–Sun: 9am – 4pm EAT</p>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- ── Safari Mini Cards (Featured from DB) ── -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:3rem">
    <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.25);margin-bottom:.75rem">Featured Experiences</p>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px" class="grid-cols-2 sm:grid-cols-4">
      <?php
      $miniTours = array_slice($featuredTours, 0, 4);
      if (empty($miniTours)) {
        $miniTours = [
          ['name'=>'Serengeti Migration','price'=>2499,'image'=>IMG_SERENGETI,'slug'=>''],
          ['name'=>'Ngorongoro Crater',  'price'=>1850,'image'=>IMG_NGORONGORO,'slug'=>''],
          ['name'=>'Kilimanjaro Trek',   'price'=>1850,'image'=>IMG_KILIMANJARO,'slug'=>''],
          ['name'=>'Zanzibar Beach',     'price'=>1299,'image'=>IMG_ZANZIBAR,'slug'=>''],
        ];
      }
      foreach ($miniTours as $mt):
        $mlink = !empty($mt['slug']) ? url('tour-detail.php?slug='.e($mt['slug'])) : url('tours.php');
      ?>
      <a href="<?= $mlink ?>" class="safari-mini">
        <img src="<?= e($mt['image']) ?>" alt="<?= e($mt['name']) ?>" loading="lazy">
        <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.88),transparent 55%)"></div>
        <div class="absolute bottom-0 left-0 right-0 p-3.5">
          <div style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:700;color:#fff;line-height:1.3"><?= e($mt['name']) ?></div>
          <div style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:#10b981;margin-top:.15rem">From $<?= number_format((float)$mt['price']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Trust & Payments ── -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:2.5rem">
    <div style="background:rgba(14,14,14,.7);border-top:1px solid rgba(255,255,255,.07);border-bottom:1px solid rgba(255,255,255,.07);padding:1.5rem 0">
      <div class="grid md:grid-cols-3 gap-6 items-center">
        <!-- Certifications -->
        <div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25);margin-bottom:.65rem">Trusted & Certified</p>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem">
            <?php foreach ([
              ['fa-shield-alt','#10b981','TATO Member',   'Tanzania Association'],
              ['fa-certificate','#f59e0b','KINAPA Licensed','National Parks Auth.'],
              ['fa-leaf',       '#34d399','Eco-Certified', 'Sustainable Tourism'],
            ] as $b): ?>
            <div class="trust-badge">
              <i class="fas <?= $b[0] ?>" style="color:<?= $b[1] ?>;font-size:.82rem"></i>
              <div>
                <div style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:rgba(255,255,255,.75)"><?= $b[2] ?></div>
                <div style="font-family:'Montserrat',sans-serif;font-size:.52rem;color:rgba(255,255,255,.28)"><?= $b[3] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Payment methods -->
        <div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25);margin-bottom:.65rem">We Accept</p>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem">
            <?php foreach ([
              ['fab fa-cc-visa',       'text-blue-300',   ''],
              ['fab fa-cc-mastercard', 'text-red-300',    ''],
              ['fab fa-cc-amex',       'text-blue-400',   ''],
              ['fab fa-cc-paypal',     'text-blue-300',   ''],
              ['fab fa-cc-stripe',     'text-purple-300', ''],
            ] as $p): ?>
            <div class="pay-icon"><i class="<?= $p[0] ?> <?= $p[1] ?>" style="font-size:1.3rem"></i></div>
            <?php endforeach; ?>
            <div class="pay-icon" style="background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.15)">
              <i class="fas fa-mobile-alt" style="color:#10b981;font-size:.78rem;margin-right:.25rem"></i>
              <span style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:#10b981">M-Pesa</span>
            </div>
          </div>
        </div>
        <!-- Security -->
        <div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25);margin-bottom:.65rem">Secure Booking</p>
          <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <?php foreach ([
              ['fa-lock',   '#10b981','SSL Encrypted', '256-bit security'],
              ['fa-undo',   '#f59e0b','Free Cancel',   '30 days notice'],
              ['fa-headset','#8b5cf6','24/7 Support',  'Always available'],
            ] as $s): ?>
            <div class="trust-badge">
              <i class="fas <?= $s[0] ?>" style="color:<?= $s[1] ?>;font-size:.78rem"></i>
              <div>
                <div style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:rgba(255,255,255,.75)"><?= $s[2] ?></div>
                <div style="font-family:'Montserrat',sans-serif;font-size:.52rem;color:rgba(255,255,255,.28)"><?= $s[3] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Bottom bar ── -->
  <div style="border-top:1px solid rgba(255,255,255,.06);background:rgba(0,0,0,.3)">
    <div style="width:100%;padding:.85rem 2rem;box-sizing:border-box">
      <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
          <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.3)">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
          <span style="color:rgba(255,255,255,.12);display:none" class="md:inline">•</span>
          <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.2)" class="hidden md:inline">Made with <i class="fas fa-heart" style="color:rgba(239,68,68,.45);font-size:.6rem"></i> in Tanzania</span>
          <span style="color:rgba(255,255,255,.12);display:none" class="md:inline">•</span>
          <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.2)">Built by <span style="color:#10b981">hiddenxcel</span></span>
        </div>
        <div style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap">
          <?php foreach (['Privacy Policy','Terms of Service','Cookie Policy','Sitemap'] as $l): ?>
          <a href="#" style="font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.25);text-decoration:none;transition:color .2s" onmouseover="this.style.color='rgba(255,255,255,.55)'" onmouseout="this.style.color='rgba(255,255,255,.25)'"><?= $l ?></a>
          <?php endforeach; ?>
        </div>
        <button onclick="window.scrollTo({top:0,behavior:'smooth'})" style="display:flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.3);background:none;border:none;cursor:pointer;transition:color .2s" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='rgba(255,255,255,.3)'">
          Back to top <i class="fas fa-arrow-up" style="font-size:.55rem"></i>
        </button>
      </div>
    </div>
  </div>
</footer>

<script>
function handleFooterNl(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.7rem"></i> Subscribing…';
  const email = e.target.querySelector('input[type=email]').value;
  fetch('<?= url('ajax/book.php') ?>', {
    method:'POST',
    body: new URLSearchParams({'<?= CSRF_TOKEN_NAME ?>':'<?= generateCsrfToken() ?>','name':'Newsletter Subscriber','email':email,'subject':'Newsletter Subscription','message':'Subscribed via footer newsletter form.'})
  }).then(() => {
    btn.innerHTML = '<i class="fas fa-check" style="font-size:.7rem"></i> Subscribed!';
    e.target.reset();
    setTimeout(() => { btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane" style="font-size:.72rem"></i> Subscribe'; }, 3000);
  }).catch(() => { btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane" style="font-size:.72rem"></i> Subscribe'; });
}
</script>

<?php require_once 'includes/booking-modal.php'; ?>

<!-- Inline hero booking form handler (reuses ajax/book.php) -->
<script>
(function(){
  const form   = document.getElementById('hero-bk-form');
  if (!form) return;
  const errDiv = document.getElementById('hero-bk-error');
  const submit = document.getElementById('hero-bk-submit');
  const wrap   = document.getElementById('hero-bk-success');
  const orig   = submit.innerHTML;

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    errDiv.classList.add('hidden');
    submit.disabled  = true;
    submit.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Sending…';
    try {
      const base = window.SITE_URL || '';
      const res  = await fetch(base + '/ajax/book.php', { method:'POST', body:new FormData(form) });
      const data = await res.json();
      if (data.success) {
        form.classList.add('hidden');
        wrap.classList.remove('hidden');
      } else {
        errDiv.textContent = data.message || 'Something went wrong. Please try again.';
        errDiv.classList.remove('hidden');
        submit.disabled  = false;
        submit.innerHTML = orig;
      }
    } catch(err) {
      errDiv.textContent = 'Network error. Please check your connection and try again.';
      errDiv.classList.remove('hidden');
      submit.disabled  = false;
      submit.innerHTML = orig;
    }
  });
})();
</script>

<!-- WhatsApp float -->
<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <i class="fab fa-whatsapp text-2xl relative z-10"></i>
</a>

<!-- Back to top -->
<button id="back-top" aria-label="Back to top">
  <i class="fas fa-chevron-up text-sm"></i>
</button>

<!-- AOS -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
(function(){
  'use strict';

  window.SITE_URL = '<?= SITE_URL ?>';

  /* ─── AOS ─── */
  AOS.init({ duration: 700, once: true, offset: 60 });

  /* ─── Navbar ─── */
  const navEl = document.getElementById('main-nav');
  window.addEventListener('scroll', () => {
    if (!navEl) return;
    navEl.style.background    = scrollY > 60 ? 'rgba(10,10,10,.96)' : '';
    navEl.style.backdropFilter = scrollY > 60 ? 'blur(20px)' : '';
    navEl.style.borderBottom  = scrollY > 60 ? '1px solid rgba(16,185,129,.1)' : '';
    navEl.style.boxShadow     = scrollY > 60 ? '0 4px 24px rgba(0,0,0,.4)' : '';
    const sp = document.getElementById('scroll-prog-home');
    if (sp) { const mx = document.documentElement.scrollHeight - innerHeight; if(mx>0) sp.style.width=(scrollY/mx*100).toFixed(2)+'%'; }
  }, { passive: true });

  /* ─── Mobile drawer (new) ─── */
  const mmNew = document.getElementById('main-mobile-menu');
  const bdNew = document.getElementById('mobile-backdrop');
  const togNew = document.getElementById('menu-toggle');
  const t1n=document.getElementById('tog-1'),t2n=document.getElementById('tog-2'),t3n=document.getElementById('tog-3');
  let mobIsOpen = false;
  function idxOpenMob() {
    if(!mmNew)return; mmNew.classList.add('open'); bdNew?.classList.add('show'); document.body.style.overflow='hidden'; mobIsOpen=true;
    if(t1n){t1n.style.transform='rotate(45deg) translate(5px,5px)';t2n.style.opacity='0';t3n.style.transform='rotate(-45deg) translate(5px,-5px)';}
  }
  function idxCloseMob() {
    if(!mmNew)return; mmNew.classList.remove('open'); bdNew?.classList.remove('show'); document.body.style.overflow=''; mobIsOpen=false;
    if(t1n){t1n.style.transform='';t2n.style.opacity='';t3n.style.transform='';}
  }
  window.idxCloseMob = idxCloseMob;
  togNew?.addEventListener('click', () => mobIsOpen ? idxCloseMob() : idxOpenMob());
  document.getElementById('mob-close-x')?.addEventListener('click', idxCloseMob);
  bdNew?.addEventListener('click', idxCloseMob);
  mmNew?.querySelectorAll('a').forEach(a => a.addEventListener('click', idxCloseMob));
  document.addEventListener('keydown', e => { if(e.key==='Escape'){idxCloseMob();idxCloseSearch();} });

  /* ─── Search overlay ─── */
  const sOv=document.getElementById('idx-search-ov'), sWr=document.getElementById('idx-swrap');
  function idxOpenSearch(){sOv.style.opacity='1';sOv.style.pointerEvents='all';if(sWr)sWr.style.transform='translateY(0)';document.body.style.overflow='hidden';setTimeout(()=>document.getElementById('idx-sinput')?.focus(),250);}
  function idxCloseSearch(){sOv.style.opacity='0';sOv.style.pointerEvents='none';if(sWr)sWr.style.transform='translateY(-16px)';document.body.style.overflow='';}
  function idxDoSearch(){const q=document.getElementById('idx-sinput')?.value?.trim();if(q)window.location=SITE_URL+'/tours.php?search='+encodeURIComponent(q);}
  window.idxOpenSearch=idxOpenSearch; window.idxCloseSearch=idxCloseSearch; window.idxDoSearch=idxDoSearch;

  /* ─── Scroll progress ─── */
  const prog = document.getElementById('scroll-progress');
  window.addEventListener('scroll', () => {
    const max = document.documentElement.scrollHeight - innerHeight;
    if (prog) prog.style.width = (scrollY / max * 100).toFixed(2) + '%';
  }, { passive: true });

  /* ─── Navbar scroll ─── */
  const nav = document.getElementById('main-nav');
  const updateNav = () => nav && nav.classList.toggle('scrolled', scrollY > 60);
  window.addEventListener('scroll', updateNav, { passive: true });
  updateNav();

  /* ─── Mobile menu ─── */
  const menuToggle  = document.getElementById('menu-toggle');
  const mobileMenu  = document.getElementById('mobile-menu');
  const backdrop    = document.getElementById('mobile-backdrop');
  const tog1 = document.getElementById('tog-1');
  const tog2 = document.getElementById('tog-2');
  const tog3 = document.getElementById('tog-3');

  function openMobileMenu() {
    mobileMenu.classList.add('open');
    backdrop.classList.add('show');
    document.body.style.overflow = 'hidden';
    if (tog1) { tog1.style.transform='rotate(45deg) translate(5px,5px)'; tog2.style.opacity='0'; tog3.style.transform='rotate(-45deg) translate(5px,-5px)'; }
  }
  function closeMobileMenu() {
    mobileMenu.classList.remove('open');
    backdrop.classList.remove('show');
    document.body.style.overflow = '';
    if (tog1) { tog1.style.transform=''; tog2.style.opacity=''; tog3.style.transform=''; }
  }
  if (menuToggle) menuToggle.addEventListener('click', () => mobileMenu.classList.contains('open') ? closeMobileMenu() : openMobileMenu());
  if (backdrop)   backdrop.addEventListener('click', closeMobileMenu);
  mobileMenu && mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMobileMenu));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobileMenu(); });
  window.addEventListener('resize', () => { if (innerWidth >= 992) closeMobileMenu(); }, { passive: true });

  /* ─── Hero slider ─── */
  const slides    = document.querySelectorAll('.hero-slide-content');
  const heroPrev  = document.getElementById('hero-prev');
  const heroNext  = document.getElementById('hero-next');
  const heroCur   = document.getElementById('hero-cur');
  const heroProg  = document.getElementById('hero-prog');
  let heroIdx = 0, heroTimer = null;
  const heroTotal = slides.length;

  function heroGoTo(i) {
    slides[heroIdx].classList.add('hidden');
    heroIdx = ((i % heroTotal) + heroTotal) % heroTotal;
    slides[heroIdx].classList.remove('hidden');
    if (heroCur) heroCur.textContent = String(heroIdx + 1).padStart(2, '0');
    if (heroProg) heroProg.style.height = ((heroIdx + 1) / heroTotal * 100) + '%';
  }
  function heroStart() { clearInterval(heroTimer); heroTimer = setInterval(() => heroGoTo(heroIdx + 1), 7000); }

  if (heroTotal > 1) {
    if (heroPrev) heroPrev.addEventListener('click', () => { heroGoTo(heroIdx - 1); heroStart(); });
    if (heroNext) heroNext.addEventListener('click', () => { heroGoTo(heroIdx + 1); heroStart(); });
    const heroEl = document.getElementById('hero');
    if (heroEl) { heroEl.addEventListener('mouseenter', () => clearInterval(heroTimer)); heroEl.addEventListener('mouseleave', heroStart); }
    heroStart();
    let tx = 0;
    document.getElementById('hero') && document.getElementById('hero').addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    document.getElementById('hero') && document.getElementById('hero').addEventListener('touchend', e => {
      const d = tx - e.changedTouches[0].clientX;
      if (Math.abs(d) > 50) { heroGoTo(heroIdx + (d > 0 ? 1 : -1)); heroStart(); }
    }, { passive: true });
  }

  /* ─── Scroll reveal ─── */
  const revealEls = document.querySelectorAll('.reveal');
  const revObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revObs.unobserve(e.target); } });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  revealEls.forEach(el => revObs.observe(el));

  /* ─── Stats counter ─── */
  const counters = document.querySelectorAll('[data-count]');
  const ease = t => 1 - Math.pow(1 - t, 3);
  const cntObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = +el.dataset.count;
      const suffix = el.dataset.suffix || '';
      const start = performance.now();
      const step = now => {
        const p = Math.min((now - start) / 2200, 1);
        el.textContent = Math.floor(ease(p) * target).toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
      cntObs.unobserve(el);
    });
  }, { threshold: 0.3 });
  counters.forEach(c => cntObs.observe(c));

  /* ─── Testimonials slider ─── */
  const track   = document.getElementById('testi-track');
  const dotsWrap = document.getElementById('testi-dots');
  const tPrev   = document.getElementById('testi-prev');
  const tNext   = document.getElementById('testi-next');
  const tCards  = track ? track.querySelectorAll('.testi-card') : [];
  let tCur = 0, tAuto;
  const tVis = () => innerWidth >= 768 ? 2 : 1;

  function tBuildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    const count = Math.ceil(tCards.length / tVis());
    for (let i = 0; i < count; i++) {
      const d = document.createElement('button');
      d.className = 'slider-dot' + (i === 0 ? ' active' : '');
      d.setAttribute('aria-label', 'Slide ' + (i + 1));
      d.addEventListener('click', () => tGoTo(i));
      dotsWrap.appendChild(d);
    }
  }
  function tGoTo(i) {
    const count = Math.ceil(tCards.length / tVis());
    tCur = ((i % count) + count) % count;
    if (track) track.style.transform = `translateX(-${tCur * (100 / tVis())}%)`;
    dotsWrap && dotsWrap.querySelectorAll('.slider-dot').forEach((d, j) => d.classList.toggle('active', j === tCur));
  }
  function tStart() { tAuto = setInterval(() => tGoTo(tCur + 1), 5000); }
  function tStop()  { clearInterval(tAuto); }

  if (track && tCards.length) {
    tBuildDots();
    tStart();
    tPrev && tPrev.addEventListener('click', () => { tStop(); tGoTo(tCur - 1); tStart(); });
    tNext && tNext.addEventListener('click', () => { tStop(); tGoTo(tCur + 1); tStart(); });
    track.parentElement.addEventListener('mouseenter', tStop);
    track.parentElement.addEventListener('mouseleave', tStart);
    window.addEventListener('resize', () => { tBuildDots(); tGoTo(0); }, { passive: true });
  }

  /* ─── Gallery lightbox ─── */
  const lb = document.getElementById('lightbox');
  const lbImg = document.getElementById('lightbox-img');
  window.openLightbox = (src, alt) => {
    if (!lb || !lbImg) return;
    lbImg.src = src; lbImg.alt = alt;
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
  };
  window.closeLightbox = () => {
    if (!lb) return;
    lb.classList.remove('open');
    document.body.style.overflow = '';
  };
  lb && lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

  /* ─── Gallery filter ─── */
  window.filterGallery = (btn, cat) => {
    document.querySelectorAll('.gallery-filter-btn').forEach(b => {
      b.className = b.className.replace('bg-brand/20 border-brand/50 text-brand', 'bg-white/5 border-white/10 text-white/60');
    });
    btn.className = btn.className.replace('bg-white/5 border-white/10 text-white/60', 'bg-brand/20 border-brand/50 text-brand');
    document.querySelectorAll('.gallery-item').forEach(item => {
      const show = cat === 'all' || item.dataset.category === cat;
      item.style.display = show ? '' : 'none';
    });
  };

  /* ─── Back to top ─── */
  const btt = document.getElementById('back-top');
  window.addEventListener('scroll', () => btt && btt.classList.toggle('visible', scrollY > 400), { passive: true });
  btt && btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* ─── WhatsApp float ─── */
  const waFloat = document.getElementById('wa-float');
  if (waFloat) setTimeout(() => waFloat.classList.add('visible'), 3000);

  /* ─── Lazy images ─── */
  if ('IntersectionObserver' in window) {
    const imgObs = new IntersectionObserver((entries, obs) => {
      entries.forEach(e => {
        if (!e.isIntersecting) return;
        const img = e.target;
        if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
        obs.unobserve(img);
      });
    }, { rootMargin: '250px' });
    document.querySelectorAll('img[data-src]').forEach(img => imgObs.observe(img));
  }

})();
</script>

<!-- Lenis Smooth Scroll -->
<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
<script>
(function(){
  const lenis = new Lenis({
    duration: 1.3,
    easing: function(t){ return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
    smooth: true,
    mouseMultiplier: 0.8,
    smoothTouch: false,
  });
  function raf(t){ lenis.raf(t); requestAnimationFrame(raf); }
  requestAnimationFrame(raf);
  window._lenis = lenis;
  document.querySelectorAll('a[href^="#"]').forEach(function(a){
    a.addEventListener('click',function(e){
      var el=document.querySelector(this.getAttribute('href'));
      if(el){e.preventDefault();lenis.scrollTo(el,{offset:-80,duration:1.4});}
    });
  });
})();
</script>

</body>
</html>








