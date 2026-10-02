<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$s1 = calvingSeason();     // mf. 2026/27
$s2 = calvingSeason(1);    // mf. 2027/28

$pageTitle       = 'Ndutu Calving Season Safari ' . $s1 . ' & ' . $s2 . ' | Dec–Mar';
$pageDescription = 'Ndutu wildebeest calving season ' . $s1 . ' and ' . $s2 . ': ~8,000 calves born a day at the peak (Feb), predators hunting and few vehicles. Safaris with Maasai guides.';
$metaKeywords    = 'ndutu calving season, wildebeest calving season ' . $s1 . ', calving season safari ' . $s2 . ', serengeti calving season, ndutu safari february, great migration calving, calving season tanzania, southern serengeti safari';
$currentPage     = 'migration';
$ogImage         = IMG_SERENGETI;
$canonicalUrl    = SITE_URL . '/calving-season';

$db = getDB();
$packages = [];
try {
    $packages = $db->query("SELECT * FROM tours
        WHERE (LOWER(name) LIKE '%calving%' OR LOWER(name) LIKE '%ndutu%' OR LOWER(description) LIKE '%calving%')
          AND slug NOT REGEXP '-[0-9]{9,10}$'
        ORDER BY featured DESC, rating DESC")->fetchAll();
} catch (\Throwable $e) {}

$faqs = [
    ['q' => 'When is the calving season in the Serengeti?',
     'a' => 'Calving happens in the southern Serengeti and Ndutu area from December to March, with the peak in February. For the ' . $s1 . ' season that means December ' . explode('/', $s1)[0] . ' to March ' . (explode('/', $s1)[0] + 1) . '; for ' . $s2 . ' it is December ' . explode('/', $s2)[0] . ' to March ' . (explode('/', $s2)[0] + 1) . '.'],
    ['q' => 'How many wildebeest calves are born during calving season?',
     'a' => 'Roughly 500,000 calves are born over a few weeks, and at the peak around 8,000 calves can be born in a single day. Most births take place on the short-grass plains of the southern Serengeti between late January and the end of February.'],
    ['q' => 'What is the best month for a calving season safari?',
     'a' => 'Late January to late February is the sweet spot: the herds are concentrated around Ndutu, thousands of newborns are on the ground and predators such as lions, cheetahs, leopards and hyenas are actively hunting. December is quieter and greener; March is calmer as the herds begin to move.'],
    ['q' => 'Is the calving season better than the Mara River crossings?',
     'a' => 'They are different experiences. The Mara River crossings (July to October) are dramatic but crowded and never guaranteed on a given day. Calving season offers constant wildlife action, fewer vehicles, lower low-season rates at many camps, and reliable sightings, so it suits families, photographers and first-time visitors well.'],
    ['q' => 'Where do we stay for the calving season?',
     'a' => 'Most calving safaris are based around Lake Ndutu and the southern Serengeti, either in lodges and camps on the edge of the plains or in seasonal camps that move with the herds. We choose the camp for the week you travel so you sleep as close to the action as possible.'],
    ['q' => 'Can I combine calving season with Zanzibar or Kilimanjaro?',
     'a' => 'Yes. December to March is also a good time for Zanzibar beaches, and we offer Zanzibar to Ndutu fly-in safaris as well as safari and Kilimanjaro combinations. Contact us for a tailor-made itinerary.'],
    ['q' => 'How far in advance should I book a calving season safari?',
     'a' => 'For February, book as early as possible. Camps around Ndutu are small and the best weeks sell out months ahead, and many travellers now plan 9 to 14 months in advance.'],
];

$ldFaqs = [];
foreach ($faqs as $f) {
    $ldFaqs[] = ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
}
$ldItems = [];
foreach ($packages as $i => $p) {
    $ldItems[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => SITE_URL . '/tour/' . $p['slug'], 'name' => $p['name']];
}
$jsonLd = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'The Great Migration', 'item' => SITE_URL . '/migration'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Calving Season', 'item' => $canonicalUrl],
    ]],
    ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $pageTitle, 'url' => $canonicalUrl,
        'description' => $pageDescription,
        'about' => ['@type' => 'Thing', 'name' => 'Serengeti wildebeest calving season'],
        'publisher' => ['@type' => 'TravelAgency', 'name' => 'Jambo Masai Tours', 'url' => SITE_URL]],
    ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Calving season safari packages', 'itemListElement' => $ldItems],
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ldFaqs],
];
$headExtra = '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

$waMsg = urlencode('Hi! I am interested in a Ndutu calving season safari (' . $s1 . '). Please share dates and prices.');

/* Month-by-month for the two seasons */
$months = [
    ['Nov', 'Short rains begin', 'Herds move south onto the fresh short-grass plains. Green, quiet and good value.'],
    ['Dec', 'Herds arrive', 'Wildebeest and zebra gather around Ndutu. First births begin; Christmas / New Year departures fill early.'],
    ['Jan', 'Calving builds', 'Thousands of newborns each week. Lions and hyenas settle around the herds.'],
    ['Feb', 'Peak calving', 'Up to ~8,000 calves a day. Best month for predator action and photography. Book earliest.'],
    ['Mar', 'Herds move on', 'Calves are strong and the herds begin drifting north-west as the plains dry. Fewer crowds.'],
];

require_once 'includes/dark_header.php';
?>

<div class="max-w-5xl mx-auto px-4 lg:px-8 pt-36 pb-10">
  <nav class="flex items-center gap-2 font-nav text-[.62rem] text-white/35 mb-5">
    <a href="<?= url() ?>" class="hover:text-white">Home</a><span>›</span>
    <a href="<?= url('migration') ?>" class="hover:text-white">Great Migration</a><span>›</span>
    <span class="text-white/55">Calving Season</span>
  </nav>
  <div class="section-tag"><i class="fas fa-baby mr-1"></i>Dec–Mar · <?= e($s1) ?> &amp; <?= e($s2) ?></div>
  <h1 class="font-heading text-white font-bold mt-3 mb-4" style="font-size:clamp(2rem,5vw,3.4rem);line-height:1.1">
    Ndutu Calving Season Safari <?= e($s1) ?> &amp; <?= e($s2) ?>
  </h1>
  <p class="text-white/60 text-lg max-w-3xl mb-6">
    Every year, around half a million wildebeest calves are born on the short-grass plains of the southern Serengeti. At the peak in February, up to 8,000 arrive in a single day, and the predators know it. It is the most intense wildlife show in Tanzania, with far fewer vehicles than the Mara River crossings.
  </p>
  <div class="flex flex-wrap gap-3">
    <a href="https://wa.me/255659667271?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="glass-card" style="padding:.8rem 1.3rem;color:#fff;font-weight:700;text-decoration:none;background:linear-gradient(135deg,#7d4817,#a05e22)"><i class="fab fa-whatsapp mr-2"></i>Ask about <?= e($s1) ?> dates</a>
    <a href="#packages" class="glass-card" style="padding:.8rem 1.3rem;color:#fff;text-decoration:none">See calving safaris</a>
  </div>
</div>

<div class="max-w-5xl mx-auto px-4 lg:px-8 pb-10">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-2">Calving season, month by month</h2>
  <p class="text-white/50 mb-6">Season <?= e($s1) ?> runs December <?= (int)explode('/', $s1)[0] ?> to March <?= (int)explode('/', $s1)[0] + 1 ?>; season <?= e($s2) ?> runs December <?= (int)explode('/', $s2)[0] ?> to March <?= (int)explode('/', $s2)[0] + 1 ?>. The pattern repeats every year, with the peak in February.</p>
  <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
    <?php foreach ($months as $m): ?>
    <div class="glass-card" style="padding:1rem 1.1rem">
      <div style="font-family:'Montserrat',sans-serif;font-size:.7rem;letter-spacing:.14em;color:#c17a3a;font-weight:700"><?= e($m[0]) ?></div>
      <div class="text-white font-semibold mt-1"><?= e($m[1]) ?></div>
      <div class="text-white/50 text-sm mt-2"><?= e($m[2]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="max-w-5xl mx-auto px-4 lg:px-8 pb-10 grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="glass-card" style="padding:1.5rem">
    <h2 class="font-heading text-white text-xl font-bold mb-3">What you will see</h2>
    <ul class="text-white/60 space-y-2 text-sm" style="list-style:disc;padding-left:1.1rem">
      <li>Newborn wildebeest standing within minutes of birth</li>
      <li>Lions, cheetahs, leopards and spotted hyenas hunting the herds</li>
      <li>Hundreds of thousands of zebra and gazelle sharing the plains</li>
      <li>Big skies, flamingos on Lake Ndutu and bird life in the green season</li>
      <li>Fewer vehicles than the northern river crossings</li>
    </ul>
  </div>
  <div class="glass-card" style="padding:1.5rem">
    <h2 class="font-heading text-white text-xl font-bold mb-3">Calving season vs river crossings</h2>
    <table class="w-full text-sm text-white/60">
      <tr class="text-white"><th class="text-left py-1">&nbsp;</th><th class="text-left">Calving (Dec–Mar)</th><th class="text-left">Crossings (Jul–Oct)</th></tr>
      <tr><td class="py-1">Area</td><td>Ndutu / south</td><td>North / Mara River</td></tr>
      <tr><td class="py-1">Action</td><td>Births + predators</td><td>River crossings</td></tr>
      <tr><td class="py-1">Crowds</td><td>Low</td><td>High</td></tr>
      <tr><td class="py-1">Certainty</td><td>Very reliable</td><td>Not guaranteed</td></tr>
    </table>
  </div>
</div>

<div id="packages" class="max-w-5xl mx-auto px-4 lg:px-8 pb-12">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-2">Calving season safari packages</h2>
  <p class="text-white/50 mb-6">Private departures with Maasai guides. Prices shown are the current starting prices, and each itinerary can be tailored.</p>
  <?php if ($packages): ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($packages as $p): ?>
    <a href="<?= url('tour/' . e($p['slug'])) ?>" class="glass-card" style="display:block;padding:1.1rem 1.3rem;text-decoration:none">
      <div class="text-white font-semibold"><?= e($p['name']) ?></div>
      <div class="text-white/45 text-sm mt-1"><?= e($p['duration']) ?> · from <span class="js-price" data-price-usd="<?= (float)$p['price'] ?>"><?= formatPrice((float)$p['price']) ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p class="text-white/60">Ask us for a tailor-made calving season itinerary. <a href="<?= url('contact') ?>" style="color:#c17a3a">Contact us</a>.</p>
  <?php endif; ?>
  <p class="text-white/45 text-sm mt-5">Also see all <a href="<?= url('tours') ?>" style="color:#c17a3a">Tanzania safari packages <?= e(seoYears()) ?></a> and the full <a href="<?= url('migration') ?>" style="color:#c17a3a">Great Migration calendar</a>.</p>
</div>

<div class="max-w-4xl mx-auto px-4 lg:px-8 pb-20">
  <h2 class="font-heading text-white text-2xl md:text-3xl font-bold mb-6">Calving season FAQ</h2>
  <?php foreach ($faqs as $f): ?>
  <details class="glass-card" style="padding:1rem 1.3rem;margin-bottom:.7rem">
    <summary class="text-white font-semibold cursor-pointer"><?= e($f['q']) ?></summary>
    <p class="text-white/60 mt-3 text-sm leading-relaxed"><?= e($f['a']) ?></p>
  </details>
  <?php endforeach; ?>
</div>

<?php require_once 'includes/dark_footer.php'; ?>
