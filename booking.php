<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';
require_once 'includes/mailer.php';

$errors   = [];
$success  = false;
$formData = [];

$db       = getDB();
$allTours = $db->query("SELECT id, name, slug, price, duration, destination FROM tours ORDER BY featured DESC, name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid security token. Please refresh and try again.');
    }
    $formData = [
        'tour_id'          => sanitizeInt($_POST['tour_id']            ?? 0, 1, 9999),
        'first_name'       => sanitizeInput($_POST['first_name']       ?? ''),
        'last_name'        => sanitizeInput($_POST['last_name']        ?? ''),
        'email'            => sanitizeEmail($_POST['email']            ?? ''),
        'phone'            => sanitizeInput($_POST['phone']            ?? ''),
        'country'          => ($_POST['country'] ?? '') === '__other__'
                           ? sanitizeInput($_POST['country_other'] ?? '')
                           : sanitizeInput($_POST['country'] ?? ''),
        'travel_date'      => sanitizeInput($_POST['travel_date']      ?? ''),
        'travelers'        => sanitizeInt($_POST['travelers']          ?? 1, 1, 50),
        'special_requests' => sanitizeInput($_POST['special_requests'] ?? ''),
    ];
    if (empty($formData['first_name'])) $errors[] = 'First name is required.';
    if (empty($formData['last_name']))  $errors[] = 'Last name is required.';
    if (!$formData['email'])            $errors[] = 'A valid email address is required.';
    if (empty($formData['phone']))      $errors[] = 'Phone number is required.';
    if (empty($formData['country']))    $errors[] = 'Country is required.';
    if (!$formData['tour_id'])          $errors[] = 'Please select a tour.';
    if (!$formData['travelers'])        $errors[] = 'Number of travellers must be between 1 and 50.';
    if (empty($formData['travel_date']) || !validateDate($formData['travel_date'])) {
        $errors[] = 'Please select a valid travel date.';
    } else {
        $td = new DateTime($formData['travel_date']);
        if ($td < new DateTime('+14 days')) $errors[] = 'Travel date must be at least 14 days from today.';
    }
    if ($formData['tour_id']) {
        $chk = $db->prepare("SELECT id FROM tours WHERE id = ? LIMIT 1");
        $chk->execute([$formData['tour_id']]);
        if (!$chk->fetch()) $errors[] = 'Selected tour does not exist.';
    }
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO bookings (tour_id,first_name,last_name,email,phone,country,travel_date,travelers,special_requests,status,created_at) VALUES (:tour_id,:first_name,:last_name,:email,:phone,:country,:travel_date,:travelers,:special_requests,'pending',NOW())");
        $stmt->execute($formData);
        $newBookingId = $db->lastInsertId();
        unset($_SESSION[CSRF_TOKEN_NAME]);

        /* Send email notifications */
        if (getSetting('notify_on_booking', '1') === '1') {
            /* Get tour name for emails */
            $tn = $db->prepare("SELECT name FROM tours WHERE id=? LIMIT 1");
            $tn->execute([$formData['tour_id']]);
            $tourRow  = $tn->fetch();
            $emailData = array_merge($formData, ['tour_name' => $tourRow['name'] ?? 'Safari Tour']);
            /* Notify admin */
            emailBookingAdmin($emailData);
            /* Confirm to customer */
            if (getSetting('notify_customer', '1') === '1') {
                emailBookingCustomer($emailData);
            }
        }

        setFlashMessage('success','Your booking request has been received! We\'ll contact you within 24 hours.');
        redirect(url('booking?success=1'));
    }
}

$csrfToken       = generateCsrfToken();
$preselectedSlug = sanitizeInput($_GET['tour'] ?? '');
$logoUrl         = getSetting('logo_url');
$logoWidth       = (int)(getSetting('logo_width','160') ?: 160);
$siteName        = getSetting('site_name','Jambo Masai Tours');
$siteTagline     = getSetting('site_tagline','Tanzania Safari Experts');
$currentPage     = 'tours';

$navItems = [
    'home'         => ['url' => url(),                   'label' => 'Home'],
    'tours'        => ['url' => url('tours'),        'label' => 'Safari Tours'],
    'destinations' => ['url' => url('destinations'), 'label' => 'Destinations'],
    'about'        => ['url' => url('about'),        'label' => 'About Us'],
    'gallery'      => ['url' => url('gallery'),      'label' => 'Gallery'],
    'blog'         => ['url' => url('blog'),         'label' => 'Blog'],
    'contact'      => ['url' => url('contact'),      'label' => 'Contact'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book a Safari | <?= e($siteName) ?></title>
  <meta name="description" content="Book your luxury Tanzania safari. Secure online booking for Serengeti, Ngorongoro, Kilimanjaro and Zanzibar tours.">
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">
  <link rel="manifest" href="<?= e(SITE_URL) ?>/manifest.json">
  <link rel="preconnect" href="https://cdn.tailwindcss.com"><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><link rel="dns-prefetch" href="https://images.unsplash.com"><script src="https://cdn.tailwindcss.com" fetchpriority="low"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#a05e22',safari:'#a05e22',dark:'#23362f'},fontFamily:{heading:['Nanum Myeongjo','Georgia','serif'],sans:['Inter','Poppins','sans-serif'],nav:['Montserrat','sans-serif']}}}}</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#23362f;color:#e5e7eb;font-family:'Inter',sans-serif;overflow-x:hidden}
    ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:#2c463d}::-webkit-scrollbar-thumb{background:#a05e22;border-radius:2px}
    .glass-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:16px}

    /* Form inputs */
    .f-input,.f-select,.f-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.8rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .25s,-webkit-backdrop-filter .25s;-webkit-appearance:none}
    .f-input:focus,.f-select:focus,.f-textarea:focus{border-color:rgba(160,94,34,.5);background:rgba(160,94,34,.04)}
    .f-textarea{resize:vertical;min-height:110px}
    .f-select option{background:#1a1a1a}
    .f-label{display:block;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.45);margin-bottom:.5rem}
    .f-label span{color:#f87171}

    /* Steps */
    .step-panel{display:none}
    .step-panel.active{display:block}

    /* Step indicator */
    .step-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:700;transition:all .3s;flex-shrink:0}
    .step-dot.done{background:rgba(160,94,34,.2);color:#c17a3a;border:1.5px solid rgba(160,94,34,.4)}
    .step-dot.active{background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;box-shadow:0 4px 14px rgba(160,94,34,.3)}
    .step-dot.pending{background:rgba(255,255,255,.05);color:rgba(255,255,255,.25);border:1.5px solid rgba(255,255,255,.08)}

    /* Glow */
    .glow-orb{position:fixed;pointer-events:none;z-index:0}
    .glow-orb-1{top:68px;left:0;width:500px;height:500px;background:radial-gradient(circle,rgba(160,94,34,0.1) 0%,transparent 70%)}
    .glow-orb-2{bottom:-5%;right:0;width:400px;height:400px;background:radial-gradient(circle,rgba(201,168,76,0.06) 0%,transparent 70%)}

    /* WhatsApp */
    .wa-float{position:fixed;bottom:1.5rem;left:1.5rem;z-index:998;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 8px 30px rgba(37,211,102,.4);opacity:0;transform:scale(.7);transition:all .4s cubic-bezier(.34,1.56,.64,1)}
    .wa-float.visible{opacity:1;transform:scale(1)}
    .wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:waPulse 2s ease-in-out infinite}
    @keyframes waPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.35);opacity:0}}

    @keyframes kenBurns{0%{transform:scale(1)}50%{transform:scale(1.06)}100%{transform:scale(1)}}
    .ken-burns{animation:kenBurns 20s ease-in-out infinite}
  </style>
</head>
<body class="bg-dark">

<div class="glow-orb glow-orb-1" aria-hidden="true"></div>
<div class="glow-orb glow-orb-2" aria-hidden="true"></div>

<?php require_once 'includes/public_navbar.php'; ?>

<!-- BOOKING PAGE — Split layout -->
<style>
  .bk-grid{display:grid;grid-template-columns:360px 1fr;gap:0;min-height:calc(100vh - 68px);margin-top:68px}
  @media(max-width:1023px){.bk-grid{grid-template-columns:1fr;margin-top:68px}}
  .bk-sidebar{background:#111520;border-right:1px solid rgba(255,255,255,.07);padding:2rem 1.75rem;position:sticky;top:68px;max-height:calc(100vh - 68px);overflow-y:auto;align-self:start}
  @media(max-width:1023px){.bk-sidebar{position:static;max-height:none;border-right:none;border-bottom:1px solid rgba(255,255,255,.07)}}
  .bk-form-area{padding:2rem 2.5rem;background:#0f1117}
  @media(max-width:767px){.bk-form-area{padding:1.25rem 1rem}}
  /* Step progress */
  .bk-steps{display:flex;align-items:center;margin-bottom:2rem}
  .bk-step-item{display:flex;flex-direction:column;align-items:center;flex:1;position:relative}
  .bk-step-item:not(:last-child)::after{content:'';position:absolute;top:18px;left:calc(50% + 22px);right:calc(-50% + 22px);height:1.5px;background:rgba(255,255,255,.1)}
  .bk-step-item.done::after,.bk-step-item.active::after{background:rgba(160,94,34,.4)}
  .bk-step-num{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:700;transition:all .3s;z-index:1;border:2px solid transparent}
  .bk-step-num.done{background:rgba(160,94,34,.2);color:#a05e22;border-color:rgba(160,94,34,.4)}
  .bk-step-num.active{background:linear-gradient(135deg,#7d4817,#a05e22);color:#fff;box-shadow:0 4px 16px rgba(160,94,34,.35);border-color:transparent}
  .bk-step-num.pending{background:rgba(255,255,255,.05);color:rgba(255,255,255,.25);border-color:rgba(255,255,255,.08)}
  .bk-step-lbl{font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-top:.4rem;text-align:center}
  /* Panel transitions */
  .bk-panel{display:none;animation:bkFade .35s ease}
  .bk-panel.active{display:block}
  @keyframes bkFade{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
  /* Tour image in sidebar */
  .bk-tour-img{width:100%;height:160px;object-fit:cover;border-radius:14px;margin-bottom:1rem}
  /* Price row */
  .bk-price-row{display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid rgba(255,255,255,.06)}
  .bk-price-row:last-child{border-bottom:none}
  .bk-price-row.total{border-top:1px solid rgba(160,94,34,.2);margin-top:.3rem;padding-top:.75rem}
</style>

<?php if (!empty($_GET['success'])): ?>
<!-- SUCCESS PAGE -->
<div style="min-height:calc(100vh - 68px);margin-top:68px;display:flex;align-items:center;justify-content:center;padding:2rem">
  <div style="max-width:480px;width:100%;background:#1a1d27;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:3rem 2rem;text-align:center">
    <div style="width:72px;height:72px;border-radius:50%;background:rgba(160,94,34,.12);border:2px solid rgba(160,94,34,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
      <i class="fas fa-check" style="font-size:1.8rem;color:#a05e22"></i>
    </div>
    <h1 class="font-heading text-white font-bold mb-3" style="font-size:1.6rem">Booking Request Received!</h1>
    <p class="text-white/55 leading-relaxed mb-2" style="font-size:.92rem">
      Thank you for choosing <strong style="color:rgba(255,255,255,.85)"><?= e($siteName) ?></strong>.
    </p>
    <p class="text-white/45 leading-relaxed mb-7" style="font-size:.88rem">
      Our safari specialists will contact you within <strong style="color:#a05e22">24 hours</strong> to confirm your itinerary and next steps.
    </p>
    <div style="display:flex;flex-direction:column;gap:.65rem">
      <a href="<?= url() ?>" style="display:flex;align-items:center;justify-content:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.82rem;text-white;padding:.9rem;border-radius:12px;text-decoration:none;color:#fff;background:linear-gradient(135deg,#7d4817,#a05e22)">
        <i class="fas fa-home text-xs"></i> Back to Home
      </a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
         style="display:flex;align-items:center;justify-content:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.82rem;padding:.9rem;border-radius:12px;text-decoration:none;color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2)">
        <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
      </a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- SPLIT LAYOUT -->
<div class="bk-grid">

  <!-- == LEFT SIDEBAR — Tour Summary == -->
  <aside class="bk-sidebar">
    <!-- Tour image -->
    <div id="bk-tour-img-wrap" style="margin-bottom:1rem">
      <img src="<?= IMG_SERENGETI ?>" alt="" id="bk-tour-img" class="bk-tour-img">
    </div>
    <!-- Tour name -->
    <div id="bk-tour-name-wrap" style="margin-bottom:1.25rem">
      <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.3);margin-bottom:.3rem">Selected Safari</p>
      <p id="bk-tour-name" style="font-family:'Nanum Myeongjo',serif;font-size:1.05rem;font-weight:700;color:#fff;line-height:1.3">
        <?php
        /* Pre-select if tour slug given */
        $preT = null;
        if ($preselectedSlug) {
            foreach ($allTours as $t) { if ($t['slug']===$preselectedSlug) { $preT=$t; break; } }
        }
        echo $preT ? e($preT['name']) : 'Choose a safari tour';
        ?>
      </p>
      <p id="bk-tour-dest" style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.4);margin-top:.2rem">
        <?= $preT ? e($preT['destination'] . ' - ' . $preT['duration']) : '' ?>
      </p>
    </div>

    <!-- Price breakdown -->
    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:1rem;margin-bottom:1.25rem">
      <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.28);margin-bottom:.65rem">Price Summary</p>
      <div class="bk-price-row">
        <span style="font-size:.8rem;color:rgba(255,255,255,.5);font-family:'Montserrat',sans-serif">Per person</span>
        <span id="sb-price" style="font-size:.88rem;font-weight:600;color:#fff;font-family:'Montserrat',sans-serif">—</span>
      </div>
      <div class="bk-price-row">
        <span style="font-size:.8rem;color:rgba(255,255,255,.5);font-family:'Montserrat',sans-serif">Travellers</span>
        <span id="sb-travelers" style="font-size:.88rem;font-weight:600;color:#fff;font-family:'Montserrat',sans-serif">—</span>
      </div>
      <div class="bk-price-row total">
        <span style="font-size:.82rem;font-weight:700;font-family:'Montserrat',sans-serif;color:rgba(255,255,255,.65)">Estimated Total</span>
        <span id="sb-total" style="font-size:1.25rem;font-weight:700;color:#a05e22;font-family:'Nanum Myeongjo',serif">—</span>
      </div>
      <p style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.2);margin-top:.6rem">No payment now · Final price confirmed within 24hrs</p>
    </div>

    <!-- What's included -->
    <div style="margin-bottom:1.25rem">
      <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.28);margin-bottom:.6rem">What's Included</p>
      <?php foreach (['Expert guides & naturalists','All meals (full board)','Park entrance fees','4WD game vehicle','Airport transfers'] as $inc): ?>
      <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.4rem">
        <i class="fas fa-check-circle" style="color:#a05e22;font-size:.72rem;flex-shrink:0"></i>
        <span style="font-family:'Inter',sans-serif;font-size:.78rem;color:rgba(255,255,255,.5)"><?= $inc ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- WhatsApp -->
    <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= urlencode('Hi! I need help booking a safari.') ?>" target="_blank" rel="noopener"
       style="display:flex;align-items:center;justify-content:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;padding:.8rem;border-radius:12px;text-decoration:none;color:#25D366;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2);transition:all .25s;margin-bottom:1.25rem"
       onmouseover="this.style.background='rgba(37,211,102,.15)'" onmouseout="this.style.background='rgba(37,211,102,.08)'">
      <i class="fab fa-whatsapp text-lg"></i> Need help? Chat with us
    </a>

    <!-- Trust badges -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
      <?php foreach ([
        ['fa-lock',        '#c17a3a','SSL Secure'],
        ['fa-certificate', '#fbbf24','TATO Licensed'],
        ['fa-shield-alt',  '#60a5fa','No Payment Now'],
        ['fa-headset',     '#f97316','24/7 Support'],
      ] as $b): ?>
      <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.6rem;display:flex;align-items:center;gap:.4rem">
        <i class="fas <?= $b[0] ?>" style="color:<?= $b[1] ?>;font-size:.7rem;flex-shrink:0"></i>
        <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:600;color:rgba(255,255,255,.45)"><?= $b[2] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- == RIGHT FORM AREA == -->
  <div class="bk-form-area">

    <!-- Page title -->
    <div style="margin-bottom:1.75rem">
      <h1 style="font-family:'Nanum Myeongjo',serif;font-weight:700;color:#fff;font-size:1.6rem;line-height:1.2">
        Book Your <span style="background:linear-gradient(135deg,#c17a3a,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Safari</span>
      </h1>
      <p style="font-family:'Montserrat',sans-serif;font-size:.78rem;color:rgba(255,255,255,.4);margin-top:.3rem">3 easy steps · No payment required now</p>
    </div>

    <!-- Step progress -->
    <div class="bk-steps">
      <?php foreach (['Trip Details','Your Info','Review & Send'] as $si => $sl): ?>
      <div class="bk-step-item <?= $si === 0 ? 'active' : 'pending' ?>" id="bk-step-<?= $si ?>">
        <div class="bk-step-num <?= $si === 0 ? 'active' : 'pending' ?>" id="step-dot-<?= $si ?>">
          <i class="fas fa-check" id="step-ck-<?= $si ?>" style="display:none;font-size:.7rem"></i>
          <span id="step-n-<?= $si ?>"><?= $si+1 ?></span>
        </div>
        <span class="bk-step-lbl" id="step-label-<?= $si ?>" style="color:<?= $si===0?'#a05e22':'rgba(255,255,255,.25)' ?>"><?= $sl ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:.85rem 1rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.65rem">
      <i class="fas fa-exclamation-circle" style="color:#f87171;font-size:.88rem;margin-top:.1rem;flex-shrink:0"></i>
      <ul style="list-style:none">
        <?php foreach ($errors as $err): ?>
        <li style="font-size:.82rem;color:#f87171">· <?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form id="booking-form" method="POST" action="booking.php" novalidate>
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">

      <!-- = PANEL 1: Trip Details = -->
      <div class="bk-panel active" id="panel-0">
        <div style="margin-bottom:1rem">
          <label class="f-label" for="tour_id">Select Safari Tour <span>*</span></label>
          <select class="f-select" id="tour_id" name="tour_id" required onchange="bkUpdateSidebar()">
            <option value="">— Choose your safari —</option>
            <?php foreach ($allTours as $t): ?>
            <option value="<?= e($t['id']) ?>" data-price="<?= e($t['price']) ?>" data-name="<?= e($t['name']) ?>"
                    data-dest="<?= e($t['destination'].' · '.$t['duration']) ?>"
                    data-img="<?= e($t['image'] ?? IMG_SERENGETI) ?>"
                    data-slug="<?= e($t['slug']) ?>"
                    <?= $preselectedSlug === $t['slug'] ? 'selected' : '' ?>>
              <?= e($t['name']) ?> — <?= e($t['destination']) ?> · <?= formatPrice($t['price']) ?>/person
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:1rem">
          <div>
            <label class="f-label" for="travel_date">Travel Date <span>*</span></label>
            <input type="date" class="f-input" id="travel_date" name="travel_date" required
                   min="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                   value="<?= e($formData['travel_date'] ?? '') ?>">
          </div>
          <div>
            <label class="f-label" for="travelers">Travellers <span>*</span></label>
            <select class="f-select" id="travelers" name="travelers" required onchange="bkUpdateSidebar()">
              <?php for ($i = 1; $i <= 20; $i++): ?>
              <option value="<?= $i ?>" <?= ($formData['travelers'] ?? 2) == $i ? 'selected' : '' ?>><?= $i ?> <?= $i===1?'Person':'People' ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <!-- Inline price calc (mobile only — sidebar hidden on mobile) -->
        <div id="mobile-price-calc" style="background:rgba(160,94,34,.07);border:1px solid rgba(160,94,34,.15);border-radius:12px;padding:1rem;margin-bottom:1rem;display:none">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.5)">Estimated Total</span>
            <span id="calc-total" style="font-family:'Nanum Myeongjo',serif;font-size:1.2rem;font-weight:700;color:#a05e22">—</span>
          </div>
          <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.2);margin-top:.25rem">No payment now · Final price confirmed within 24hrs</p>
        </div>
        <!-- Hidden inputs for sidebar JS compat -->
        <input type="hidden" id="calc-price" value="">
        <input type="hidden" id="calc-travelers" value="">

        <div style="display:flex;justify-content:flex-end;margin-top:1.5rem">
          <button type="button" onclick="goStep(1)" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.82rem;text-white;padding:.85rem 1.75rem;border-radius:12px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;cursor:pointer;box-shadow:0 4px 18px rgba(160,94,34,.3);transition:all .25s" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform=''">
            Your Information <i class="fas fa-arrow-right" style="font-size:.72rem"></i>
          </button>
        </div>
      </div>

      <!-- = PANEL 2: Personal Info = -->
      <div class="bk-panel" id="panel-1">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:.85rem">
          <div>
            <label class="f-label" for="first_name">First Name <span>*</span></label>
            <input type="text" class="f-input" id="first_name" name="first_name" required placeholder="John" value="<?= e($formData['first_name'] ?? '') ?>">
          </div>
          <div>
            <label class="f-label" for="last_name">Last Name <span>*</span></label>
            <input type="text" class="f-input" id="last_name" name="last_name" required placeholder="Smith" value="<?= e($formData['last_name'] ?? '') ?>">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:.85rem">
          <div>
            <label class="f-label" for="email">Email Address <span>*</span></label>
            <input type="email" class="f-input" id="email" name="email" required placeholder="john@example.com" value="<?= e($formData['email'] ?? '') ?>">
          </div>
          <div>
            <label class="f-label" for="phone">Phone / WhatsApp <span>*</span></label>
            <input type="tel" class="f-input" id="phone" name="phone" required placeholder="+255 700 000 000" value="<?= e($formData['phone'] ?? '') ?>">
          </div>
        </div>
        <div style="margin-bottom:.85rem">
          <label class="f-label" for="country">Country of Residence <span>*</span></label>
          <?php
          $countries = ['Afghanistan','Albania','Algeria','Andorra','Angola','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahrain','Bangladesh','Belarus','Belgium','Belize','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Bulgaria','Cambodia','Cameroon','Canada','Chile','China','Colombia','Congo','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Dominican Republic','Ecuador','Egypt','Estonia','Ethiopia','Finland','France','Georgia','Germany','Ghana','Greece','Guatemala','Honduras','Hungary','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Ivory Coast','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kuwait','Latvia','Lebanon','Libya','Lithuania','Luxembourg','Madagascar','Malaysia','Maldives','Mali','Malta','Mauritius','Mexico','Moldova','Monaco','Morocco','Mozambique','Myanmar','Namibia','Nepal','Netherlands','New Zealand','Nigeria','Norway','Oman','Pakistan','Panama','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saudi Arabia','Senegal','Serbia','Singapore','Slovakia','Slovenia','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Sweden','Switzerland','Syria','Taiwan','Tanzania','Thailand','Tunisia','Turkey','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'];
          $preSelected = $formData['country'] ?? '';
          ?>
          <select class="f-select" id="country" name="country" required onchange="toggleOtherCountry(this)">
            <option value="">— Select your country —</option>
            <?php foreach ($countries as $c): ?>
            <option value="<?= e($c) ?>" <?= $preSelected === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
            <option value="__other__" <?= (!empty($preSelected) && !in_array($preSelected, $countries)) ? 'selected' : '' ?>>Other (type manually)</option>
          </select>
          <input type="text" id="country_other" name="country_other" class="f-input"
                 placeholder="Type your country name…"
                 value="<?= (!empty($preSelected) && !in_array($preSelected, $countries)) ? e($preSelected) : '' ?>"
                 style="margin-top:.5rem;display:<?= (!empty($preSelected) && !in_array($preSelected, $countries)) ? 'block' : 'none' ?>">
        </div>
        <div style="margin-bottom:1.25rem">
          <label class="f-label" for="special_requests">Special Requests</label>
          <textarea class="f-textarea" id="special_requests" name="special_requests"
                    placeholder="Dietary needs, accommodation preferences, special occasions, accessibility requirements…"><?= e($formData['special_requests'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap">
          <button type="button" onclick="goStep(0)" style="display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.78rem;padding:.75rem 1.25rem;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);cursor:pointer;transition:all .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
            <i class="fas fa-arrow-left" style="font-size:.65rem"></i> Back
          </button>
          <button type="button" onclick="goStep(2)" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.82rem;padding:.85rem 1.75rem;border-radius:12px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;cursor:pointer;box-shadow:0 4px 18px rgba(160,94,34,.3);transition:all .25s" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform=''">
            Review Booking <i class="fas fa-arrow-right" style="font-size:.72rem"></i>
          </button>
        </div>
      </div>

      <!-- = PANEL 3: Review & Submit = -->
      <div class="bk-panel" id="panel-2">
        <!-- Summary -->
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:1.1rem;margin-bottom:1.1rem">
          <p style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:#a05e22;margin-bottom:.75rem">Booking Summary</p>
          <?php foreach ([['Safari Tour','s-tour'],['Travel Date','s-date'],['Travellers','s-travelers'],['Full Name','s-name'],['Email','s-email'],['Country','s-country'],['Est. Total','s-total']] as $sf): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid rgba(255,255,255,.05)">
            <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;color:rgba(255,255,255,.38)"><?= $sf[0] ?></span>
            <span id="<?= $sf[1] ?>" style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.85)">—</span>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Note -->
        <div style="background:rgba(160,94,34,.06);border:1px solid rgba(160,94,34,.18);border-radius:12px;padding:.85rem 1rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.6rem">
          <i class="fas fa-info-circle" style="color:#a05e22;font-size:.82rem;margin-top:.1rem;flex-shrink:0"></i>
          <p style="font-family:'Inter',sans-serif;font-size:.8rem;color:rgba(255,255,255,.5);line-height:1.6">
            By submitting you agree to our Terms & Conditions. <strong style="color:rgba(255,255,255,.75)">No payment required</strong> at this stage. We'll confirm within 24 hours.
          </p>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap">
          <button type="button" onclick="goStep(1)" style="display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.78rem;padding:.75rem 1.25rem;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);cursor:pointer;transition:all .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
            <i class="fas fa-arrow-left" style="font-size:.65rem"></i> Back
          </button>
          <button type="submit" style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.88rem;padding:.9rem 2rem;border-radius:12px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;cursor:pointer;box-shadow:0 6px 24px rgba(160,94,34,.35);transition:all .25s" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform=''">
            <i class="fas fa-paper-plane" style="font-size:.78rem"></i> Submit Booking Request
          </button>
        </div>
      </div>

    </form>
  </div><!-- /form area -->
</div><!-- /bk-grid -->
<?php endif; ?>

<!-- Footer -->
<footer class="border-t border-white/[.07] py-8 px-4 lg:px-0 mt-4">
  <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
    <span class="font-heading text-white/40 font-bold text-sm"><?= e($siteName) ?></span>
    <p class="text-white/20 text-xs font-nav">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
    <div class="flex gap-4">
      <a href="<?= url('tours') ?>" class="text-white/35 hover:text-emerald-400 text-xs font-nav transition-colors">? All Tours</a>
      <a href="<?= url('contact') ?>" class="text-white/35 hover:text-white text-xs font-nav transition-colors">Contact</a>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener"><i class="fab fa-whatsapp text-2xl relative z-10"></i></a>

<script>
(function(){
  /* Navbar */
  const nav=document.getElementById('main-nav');
  window.addEventListener('scroll',()=>nav&&nav.classList.toggle('scrolled',scrollY>60),{passive:true});

  /* Mobile menu */
  const tog=document.getElementById('menu-toggle'),mm=document.getElementById('mobile-menu'),bd=document.getElementById('mobile-backdrop');
  const t1=document.getElementById('tog-1'),t2=document.getElementById('tog-2'),t3=document.getElementById('tog-3');
  function openM(){mm.classList.add('open');bd.classList.add('show');document.body.style.overflow='hidden';if(t1){t1.style.transform='rotate(45deg) translate(5px,5px)';t2.style.opacity='0';t3.style.transform='rotate(-45deg) translate(5px,-5px)';}}
  function closeM(){mm.classList.remove('open');bd.classList.remove('show');document.body.style.overflow='';if(t1){t1.style.transform='';t2.style.opacity='';t3.style.transform='';}}
  tog&&tog.addEventListener('click',()=>mm.classList.contains('open')?closeM():openM());
  bd&&bd.addEventListener('click',closeM);

  /* WhatsApp */
  const wa=document.getElementById('wa-float');
  if(wa)setTimeout(()=>wa.classList.add('visible'),2500);

  /* Multi-step form */
  let currentStep = 0;

  window.goStep = function(target) {
    if (target > currentStep && !validateStep(currentStep)) return;
    document.getElementById('panel-' + currentStep).classList.remove('active');
    document.getElementById('panel-' + target).classList.add('active');

    /* Update step dots */
    for (let i = 0; i < 3; i++) {
      const dot   = document.getElementById('step-dot-' + i);
      const lbl   = document.getElementById('step-label-' + i);
      const chk   = document.getElementById('step-ck-' + i);
      const num   = document.getElementById('step-n-' + i);
      const item  = document.getElementById('bk-step-' + i);
      if (!dot) continue;
      const state = i < target ? 'done' : i === target ? 'active' : 'pending';
      dot.className  = 'bk-step-num ' + state;
      if (item) item.className = 'bk-step-item ' + state;
      if (lbl)  lbl.style.color = i <= target ? '#a05e22' : 'rgba(255,255,255,.25)';
      if (chk)  chk.style.display = state === 'done' ? 'block' : 'none';
      if (num)  num.style.display = state === 'done' ? 'none' : 'block';
    }

    currentStep = target;
    if (target === 2) buildSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  function validateStep(step) {
    if (step === 0) {
      const tour = document.getElementById('tour_id');
      const date = document.getElementById('travel_date');
      if (!tour.value) { alert('Please select a safari tour.'); tour.focus(); return false; }
      if (!date.value) { alert('Please select a travel date.'); date.focus(); return false; }
      return true;
    }
    if (step === 1) {
      const fn = document.getElementById('first_name');
      const ln = document.getElementById('last_name');
      const em = document.getElementById('email');
      const ph = document.getElementById('phone');
      const co = document.getElementById('country');
      if (!fn.value.trim()) { alert('Please enter your first name.'); fn.focus(); return false; }
      if (!ln.value.trim()) { alert('Please enter your last name.');  ln.focus(); return false; }
      if (!em.value.trim()) { alert('Please enter your email.');       em.focus(); return false; }
      if (!ph.value.trim()) { alert('Please enter your phone number.');ph.focus(); return false; }
      if (!co.value) { alert('Please select your country.'); co.focus(); return false; }
      if (co.value === '__other__') {
        const ot = document.getElementById('country_other');
        if (!ot || !ot.value.trim()) { alert('Please type your country name.'); ot?.focus(); return false; }
      }
      return true;
    }
    return true;
  }

  function buildSummary() {
    const tourSel    = document.getElementById('tour_id');
    const tourText   = tourSel.options[tourSel.selectedIndex]?.text || '—';
    const date       = document.getElementById('travel_date').value;
    const trav       = document.getElementById('travelers').value;
    const fn         = document.getElementById('first_name').value;
    const ln         = document.getElementById('last_name').value;
    const em         = document.getElementById('email').value;
    const co         = document.getElementById('country').value;
    const total      = document.getElementById('sb-total')?.textContent || document.getElementById('calc-total')?.textContent || '—';

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
    set('s-tour',     tourText.split('—')[0].trim());
    set('s-date',     date || '—');
    set('s-travelers',trav + (trav == 1 ? ' Person' : ' People'));
    set('s-name',     (fn + ' ' + ln).trim() || '—');
    set('s-email',    em || '—');
    set('s-country',  co || '—');
    set('s-total',    total);
  }

  /* Country "Other" toggle */
  window.toggleOtherCountry = function(sel) {
    const other = document.getElementById('country_other');
    if (!other) return;
    if (sel.value === '__other__') {
      other.style.display = 'block';
      other.required = true;
      other.focus();
    } else {
      other.style.display = 'none';
      other.required = false;
    }
  };

  /* Sidebar + price updater */
  function bkUpdateSidebar() {
    const tourSel  = document.getElementById('tour_id');
    const travelers= parseInt(document.getElementById('travelers')?.value) || 0;
    const opt      = tourSel ? tourSel.options[tourSel.selectedIndex] : null;
    const price    = opt ? parseFloat(opt.dataset.price) : 0;
    const fmt = v => v > 0 ? '$' + v.toLocaleString('en-US',{minimumFractionDigits:0}) : '—';
    const total    = (price > 0 && travelers > 0) ? fmt(price * travelers) : '—';

    /* Sidebar elements */
    const sbPrice    = document.getElementById('sb-price');
    const sbTrav     = document.getElementById('sb-travelers');
    const sbTotal    = document.getElementById('sb-total');
    const sbName     = document.getElementById('bk-tour-name');
    const sbDest     = document.getElementById('bk-tour-dest');
    const sbImg      = document.getElementById('bk-tour-img');
    if (sbPrice)    sbPrice.textContent    = fmt(price);
    if (sbTrav)     sbTrav.textContent     = travelers > 0 ? travelers + (travelers===1?' Person':' People') : '—';
    if (sbTotal)    sbTotal.textContent    = total;
    if (sbName && opt && opt.value) sbName.textContent = opt.dataset.name || opt.text.split('—')[0].trim();
    if (sbDest && opt && opt.value) sbDest.textContent = opt.dataset.dest || '';
    if (sbImg  && opt && opt.dataset.img) sbImg.src = opt.dataset.img;

    /* Mobile calc */
    const mCalc = document.getElementById('mobile-price-calc');
    const cTot  = document.getElementById('calc-total');
    if (mCalc) mCalc.style.display = price > 0 ? 'block' : 'none';
    if (cTot)  cTot.textContent = total;
  }
  window.bkUpdateSidebar = bkUpdateSidebar;

  function calcPrice() { bkUpdateSidebar(); } /* legacy compat */

  document.getElementById('tour_id')   && document.getElementById('tour_id').addEventListener('change', bkUpdateSidebar);
  document.getElementById('travelers') && document.getElementById('travelers').addEventListener('change', bkUpdateSidebar);
  /* Show mobile price calc on small screens */
  function checkMobileCalc() {
    const mc = document.getElementById('mobile-price-calc');
    if (mc && window.innerWidth < 1024) mc.style.display = document.getElementById('tour_id')?.value ? 'block' : 'none';
    else if (mc) mc.style.display = 'none';
  }
  window.addEventListener('resize', checkMobileCalc, {passive:true});
  /* Init on load */
  bkUpdateSidebar(); checkMobileCalc();
  document.getElementById('travelers') && document.getElementById('travelers').addEventListener('change', calcPrice);
  calcPrice();

})();
</script>

<?php require_once 'includes/chatbot-widget.php'; ?>
</body>
</html>






