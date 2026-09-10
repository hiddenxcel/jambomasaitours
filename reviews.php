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

  /* Card text clamp + read-more trigger */
  .rev-card{display:flex;flex-direction:column;cursor:pointer}
  .rev-card-text{display:-webkit-box;-webkit-line-clamp:5;line-clamp:5;-webkit-box-orient:vertical;overflow:hidden}
  .rev-readmore{display:none;align-items:center;gap:.35rem;background:none;border:none;padding:0;margin-bottom:0;font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.03em;color:#c17f3f;cursor:pointer;transition:gap .2s,color .2s}
  .rev-readmore:hover{gap:.55rem;color:#e0a468}
  .rev-card.has-overflow .rev-readmore{display:inline-flex}

  /* Modal */
  .rev-modal-overlay{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:1.25rem;background:rgba(8,8,8,.72);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s ease}
  .rev-modal-overlay.open{opacity:1;pointer-events:auto}
  .rev-modal-frame{display:flex;align-items:center;gap:1rem;width:100%;max-width:760px}
  .rev-modal{position:relative;flex:1;min-width:0;width:100%;max-width:620px;max-height:85vh;overflow-y:auto;background:#171310;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;transform:translateY(24px) scale(.97);opacity:0;transition:transform .38s cubic-bezier(.2,.9,.3,1.3),opacity .3s ease}
  .rev-modal-overlay.open .rev-modal{transform:translateY(0) scale(1);opacity:1}
  .rev-modal-close{position:absolute;top:1rem;right:1rem;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;font-size:.85rem}
  .rev-modal-close:hover{background:rgba(255,255,255,.12);color:#fff;transform:rotate(90deg)}
  .rev-modal-body{transition:opacity .18s ease}
  .rev-modal-body.swapping{opacity:0}
  .rev-modal-text{white-space:pre-line}
  .rev-modal-nav{flex-shrink:0;width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;opacity:0;pointer-events:none}
  .rev-modal-nav.open{opacity:1;pointer-events:auto}
  .rev-modal-nav:hover{background:rgba(160,94,34,.5);border-color:transparent;transform:scale(1.08)}
  @media(max-width:820px){
    .rev-modal-frame{gap:.5rem}
    .rev-modal-nav{width:40px;height:40px}
  }
  @media(max-width:640px){
    .rev-modal-frame{position:relative}
    .rev-modal-nav{position:absolute;top:.85rem;width:34px;height:34px;background:rgba(255,255,255,.1);z-index:2}
    .rev-modal-nav.prev{left:1rem}
    .rev-modal-nav.next{left:3.3rem;right:auto}
    .rev-modal{padding:1.4rem;padding-top:3.6rem;max-height:80vh}
  }
  body.rev-modal-lock{overflow:hidden}
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
      <div class="rev-card" data-source="<?= e($tSource) ?>" data-review-index="<?= $ti ?>" style="transition-delay:<?= min($ti,8) * 60 ?>ms">
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
        <p class="rev-card-text text-white/70 text-sm leading-relaxed mb-2"><?= e($t['review']) ?></p>
        <button type="button" class="rev-readmore" data-open-review="<?= $ti ?>">Read full review <i class="fas fa-arrow-right" style="font-size:.6rem"></i></button>
        <div class="flex items-center gap-3 mt-4">
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

<!-- REVIEW MODAL -->
<div class="rev-modal-overlay" id="rev-modal-overlay">
  <div class="rev-modal-frame">
    <div class="rev-modal-nav prev" id="rev-modal-prev" aria-label="Previous review"><i class="fas fa-chevron-left"></i></div>
    <div class="rev-modal" role="dialog" aria-modal="true" aria-labelledby="rev-modal-name">
    <button type="button" class="rev-modal-close" id="rev-modal-close" aria-label="Close"><i class="fas fa-times"></i></button>
    <div class="rev-modal-body" id="rev-modal-body">
      <div class="flex items-center justify-between mb-4 pr-8">
        <div class="stars text-base" id="rev-modal-stars"></div>
        <span id="rev-modal-chip"></span>
      </div>
      <p class="rev-modal-text text-white/80 text-[.95rem] leading-relaxed mb-6" id="rev-modal-text"></p>
      <div class="flex items-center gap-3">
        <img id="rev-modal-photo" src="" alt="" class="w-12 h-12 rounded-full object-cover border-2 border-brand/30 flex-shrink-0">
        <div class="min-w-0">
          <div class="text-white font-semibold text-sm" id="rev-modal-name"></div>
          <div class="text-white/40 text-xs" id="rev-modal-meta"></div>
        </div>
      </div>
    </div>
    </div>
    <div class="rev-modal-nav next" id="rev-modal-next" aria-label="Next review"><i class="fas fa-chevron-right"></i></div>
  </div>
</div>

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

<script type="application/json" id="reviews-data"><?= json_encode(array_map(function($t) {
    return [
        'name'    => strip_tags($t['customer_name']),
        'country' => strip_tags($t['country']),
        'rating'  => (int)$t['rating'],
        'review'  => strip_tags($t['review']),
        'tour'    => strip_tags($t['tour_name'] ?? ''),
        'source'  => $t['source'] ?? 'site',
        'photo'   => $t['photo'] ?: ('https://ui-avatars.com/api/?name=' . urlencode($t['customer_name']) . '&background=10b981&color=fff'),
    ];
}, $testimonials), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

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

  /* Mark cards whose text is actually clamped so "Read full review" only shows when needed */
  function markOverflow() {
    document.querySelectorAll('.rev-card').forEach(card => {
      const p = card.querySelector('.rev-card-text');
      if (p && p.scrollHeight > p.clientHeight + 2) card.classList.add('has-overflow');
      else card.classList.remove('has-overflow');
    });
  }
  markOverflow();
  window.addEventListener('resize', markOverflow);

  /* ---- Review modal with prev/next navigation ---- */
  const reviewsData = JSON.parse(document.getElementById('reviews-data').textContent || '[]');
  const overlay   = document.getElementById('rev-modal-overlay');
  const body      = document.getElementById('rev-modal-body');
  const btnPrev   = document.getElementById('rev-modal-prev');
  const btnNext   = document.getElementById('rev-modal-next');
  const btnClose  = document.getElementById('rev-modal-close');
  const sourceLabel = { google: 'Google', tripadvisor: 'TripAdvisor', safaribookings: 'SafariBookings' };
  let currentIndex = 0;

  function visibleIndices() {
    const idxs = [];
    cards.forEach(card => { if (card.style.display !== 'none') idxs.push(parseInt(card.dataset.reviewIndex, 10)); });
    return idxs;
  }

  function renderModal(idx) {
    const t = reviewsData[idx];
    if (!t) return;
    document.getElementById('rev-modal-stars').textContent = '★'.repeat(t.rating) + '☆'.repeat(5 - t.rating);
    document.getElementById('rev-modal-text').textContent = t.review;
    document.getElementById('rev-modal-photo').src = t.photo;
    document.getElementById('rev-modal-photo').alt = t.name;
    document.getElementById('rev-modal-name').textContent = t.name;
    document.getElementById('rev-modal-meta').textContent = t.country + (t.tour ? ' · ' + t.tour : '');
    const chipEl = document.getElementById('rev-modal-chip');
    if (sourceLabel[t.source]) {
      chipEl.innerHTML = '<span class="rev-source-chip ' + t.source + '">' + sourceLabel[t.source] + '</span>';
    } else {
      chipEl.innerHTML = '';
    }
  }

  function goTo(idx) {
    const idxs = visibleIndices();
    if (!idxs.includes(idx)) idx = idxs[0] ?? idx;
    currentIndex = idx;
    body.classList.add('swapping');
    setTimeout(() => { renderModal(currentIndex); body.classList.remove('swapping'); }, 140);
  }

  function step(dir) {
    const idxs = visibleIndices();
    if (!idxs.length) return;
    const pos = idxs.indexOf(currentIndex);
    const nextPos = (pos + dir + idxs.length) % idxs.length;
    goTo(idxs[nextPos]);
  }

  function openModal(idx) {
    currentIndex = idx;
    renderModal(idx);
    overlay.classList.add('open');
    btnPrev.classList.add('open');
    btnNext.classList.add('open');
    document.body.classList.add('rev-modal-lock');
  }
  function closeModal() {
    overlay.classList.remove('open');
    btnPrev.classList.remove('open');
    btnNext.classList.remove('open');
    document.body.classList.remove('rev-modal-lock');
  }

  document.querySelectorAll('[data-open-review]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openModal(parseInt(btn.dataset.openReview, 10));
    });
  });
  cards.forEach(card => {
    card.addEventListener('click', () => openModal(parseInt(card.dataset.reviewIndex, 10)));
  });

  btnPrev.addEventListener('click', () => step(-1));
  btnNext.addEventListener('click', () => step(1));
  btnClose.addEventListener('click', closeModal);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', (e) => {
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'Escape') closeModal();
    if (e.key === 'ArrowLeft') step(-1);
    if (e.key === 'ArrowRight') step(1);
  });
})();
</script>
JS;
require_once 'includes/dark_footer.php';
?>
