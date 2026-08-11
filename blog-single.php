<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { redirect(url('blog')); }

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND published = 1 LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); redirect(url('blog')); }

$relStmt = $db->prepare("SELECT id,title,slug,image,excerpt,created_at FROM blog_posts WHERE published=1 AND id != ? ORDER BY created_at DESC LIMIT 3");
$relStmt->execute([$post['id']]);
$related = $relStmt->fetchAll();

/* Ongeza chapa pale tu kichwa ni kifupi vya kutosha; vinginevyo Google
   inakata (>60). Kichwa cha makala chenyewe ndicho muhimu zaidi. */
$pageTitle       = mb_strlen($post['title'], 'UTF-8') <= 38
                 ? $post['title'] . ' | Jambo Masai Tours'
                 : truncate($post['title'], 60);
$pageDescription = truncate(strip_tags($post['excerpt']), 160);
$ogImage         = $post['image'];
$currentPage     = 'blog';
$canonicalUrl    = SITE_URL . '/blog/' . $post['slug'];
$ogType          = 'article';

$shareUrl   = urlencode($canonicalUrl);
$shareTitle = urlencode($post['title']);

$logoUrl_seo = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$postDate    = date('c', strtotime($post['created_at']));
$postCat     = $post['category'] ?? 'Safari Tips';

$wordCount   = str_word_count(strip_tags($post['content']));
$readingTime = max(1, (int)ceil($wordCount / 200));

$_bFav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$headExtra = '
  <link rel="icon" type="image/png" href="' . e($_bFav) . '">
  <link rel="shortcut icon" href="' . e($_bFav) . '">
  <link rel="apple-touch-icon" href="' . e($_bFav) . '">
  <meta property="article:published_time" content="' . $postDate . '">
  <meta property="article:author"         content="' . e($post['author']) . '">
  <meta property="article:section"        content="' . e($postCat) . '">
  <meta property="article:tag"            content="safari, tanzania, ' . e(strtolower($postCat)) . '">
  <script type="application/ld+json">
  [
    {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      "headline": ' . json_encode($post['title']) . ',
      "description": ' . json_encode($pageDescription) . ',
      "image": {
        "@type": "ImageObject",
        "url": ' . json_encode($post['image']) . ',
        "width": 1200,
        "height": 630
      },
      "author": {
        "@type": "Person",
        "name": ' . json_encode($post['author']) . '
      },
      "publisher": {
        "@type": "Organization",
        "name": "Jambo Masai Tours",
        "logo": { "@type": "ImageObject", "url": ' . json_encode($logoUrl_seo) . ' }
      },
      "datePublished": "' . $postDate . '",
      "url": "' . $canonicalUrl . '",
      "keywords": ' . json_encode('safari, tanzania, ' . $postCat) . ',
      "mainEntityOfPage": { "@type": "WebPage", "@id": "' . $canonicalUrl . '" }
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "' . SITE_URL . '" },
        { "@type": "ListItem", "position": 2, "name": "Blog", "item": "' . SITE_URL . '/blog" },
        { "@type": "ListItem", "position": 3, "name": ' . json_encode($post['title']) . ' }
      ]
    }
  ]
  </script>
';

/* Mtindo wa `.prose` (pamoja na .checklist, .tip-box, .faq-item n.k.) uko
   kwenye assets/css/article.css — faili moja inayotumika pia na preview ya
   admin, ili post mpya yoyote ipangwe vizuri bila kuongeza CSS hapa. */
$headExtra .= '<link rel="stylesheet" href="' . e(SITE_URL) . '/assets/css/article.css?v=' .
              @filemtime(__DIR__ . '/assets/css/article.css') . '">';

$extraCss = '
  .related-card{background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:12px;overflow:hidden;transition:all .3s;text-decoration:none;display:block}
  .related-card:hover{border-color:rgba(160,94,34,.25);transform:translateY(-3px)}
  .related-card img{width:100%;height:140px;object-fit:cover;transition:transform .5s}
  .related-card:hover img{transform:scale(1.04)}

  /* Article hero */
  .post-hero{position:relative;isolation:isolate;overflow:hidden;min-height:clamp(380px,48vh,520px);display:flex;align-items:flex-end}
  .post-hero__scrim{position:absolute;inset:0;background:linear-gradient(180deg,rgba(6,10,9,.55) 0%,rgba(6,10,9,.08) 20%,rgba(6,10,9,.18) 42%,rgba(6,10,9,.6) 80%,rgba(6,10,9,.95) 100%)}
  .post-hero__vignette{position:absolute;inset:0;background:linear-gradient(90deg,rgba(5,8,7,.3) 0%,transparent 30%,transparent 70%,rgba(5,8,7,.3) 100%)}
  .post-hero__inner{position:relative;z-index:1;width:100%;min-width:0;padding:clamp(5rem,13vw,7rem) 0 clamp(1.75rem,4vw,2.75rem)}
  .post-breadcrumb ol{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem;list-style:none;padding:0;margin:0 0 1.25rem}
  .post-breadcrumb a{color:rgba(255,255,255,.45);text-decoration:none;transition:color .2s}
  .post-breadcrumb a:hover,.post-breadcrumb a:focus-visible{color:#c17a3a}
  .post-breadcrumb li[aria-current]{color:rgba(255,255,255,.85);max-width:38ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .post-breadcrumb .sep{color:rgba(255,255,255,.2)}
  .post-hero__title{max-width:20ch;text-wrap:balance;overflow-wrap:break-word;text-shadow:0 4px 30px rgba(0,0,0,.4)}
  .post-hero__avatar{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;font-family:"Montserrat",sans-serif;font-weight:700;font-size:.7rem;flex-shrink:0}
  .post-hero__dot{width:3px;height:3px;border-radius:50%;background:rgba(255,255,255,.25);flex-shrink:0}
  .post-hero__scroll{position:absolute;left:50%;bottom:.85rem;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:.4rem;color:rgba(255,255,255,.4);text-decoration:none;z-index:2;transition:color .25s}
  .post-hero__scroll:hover,.post-hero__scroll:focus-visible{color:#c17a3a}
  .post-hero__scroll span{width:1px;height:22px;background:linear-gradient(to bottom,rgba(255,255,255,.5),transparent)}
  .post-hero__scroll i{font-size:.65rem;animation:heroBounce 2.4s ease-in-out infinite}
  @keyframes heroBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(5px)}}
  @media (prefers-reduced-motion:reduce){.post-hero__scroll i,.ken-burns{animation:none}}
  @media (max-width:640px){.post-hero__scroll{display:none}}
';
require_once 'includes/dark_header.php';
?>

<!-- ARTICLE HERO -->
<section class="post-hero" aria-labelledby="post-title">
  <img src="<?= e($post['image']) ?>" alt=""
       class="absolute inset-0 w-full h-full object-cover ken-burns" fetchpriority="high">
  <div class="post-hero__scrim" aria-hidden="true"></div>
  <div class="post-hero__vignette" aria-hidden="true"></div>

  <div class="post-hero__inner max-w-4xl mx-auto px-4 lg:px-6 w-full">
    <nav aria-label="Breadcrumb" class="post-breadcrumb font-nav text-[.68rem] font-semibold uppercase tracking-wider">
      <ol>
        <li><a href="<?= url() ?>">Home</a></li>
        <li class="sep" aria-hidden="true">/</li>
        <li><a href="<?= url('blog') ?>">Blog</a></li>
        <li class="sep" aria-hidden="true">/</li>
        <li aria-current="page"><?= e(truncate($post['title'], 48)) ?></li>
      </ol>
    </nav>

    <span class="section-tag">
      <i class="fas fa-tag" aria-hidden="true"></i><?= e($postCat) ?>
    </span>

    <h1 id="post-title" class="post-hero__title font-heading text-white font-bold leading-[1.15]" style="font-size:clamp(2rem,5.5vw,3.75rem)">
      <?= e($post['title']) ?>
    </h1>

    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mt-6 mb-8 text-sm text-white/55">
      <span class="flex items-center gap-2.5">
        <span class="post-hero__avatar" aria-hidden="true"><?= e(strtoupper(substr($post['author'], 0, 1))) ?></span>
        <span><?= e($post['author']) ?></span>
      </span>
      <span class="post-hero__dot" aria-hidden="true"></span>
      <span class="flex items-center gap-2">
        <i class="far fa-calendar text-emerald-400 text-xs" aria-hidden="true"></i>
        <time datetime="<?= date('Y-m-d', strtotime($post['created_at'])) ?>"><?= formatDate($post['created_at']) ?></time>
      </span>
      <span class="post-hero__dot" aria-hidden="true"></span>
      <span class="flex items-center gap-2">
        <i class="far fa-clock text-emerald-400 text-xs" aria-hidden="true"></i>
        <?= $readingTime ?> min read
      </span>
    </div>

    <div class="flex flex-wrap gap-3">
      <a href="<?= url('booking') ?>" class="btn-em btn-em-primary">
        <i class="fas fa-paper-plane" aria-hidden="true"></i> Plan This Safari
      </a>
      <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener noreferrer" class="btn-em btn-em-wa">
        <i class="fab fa-whatsapp" aria-hidden="true"></i> Ask an Expert
      </a>
    </div>
  </div>

  <a href="#article-content" class="post-hero__scroll" aria-label="Scroll to article content">
    <span aria-hidden="true"></span>
    <i class="fas fa-chevron-down" aria-hidden="true"></i>
  </a>
</section>

<!-- ARTICLE CONTENT -->
<section id="article-content" class="py-14 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-[1fr_300px] gap-12 items-start">

      <!-- Article -->
      <article>
        <div class="prose reveal">
          <?= blogContent($post['content']) ?>
        </div>

        <!-- Share buttons -->
        <div class="mt-10 pt-8 border-t border-white/[.07] flex flex-wrap items-center gap-3 reveal">
          <span class="font-nav font-bold text-[.65rem] uppercase tracking-widest text-white/30 mr-2">Share:</span>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 font-nav font-semibold text-xs px-4 py-2 rounded-lg transition-all hover:scale-105" style="background:rgba(59,130,246,.12);color:#60a5fa;border:1px solid rgba(59,130,246,.2)">
            <i class="fab fa-facebook-f text-xs"></i> Facebook
          </a>
          <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 font-nav font-semibold text-xs px-4 py-2 rounded-lg transition-all hover:scale-105" style="background:rgba(29,161,242,.12);color:#38bdf8;border:1px solid rgba(29,161,242,.2)">
            <i class="fab fa-twitter text-xs"></i> Twitter
          </a>
          <a href="https://api.whatsapp.com/send?text=<?= $shareTitle . '+' . $shareUrl ?>" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 font-nav font-semibold text-xs px-4 py-2 rounded-lg transition-all hover:scale-105" style="background:rgba(37,211,102,.1);color:#25D366;border:1px solid rgba(37,211,102,.2)">
            <i class="fab fa-whatsapp text-sm"></i> WhatsApp
          </a>
        </div>
      </article>

      <!-- Sidebar -->
      <aside class="sticky top-24 space-y-5">
        <!-- Book CTA -->
        <div class="glass-card p-6 text-center">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:rgba(160,94,34,.12)">
            <i class="fas fa-binoculars text-emerald-400 text-xl"></i>
          </div>
          <h3 class="font-heading text-white text-lg font-bold mb-2">Ready to Go?</h3>
          <p class="text-white/45 text-sm leading-relaxed mb-5">Turn these tips into your own safari adventure.</p>
          <a href="<?= url('booking') ?>" class="btn-em btn-em-primary w-full justify-center mb-3 text-xs">
            <i class="fas fa-calendar-check text-xs"></i> Book a Safari
          </a>
          <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener" class="btn-em btn-em-wa w-full justify-center text-xs">
            <i class="fab fa-whatsapp text-base"></i> WhatsApp Us
          </a>
        </div>

        <!-- Related posts -->
        <?php if ($related): ?>
        <div class="glass-card p-5">
          <h3 class="font-nav font-bold text-[.65rem] uppercase tracking-widest text-emerald-400 mb-4">More Articles</h3>
          <div class="space-y-3">
            <?php foreach ($related as $r): ?>
            <a href="<?= url('blog/' . e($r['slug'])) ?>" class="related-card">
              <img src="<?= e($r['image']) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
              <div class="p-3">
                <h4 class="font-heading text-white text-[.88rem] font-bold leading-snug mb-1"><?= e($r['title']) ?></h4>
                <span class="text-white/30 text-[.7rem] font-nav"><?= formatDate($r['created_at']) ?></span>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </aside>

    </div>
  </div>
</section>

<?php require_once 'includes/dark_footer.php'; ?>

