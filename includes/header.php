<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? SITE_NAME . ' | Luxury Safari Tours Tanzania') ?></title>
  <meta name="description" content="<?= e($pageDescription ?? 'Experience world-class luxury safari tours in Tanzania. Serengeti, Ngorongoro, Kilimanjaro, Zanzibar and more with Jambo Masai Tours.') ?>">
  <?php if (!empty($pageKeywords)): ?>
  <meta name="keywords" content="<?= e($pageKeywords) ?>">
  <?php endif; ?>
  <meta name="author" content="Jambo Masai Tours">
  <meta name="robots" content="index, follow">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>">
  <meta property="og:title"       content="<?= e($pageTitle ?? SITE_NAME) ?>">
  <meta property="og:description" content="<?= e($pageDescription ?? 'Luxury safari tours in Tanzania') ?>">
  <meta property="og:image"       content="<?= e($ogImage ?? IMG_HERO) ?>">
  <meta property="og:url"         content="<?= e(SITE_URL . $_SERVER['REQUEST_URI']) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= e($pageTitle ?? SITE_NAME) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription ?? '') ?>">
  <meta name="twitter:image"       content="<?= e($ogImage ?? IMG_HERO) ?>">

  <!-- Canonical -->
  <link rel="canonical" href="<?= e(SITE_URL . '/' . ltrim(strtok($_SERVER['REQUEST_URI'], '?'), '/')) ?>">

  <!-- Favicon -->
  <?php $_fav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png'); ?>
  <link rel="icon" type="image/png" href="<?= e($_fav) ?>">
  <link rel="shortcut icon" href="<?= e($_fav) ?>">
  <link rel="apple-touch-icon" href="<?= e($_fav) ?>">

  <?php if (!empty($schemaMarkup)) echo $schemaMarkup; ?>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:ital,wght@0,400;0,600;0,700;0,900;1,400;1,700&family=Poppins:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700;800&display=swap">

  <!-- AOS -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/animations.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<!-- Scroll progress -->
<div class="scroll-progress" aria-hidden="true"></div>

<!-- Loading Screen -->
<div id="loading-screen" class="loading-screen" role="status" aria-label="Loading">
  <div class="loader__brand">Jambo <span>Masai</span> Tours</div>
  <div class="loader__bar"><div class="loader__fill"></div></div>
</div>
