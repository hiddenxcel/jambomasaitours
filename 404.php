<?php
/* Ukurasa wa 404 halisi. Unaitwa na render404() (tour/blog/destination zisizopo)
   na Apache ErrorDocument. Status inabaki 404 ili Google isiuone kama soft-404. */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

http_response_code(404);

$pageTitle       = 'Page Not Found | Jambo Masai Tours';
$pageDescription = 'The page you were looking for could not be found. Explore our Tanzania safari tours, destinations and travel guides instead.';
$currentPage     = '';
$canonicalUrl    = SITE_URL . '/404';
$metaRobots      = 'noindex, follow';

try {
    $_nfTours = getDB()->query("SELECT name, slug, price, duration FROM tours ORDER BY featured DESC, rating DESC LIMIT 6")->fetchAll();
} catch (\Throwable $e) { $_nfTours = []; }

require __DIR__ . '/includes/dark_header.php';
?>

<div class="max-w-4xl mx-auto px-4 lg:px-8 pt-40 pb-20 text-center">
  <div class="section-tag"><i class="fas fa-compass mr-1"></i>404</div>
  <h1 class="font-heading text-white font-bold mt-3 mb-4" style="font-size:clamp(2rem,5vw,3rem)">This trail has gone cold</h1>
  <p class="text-white/55 text-lg max-w-xl mx-auto mb-10">The page you were looking for has moved or no longer exists. Here are the best places to continue planning your Tanzania adventure.</p>

  <div class="flex flex-wrap justify-center gap-3 mb-14">
    <?php foreach ([
        ['tours', 'Safari Tours', 'fa-compass'],
        ['migration', 'Great Migration', 'fa-paw'],
        ['mountain-trekking', 'Kilimanjaro & Meru', 'fa-mountain'],
        ['destinations', 'Destinations', 'fa-map-marker-alt'],
        ['blog', 'Travel Guides', 'fa-newspaper'],
        ['contact', 'Contact Us', 'fa-envelope'],
    ] as $_l): ?>
    <a href="<?= url($_l[0]) ?>" class="glass-card" style="padding:.7rem 1.2rem;color:#fff;text-decoration:none;font-family:'Montserrat',sans-serif;font-size:.8rem;font-weight:600">
      <i class="fas <?= $_l[2] ?>" style="color:#a05e22;margin-right:.4rem"></i><?= e($_l[1]) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($_nfTours): ?>
  <h2 class="font-heading text-white text-2xl font-bold mb-6">Popular safaris</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
    <?php foreach ($_nfTours as $_t): ?>
    <a href="<?= url('tour/' . e($_t['slug'])) ?>" class="glass-card" style="display:block;padding:1rem 1.2rem;text-decoration:none">
      <div style="color:#fff;font-weight:600"><?= e($_t['name']) ?></div>
      <div style="color:rgba(255,255,255,.45);font-size:.8rem;margin-top:.25rem"><?= e($_t['duration']) ?> · from <span class="js-price" data-price-usd="<?= (float)$_t['price'] ?>"><?= formatPrice((float)$_t['price']) ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/dark_footer.php'; ?>
