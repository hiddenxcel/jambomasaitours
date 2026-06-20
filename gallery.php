<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Safari Photo Gallery | Tanzania Wildlife & Landscapes — Jambo Masai Tours';
$pageDescription = 'Stunning safari photography from our Tanzania tours. Wildlife, landscapes, Maasai culture, Zanzibar beaches and luxury camps.';
$currentPage     = 'gallery';
$canonicalUrl    = SITE_URL . '/gallery.php';

$db         = getDB();
$gallery    = $db->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll();
$categories = $db->query("SELECT DISTINCT category FROM gallery ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$extraCss = '
  .gallery-masonry{columns:3;column-gap:10px}
  @media(max-width:767px){.gallery-masonry{columns:2}}
  @media(max-width:480px){.gallery-masonry{columns:1}}
  .gallery-item{break-inside:avoid;margin-bottom:10px;border-radius:12px;overflow:hidden;cursor:pointer;position:relative;display:block}
  .gallery-item img{width:100%;display:block;transition:transform .5s cubic-bezier(.4,0,.2,1)}
  .gallery-item:hover img{transform:scale(1.05)}
  .gallery-item-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;font-size:1.4rem}
  .gallery-item:hover .gallery-item-overlay{opacity:1}
  #lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.96);display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity .3s}
  #lightbox.open{opacity:1;pointer-events:all}
  #lightbox img{max-width:100%;max-height:90vh;border-radius:10px;object-fit:contain}
  #lb-close{position:absolute;top:1.5rem;right:1.5rem;color:#fff;background:rgba(255,255,255,.1);width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;font-size:1.2rem;transition:background .2s}
  #lb-close:hover{background:rgba(255,255,255,.2)}
  .filter-btn.active{background:linear-gradient(135deg,#5e3611,#a05e22) !important;color:#fff !important;border-color:transparent !important;box-shadow:0 4px 14px rgba(160,94,34,.3)}
';
require_once 'includes/dark_header.php';
?>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_SERENGETI ?>" alt="Gallery" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.35">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">Gallery</span>
    </nav>
    <div class="section-tag">Photo Gallery</div>
    <h1 class="font-heading text-white font-bold" style="font-size:clamp(2rem,5vw,3rem)">
      Wildlife &amp; <span class="hero-grad">Wonders</span>
    </h1>
    <p class="text-white/50 mt-2 text-[.92rem]">Moments captured across Tanzania's most breathtaking landscapes.</p>
  </div>
</div>

<!-- GALLERY -->
<section class="py-14 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">

    <!-- Filter tabs -->
    <div class="flex flex-wrap gap-2 justify-center mb-10 reveal">
      <button class="filter-btn font-nav text-[.68rem] font-semibold px-4 py-2 rounded-full border border-white/10 text-white/60 hover:text-white active" style="background:rgba(255,255,255,.05)" data-cat="all" onclick="filterGallery(this,'all')">
        All Photos <?php if (!empty($gallery)): ?>(<?= count($gallery) ?>)<?php endif; ?>
      </button>
      <?php foreach ($categories as $cat): ?>
      <button class="filter-btn font-nav text-[.68rem] font-semibold px-4 py-2 rounded-full border border-white/10 text-white/60 hover:text-white" style="background:rgba(255,255,255,.05)"
              data-cat="<?= e($cat) ?>" onclick="filterGallery(this,'<?= e($cat) ?>')">
        <?= e(ucwords(str_replace('-', ' ', $cat))) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Masonry grid -->
    <?php if (empty($gallery)): ?>
    <div class="text-center py-20">
      <i class="fas fa-images text-white/20 text-5xl mb-4"></i>
      <p class="text-white/40">No gallery images yet. Check back soon!</p>
    </div>
    <?php else: ?>
    <div class="gallery-masonry reveal" id="gallery-grid">
      <?php foreach ($gallery as $g): ?>
      <div class="gallery-item" data-category="<?= e($g['category']) ?>"
           onclick="openLb('<?= addslashes(e($g['image'])) ?>','<?= addslashes(e($g['title'])) ?>')">
        <img src="<?= e($g['image']) ?>" alt="<?= e($g['title']) ?>" loading="lazy" width="600" height="400">
        <div class="gallery-item-overlay"><i class="fas fa-search-plus text-white/80"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- CTA -->
<section class="py-16 px-4 lg:px-0 text-center">
  <div class="max-w-lg mx-auto reveal">
    <div class="section-tag">Your Story Awaits</div>
    <h2 class="font-heading text-white text-3xl font-bold mt-1 mb-4">
      Create Your Own <span class="hero-grad">Safari Memories</span>
    </h2>
    <p class="text-white/50 text-[.92rem] mb-7 leading-relaxed">These photographs were taken by our guests. Your adventure — and your perfect shot — is waiting.</p>
    <a href="<?= url('booking.php') ?>" class="btn-em btn-em-primary"><i class="fas fa-compass text-xs"></i> Book a Safari</a>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox">
  <button id="lb-close" onclick="closeLb()" aria-label="Close"><i class="fas fa-times"></i></button>
  <img id="lb-img" src="" alt="">
</div>

<?php
$pageScript = <<<'JS'
<script>
  window.openLb = (src, alt) => {
    const lb = document.getElementById('lightbox'), img = document.getElementById('lb-img');
    if (!lb || !img) return;
    img.src = src; img.alt = alt;
    lb.classList.add('open'); document.body.style.overflow = 'hidden';
  };
  window.closeLb = () => {
    const lb = document.getElementById('lightbox');
    lb && lb.classList.remove('open'); document.body.style.overflow = '';
  };
  document.getElementById('lightbox') && document.getElementById('lightbox').addEventListener('click', e => { if (e.target === document.getElementById('lightbox')) closeLb(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLb(); });

  window.filterGallery = (btn, cat) => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.gallery-item').forEach(item => {
      item.style.display = (cat === 'all' || item.dataset.category === cat) ? '' : 'none';
    });
  };
</script>
JS;
require_once 'includes/dark_footer.php';
?>
