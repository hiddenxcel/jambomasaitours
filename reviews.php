<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Guest Reviews — Google & TripAdvisor | Jambo Masai Tours';
$pageDescription = 'Read verified guest reviews of Jambo Masai Tours from Google and TripAdvisor. See what travellers say about our Tanzania safaris, and share your own experience.';
$currentPage     = 'reviews';
$ogImage         = IMG_HERO;
$canonicalUrl    = SITE_URL . '/reviews';

$db = getDB();
$testimonials = $db->query("SELECT * FROM testimonials WHERE approved = 1 ORDER BY created_at DESC")->fetchAll();
if (empty($testimonials)) {
    $testimonials = [
        ['customer_name'=>'Sarah M.','country'=>'United Kingdom','rating'=>5,'review'=>'An absolutely life-changing experience. Our guide Joseph knew every animal by name. The Serengeti at sunrise is something I will never forget.','photo'=>'','source'=>'google','tour_name'=>'','created_at'=>date('Y-m-d')],
        ['customer_name'=>'David K.','country'=>'United States', 'rating'=>5,'review'=>'Jambo Masai exceeded every expectation. The camp was luxurious, the food incredible, and the wildlife encounters were beyond anything I imagined.','photo'=>'','source'=>'tripadvisor','tour_name'=>'','created_at'=>date('Y-m-d')],
    ];
}

$googleReviewUrl = getSetting('google_review_url', '');
$tripadvisorUrl  = getSetting('social_tripadvisor', '');
$reviewAvgRating = 4.9;
$reviewTotalCount = max(count($testimonials), 120);
$googleCount = count(array_filter($testimonials, fn($t) => ($t['source'] ?? '') === 'google'));
$tripCount   = count(array_filter($testimonials, fn($t) => ($t['source'] ?? '') === 'tripadvisor'));
$safariCount = count(array_filter($testimonials, fn($t) => ($t['source'] ?? '') === 'safaribookings'));
$safariBookingsUrl = 'https://www.safaribookings.com/p6419';

$_rFav = getSetting('favicon_url') ?: getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');
$headExtra = '<link rel="icon" type="image/png" href="' . e($_rFav) . '">
<link rel="shortcut icon" href="' . e($_rFav) . '">
<link rel="apple-touch-icon" href="' . e($_rFav) . '">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "name": "Jambo Masai Tours",
  "url": "' . SITE_URL . '",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "' . $reviewAvgRating . '",
    "reviewCount": "' . $reviewTotalCount . '"
  },
  "review": [' . implode(',', array_map(function($t) {
      return json_encode([
          '@type' => 'Review',
          'author' => ['@type' => 'Person', 'name' => strip_tags($t['customer_name'])],
          'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (int)$t['rating'], 'bestRating' => 5],
          'reviewBody' => strip_tags($t['review']),
      ]);
  }, array_slice($testimonials, 0, 10))) . ']
}
</script>';
require_once 'includes/dark_header.php';
?>

<style>
  .stars{color:#a05e22;letter-spacing:.1em;font-size:.85rem}
  .btn-gold{display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;padding:.8rem 2rem;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer}
  .btn-gold:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(160,94,34,.4)}
  .rev-hero-badge{display:flex;align-items:center;gap:1rem;background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:1.1rem 1.5rem}
  .rev-platform-badge{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .rev-filter-btn{font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:.6rem 1.2rem;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:rgba(255,255,255,.55);cursor:pointer;transition:all .25s;white-space:nowrap}
  .rev-filter-btn:hover{border-color:rgba(160,94,34,.4);color:#fff}
  .rev-filter-btn.active{background:linear-gradient(135deg,#a05e22,#7d4817);border-color:transparent;color:#fff;box-shadow:0 8px 24px rgba(160,94,34,.3)}
  .rev-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:1.6rem;transition:all .35s cubic-bezier(.4,0,.2,1);opacity:0;transform:translateY(16px)}
  .rev-card.visible{opacity:1;transform:translateY(0)}
  .rev-card:hover{transform:translateY(-5px);border-color:rgba(160,94,34,.28);box-shadow:0 20px 45px rgba(0,0,0,.3)}
  .rev-source-chip{display:inline-flex;align-items:center;gap:.35rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.04em;padding:.3rem .6rem;border-radius:999px}
  .rev-source-chip.google{background:rgba(66,133,244,.12);color:#8ab4f8}
  .rev-source-chip.tripadvisor{background:rgba(52,224,161,.12);color:#34e0a1}
  .rev-source-chip.safaribookings{background:rgba(249,115,22,.12);color:#f97316}
  .rev-write-btn{position:relative;overflow:hidden}
  .rev-write-btn::after{content:'';position:absolute;top:0;left:-60%;width:45%;height:100%;background:linear-gradient(115deg,transparent 0%,rgba(255,255,255,.35) 50%,transparent 100%);transform:skewX(-20deg);animation:revShine 3.4s ease-in-out infinite}
  @keyframes revShine{0%{left:-60%}35%,100%{left:130%}}
  @media(max-width:640px){.rev-hero-badge{flex-direction:column;text-align:center}}
</style>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_HERO ?>" alt="Jambo Masai Tours guest reviews" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.3">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.6),transparent 60%)"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">Reviews</span>
    </nav>
    <div class="section-tag">Guest Reviews</div>
    <h1 class="font-heading text-white font-bold leading-tight" style="font-size:clamp(2rem,5vw,3.2rem)">
      What Our Travellers <span class="hero-grad">Are Saying</span>
    </h1>
    <p class="text-white/50 mt-3 max-w-xl text-[.95rem]">Real experiences from real guests — verified across Google and TripAdvisor.</p>
  </div>
</div>

<!-- RATING SUMMARY -->
<section class="py-14 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl mx-auto reveal">
      <div class="rev-hero-badge">
        <div class="rev-platform-badge" style="background:#fff">
          <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 justify-center sm:justify-start">
            <span class="font-heading text-2xl font-bold text-white"><?= number_format($reviewAvgRating,1) ?></span>
            <span class="stars text-sm">★★★★★</span>
          </div>
          <div class="text-white/40 text-[.75rem] font-nav mt-0.5">Google Reviews · <?= $reviewTotalCount ?>+ ratings</div>
        </div>
        <?php if ($googleReviewUrl): ?>
        <a href="<?= e($googleReviewUrl) ?>" target="_blank" rel="noopener" class="rev-write-btn btn-gold" style="padding:.6rem 1.1rem;font-size:.65rem">
          Write a Review
        </a>
        <?php endif; ?>
      </div>
      <div class="rev-hero-badge">
        <div class="rev-platform-badge" style="background:#34e0a1">
          <img src="<?= url('assets/images/tripadvisor-icon.svg') ?>" alt="" style="width:22px;height:22px;filter:brightness(0)">
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 justify-center sm:justify-start">
            <span class="font-heading text-2xl font-bold text-white">4.9</span>
            <span class="stars text-sm">★★★★★</span>
          </div>
          <div class="text-white/40 text-[.75rem] font-nav mt-0.5">TripAdvisor · Certificate of Excellence</div>
        </div>
        <div id="TA_socialButtonBubbles617" class="TA_socialButtonBubbles">
          <ul id="4NlwYtynPy" class="TA_links T3la4ds4k">
            <li id="Aa6935" class="DKFnzV">
              <a target="_blank" rel="noopener" href="https://www.tripadvisor.com/Attraction_Review-g297913-d34602506-Reviews-Jambo_Masai_Tours-Arusha_Arusha_Region.html">
                <img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_logomark.svg" alt="TripAdvisor" width="28" height="28">
              </a>
            </li>
          </ul>
        </div>
        <script async src="https://www.jscache.com/wejs?wtype=socialButtonBubbles&amp;uniq=617&amp;locationId=34602506&amp;color=green&amp;size=rect&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
      </div>
    </div>
  </div>
</section>

<!-- FILTERS -->
<section class="px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-center gap-2.5 mb-10 reveal">
      <button class="rev-filter-btn active" data-filter="all">All Reviews (<?= count($testimonials) ?>)</button>
      <button class="rev-filter-btn" data-filter="google"><i class="fab fa-google mr-1"></i> Google (<?= $googleCount ?>)</button>
      <button class="rev-filter-btn" data-filter="tripadvisor"><img src="<?= url('assets/images/tripadvisor-icon.svg') ?>" alt="" class="inline-block w-3 h-3 mr-1" style="vertical-align:-1px"> TripAdvisor (<?= $tripCount ?>)</button>
      <?php if ($safariCount): ?>
      <button class="rev-filter-btn" data-filter="safaribookings"><img src="<?= url('assets/images/safaribookings-icon.png') ?>" alt="" class="inline-block w-3 h-3 mr-1" style="vertical-align:-1px"> SafariBookings (<?= $safariCount ?>)</button>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- REVIEWS GRID -->
<section class="pb-20 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="reviews-grid">
      <?php foreach ($testimonials as $ti => $t):
        $tSource = $t['source'] ?? 'site';
      ?>
      <div class="rev-card" data-source="<?= e($tSource) ?>" style="transition-delay:<?= min($ti,8) * 60 ?>ms">
        <div class="flex items-center justify-between mb-4">
          <div class="stars text-sm"><?= str_repeat('★',(int)$t['rating']) ?><?= str_repeat('☆',5-(int)$t['rating']) ?></div>
          <?php if ($tSource === 'google'): ?>
          <span class="rev-source-chip google"><i class="fab fa-google"></i> Google</span>
          <?php elseif ($tSource === 'tripadvisor'): ?>
          <span class="rev-source-chip tripadvisor"><img src="<?= url('assets/images/tripadvisor-icon.svg') ?>" alt="" class="inline-block w-3 h-3" style="vertical-align:-1px"> TripAdvisor</span>
          <?php elseif ($tSource === 'safaribookings'): ?>
          <span class="rev-source-chip safaribookings"><img src="<?= url('assets/images/safaribookings-icon.png') ?>" alt="" class="inline-block w-3 h-3" style="vertical-align:-1px"> SafariBookings</span>
          <?php endif; ?>
        </div>
        <p class="text-white/70 text-sm leading-relaxed mb-6"><?= e($t['review']) ?></p>
        <div class="flex items-center gap-3">
          <img src="<?= e($t['photo'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($t['customer_name']) . '&background=10b981&color=fff') ?>"
               alt="<?= e($t['customer_name']) ?>" loading="lazy"
               class="w-11 h-11 rounded-full object-cover border-2 border-brand/30 flex-shrink-0">
          <div class="min-w-0">
            <div class="text-white font-semibold text-sm truncate"><?= e($t['customer_name']) ?></div>
            <div class="text-white/40 text-xs truncate"><?= e($t['country']) ?><?= !empty($t['tour_name']) ? ' · ' . e($t['tour_name']) : '' ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p id="reviews-empty" class="text-center text-white/40 text-sm py-16" style="display:none">No reviews found for this filter yet.</p>
  </div>
</section>

<!-- CTA -->
<section class="py-20 px-4 lg:px-0 text-center">
  <div class="max-w-xl mx-auto reveal">
    <div class="section-tag">Share Your Story</div>
    <h2 class="font-heading text-white text-3xl lg:text-4xl font-bold mt-1 mb-5">
      Been on Safari <span class="hero-grad">With Us?</span>
    </h2>
    <p class="text-white/50 leading-relaxed mb-8">Your review helps future travellers discover Tanzania the right way — and means the world to our team and guides.</p>
    <div class="flex flex-wrap gap-3 justify-center">
      <?php if ($googleReviewUrl): ?>
      <a href="<?= e($googleReviewUrl) ?>" target="_blank" rel="noopener" class="rev-write-btn btn-em btn-em-primary">
        <i class="fab fa-google text-xs"></i> Review on Google
      </a>
      <?php endif; ?>
      <div id="TA_cdswritereviewlg871" class="TA_cdswritereviewlg" style="display:inline-flex;align-items:center;height:44px">
        <ul id="lRPGt5" class="TA_links khCDy8uf" style="margin:0;padding:0;list-style:none">
          <li id="2J9YE7xxP" class="NGXR8SnPCDX8">
            <a target="_blank" rel="noopener" href="https://www.tripadvisor.com/Attraction_Review-g297913-d34602506-Reviews-Jambo_Masai_Tours-Arusha_Arusha_Region.html" style="display:inline-flex">
              <img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" alt="Write a review on TripAdvisor" style="height:44px;width:auto">
            </a>
          </li>
        </ul>
      </div>
      <script async src="https://www.jscache.com/wejs?wtype=cdswritereviewlg&amp;uniq=871&amp;locationId=34602506&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
      <a href="<?= e($safariBookingsUrl) ?>" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 font-nav font-semibold text-[.8rem] px-6 py-3 rounded-xl transition-all hover:scale-105"
         style="color:#f97316;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25)">
        <img src="<?= url('assets/images/safaribookings-icon.png') ?>" alt="" class="inline-block w-3 h-3"> Review us on SafariBookings
      </a>
      <a href="<?= url('tours') ?>" class="btn-em btn-em-outline">Browse Safaris</a>
    </div>
  </div>
</section>

<?php
$pageScript = <<<'JS'
<script>
(function(){
  /* Reveal-on-scroll for review cards (separate from the site-wide .reveal
     observer so cards can stagger their own transition-delay cleanly) */
  const cardObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); cardObs.unobserve(entry.target); }
    });
  }, { threshold: .15 });
  document.querySelectorAll('.rev-card').forEach(c => cardObs.observe(c));

  /* Filter buttons */
  const filterBtns = document.querySelectorAll('.rev-filter-btn');
  const cards = document.querySelectorAll('.rev-card');
  const emptyMsg = document.getElementById('reviews-empty');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;
      let visibleCount = 0;
      cards.forEach(card => {
        const show = filter === 'all' || card.dataset.source === filter;
        card.style.display = show ? '' : 'none';
        if (show) { visibleCount++; card.classList.add('visible'); }
      });
      emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    });
  });
})();
</script>
JS;
require_once 'includes/dark_footer.php';
?>
