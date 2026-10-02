<?php
/**
 * Template ya kurasa za maudhui ya SEO (calving, best time, river crossings, n.k.).
 * Ukurasa unaandaa $cfg kisha: require 'includes/seo_page.php';
 *
 * $cfg keys:
 *  title, desc, keywords, canonical(path e.g. '/green-season-safari'), crumbs [[name, path]...]
 *  badge, h1, lead, cta_msg, sections [ ['h2'=>, 'p'=>[...], 'list'=>[...], 'cards'=>[[t,sub,txt]...], 'table'=>['head'=>[], 'rows'=>[[]]]] ]
 *  tour_sql (WHERE clause on tours, no user input), tour_heading, tour_intro, faqs [[q,a]], related [[label,path]]
 */
$cfg = $cfg ?? [];
$pageTitle       = $cfg['title'];
$pageDescription = $cfg['desc'];
$metaKeywords    = $cfg['keywords'] ?? '';
$currentPage     = $cfg['current'] ?? 'migration';
$ogImage         = $cfg['og'] ?? IMG_SERENGETI;
$canonicalUrl    = SITE_URL . $cfg['canonical'];

$packages = [];
if (!empty($cfg['tour_sql'])) {
    try {
        $packages = getDB()->query("SELECT * FROM tours WHERE (" . $cfg['tour_sql'] . ") AND slug NOT REGEXP '-[0-9]{9,10}\$' ORDER BY featured DESC, rating DESC LIMIT " . (int)($cfg['tour_limit'] ?? 8))->fetchAll();
    } catch (\Throwable $e) {}
}

$crumbLd = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/']];
foreach ($cfg['crumbs'] as $i => $c) {
    $crumbLd[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $c[0], 'item' => SITE_URL . $c[1]];
}
$ld = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbLd],
    ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $pageTitle, 'url' => $canonicalUrl, 'description' => $pageDescription,
        'dateModified' => date('Y-m-d'),
        'publisher' => ['@type' => 'TravelAgency', 'name' => 'Jambo Masai Tours', 'url' => SITE_URL]],
];
if ($packages) {
    $items = [];
    foreach ($packages as $i => $p) $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => SITE_URL . '/tour/' . $p['slug'], 'name' => $p['name']];
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $cfg['tour_heading'] ?? 'Safari packages', 'itemListElement' => $items];
}
if (!empty($cfg['faqs'])) {
    $qs = [];
    foreach ($cfg['faqs'] as $f) $qs[] = ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]];
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qs];
}
$headExtra = '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
$waMsg = urlencode($cfg['cta_msg'] ?? 'Hi! I would like to plan a Tanzania safari. Please share options and prices.');

require __DIR__ . '/dark_header.php';
?>

<div class="max-w-5xl mx-auto px-4 lg:px-8 pt-36 pb-10">
  <nav class="flex flex-wrap items-center gap-2 font-nav text-[.62rem] text-white/35 mb-5">
    <a href="<?= url() ?>" class="hover:text-white">Home</a>
    <?php foreach ($cfg['crumbs'] as $i => $c): ?>
      <span>›</span>
      <?php if ($i < count($cfg['crumbs']) - 1): ?><a href="<?= SITE_URL . e($c[1]) ?>" class="hover:text-white"><?= e($c[0]) ?></a>
      <?php else: ?><span class="text-white/55"><?= e($c[0]) ?></span><?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <?php if (!empty($cfg['badge'])): ?><div class="section-tag"><i class="fas fa-compass mr-1"></i><?= e($cfg['badge']) ?></div><?php endif; ?>
  <h1 class="font-heading text-white font-bold mt-3 mb-4" style="font-size:clamp(2rem,5vw,3.4rem);line-height:1.1"><?= e($cfg['h1']) ?></h1>
  <p class="text-white/60 text-lg max-w-3xl mb-6"><?= e($cfg['lead']) ?></p>
  <div class="flex flex-wrap gap-3">
    <a href="https://wa.me/255659667271?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="glass-card" style="padding:.8rem 1.3rem;color:#fff;font-weight:700;text-decoration:none;background:linear-gradient(135deg,#7d4817,#a05e22)"><i class="fab fa-whatsapp mr-2"></i><?= e($cfg['cta'] ?? 'Get a free quote') ?></a>
    <?php if ($packages): ?><a href="#packages" class="glass-card" style="padding:.8rem 1.3rem;color:#fff;text-decoration:none">See safari packages</a><?php endif; ?>
  </div>
</div>

<?php foreach ($cfg['sections'] as $s): ?>
<div class="max-w-5xl mx-auto px-4 lg:px-8 pb-10">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-3"><?= e($s['h2']) ?></h2>
  <?php foreach ($s['p'] ?? [] as $para): ?><p class="text-white/60 mb-4 leading-relaxed max-w-3xl"><?= e($para) ?></p><?php endforeach; ?>
  <?php if (!empty($s['list'])): ?>
  <ul class="text-white/60 space-y-2 mb-4 max-w-3xl" style="list-style:disc;padding-left:1.2rem">
    <?php foreach ($s['list'] as $li): ?><li><?= e($li) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php if (!empty($s['cards'])): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <?php foreach ($s['cards'] as $card): ?>
    <div class="glass-card" style="padding:1rem 1.1rem<?= !empty($card[3]) ? ';border-color:rgba(193,122,58,.7)' : '' ?>">
      <div style="font-family:'Montserrat',sans-serif;font-size:.7rem;letter-spacing:.14em;color:#c17a3a;font-weight:700"><?= e($card[0]) ?><?= !empty($card[3]) ? ' · NOW' : '' ?></div>
      <div class="text-white font-semibold mt-1"><?= e($card[1]) ?></div>
      <div class="text-white/50 text-sm mt-2"><?= e($card[2]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($s['table'])): ?>
  <div class="glass-card overflow-x-auto" style="padding:1rem 1.2rem">
    <table class="w-full text-sm text-white/60" style="min-width:480px">
      <tr class="text-white"><?php foreach ($s['table']['head'] as $h): ?><th class="text-left py-2 pr-3"><?= e($h) ?></th><?php endforeach; ?></tr>
      <?php foreach ($s['table']['rows'] as $row): ?>
      <tr style="border-top:1px solid rgba(255,255,255,.07)"><?php foreach ($row as $ci => $cell): ?><td class="py-2 pr-3<?= $ci === 0 ? ' text-white' : '' ?>"><?= e($cell) ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($packages): ?>
<div id="packages" class="max-w-5xl mx-auto px-4 lg:px-8 pb-12">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-2"><?= e($cfg['tour_heading'] ?? 'Safari packages') ?></h2>
  <p class="text-white/50 mb-6"><?= e($cfg['tour_intro'] ?? 'Private departures with Maasai guides. Prices shown are current starting prices and each itinerary can be tailored.') ?></p>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($packages as $p): ?>
    <a href="<?= url('tour/' . e($p['slug'])) ?>" class="glass-card" style="display:block;padding:1.1rem 1.3rem;text-decoration:none">
      <div class="text-white font-semibold"><?= e($p['name']) ?></div>
      <div class="text-white/45 text-sm mt-1"><?= e($p['duration']) ?> · from <span class="js-price" data-price-usd="<?= (float)$p['price'] ?>"><?= formatPrice((float)$p['price']) ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($cfg['related'])): ?>
<div class="max-w-5xl mx-auto px-4 lg:px-8 pb-10">
  <h2 class="font-heading text-white text-xl font-bold mb-3">Keep planning</h2>
  <div class="flex flex-wrap gap-3">
    <?php foreach ($cfg['related'] as $r): ?>
    <a href="<?= SITE_URL . e($r[1]) ?>" class="glass-card" style="padding:.6rem 1.1rem;color:#fff;text-decoration:none;font-size:.85rem"><?= e($r[0]) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($cfg['faqs'])): ?>
<div class="max-w-4xl mx-auto px-4 lg:px-8 pb-20">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-6">Frequently asked questions</h2>
  <?php foreach ($cfg['faqs'] as $f): ?>
  <details class="glass-card" style="padding:1rem 1.3rem;margin-bottom:.7rem">
    <summary class="text-white font-semibold cursor-pointer"><?= e($f[0]) ?></summary>
    <p class="text-white/60 mt-3 text-sm leading-relaxed"><?= e($f[1]) ?></p>
  </details>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/dark_footer.php'; ?>
