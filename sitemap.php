<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$base = rtrim(SITE_URL, '/');

/* Fetch published tours */
$tours = [];
try {
    $tours = getDB()->query("SELECT slug, created_at FROM tours ORDER BY featured DESC, rating DESC")->fetchAll();
} catch (\Throwable $e) {}

/* Fetch published blog posts */
$posts = [];
try {
    $posts = getDB()->query("SELECT slug, created_at FROM blog_posts WHERE published=1 ORDER BY created_at DESC")->fetchAll();
} catch (\Throwable $e) {}

function xmlDate(?string $d): string {
    if (!$d) return date('Y-m-d');
    return date('Y-m-d', strtotime($d));
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- Static pages -->
  <url>
    <loc><?= $base ?>/</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/tours</loc>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/mountain-trekking.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.85</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/destinations.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>

  <!-- Individual destination pages -->
  <?php foreach (['serengeti','ngorongoro','kilimanjaro','zanzibar','tarangire','maasai-heartland'] as $ds): ?>
  <url>
    <loc><?= $base ?>/destination/<?= $ds ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.85</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <?php endforeach; ?>
  <url>
    <loc><?= $base ?>/faq.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.75</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/blog.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/booking.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.85</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/about.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/gallery.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <url>
    <loc><?= $base ?>/contact.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>

  <!-- Dynamic tour pages -->
  <?php foreach ($tours as $tour): ?>
  <url>
    <loc><?= $base ?>/tour/<?= htmlspecialchars($tour['slug'], ENT_XML1) ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
    <lastmod><?= xmlDate($tour['created_at']) ?></lastmod>
  </url>
  <?php endforeach; ?>

  <!-- Dynamic blog posts -->
  <?php foreach ($posts as $post): ?>
  <url>
    <loc><?= $base ?>/blog/<?= htmlspecialchars($post['slug'], ENT_XML1) ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
    <lastmod><?= xmlDate($post['created_at']) ?></lastmod>
  </url>
  <?php endforeach; ?>

</urlset>
