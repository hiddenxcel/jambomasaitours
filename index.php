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
        ['title'=>'Kilimanjaro',     'slug'=>'kilimanjaro','region'=>'Kilimanjaro Region',   'highlights'=>'Roof of Africa – 5,895m|Trekking Routes|Snow Cap',  'best_season'=>'Jan – Mar', 'image'=>IMG_KILIMANJARO],
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
  <meta name="theme-color"        content="#a05e22">
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
          "urlTemplate": "<?= SITE_URL ?>/tours?q={search_term_string}"
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
            brand:  '#a05e22',   /* burnt orange - primary */
            brandd: '#7d4817',   /* darker orange */
            safari: '#3b5c51',   /* forest green */
            forest: '#3b5c51',
            forestd:'#2c463d',   /* deeper green */
            cream:  '#f4e1c3',   /* warm light bg */
            creaml: '#faf3e6',   /* near-white cream for cards */
            dark:   '#23362f',   /* dark = deep forest now */
            card:   '#2c463d',
            glass:  'rgba(255,255,255,0.05)',
            /* Re-map Tailwind's emerald scale to our burnt-orange brand so any
               leftover emerald-* utility classes pick up the new palette. */
            emerald: {
              100:'#f1ddc4', 200:'#e6c39b', 300:'#d9a36f',
              400:'#c17a3a', 500:'#a05e22', 600:'#7d4817', 700:'#5e3611',
            },
          },
          fontFamily: {
            heading: ['Nanum Myeongjo','Georgia','serif'],
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
  <link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- AOS -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e9efe9;font-family:'Inter','Poppins',sans-serif;line-height:1.6;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}
    ::-webkit-scrollbar-track{background:#2c463d}
    ::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}

    /* Ambient glow */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:-5%;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.12) 0%,transparent 70%)}
    .glow-orb-2{bottom:-10%;right:-5%;width:600px;height:600px;background:radial-gradient(circle,rgba(160,94,34,0.08) 0%,transparent 70%)}

    /* Glass card */
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}

    /* Gradient text */
    .gradient-text{background:linear-gradient(135deg,#c17a3a,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* Section tag */
    .section-tag{display:inline-block;background:rgba(160,94,34,.12);color:#a05e22;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;padding:.35rem 1rem;border-radius:999px;border:1px solid rgba(160,94,34,.2);margin-bottom:1.2rem}

    /* Buttons */
    .btn-gold{display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer}
    .btn-gold:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(160,94,34,.4)}
    .btn-outline{display:inline-flex;align-items:center;gap:.5rem;background:transparent;color:#a05e22;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;border:1px solid rgba(160,94,34,.4);transition:all .3s;cursor:pointer}
    .btn-outline:hover{background:rgba(160,94,34,.08);border-color:#a05e22;transform:translateY(-2px)}
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
    .scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#a05e22,#7d4817);z-index:9999;width:0%;transition:width .1s}

    /* Back to top */
    #back-top{position:fixed;bottom:2rem;right:2rem;z-index:999;width:44px;height:44px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);color:#a05e22;font-size:1.1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(20px);transition:all .3s}
    #back-top.visible{opacity:1;transform:translateY(0)}
    #back-top:hover{background:rgba(160,94,34,.25)}

    /* WhatsApp float */
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(0.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float:hover{background:#1da851;transform:scale(1.1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.35);opacity:0}}

    /* Nav */

    /* Tour card */
    .tour-card{background:#2c463d;border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden;transition:all .4s;position:relative}
    .tour-card:hover{transform:translateY(-6px);border-color:rgba(160,94,34,.3);box-shadow:0 20px 60px rgba(0,0,0,.5)}
    .tour-card img{width:100%;height:220px;object-fit:cover;transition:transform .6s}
    .tour-card:hover img{transform:scale(1.05)}

    /* Destination card */
    .dest-card{position:relative;border-radius:16px;overflow:hidden;cursor:pointer;transition:transform .4s}
    .dest-card:hover{transform:translateY(-4px)}
    .dest-card img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
    .dest-card:hover img{transform:scale(1.08)}
    .dest-card-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.85) 0%,rgba(0,0,0,.2) 60%,transparent 100%)}

    /* Gallery - horizontal auto-sliding carousel (featured wide + portrait cards) */
    .gallery-carousel{overflow:hidden;width:100%;border-radius:16px}
    .gallery-track{display:flex;gap:16px;transition:transform .9s cubic-bezier(.65,0,.35,1);will-change:transform}
    .gallery-item{flex:0 0 auto;width:300px;height:460px;border-radius:16px;overflow:hidden;cursor:pointer;position:relative;border:1px solid rgba(255,255,255,.06)}
    .gallery-item.is-featured{width:620px}
    .gallery-item img{width:100%;height:100%;display:block;object-fit:cover;transition:transform .5s}
    .gallery-item:hover img{transform:scale(1.06)}
    .gallery-item-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.6),transparent 55%);display:flex;align-items:flex-end;justify-content:flex-start;padding:1.1rem;opacity:0;transition:opacity .3s}
    .gallery-item:hover .gallery-item-overlay{opacity:1}
    .gallery-item-overlay .ico{position:absolute;top:1rem;right:1rem;width:36px;height:36px;border-radius:50%;background:rgba(160,94,34,.85);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem}
    /* Progress bar */
    .gallery-progress{position:relative;height:3px;border-radius:3px;background:rgba(255,255,255,.08);margin-top:1.75rem;overflow:hidden}
    .gallery-progress span{position:absolute;inset:0 auto 0 0;width:0;border-radius:3px;background:linear-gradient(90deg,#c17a3a,#a05e22,#7d4817)}
    @media(max-width:767px){.gallery-item{width:220px;height:360px}.gallery-item.is-featured{width:300px}}

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
    .slider-dot.active{background:#a05e22;width:24px;border-radius:4px}

    /* Star rating */
    .stars{color:#a05e22;letter-spacing:.1em;font-size:.85rem}

    /* Mobile nav */

    /* Animate on scroll */
    .reveal{opacity:0;transform:translateY(30px);transition:all .7s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}

    /* Section divider */
    .section-divider{height:1px;background:linear-gradient(to right,transparent,rgba(160,94,34,.2),transparent)}

    /* Progress bar in search form */
    .search-select,.search-input{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:8px;padding:.75rem 1rem;font-family:'Inter',sans-serif;font-size:.85rem;width:100%;outline:none;transition:border-color .2s;-webkit-appearance:none}
    .search-select:focus,.search-input:focus{border-color:rgba(160,94,34,.5)}
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
    .hero-grad{background:linear-gradient(135deg,#c17a3a,#7d4817,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    /* Animated shimmering gradient headline */
    .hero-grad-anim{background:linear-gradient(120deg,#d9a36f,#a05e22,#c17a3a,#7d4817,#d9a36f);background-size:250% 100%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:gradShift 6s ease infinite}
    @keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
    /* Hero badge with subtle glow */
    .hero-badge{background:rgba(160,94,34,.1);border:1px solid rgba(160,94,34,.25);box-shadow:0 0 30px rgba(160,94,34,.12),inset 0 1px 0 rgba(255,255,255,.05);backdrop-filter:blur(8px)}
    /* Stat numbers - gradient with glow */
    .hero-stat{color:#fff;text-shadow:0 0 24px rgba(160,94,34,.25)}
    /* Primary button shine sweep */
    .btn-shine{position:relative;overflow:hidden}
    .btn-shine::after{content:'';position:absolute;top:0;left:-120%;width:60%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.35),transparent);transform:skewX(-20deg);transition:left .7s ease}
    .btn-shine:hover::after{left:140%}
    /* Hero floating card subtle 3D tilt on hover */
    .hero-card-tilt{transition:transform .5s cubic-bezier(.16,1,.3,1)}
    .hero-card-tilt:hover{transform:perspective(1000px) rotateY(-4deg) rotateX(2deg)}
    @media(prefers-reduced-motion:reduce){.hero-grad-anim,.anim-float{animation:none}.hero-card-tilt:hover{transform:none}}

    /* ── Hero vertical scrolling image columns — FULL-BLEED right edge ── */
    /* Pinned to the top/right/bottom of the hero, spanning ~half the width. */
    .hero-cols{position:absolute;top:0;right:0;bottom:0;width:48vw;max-width:720px;z-index:5;
      grid-template-columns:1fr 1fr;gap:14px;padding:80px 14px 14px 0;overflow:hidden}
    .hero-col{height:100%;overflow:hidden}
    .hero-col-track{display:flex;flex-direction:column;gap:14px;will-change:transform}
    /* up: starts at 0, moves to -50% (content is doubled, so it loops seamlessly) */
    .hero-col-track.is-up{animation:heroColUp var(--dur,32s) linear infinite}
    /* down: starts at -50%, moves to 0 */
    .hero-col-track.is-down{animation:heroColDown var(--dur,32s) linear infinite}
    @keyframes heroColUp{from{transform:translateY(0)}to{transform:translateY(-50%)}}
    @keyframes heroColDown{from{transform:translateY(-50%)}to{transform:translateY(0)}}
    .hero-col-card{position:relative;border-radius:16px;overflow:hidden;flex-shrink:0;border:1px solid rgba(255,255,255,.08);box-shadow:0 10px 30px rgba(0,0,0,.35)}
    .hero-col-card img{width:100%;height:300px;object-fit:cover;display:block;transition:transform .5s}
    .hero-col-card:hover img{transform:scale(1.06)}
    .hero-col-cap{position:absolute;left:.6rem;bottom:.55rem;right:.6rem;color:#fff;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:600;letter-spacing:.02em;text-shadow:0 1px 6px rgba(0,0,0,.7);opacity:0;transition:opacity .3s}
    .hero-col-card::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55),transparent 50%);opacity:0;transition:opacity .3s}
    .hero-col-card:hover .hero-col-cap,.hero-col-card:hover::after{opacity:1}
    @media(max-width:1023px){.hero-cols{width:42vw}.hero-col-card img{height:230px}}

    /* ── Scroll-driven word reveal (Snippe-style) ── */
    .scroll-reveal .w{
      display:inline-block;
      color:inherit;
      opacity:.22;                 /* start faded */
      transition:opacity .25s ease;
      will-change:opacity;
    }
    .scroll-reveal .w.on{opacity:1} /* revealed */
    /* keep natural spaces between words */
    .scroll-reveal .sp{display:inline-block;width:.28em}
    /* Partners strip - logo-brown background with green edges (matches the logo) */
    .partners-strip{background:#5c3a21;border-top:4px solid #1f8a43;border-bottom:4px solid #1f8a43;box-shadow:inset 0 12px 30px rgba(0,0,0,.25),inset 0 -12px 30px rgba(0,0,0,.25)}
    /* On brown bg, brighten the badges so they read well */
    .partners-strip .partner-badge{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14)}
    .partners-strip .partner-badge:hover{background:rgba(255,255,255,.16) !important;border-color:rgba(255,255,255,.3) !important}
    .partners-strip .partner-name{color:rgba(255,255,255,.85)}
    .partners-strip .partner-badge:hover .partner-name{color:#fff}

    /* Scroll-down indicator (mouse + pulsing chevrons) */
    .scrolldown{--color:#c17a3a;width:28px;height:46px;border:3px solid var(--color);border-radius:50px;box-sizing:border-box;position:relative;cursor:pointer;transition:border-color .3s,box-shadow .3s}
    .scrolldown:hover{border-color:#a05e22;box-shadow:0 0 18px rgba(160,94,34,.35)}
    .scrolldown::before{content:'';position:absolute;top:6px;left:50%;width:6px;height:6px;margin-left:-3px;background:var(--color);border-radius:50%;box-shadow:0 0 6px rgba(160,94,34,.7);animation:scrolldownDot 2s infinite}
    @keyframes scrolldownDot{0%{opacity:0;height:6px}35%{opacity:1;height:9px}70%{transform:translateY(20px);height:9px;opacity:1}100%{transform:translateY(22px);height:4px;opacity:0}}
    .chevrons{display:flex;flex-direction:column;align-items:center;gap:4px}
    .chevrondown{width:9px;height:9px;border:solid var(--color);border-width:0 2px 2px 0;transform:rotate(45deg)}
    .chevrondown:nth-child(1){animation:chevronPulse .6s ease infinite alternate}
    .chevrondown:nth-child(2){animation:chevronPulse .6s ease infinite alternate .3s}
    @keyframes chevronPulse{from{opacity:.15}to{opacity:.85}}

    /* ------------ LIGHT SECTIONS (cream background) ------------
       Add class "section-light" to any <section> to flip it to a warm cream
       theme. These rules re-map the white text / glass cards used elsewhere
       so everything stays readable on the pale background. Palette:
       cream #f4e1c3 bg, forest-green text #233a32, burnt-orange accents. */
    .section-light{background:#f4e1c3;color:#2c463d}
    /* Headings & body text ? forest green, readable */
    .section-light h1,.section-light h2,.section-light h3,.section-light h4{color:#233a32}
    .section-light .text-white{color:#233a32 !important}
    .section-light .text-white\/90{color:#2c463d !important}
    .section-light .text-white\/80,.section-light .text-white\/75{color:#3b5c51 !important}
    .section-light .text-white\/60,.section-light .text-white\/55,.section-light .text-white\/50{color:#5a6f64 !important}
    .section-light .text-white\/45,.section-light .text-white\/40,.section-light .text-white\/35,.section-light .text-white\/30{color:#7a8b80 !important}
    /* Accent gradient/text ? burnt orange */
    .section-light .hero-grad,.section-light .gradient-text{background:linear-gradient(135deg,#a05e22,#7d4817);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .section-light .text-emerald-400,.section-light .text-emerald-500{color:#a05e22 !important}
    .section-light .text-emerald-100{color:#5e3611 !important}
    /* Glass cards ? solid cream-white cards with soft shadow */
    .section-light .glass-card{background:#faf3e6 !important;border:1px solid rgba(44,70,61,.1) !important;box-shadow:0 10px 30px rgba(44,70,61,.1) !important;backdrop-filter:none !important}
    /* Generic translucent-white surfaces (bg-white/5 etc.) ? cream card */
    .section-light .bg-white\/5,.section-light .bg-white\/\[\.05\]{background:#faf3e6 !important}
    /* Translucent-white borders ? subtle green border */
    .section-light .border-white\/10,.section-light .border-white\/\[\.1\],.section-light .border-white\/5{border-color:rgba(44,70,61,.14) !important}
    /* Section pill (accent glass) ? light orange chip */
    .section-light .bg-emerald-500\/10{background:rgba(160,94,34,.14) !important}
    .section-light .border-emerald-500\/20{border-color:rgba(160,94,34,.35) !important}
    /* Gallery progress track on cream bg */
    .section-light .gallery-progress{background:rgba(44,70,61,.14)}
    /* Card surfaces that hard-code #111 (tour cards etc.) */
    .section-light .tour-card{background:#faf3e6;border-color:rgba(44,70,61,.1)}
    .section-light .tour-card:hover{box-shadow:0 20px 50px rgba(44,70,61,.18)}
    /* Section tag pill */
    .section-light .section-tag{background:rgba(160,94,34,.14);border-color:rgba(160,94,34,.35);color:#7d4817}
    /* Soft top hairline for light sections */
    .section-light{border-top:1px solid rgba(44,70,61,.08)}
    /* Dot grid on cream */
    .section-light .about-dots{background-image:radial-gradient(rgba(44,70,61,.10) 1px,transparent 1px)}
    /* Scroll-driven word highlight — gray → dark on scroll */
    .section-light .scroll-word-reveal span{display:inline-block;transition:color .25s ease;color:#c5c5c5}
    .section-light .scroll-word-reveal span.active{color:#1a1a1a}
    /* "Book Your Safari" secondary button on light ? outlined green */
    .section-light .hover\:bg-white\/10:hover{background:rgba(160,94,34,.1) !important}
  </style>
</head>
<body class="bg-dark text-gray-200">

<!-- Scroll progress -->
<div class="scroll-progress" id="scroll-progress"></div>

<!-- Ambient glow orbs -->
<div class="glow-orb glow-orb-1" aria-hidden="true"></div>
<div class="glow-orb glow-orb-2" aria-hidden="true"></div>

<?php
/* Nav items - shorter labels for desktop, full for mobile */
$navItems = [
  'home'         => ['url'=>url(),                        'desk'=>'Home',       'mob'=>'Home',             'icon'=>'fa-home'],
  'tours'        => ['url'=>url('tours'),             'desk'=>'Safaris',    'mob'=>'Safari Tours',     'icon'=>'fa-compass'],
  'trekking'     => ['url'=>url('mountain-trekking'), 'desk'=>'Trekking',   'mob'=>'Mountain Trekking','icon'=>'fa-mountain'],
  'destinations' => ['url'=>url('destinations'),      'desk'=>'Destinations','mob'=>'Destinations',    'icon'=>'fa-map-marker-alt'],
  'gallery'      => ['url'=>url('gallery'),           'desk'=>'Gallery',    'mob'=>'Gallery',          'icon'=>'fa-images'],
  'blog'         => ['url'=>url('blog'),              'desk'=>'Blog',       'mob'=>'Blog',             'icon'=>'fa-newspaper'],
  'about'        => ['url'=>url('about'),             'desk'=>'About',      'mob'=>'About Us',         'icon'=>'fa-info-circle'],
  'contact'      => ['url'=>url('contact'),           'desk'=>'Contact',    'mob'=>'Contact',          'icon'=>'fa-envelope'],
];
/* Fetch featured tours for mega menu */
try {
  $idxMegaTours = getDB()->query("SELECT name,slug,price,destination FROM tours WHERE featured=1 ORDER BY rating DESC LIMIT 4")->fetchAll();
} catch (\Throwable $e) { $idxMegaTours = []; }
?>

<?php require_once 'includes/public_navbar.php'; ?>

<!-- --------------------------------------
     HERO - 2-column with floating card
-------------------------------------- -->
<section class="relative flex items-center overflow-hidden lg:min-h-screen" id="hero">

  <!-- Ken Burns / video background -->
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
    <!-- Overlays (forest-tinted dark for text legibility) -->
    <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(20,33,28,.85) 0%,rgba(20,33,28,.4) 45%,rgba(20,33,28,.85) 100%)"></div>
    <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(20,33,28,.85) 0%,rgba(20,33,28,.35) 60%,rgba(20,33,28,0) 100%)"></div>
    <div class="absolute -left-32 top-1/4 w-[480px] h-[480px] rounded-full pointer-events-none" style="background:radial-gradient(circle,rgba(160,94,34,.16) 0%,transparent 70%);filter:blur(20px)"></div>
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at center,transparent 55%,rgba(0,0,0,.45) 100%)"></div>
  </div>

  <!-- Content -->
  <div class="relative z-10 w-full max-w-7xl mx-auto px-4 lg:px-6 pt-24 pb-16 lg:pt-36 lg:pb-24">
    <div class="grid md:grid-cols-2 gap-10 lg:gap-16 items-center">

      <!-- -- Left: Slide text -- -->
      <div id="hero-left" class="max-w-xl md:max-w-none">
        <div class="hero-slide-content">

          <!-- Heading -->
          <h1 class="anim-up font-heading text-white leading-[1.04] tracking-tight mb-6"
              style="font-size:clamp(2.6rem,6.8vw,4.8rem);animation-delay:.2s;text-shadow:0 2px 30px rgba(0,0,0,.5)">
            Experience Tanzania Like<br>
            <span class="hero-grad hero-grad-anim">Never Before</span>
          </h1>

          <!-- Subtitle -->
          <p class="anim-up text-white/65 text-[1.05rem] leading-relaxed max-w-lg mb-8" style="animation-delay:.3s">
            Luxury Safaris, Maasai Culture &amp; Unforgettable Adventures
          </p>

          <!-- CTA buttons -->
          <div class="anim-up grid grid-cols-1 sm:flex sm:flex-wrap gap-3 mb-10" style="animation-delay:.4s">
            <a href="<?= url('tours') ?>"
               class="btn-shine group inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] text-white px-7 py-3.5 rounded-xl transition-all hover:scale-105 w-full sm:w-auto"
               style="background:linear-gradient(135deg,#7d4817,#a05e22);box-shadow:0 8px 30px rgba(160,94,34,.35)">
              <i class="fas fa-compass text-xs"></i>
              Explore Safaris
              <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
            </a>
            <div class="grid grid-cols-2 gap-3 sm:contents">
              <a href="<?= url('booking') ?>"
                 class="inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] text-white px-7 py-3.5 rounded-xl transition-all hover:bg-white/10 w-full sm:w-auto"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)">
                <i class="fas fa-play text-emerald-400 text-xs"></i>
                Book Safari
              </a>
              <a href="https://wa.me/+255659667271" target="_blank" rel="noopener"
                 class="inline-flex items-center justify-center gap-2 font-nav font-semibold text-[.8rem] px-6 py-3.5 rounded-xl transition-all w-full sm:w-auto"
                 style="color:#25D366;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2)">
                <i class="fab fa-whatsapp text-base"></i> WhatsApp
              </a>
            </div>
          </div>

          <!-- Stats row -->
          <div class="anim-up flex items-center justify-between sm:justify-start gap-2 sm:gap-8" style="animation-delay:.5s">
            <div>
              <div class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading hero-stat">15+</div>
              <div class="text-[.6rem] sm:text-[.65rem] text-white/40 mt-0.5 uppercase tracking-wider font-nav whitespace-nowrap">Years Experience</div>
            </div>
            <div class="pl-3 sm:pl-8 border-l border-white/[.12]">
              <div class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading hero-stat">1,200+</div>
              <div class="text-[.6rem] sm:text-[.65rem] text-white/40 mt-0.5 uppercase tracking-wider font-nav whitespace-nowrap">Travellers</div>
            </div>
            <div class="pl-3 sm:pl-8 border-l border-white/[.12]">
              <div class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading hero-stat">4.9 ★</div>
              <div class="text-[.6rem] sm:text-[.65rem] text-white/40 mt-0.5 uppercase tracking-wider font-nav whitespace-nowrap">Avg Rating</div>
            </div>
          </div>

        </div>

        <!-- Scroll indicator (mobile only - in normal flow) -->
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

      <!-- spacer so the left text doesn't sit under the absolute image columns -->
      <div class="hidden md:block" aria-hidden="true"></div>

    </div>
  </div>

  <!-- -- Right: full-bleed scrolling vertical image columns (touches right/top/bottom edge) -- -->
  <?php
  /* Build an image pool for the columns from gallery (fallback to hero consts) */
  $heroColImgs = [];
  foreach ($galleryImages as $g) { if (!empty($g['image'])) $heroColImgs[] = ['src'=>$g['image'],'title'=>$g['title'] ?? '']; }
  $heroColImgs[] = ['src'=>IMG_HERO,'title'=>'Tanzania'];
  $heroColImgs[] = ['src'=>IMG_SERENGETI,'title'=>'Serengeti'];
  /* Split into 2 columns, round-robin */
  $cols = [[],[]];
  foreach ($heroColImgs as $i=>$im) { $cols[$i % 2][] = $im; }
  $colDirs = ['up','up'];          // both scroll bottom -> top
  $colDur  = ['35s','42s'];        // slow but clearly visible, slightly different speeds
  ?>
  <div class="hero-cols hidden md:grid" id="hero-cols">
    <?php foreach ($cols as $ci => $col): if (empty($col)) continue; ?>
    <div class="hero-col">
      <div class="hero-col-track <?= $colDirs[$ci]==='down' ? 'is-down' : 'is-up' ?>" style="--dur:<?= $colDur[$ci] ?>;animation-duration:<?= $colDur[$ci] ?>">
        <?php for ($r=0; $r<2; $r++): foreach ($col as $im): ?>
        <div class="hero-col-card">
          <img src="<?= e($im['src']) ?>" alt="<?= e($im['title']) ?>" loading="lazy">
          <?php if (!empty($im['title'])): ?><span class="hero-col-cap"><?= e($im['title']) ?></span><?php endif; ?>
        </div>
        <?php endforeach; endfor; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Slide prev/next (only when multiple slides, desktop only) -->
  <?php if (count($heroSlides) > 1): ?>
  <div class="hidden lg:flex absolute bottom-10 right-6 z-20 gap-2">
    <button id="hero-prev" class="w-10 h-10 rounded-full text-white flex items-center justify-center transition-all"
            style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)" aria-label="Prev slide"><i class="fas fa-chevron-left text-xs"></i></button>
    <button id="hero-next" class="w-10 h-10 rounded-full text-white flex items-center justify-center transition-all"
            style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)" aria-label="Next slide"><i class="fas fa-chevron-right text-xs"></i></button>
  </div>
  <?php endif; ?>

  <!-- Scroll indicator (desktop only - absolute over full-height hero) -->
  <a href="#" onclick="event.preventDefault();window.scrollBy({top:window.innerHeight*0.85,behavior:'smooth'})" class="hidden lg:flex absolute bottom-16 left-1/2 -translate-x-1/2 z-10 flex-col items-center gap-2" aria-label="Scroll down">
    <div class="scrolldown"></div>
    <div class="chevrons">
      <div class="chevrondown"></div>
      <div class="chevrondown"></div>
    </div>
  </a>

  <!-- Curved wave divider - flows into the section below -->
  <div class="absolute bottom-0 inset-x-0 z-[5] pointer-events-none leading-[0]" aria-hidden="true">
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none" class="w-full h-[50px] lg:h-[70px]">
      <path d="M0,40 C360,90 1080,0 1440,40 L1440,80 L0,80 Z" fill="#5c3a21"></path>
    </svg>
  </div>

</section>

<!-- --------------------------------------
     TRUSTED PARTNERS / CERTIFICATIONS
-------------------------------------- -->
<section class="partners-strip relative z-10 py-10">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-7">
      <div class="flex-1 h-px" style="background:linear-gradient(to right,transparent,rgba(255,255,255,.2))"></div>
      <span class="font-nav text-[.6rem] font-700 uppercase tracking-[.22em] text-white/55 flex-shrink-0">
        Certified &amp; Featured On
      </span>
      <div class="flex-1 h-px" style="background:linear-gradient(to left,transparent,rgba(255,255,255,.2))"></div>
    </div>

    <!-- Partner badges - infinite scroll carousel -->
    <?php
    $partners = [
      ['name' => 'SafariBookings',  'short' => 'Safari Bookings', 'icon' => 'fa-binoculars',    'color' => '#f59e0b', 'url' => 'https://www.safaribookings.com/p6419'],
      ['name' => 'TATO',            'short' => 'TATO',            'icon' => 'fa-certificate',   'color' => '#a05e22', 'url' => 'https://www.tatotz.org/'],
      ['name' => 'TourHQ',          'short' => 'TourHQ',          'icon' => 'fa-globe',         'color' => '#3b82f6', 'url' => 'https://www.tourhq.com/'],
      ['name' => 'Trustpilot',      'short' => 'Trustpilot',      'icon' => 'fa-star',          'color' => '#00b67a', 'url' => 'https://www.trustpilot.com/'],
      ['name' => 'KPAP',            'short' => 'KPAP',            'icon' => 'fa-mountain',      'color' => '#8b5cf6', 'url' => 'https://www.kpap.org/'],
      ['name' => 'TripAdvisor',     'short' => 'TripAdvisor',     'icon' => 'fa-map-marked-alt','color' => '#c17a3a', 'url' => 'https://www.tripadvisor.com/'],
      ['name' => 'SafariOptions',   'short' => 'Safari Options',  'icon' => 'fa-paw',           'color' => '#f97316', 'url' => 'https://www.safarioptions.com/'],
      ['name' => 'SafariGo',        'short' => 'SafariGo',        'icon' => 'fa-compass',       'color' => '#a05e22', 'url' => 'https://www.safarigo.com/'],
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
  border-color: rgba(160,94,34,.25) !important;
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

/* About section - subtle dot grid background */
.about-dots{
  background-image:radial-gradient(rgba(160,94,34,.12) 1px,transparent 1px);
  background-size:26px 26px;
  -webkit-mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 80%);
  mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 80%);
}
</style>

<!-- --------------------------------------
     ABOUT
-------------------------------------- -->
<section class="about-section section-light relative py-20 lg:py-28 px-4 lg:px-0 overflow-hidden" id="about">
  <!-- Background decoration -->
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
    <!-- Emerald glow - left -->
    <div class="absolute -left-40 top-10 w-[520px] h-[520px] rounded-full" style="background:radial-gradient(circle,rgba(160,94,34,.18) 0%,transparent 70%);filter:blur(30px)"></div>
    <!-- Emerald glow - right -->
    <div class="absolute -right-40 bottom-0 w-[460px] h-[460px] rounded-full" style="background:radial-gradient(circle,rgba(5,150,105,.14) 0%,transparent 70%);filter:blur(30px)"></div>
    <!-- Subtle dot grid -->
    <div class="absolute inset-0 about-dots opacity-[.5]"></div>
  </div>
  <div class="relative z-10 max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-[5fr_6fr] gap-14 lg:gap-20 items-center">

      <!-- -- Left: Image composition -- -->
      <div class="relative reveal">

        <!-- Main image -->
        <div class="rounded-3xl overflow-hidden shadow-2xl" style="height:520px">
          <img src="<?= IMG_ABOUT ?>" alt="Jambo Masai Tours safari experience"
               loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>

        <!-- Secondary inset image - bottom right -->
        <div class="absolute -bottom-7 -right-3 lg:-right-8 w-40 h-32 lg:w-52 lg:h-40 rounded-2xl overflow-hidden shadow-2xl"
             style="border:3px solid #f4e1c3">
          <img src="<?= IMG_MAASAI ?>" alt="Maasai culture Tanzania"
               loading="lazy" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          <p class="absolute bottom-2 left-3 text-white text-[.6rem] font-nav font-semibold tracking-wider uppercase">Jambo Masai Tours</p>
        </div>

        <!-- Experience badge - top left -->
        <div class="absolute -top-4 -left-3 lg:-left-6 rounded-2xl px-5 py-4 shadow-2xl"
             style="background:linear-gradient(135deg,#5e3611,#a05e22)">
          <div class="text-3xl font-bold font-heading leading-none" style="color:#fff">15+</div>
          <div class="text-[.72rem] mt-1 leading-tight font-nav uppercase tracking-wider" style="color:rgba(255,255,255,.9)">Years of<br>Adventure</div>
        </div>

        <!-- Floating cert card - middle right -->
        <div class="glass-card absolute top-1/2 -translate-y-1/2 -right-3 lg:-right-10 p-4 space-y-2.5 hidden lg:block">
          <?php foreach ([
            ['fa-star',       '#f59e0b', 'Top Rated',     '4.9 · TripAdvisor'],
            ['fa-certificate','#a05e22', 'TATO Certified','Licensed Operator'],
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

      <!-- -- Right: Text content -- -->
      <div class="reveal" style="transition-delay:120ms">

        <!-- Section pill -->
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-5">
          <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
          <span class="text-emerald-400 text-[.62rem] font-bold tracking-[.18em] uppercase font-nav">About Us</span>
        </div>

        <!-- Heading — scroll-driven word reveal -->
        <h2 class="leading-[1.12] mb-2 scroll-reveal" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:clamp(2rem,4vw,3.1rem)">
          Tanzania's Most Trusted Safari Experience
        </h2>

        <!-- Location tag -->
        <p class="text-emerald-500 font-nav text-[.78rem] font-semibold mb-5 tracking-wide">
          <i class="fas fa-map-marker-alt text-xs mr-1"></i> Premier Safari Provider · Heart of Arusha, Tanzania
        </p>

        <!-- Body copy — scroll-driven word reveal -->
        <p class="leading-[1.85] mb-4 text-[1.02rem] scroll-reveal">
          At Jambo Masai Tours, we invite you to explore the wonders of Tanzania — from the majestic heights of Kilimanjaro to the pristine shores of Zanzibar.
        </p>
        <p class="leading-[1.85] mb-7 text-[1.02rem] scroll-reveal">
          We specialise in crafting tailor-made experiences that cater to your every desire — whether you seek the thrill of mountain climbing, the serenity of a beach retreat, or the excitement of encountering Africa's iconic wildlife. Our expert team is here to make your dreams a reality.
        </p>

        <!-- Feature grid 2-3 -->
        <div class="grid grid-cols-2 gap-x-4 gap-y-3 mb-8">
          <?php foreach ([
            ['fa-compass',       '#a05e22', 'Expert Maasai Guides',   'Born & raised in Tanzania'],
            ['fa-mountain',      '#8b5cf6', 'Kilimanjaro Expeditions','All routes & skill levels'],
            ['fa-water',         '#3b82f6', 'Zanzibar Retreats',      'Beach & island packages'],
            ['fa-paw',           '#f59e0b', 'Wildlife Safaris',       'Big Five guaranteed'],
            ['fa-leaf',          '#c17a3a', 'Eco-Responsible',        'Carbon-neutral by 2027'],
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
          <a href="<?= url('about') ?>"
             class="group inline-flex items-center gap-2 font-nav font-semibold text-[.78rem] px-6 py-3 rounded-xl transition-all hover:scale-105 hover:shadow-lg"
             style="background:linear-gradient(135deg,#7d4817,#a05e22);box-shadow:0 4px 18px rgba(160,94,34,.2);color:#fff">
            <i class="fas fa-compass text-xs" style="color:#fff"></i>
            Our Full Story
            <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform" style="color:#fff"></i>
          </a>
          <a href="<?= url('booking') ?>"
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

<!-- --------------------------------------
     SEARCH BAR
-------------------------------------- -->
<section class="relative z-20 -mt-px">
  <div class="max-w-6xl mx-auto px-4 lg:px-6 py-6">
    <form action="<?= url('tours') ?>" method="GET"
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

<!-- --------------------------------------
     FEATURED TOURS
-------------------------------------- -->
<section class="py-20 lg:py-24 px-4 lg:px-0" id="tours">
  <div class="max-w-7xl mx-auto">

    <!-- Section header - centered -->
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
                  style="background:rgba(160,94,34,.1)">
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
            <a href="<?= url('tour/' . e($tour['slug'])) ?>"
               class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110 hover:shadow-lg flex-shrink-0"
               style="background:linear-gradient(135deg,#7d4817,#a05e22);box-shadow:0 3px 12px rgba(160,94,34,.3)"
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
      <a href="<?= url('tours') ?>"
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
.dest-bento-card:hover .dest-bento-arrow{background:linear-gradient(135deg,#7d4817,#a05e22);border-color:transparent;transform:rotate(-30deg)}
</style>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- --------------------------------------
     POPULAR DESTINATIONS
-------------------------------------- -->
<section class="py-20 lg:py-24 px-4 lg:px-0" id="destinations">
  <div class="max-w-7xl mx-auto">

    <!-- Header - centered -->
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
        <a href="<?= url('destinations') ?>"
           class="inline-flex items-center gap-2 font-nav font-semibold text-[.75rem] text-white/45 hover:text-emerald-400 transition-colors group">
          View all destinations
          <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform text-emerald-400"></i>
        </a>
      </div>
    </div>

    <!-- Bento grid - DB driven -->
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
      <a href="<?= url('destinations?d=' . e($slug)) ?>"
         class="dest-bento-card <?= $isLarge ? 'dest-bento-large' : '' ?>">

        <img src="<?= e($img) ?>" alt="<?= e($dest['title']) ?>" loading="<?= $idx < 2 ? 'eager' : 'lazy' ?>">

        <div class="dest-bento-overlay"></div>

        <!-- Number - top left -->
        <div class="absolute top-4 left-4 z-10">
          <span class="font-nav font-bold text-[.58rem] tracking-[.2em] text-white/30"><?= $num ?></span>
        </div>

        <!-- Region - top right, on hover -->
        <div class="absolute top-4 right-4 z-10 dest-bento-extra">
          <span class="font-nav text-[.6rem] text-white/75 px-2.5 py-1 rounded-full"
                style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px)">
            <?= e($region) ?>
          </span>
        </div>

        <!-- Bottom info -->
        <div class="absolute bottom-0 left-0 right-0 z-10 p-5">

          <!-- Tag + season - on hover -->
          <div class="dest-bento-extra flex flex-wrap items-center gap-2 mb-2.5">
            <?php if ($tag): ?>
            <span class="font-nav text-[.6rem] text-emerald-400 font-semibold px-2 py-0.5 rounded-full"
                  style="background:rgba(160,94,34,.12);border:1px solid rgba(160,94,34,.2)">
              <?= e($tag) ?>
            </span>
            <?php endif; ?>
            <span class="font-nav text-[.6rem] text-white/40 flex items-center gap-1">
              <i class="fas fa-sun text-amber-400 text-[.55rem]"></i><?= e($season) ?>
            </span>
          </div>

          <!-- Name + count + arrow - always visible -->
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

<!-- --------------------------------------
     STATS
-------------------------------------- -->
<section class="py-16 px-4 lg:px-0" id="stats" style="background:linear-gradient(135deg,#1c2c26,#2c463d)">
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

<!-- --------------------------------------
     WHY CHOOSE US
-------------------------------------- -->
<section class="section-light py-20 px-4 lg:px-0" id="why-us">
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

<!-- --------------------------------------
     GALLERY
-------------------------------------- -->
<section class="section-light py-20 px-4 lg:px-6" id="gallery">
  <div class="max-w-7xl mx-auto">
    <!-- Header row: title left, controls right -->
    <div class="flex items-end justify-between gap-4 mb-10 reveal">
      <div>
        <span class="section-tag">Gallery</span>
        <h2 class="font-heading text-4xl lg:text-5xl text-white mt-2">Wildlife &amp; <em class="gradient-text not-italic">Wonders</em></h2>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <button id="gallery-prev" class="w-11 h-11 rounded-xl flex items-center justify-center text-white transition-all" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)" aria-label="Previous"><i class="fas fa-arrow-left text-sm"></i></button>
        <button id="gallery-next" class="w-11 h-11 rounded-xl flex items-center justify-center text-white transition-all" style="background:linear-gradient(135deg,#a05e22,#7d4817)" aria-label="Next"><i class="fas fa-arrow-right text-sm"></i></button>
      </div>
    </div>

    <!-- Auto-sliding carousel (clipped to the container on both sides) -->
    <div class="gallery-carousel reveal" id="gallery-carousel">
      <div class="gallery-track" id="gallery-track">
        <?php foreach ($galleryImages as $i => $g):
          $isFeatured = ($i % 4 === 0);   // every 4th card is the wide "featured" one
          $cls = $isFeatured ? 'gallery-item is-featured' : 'gallery-item';
        ?>
        <div class="<?= $cls ?>"
             onclick="openLightbox('<?= addslashes(e($g['image'])) ?>','<?= addslashes(e($g['title'])) ?>')">
          <img src="<?= e($g['image']) ?>" alt="<?= e($g['title']) ?>" loading="lazy" width="600" height="400">
          <div class="gallery-item-overlay">
            <span class="ico"><i class="fas fa-search-plus"></i></span>
            <span class="font-nav text-white text-[.78rem] font-600"><?= e($g['title']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="gallery-progress reveal" id="gallery-progress"><span id="gallery-progress-bar"></span></div>

    <div class="text-center mt-12 reveal">
      <a href="<?= url('gallery') ?>" class="btn-outline">View Full Gallery</a>
    </div>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox">
  <button id="lightbox-close" onclick="closeLightbox()" aria-label="Close"><i class="fas fa-times"></i></button>
  <img id="lightbox-img" src="" alt="">
</div>

<div class="section-divider max-w-7xl mx-auto px-4"></div>

<!-- --------------------------------------
     TESTIMONIALS
-------------------------------------- -->
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
                <div class="stars text-xs mt-0.5"><?= str_repeat('?',(int)$t['rating']) ?></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="flex items-center justify-center gap-4 mt-8">
        <button id="testi-prev" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center transition hover:bg-brand/20 hover:border-brand" aria-label="Prev"><i class="fas fa-chevron-left text-xs"></i></button>
        <div id="testi-dots" class="flex items-center gap-2"></div>
        <button id="testi-next" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center transition hover:bg-brand/20 hover:border-brand" aria-label="Next"><i class="fas fa-chevron-right text-xs"></i></button>
      </div>
    </div>
  </div>
</section>

<!-- --------------------------------------
     BLOG
-------------------------------------- -->
<?php if (!empty($blogPosts)): ?>
<section class="section-light py-20 px-4 lg:px-0" id="blog">
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
          <a href="<?= url('blog/' . e($post['slug'])) ?>"
             class="text-brand font-nav text-[.72rem] font-600 uppercase tracking-wider hover:text-brand/80 transition-colors">
            Read More ?
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-10 reveal">
      <a href="<?= url('blog') ?>" class="btn-outline">All Travel Articles</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- --------------------------------------
     CTA BANNER
-------------------------------------- -->
<section class="py-20 px-4 lg:px-0 relative overflow-hidden"
         style="background:linear-gradient(135deg,rgba(160,94,34,.18) 0%,rgba(20,33,28,0) 60%),linear-gradient(to bottom right,#1c2c26,#2c463d)">
  <div class="max-w-3xl mx-auto text-center relative z-10 reveal">
    <span class="section-tag">Ready to Go?</span>
    <h2 class="font-heading text-4xl lg:text-5xl text-white mt-3 leading-snug">
      Your <em class="gradient-text not-italic">Dream Safari</em> Awaits
    </h2>
    <p class="text-white/60 mt-5 text-lg leading-relaxed">
      Contact us today and let our experts design the perfect African adventure just for you.
    </p>
    <div class="flex flex-wrap gap-3 justify-center mt-10">
      <a href="<?= url('booking') ?>" class="btn-gold btn-lg">
        <i class="fas fa-calendar-check text-sm"></i> Start Booking
      </a>
      <a href="<?= url('contact') ?>" class="btn-outline">
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

<!-- --------------------------------------
     MEGA FOOTER
-------------------------------------- -->
<style>
  .ftr-link{transition:all .25s;display:flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-size:.8rem;color:rgba(255,255,255,.45);text-decoration:none}
  .ftr-link:hover{color:#c17a3a;transform:translateX(4px)}
  .ftr-social{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;transition:all .3s;text-decoration:none;flex-shrink:0}
  .ftr-social:hover{transform:translateY(-3px) scale(1.08)}
  .trust-badge{display:inline-flex;align-items:center;gap:.55rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:12px;padding:.55rem .8rem;transition:all .3s;cursor:default}
  .trust-badge:hover{border-color:rgba(160,94,34,.4);background:rgba(160,94,34,.08);transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,0,0,.25)}
  .pay-icon{background:rgba(255,255,255,.92);border:1px solid rgba(255,255,255,.12);border-radius:9px;padding:.4rem .65rem;display:flex;align-items:center;justify-content:center;transition:all .25s;min-width:46px}
  .pay-icon:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 8px 20px rgba(0,0,0,.3)}
  .safari-mini{position:relative;border-radius:16px;overflow:hidden;height:130px;border:1px solid rgba(255,255,255,.06);transition:all .35s;text-decoration:none;display:block}
  .safari-mini:hover{transform:translateY(-4px);border-color:rgba(160,94,34,.2);box-shadow:0 12px 32px rgba(0,0,0,.5)}
  .safari-mini img{width:100%;height:100%;object-fit:cover;opacity:.5;transition:transform .6s,opacity .3s}
  .safari-mini:hover img{transform:scale(1.08);opacity:.65}
  .nl-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:12px;padding:.8rem 1rem .8rem 2.75rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;box-sizing:border-box}
  .nl-input:focus{border-color:rgba(160,94,34,.5);box-shadow:0 0 0 3px rgba(160,94,34,.1)}
  .nl-input::placeholder{color:rgba(255,255,255,.25)}
  .footer-glow{position:absolute;top:-80px;left:50%;transform:translateX(-50%);width:600px;height:160px;background:radial-gradient(ellipse,rgba(160,94,34,.07) 0%,transparent 70%);pointer-events:none}
  /* Footer bottom bar */
  .ftr-bottom-inner{width:100%;max-width:1280px;margin:0 auto;padding:.95rem 2rem;box-sizing:border-box;display:flex;align-items:center;justify-content:space-between;gap:1rem}
  .ftr-legal{display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;order:2}
  .ftr-legal a{font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.3);text-decoration:none;transition:color .2s;white-space:nowrap}
  .ftr-legal a:hover{color:#c17a3a}
  .ftr-copy{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.3);order:1}
  .ftr-dot{color:rgba(255,255,255,.15)}
  .ftr-totop{display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.35);background:rgba(160,94,34,.1);border:1px solid rgba(160,94,34,.2);border-radius:999px;padding:.45rem 1rem;cursor:pointer;transition:all .25s;order:3;white-space:nowrap}
  .ftr-totop:hover{color:#fff;background:rgba(160,94,34,.25);border-color:rgba(160,94,34,.4)}
  @media(max-width:767px){
    .ftr-bottom-inner{flex-direction:column;text-align:center;gap:1.1rem;padding:1.4rem 1.25rem}
    .ftr-legal{justify-content:center;gap:.5rem 1.1rem;order:1}
    .ftr-copy{flex-direction:column;gap:.35rem;order:2}
    .ftr-copy .ftr-dot{display:none}
    .ftr-totop{order:3;width:100%;max-width:240px;justify-content:center}
  }
</style>

<footer id="site-footer" class="relative border-t border-white/[.06] pt-20 pb-0 overflow-hidden">
  <div class="footer-glow"></div>

  <!-- -- Newsletter CTA -- -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:3.5rem">
    <div class="relative overflow-hidden" style="background:rgba(18,18,18,.8);backdrop-filter:blur(20px);border-top:1px solid rgba(160,94,34,.1);border-bottom:1px solid rgba(255,255,255,.06)">
      <div class="absolute inset-0 pointer-events-none" style="background:linear-gradient(to right,rgba(160,94,34,.12),transparent 50%,rgba(201,168,76,.06))"></div>
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
            <button type="submit" style="background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;padding:.8rem 1.6rem;border-radius:12px;border:none;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:.5rem;transition:all .25s;box-shadow:0 4px 16px rgba(160,94,34,.3)" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform=''">
              <i class="fas fa-paper-plane" style="font-size:.72rem"></i> Subscribe
            </button>
          </form>
          <p style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.2);margin-top:.5rem;text-align:center">Join 5,000+ adventurers. Unsubscribe anytime.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- -- Main Grid -- -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:3rem">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-8 lg:gap-5">

      <!-- Brand (4 cols) -->
      <div class="col-span-2 md:col-span-3 lg:col-span-4">
        <a href="<?= url() ?>" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;margin-bottom:1.25rem">
          <img src="<?= SITE_URL ?>/uploads/logo-husika.png" alt="<?= e($siteName) ?>"
               style="height:48px;width:auto;max-width:160px;object-fit:contain;display:block">
          <?php if (false): ?>
            <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#a05e22,#7d4817);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas fa-tree" style="color:#fff;font-size:1rem"></i>
            </div>
            <div>
              <div style="font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:1.15rem;line-height:1.2">
                <?= e(explode(' ',$siteName)[0]??'Jambo') ?>
                <span style="background:linear-gradient(135deg,#c17a3a,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"> <?= e(explode(' ',$siteName)[1]??'Masai') ?></span>
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
            ['fab fa-tiktok',getSetting('social_tiktok','#'),'rgba(160,94,34,.12)','#c17a3a'],
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
            <?php for($i=0;$i<5;$i++): ?><i class="fas fa-circle" style="color:#c17a3a;font-size:.5rem"></i><?php endfor; ?>
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
          <span style="width:3px;height:14px;background:#a05e22;border-radius:2px;display:inline-block"></span> Safaris
        </h4>
        <ul style="space-y:0">
          <?php foreach ([
            ['Great Migration Safari', 'migration'],
            ['Ngorongoro Crater',   'tours.php?destination=Ngorongoro'],
            ['Zanzibar & Beach',    'tours.php?tour_type=Beach+Holiday'],
            ['Wildlife Big Five',   'tours.php?tour_type=Wildlife+Safari'],
            ['Cultural Maasai',     'tours.php?tour_type=Cultural+Tour'],
            ['Family Safari',       'tours.php?tour_type=Family+Safari'],
          ] as $l): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url($l[1]) ?>" class="ftr-link">
              <i class="fas fa-chevron-right" style="font-size:.45rem;color:rgba(160,94,34,.4);flex-shrink:0"></i><?= $l[0] ?>
            </a>
          </li>
          <?php endforeach; ?>
          <li style="margin-top:.85rem">
            <a href="<?= url('tours') ?>" style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:700;color:#a05e22;text-decoration:none;display:flex;align-items:center;gap:.4rem">View All Safaris <i class="fas fa-arrow-right" style="font-size:.55rem"></i></a>
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
            ['fa-paw',             'Serengeti',        'serengeti'],
            ['fa-mountain-sun',    'Ngorongoro',       'ngorongoro'],
            ['fa-mountain',        'Kilimanjaro',      'kilimanjaro'],
            ['fa-umbrella-beach',  'Zanzibar',         'zanzibar'],
            ['fa-tree',            'Tarangire',        'tarangire'],
            ['fa-person-rays',     'Maasai Heartland', 'maasai'],
          ] as $d): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url('destinations?d='.$d[2]) ?>" class="ftr-link"><i class="fas <?= $d[0] ?>" style="color:#a05e22;font-size:.7rem;width:1rem;margin-right:.35rem"></i><?= $d[1] ?></a>
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
            ['fa-route',          'Machame Route',  'mountain-trekking.php'],
            ['fa-route',          'Marangu Route',  'mountain-trekking.php'],
            ['fa-route',          'Lemosho Route',  'mountain-trekking.php'],
            ['fa-route',          'Rongai Route',   'mountain-trekking.php'],
            ['fa-mountain',       'Mt. Meru',       'mountain-trekking.php'],
            ['fa-clipboard-list', 'Gear Checklist', 'mountain-trekking.php#gear'],
          ] as $t): ?>
          <li style="margin-bottom:.65rem">
            <a href="<?= url($t[2]) ?>" class="ftr-link"><i class="fas <?= $t[0] ?>" style="color:#a05e22;font-size:.7rem;width:1rem;margin-right:.35rem"></i><?= $t[1] ?></a>
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
            ['fas fa-map-marker-alt','#a05e22','<a href="https://maps.app.goo.gl/cqcERfdGpABg9xo49" target="_blank" rel="noopener" style="color:rgba(255,255,255,.42);text-decoration:none">Arusha, Tanzania 12105</a>'],
            ['fas fa-phone',         '#a05e22','<a href="tel:'.e(SITE_PHONE).'" style="color:rgba(255,255,255,.42);text-decoration:none">'.e(SITE_PHONE).'</a>'],
            ['fas fa-envelope',      '#a05e22','<a href="mailto:'.e(SITE_EMAIL).'" style="color:rgba(255,255,255,.42);text-decoration:none">'.e(SITE_EMAIL).'</a>'],
            ['fab fa-whatsapp',      '#25D366','<a href="https://wa.me/'.e(WHATSAPP_NUMBER).'" target="_blank" rel="noopener" style="color:#25D366;font-weight:600;text-decoration:none">Chat on WhatsApp</a>'],
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
            <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#a05e22;margin-bottom:.3rem">Office Hours</p>
            <p style="font-family:'Inter',sans-serif;font-size:.72rem;color:rgba(255,255,255,.38)">Mon–Fri: 8am – 6pm EAT</p>
            <p style="font-family:'Inter',sans-serif;font-size:.72rem;color:rgba(255,255,255,.38)">Sat–Sun: 9am – 4pm EAT</p>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- -- Safari Mini Cards (Featured from DB) -- -->
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
        $mlink = !empty($mt['slug']) ? url('tour/'.e($mt['slug'])) : url('tours');
      ?>
      <a href="<?= $mlink ?>" class="safari-mini">
        <img src="<?= e($mt['image']) ?>" alt="<?= e($mt['name']) ?>" loading="lazy">
        <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.88),transparent 55%)"></div>
        <div class="absolute bottom-0 left-0 right-0 p-3.5">
          <div style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:700;color:#fff;line-height:1.3"><?= e($mt['name']) ?></div>
          <div style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:#a05e22;margin-top:.15rem">From $<?= number_format((float)$mt['price']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- -- Trust & Payments -- -->
  <div style="width:100%;padding:0 2rem 0;margin-bottom:2.5rem">
    <div style="background:linear-gradient(180deg,rgba(28,44,38,.6),rgba(20,33,28,.6));border-top:1px solid rgba(160,94,34,.12);border-bottom:1px solid rgba(160,94,34,.12);border-radius:16px;padding:1.5rem;margin-top:1rem">
      <div class="grid md:grid-cols-3 gap-6 items-center">
        <!-- Certifications -->
        <div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25);margin-bottom:.65rem">Trusted & Certified</p>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem">
            <?php foreach ([
              ['fa-shield-alt','#a05e22','TATO Member',   'Tanzania Association'],
              ['fa-certificate','#f59e0b','KINAPA Licensed','National Parks Auth.'],
              ['fa-leaf',       '#c17a3a','Eco-Certified', 'Sustainable Tourism'],
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
              ['fab fa-cc-visa',       '#1a1f71'],
              ['fab fa-cc-mastercard', '#eb001b'],
              ['fab fa-cc-amex',       '#2e77bc'],
              ['fab fa-cc-paypal',     '#003087'],
              ['fab fa-cc-stripe',     '#635bff'],
            ] as $p): ?>
            <div class="pay-icon"><i class="<?= $p[0] ?>" style="font-size:1.3rem;color:<?= $p[1] ?>"></i></div>
            <?php endforeach; ?>
            <div class="pay-icon" style="background:#a05e22;border-color:#a05e22">
              <i class="fas fa-mobile-alt" style="color:#fff;font-size:.78rem;margin-right:.25rem"></i>
              <span style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:#fff">M-Pesa</span>
            </div>
          </div>
        </div>
        <!-- Security -->
        <div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25);margin-bottom:.65rem">Secure Booking</p>
          <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <?php foreach ([
              ['fa-lock',   '#a05e22','SSL Encrypted', '256-bit security'],
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

  <!-- -- Bottom bar -- -->
  <div class="ftr-bottom" style="border-top:1px solid rgba(160,94,34,.15);background:linear-gradient(180deg,rgba(20,33,28,.5),rgba(28,44,38,.8))">
    <div class="ftr-bottom-inner">
      <!-- Links -->
      <div class="ftr-legal">
        <?php foreach (['Privacy Policy','Terms of Service','Cookie Policy','Sitemap'] as $l): ?>
        <a href="#"><?= $l ?></a>
        <?php endforeach; ?>
      </div>
      <!-- Copyright -->
      <div class="ftr-copy">
        <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
        <span class="ftr-dot">·</span>
        <span>Made with <i class="fas fa-heart" style="color:rgba(239,68,68,.55);font-size:.6rem"></i> in Tanzania</span>
        <span class="ftr-dot">·</span>
        <span>Built by <span style="color:#c17a3a">hiddenxcel</span></span>
      </div>
      <!-- Back to top -->
      <button class="ftr-totop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        Back to top <i class="fas fa-arrow-up" style="font-size:.55rem"></i>
      </button>
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

<?php require_once 'includes/chatbot-widget.php'; ?>

<!-- AOS -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
(function(){
  'use strict';

  window.SITE_URL = '<?= SITE_URL ?>';

  /* --- AOS --- */
  AOS.init({ duration: 700, once: true, offset: 60 });

  /* --- Navbar --- */
  const navEl = document.getElementById('main-nav');
  window.addEventListener('scroll', () => {
    if (!navEl) return;
    navEl.style.background    = scrollY > 60 ? 'rgba(10,10,10,.96)' : '';
    navEl.style.backdropFilter = scrollY > 60 ? 'blur(20px)' : '';
    navEl.style.borderBottom  = scrollY > 60 ? '1px solid rgba(160,94,34,.1)' : '';
    navEl.style.boxShadow     = scrollY > 60 ? '0 4px 24px rgba(0,0,0,.4)' : '';
    const sp = document.getElementById('scroll-prog-home');
    if (sp) { const mx = document.documentElement.scrollHeight - innerHeight; if(mx>0) sp.style.width=(scrollY/mx*100).toFixed(2)+'%'; }
  }, { passive: true });

  /* --- Mobile drawer (new) --- */
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

  /* --- Search overlay --- */
  const sOv=document.getElementById('idx-search-ov'), sWr=document.getElementById('idx-swrap');
  function idxOpenSearch(){sOv.style.opacity='1';sOv.style.pointerEvents='all';if(sWr)sWr.style.transform='translateY(0)';document.body.style.overflow='hidden';setTimeout(()=>document.getElementById('idx-sinput')?.focus(),250);}
  function idxCloseSearch(){sOv.style.opacity='0';sOv.style.pointerEvents='none';if(sWr)sWr.style.transform='translateY(-16px)';document.body.style.overflow='';}
  function idxDoSearch(){const q=document.getElementById('idx-sinput')?.value?.trim();if(q)window.location=SITE_URL+'/tours?search='+encodeURIComponent(q);}
  window.idxOpenSearch=idxOpenSearch; window.idxCloseSearch=idxCloseSearch; window.idxDoSearch=idxDoSearch;

  /* --- Scroll progress --- */
  const prog = document.getElementById('scroll-progress');
  window.addEventListener('scroll', () => {
    const max = document.documentElement.scrollHeight - innerHeight;
    if (prog) prog.style.width = (scrollY / max * 100).toFixed(2) + '%';
  }, { passive: true });

  /* --- Navbar scroll --- */
  const nav = document.getElementById('main-nav');
  const updateNav = () => nav && nav.classList.toggle('scrolled', scrollY > 60);
  window.addEventListener('scroll', updateNav, { passive: true });
  updateNav();

  /* --- Mobile menu --- */
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

  /* --- Hero slider --- */
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

  /* --- Scroll reveal --- */
  const revealEls = document.querySelectorAll('.reveal');
  const revObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revObs.unobserve(e.target); } });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  revealEls.forEach(el => revObs.observe(el));

  /* --- Stats counter --- */
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

  /* --- Testimonials slider --- */
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

  /* --- Gallery lightbox --- */
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

  /* --- Gallery carousel - step one card every 4s, infinite, progress bar, prev/next --- */
  (function(){
    const track    = document.getElementById('gallery-track');
    const carousel = document.getElementById('gallery-carousel');
    const bar      = document.getElementById('gallery-progress-bar');
    const prevBtn  = document.getElementById('gallery-prev');
    const nextBtn  = document.getElementById('gallery-next');
    if (!track || track.children.length === 0) return;

    const GAP = 16, STEP_MS = 4000, ANIM_MS = 900;
    let timer = null, animating = false, paused = false;

    /* progress bar: reset to 0 then run to 100% over STEP_MS (all inline so nothing overrides it) */
    const runBar = () => {
      if (!bar) return;
      bar.style.transition = 'none';
      bar.style.width = '0%';
      void bar.offsetWidth;                 // reflow so the reset isn't animated
      bar.style.transition = `width ${STEP_MS}ms linear`;
      bar.style.width = '100%';
    };
    const pauseBar = () => {
      if (!bar) return;
      const w = getComputedStyle(bar).width; // freeze current width in px
      bar.style.transition = 'none';
      bar.style.width = w;
    };

    const slide = (dir = 1) => {
      if (animating) return;
      animating = true;
      if (dir > 0) {
        const first = track.children[0];
        const shift = first.getBoundingClientRect().width + GAP;
        track.style.transition = `transform ${ANIM_MS}ms cubic-bezier(.65,0,.35,1)`;
        track.style.transform  = `translateX(-${shift}px)`;
        const done = () => {
          track.style.transition = 'none';
          track.style.transform  = 'translateX(0)';
          track.appendChild(first);         // recycle first ? end (infinite)
          track.removeEventListener('transitionend', done);
          void track.offsetWidth;
          animating = false;
        };
        track.addEventListener('transitionend', done);
      } else {
        const last = track.children[track.children.length - 1];
        track.insertBefore(last, track.children[0]);   // bring last to front
        const shift = last.getBoundingClientRect().width + GAP;
        track.style.transition = 'none';
        track.style.transform  = `translateX(-${shift}px)`;
        void track.offsetWidth;
        track.style.transition = `transform ${ANIM_MS}ms cubic-bezier(.65,0,.35,1)`;
        track.style.transform  = 'translateX(0)';
        const done = () => { track.removeEventListener('transitionend', done); animating = false; };
        track.addEventListener('transitionend', done);
      }
    };

    const tick  = () => { slide(1); runBar(); };
    const start = () => { if (timer || paused) return; runBar(); timer = setInterval(tick, STEP_MS); };
    const stop  = () => { clearInterval(timer); timer = null; pauseBar(); };

    /* manual controls reset the timer */
    const manual = (dir) => { stop(); slide(dir); if (!paused) { runBar(); timer = setInterval(tick, STEP_MS); } };
    nextBtn && nextBtn.addEventListener('click', () => manual(1));
    prevBtn && prevBtn.addEventListener('click', () => manual(-1));

    carousel.addEventListener('mouseenter', () => { paused = true; stop(); });
    carousel.addEventListener('mouseleave', () => { paused = false; start(); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else if (!paused) start(); });

    if (track.scrollWidth > carousel.clientWidth) start();
  })();

  /* --- Back to top --- */
  const btt = document.getElementById('back-top');
  window.addEventListener('scroll', () => btt && btt.classList.toggle('visible', scrollY > 400), { passive: true });
  btt && btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* --- WhatsApp float --- */
  const waFloat = document.getElementById('wa-float');
  if (waFloat) setTimeout(() => waFloat.classList.add('visible'), 3000);

  /* --- Lazy images --- */
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

  /* --- Scroll-driven word reveal (Snippe-style) --- */
  (function(){
    const els = Array.from(document.querySelectorAll('.scroll-reveal'));
    if (!els.length) return;

    /* Split each element's text into word spans (once) */
    const items = els.map(el => {
      const text = el.textContent.trim().replace(/\s+/g, ' ');
      el.textContent = '';
      const words = [];
      text.split(' ').forEach((word, i, arr) => {
        const span = document.createElement('span');
        span.className = 'w';
        span.textContent = word;
        el.appendChild(span);
        words.push(span);
        if (i < arr.length - 1) {
          const sp = document.createElement('span');
          sp.className = 'sp';
          el.appendChild(sp);
        }
      });
      return { el, words };
    });

    let ticking = false;
    function update(){
      ticking = false;
      const vh = window.innerHeight;
      items.forEach(({ el, words }) => {
        const r = el.getBoundingClientRect();
        /* progress: 0 when the element's top hits 85% of viewport,
           1 when it reaches 35% of viewport (reveal finishes a bit before center) */
        const start = vh * 0.85, end = vh * 0.35;
        let p = (start - r.top) / (start - end);
        p = Math.max(0, Math.min(1, p));
        const reveal = Math.round(p * words.length);
        for (let i = 0; i < words.length; i++) {
          words[i].classList.toggle('on', i < reveal);
        }
      });
    }
    function onScroll(){ if (!ticking){ ticking = true; requestAnimationFrame(update); } }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();
  })();

})();

/* --- Lenis Smooth Scroll --- */
</script>
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

  /* Scroll-driven word highlight — runs every RAF frame */
  (function(){
    var el=document.querySelector('.scroll-word-reveal');
    if(!el)return;
    var words=el.querySelectorAll('span'), total=words.length;
    var prev=-1;
    function update(){
      var r=el.getBoundingClientRect(), vh=window.innerHeight;
      if(r.top>vh){if(prev!=-1){prev=-1;for(var i=0;i<total;i++)words[i].classList.remove('active')}return}
      if(r.bottom<0){if(prev!==total){prev=total;for(var i=0;i<total;i++)words[i].classList.add('active')}return}
      var p=Math.max(0,Math.min(1,(vh-r.top)/(vh+r.height)));
      var idx=Math.min(total-1,Math.floor(p*total));
      if(idx===prev)return;prev=idx;
      for(var i=0;i<total;i++)words[i].classList.toggle('active',i<=idx);
    }
    window._wordUpdate=update;
  })();

  function raf(t){ lenis.raf(t); window._wordUpdate&&window._wordUpdate(); requestAnimationFrame(raf); }
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








