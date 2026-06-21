<?php
$navItems = [
  'home'         => ['url' => url(),                   'label' => 'Home'],
  'tours'        => ['url' => url('tours'),        'label' => 'Safari Tours'],
  'destinations' => ['url' => url('destinations'), 'label' => 'Destinations'],
  'about'        => ['url' => url('about'),        'label' => 'About Us'],
  'gallery'      => ['url' => url('gallery'),      'label' => 'Gallery'],
  'blog'         => ['url' => url('blog'),         'label' => 'Blog'],
  'contact'      => ['url' => url('contact'),      'label' => 'Contact'],
];
$logoUrl     = getSetting('logo_url');
$logoWidth   = (int)(getSetting('logo_width', '160') ?: 160);
$siteName    = getSetting('site_name', 'Jambo Masai Tours');
$siteTagline = getSetting('site_tagline', 'Tanzania Safari Experts');
?>
<nav id="main-nav" class="navbar navbar--transparent" role="navigation" aria-label="Main navigation">
  <div class="container navbar__container">

    <!-- ── Logo ── -->
    <a href="<?= url() ?>" class="navbar__logo" aria-label="<?= e($siteName) ?> — Home">
      <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="navbar__logo-img"
             style="width:<?= $logoWidth ?>px;" loading="eager">
      <?php else: ?>
        <div class="navbar__logo-mark" aria-hidden="true">🦁</div>
        <div class="navbar__logo-text">
          <span class="navbar__logo-name"><?= e($siteName) ?></span>
          <span class="navbar__logo-sub"><?= e($siteTagline) ?></span>
        </div>
      <?php endif; ?>
    </a>

    <!-- ── Desktop + Mobile menu ── -->
    <ul id="nav-menu" class="navbar__menu" role="list">
      <?php foreach ($navItems as $key => $item): ?>
      <li class="navbar__item">
        <a href="<?= e($item['url']) ?>"
           class="navbar__link <?= (($currentPage ?? '') === $key) ? 'navbar__link--active' : '' ?>"
           <?= (($currentPage ?? '') === $key) ? 'aria-current="page"' : '' ?>>
          <?= e($item['label']) ?>
        </a>
      </li>
      <?php endforeach; ?>
      <!-- Book Now — visible inside drawer on mobile only -->
      <li class="navbar__item navbar__item--cta">
        <a href="<?= url('booking') ?>" class="navbar__cta-btn">Book Safari →</a>
      </li>
    </ul>

    <!-- ── Right actions (desktop CTA + hamburger) ── -->
    <div class="navbar__actions">
      <a href="<?= url('booking') ?>" class="navbar__cta-btn" aria-label="Book a safari">
        Book Safari
      </a>
      <button class="navbar__toggle" id="nav-toggle"
              aria-expanded="false" aria-controls="nav-menu"
              aria-label="Open navigation menu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>

  <!-- Mobile backdrop -->
  <div class="navbar__backdrop" id="nav-backdrop" aria-hidden="true"></div>
</nav>
