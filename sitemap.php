<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$base = rtrim(SITE_URL, '/');

/* Tours zote isipokuwa nakala zilizojirudia (slug + "-1784806941") ambazo zina 301 kwenda ya asili */
$tours = [];
try {
    $tours = getDB()->query("SELECT slug, image, created_at FROM tours WHERE slug NOT REGEXP '-[0-9]{9,10}$' ORDER BY featured DESC, rating DESC")->fetchAll();
} catch (\Throwable $e) {}

$posts = [];
try {
    $posts = getDB()->query("SELECT slug, image, created_at FROM blog_posts WHERE published=1 ORDER BY created_at DESC")->fetchAll();
} catch (\Throwable $e) {}

function xmlDate(?string $d): string {
    return $d ? date('Y-m-d', strtotime($d)) : '';
}
function xmlUrl(string $loc, string $lastmod = '', string $image = ''): void {
    echo "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    if ($lastmod) echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    if ($image && preg_match('#^https?://#', $image)) {
        echo "    <image:image><image:loc>" . htmlspecialchars($image, ENT_XML1) . "</image:loc></image:image>\n";
    }
    echo "  </url>\n";
}

/* Kurasa tuli: lastmod haijawekwa kwa makusudi — tarehe ya uongo (leo kila siku) inafanya Google ipuuze lastmod yote */
$static = ['', 'tours', 'migration', 'calving-season', 'migration/river-crossings', 'migration/where-is-the-migration-now', 'best-time-to-visit-tanzania', 'green-season-safari', 'christmas-new-year-safari', 'mountain-trekking',
           'kilimanjaro', 'kilimanjaro/lemosho-route', 'kilimanjaro/machame-route', 'kilimanjaro/marangu-route', 'kilimanjaro/rongai-route',
           'kilimanjaro/northern-circuit', 'kilimanjaro/umbwe-route', 'kilimanjaro/best-time-to-climb', 'kilimanjaro/packing-list',
           'kilimanjaro/success-rate-and-acclimatization', 'kilimanjaro/training-plan', 'kilimanjaro/cost-and-whats-included', 'mount-meru',
           'tanzania-safari-packages', 'tanzania-vs-kenya-safari', 'tanzania-safari-for-first-timers', 'how-to-choose-a-tanzania-safari-company',
           'safari/honeymoon', 'safari/family', 'safari/budget', 'safari/camping', 'safari/luxury', 'safari/fly-in', 'safari/balloon', 'safari/zanzibar-combo',
           'safari/kenya-tanzania-combo', 'safari/group', 'safari/2-day', 'safari/3-day', 'safari/4-day', 'safari/5-day', 'safari/6-day', 'safari/7-day', 'safari/8-day',
           'parks/manyara', 'parks/arusha-national-park', 'parks/northern-serengeti', 'parks/western-serengeti', 'parks/ruaha', 'parks/nyerere',
           'cultural-tours', 'cultural-tours/maasai-village-visit', 'cultural-tours/hadzabe-datoga-lake-eyasi', 'cultural-tours/materuni-waterfalls-coffee-tour',
           'cultural-tours/kikuletwa-hot-springs', 'cultural-tours/ethical-maasai-village-visit', 'cultural-tours/day-trips-from-arusha', 'destinations',
           'destination/serengeti', 'destination/ngorongoro', 'destination/kilimanjaro',
           'destination/zanzibar', 'destination/tarangire', 'destination/maasai-heartland',
           'faq', 'blog', 'reviews', 'about', 'gallery', 'contact'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php
foreach ($static as $p) xmlUrl($base . '/' . $p);
foreach ($tours as $t) xmlUrl($base . '/tour/' . $t['slug'], xmlDate($t['created_at']), $t['image'] ?? '');
foreach ($posts as $p) xmlUrl($base . '/blog/' . $p['slug'], xmlDate($p['created_at']), $p['image'] ?? '');
?>
</urlset>
