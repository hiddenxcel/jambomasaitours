<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle       = 'Safari Travel Blog | Tanzania Tips & Guides — Jambo Masai Tours';
$pageDescription = 'Expert safari tips, Tanzania travel guides, packing lists, best time to visit and Zanzibar travel advice from our professional safari team.';
$currentPage     = 'blog';

$db = getDB();

/* Add category column silently if missing */
try { $db->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT 'Safari Tips'"); } catch (\Throwable $e) {}

/* Search / category filter */
$search   = sanitizeInput($_GET['search'] ?? '');
$catFilter= sanitizeInput($_GET['cat']    ?? '');

$where = ["published = 1"];
$params= [];
if ($search) {
    $where[]  = "(title LIKE :s OR excerpt LIKE :s2 OR author LIKE :s3)";
    $params[':s'] = $params[':s2'] = $params[':s3'] = "%$search%";
}
if ($catFilter) {
    $where[]  = "category = :cat";
    $params[':cat'] = $catFilter;
}
$sql   = "SELECT * FROM blog_posts WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

/* Split: featured = first post, rest = remaining */
$featured = !empty($posts) ? $posts[0] : null;
$restPosts= array_slice($posts, 1);

/* Sidebar: recent posts (regardless of filter) */
$recentPosts = $db->query("SELECT id,title,slug,image,created_at FROM blog_posts WHERE published=1 ORDER BY created_at DESC LIMIT 4")->fetchAll();

/* Reading time helper */
function readTime(string $content): int {
    return max(1, (int)ceil(str_word_count(strip_tags($content)) / 200));
}

/* Category colours */
function catStyle(string $cat): array {
    return match(strtolower($cat)) {
        'wildlife'     => ['#c17a3a','rgba(160,94,34,.15)','fa-paw'],
        'culture'      => ['#a78bfa','rgba(139,92,246,.15)','fa-users'],
        'trekking'     => ['#60a5fa','rgba(59,130,246,.15)','fa-mountain'],
        'conservation' => ['#4ade80','rgba(74,222,128,.15)','fa-leaf'],
        'zanzibar'     => ['#38bdf8','rgba(56,189,248,.15)','fa-umbrella-beach'],
        default        => ['#fbbf24','rgba(245,158,11,.15)','fa-lightbulb'],
    };
}

$categories   = ['Safari Tips','Wildlife','Culture','Trekking','Conservation','Zanzibar'];
$canonicalUrl = SITE_URL . '/blog' . ($catFilter ? '?cat=' . urlencode($catFilter) : '');

$extraCss = '
  /* Blog card */
  .bc{background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;transition:all .4s;display:block;text-decoration:none}
  .bc:hover{transform:translateY(-5px);border-color:rgba(160,94,34,.25);box-shadow:0 20px 50px rgba(0,0,0,.5)}
  .bc-img{width:100%;height:200px;object-fit:cover;transition:transform .6s;display:block}
  .bc:hover .bc-img{transform:scale(1.05)}
  /* Featured card */
  .fc{background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden;transition:all .4s;text-decoration:none;display:block}
  .fc:hover{border-color:rgba(160,94,34,.25);box-shadow:0 24px 60px rgba(0,0,0,.6)}
  .fc:hover .fc-img{transform:scale(1.03)}
  .fc-img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
  /* Filter tabs */
  .ftab{transition:all .25s;cursor:pointer;font-family:"Montserrat",sans-serif;font-size:.68rem;font-weight:600;padding:.55rem 1rem;border-radius:999px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:rgba(255,255,255,.5);text-decoration:none;display:inline-flex;align-items:center;gap:.35rem}
  .ftab:hover{background:rgba(255,255,255,.08);color:#fff}
  .ftab.active{background:rgba(160,94,34,.15);border-color:rgba(160,94,34,.35);color:#a05e22}
  /* Sidebar widgets */
  .sw{background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.25rem;margin-bottom:1rem}
  .sw h3{font-family:"Montserrat",sans-serif;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.4);margin-bottom:.85rem;display:flex;align-items:center;gap:.45rem}
  /* Search input */
  .s-inp{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:10px;padding:.65rem 1rem .65rem 2.35rem;font-family:"Inter",sans-serif;font-size:.82rem;color:#fff;outline:none;transition:border-color .2s;box-sizing:border-box}
  .s-inp:focus{border-color:rgba(160,94,34,.45)}
  .s-inp::placeholder{color:rgba(255,255,255,.2)}
  /* Category badge */
  .cbadge{display:inline-flex;align-items:center;gap:.3rem;font-family:"Montserrat",sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;padding:.22rem .65rem;border-radius:999px}
  /* Recent post item */
  .rp{display:flex;align-items:center;gap:.75rem;text-decoration:none;padding:.55rem .5rem;border-radius:10px;transition:background .2s}
  .rp:hover{background:rgba(255,255,255,.04)}
  .rp:hover .rp-title{color:#a05e22}
  .rp-title{font-size:.78rem;font-weight:600;color:rgba(255,255,255,.75);line-height:1.3;transition:color .2s}
  .rp-date{font-family:"Montserrat",sans-serif;font-size:.6rem;color:rgba(255,255,255,.28);margin-top:.15rem}
  /* Tag pills */
  .tpill{display:inline-flex;font-family:"Montserrat",sans-serif;font-size:.62rem;font-weight:600;padding:.28rem .75rem;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.45);text-decoration:none;transition:all .2s;cursor:pointer}
  .tpill:hover{background:rgba(160,94,34,.1);border-color:rgba(160,94,34,.25);color:#a05e22}
';
require_once 'includes/dark_header.php';
?>

<!-- PAGE HERO — compact -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_KILIMANJARO ?>" alt="Safari Blog" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.28">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.7),rgba(10,10,10,1))"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full text-center">
    <nav class="flex items-center justify-center gap-2 font-nav text-[.65rem] text-white/35 mb-5">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">Blog</span>
    </nav>
    <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 mb-4" style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2)">
      <i class="fas fa-book-open text-amber-400 text-xs"></i>
      <span class="font-nav font-bold text-[.62rem] tracking-[.18em] uppercase text-amber-400">The Jambo Journal</span>
    </div>
    <h1 class="font-heading text-white font-bold mb-3" style="font-size:clamp(2rem,5vw,3.2rem)">
      Stories from the <span class="hero-grad">Wild</span>
    </h1>
    <p class="text-white/45 max-w-xl mx-auto text-[.92rem]">
      Safari tips, wildlife insights, Maasai culture and trekking guides — everything to plan your Tanzania adventure.
    </p>
  </div>
</div>

<!-- CATEGORY FILTER TABS -->
<section class="px-4 lg:px-0 py-5 relative z-10">
  <div class="max-w-7xl mx-auto">
    <div style="display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center" class="reveal">
      <a href="<?= url('blog') ?>" class="ftab <?= !$catFilter&&!$search?'active':'' ?>">
        All Posts
      </a>
      <?php foreach ($categories as $cat):
        [$clr,$bg,$icon] = catStyle($cat);
        $isActive = $catFilter === $cat;
      ?>
      <a href="<?= url('blog?cat='.urlencode($cat)) ?>" class="ftab <?= $isActive?'active':'' ?>">
        <i class="fas <?= $icon ?>" style="font-size:.6rem;color:<?= $clr ?>"></i>
        <?= $cat ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- MAIN CONTENT + SIDEBAR -->
<section class="py-8 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">

    <?php if ($search): ?>
    <div style="margin-bottom:1.25rem;padding:.65rem 1rem;background:rgba(160,94,34,.08);border:1px solid rgba(160,94,34,.2);border-radius:12px;font-family:'Montserrat',sans-serif;font-size:.78rem;color:#c17a3a;display:flex;align-items:center;gap:.5rem">
      <i class="fas fa-search text-xs"></i>
      Showing results for "<strong><?= e($search) ?></strong>" · <?= count($posts) ?> article<?= count($posts)!==1?'s':'' ?> found
      <a href="<?= url('blog') ?>" style="margin-left:auto;color:rgba(255,255,255,.4);font-size:.7rem;text-decoration:none">Clear ×</a>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-[1fr_300px] gap-8">

      <!-- LEFT: Posts -->
      <div>

        <!-- Featured Post (first post, full width) -->
        <?php if ($featured && !$search): ?>
        <a href="<?= url('blog/'.e($featured['slug'])) ?>" class="fc reveal" style="margin-bottom:1.5rem">
          <div style="display:grid;grid-template-columns:1fr 1fr" class="fc-grid">
            <div style="overflow:hidden;min-height:260px">
              <img src="<?= e($featured['image']) ?>" alt="<?= e($featured['title']) ?>"
                   class="fc-img" style="height:100%;min-height:260px" fetchpriority="high">
            </div>
            <div style="padding:2rem;display:flex;flex-direction:column;justify-content:center">
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem">
                <span style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.2);padding:.18rem .65rem;border-radius:999px;text-transform:uppercase;letter-spacing:.1em">Featured</span>
                <?php [$fc,$fb,$fi] = catStyle($featured['category']??'Safari Tips'); ?>
                <span class="cbadge" style="background:<?= $fb ?>;color:<?= $fc ?>;border:1px solid <?= $fc ?>30">
                  <i class="fas <?= $fi ?>" style="font-size:.5rem"></i><?= e($featured['category']??'Safari Tips') ?>
                </span>
              </div>
              <h2 class="font-heading text-white font-bold leading-snug mb-3" style="font-size:1.35rem">
                <?= e($featured['title']) ?>
              </h2>
              <p style="color:rgba(255,255,255,.45);font-size:.83rem;line-height:1.7;margin-bottom:1.1rem">
                <?= e(truncate($featured['excerpt']??'', 160)) ?>
              </p>
              <div style="display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:.65rem">
                  <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#a05e22,#7d4817);display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;color:#fff;flex-shrink:0">
                    <?= strtoupper(substr($featured['author']??'A',0,2)) ?>
                  </div>
                  <div>
                    <div style="font-size:.78rem;color:rgba(255,255,255,.75);font-weight:600"><?= e($featured['author']??'Team') ?></div>
                    <div style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.3)"><?= formatDate($featured['created_at']) ?> · <?= readTime($featured['content']??'') ?> min read</div>
                  </div>
                </div>
                <span style="font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:700;color:#a05e22;display:flex;align-items:center;gap:.3rem">
                  Read <i class="fas fa-arrow-right" style="font-size:.6rem"></i>
                </span>
              </div>
            </div>
          </div>
        </a>

        <!-- Section divider -->
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem">
          <div style="flex:1;height:1px;background:rgba(255,255,255,.07)"></div>
          <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.25)">More Articles</span>
          <div style="flex:1;height:1px;background:rgba(255,255,255,.07)"></div>
        </div>

        <?php $displayPosts = $restPosts; else: $displayPosts = $posts; endif; ?>

        <!-- Posts Grid -->
        <?php if (empty($displayPosts)): ?>
        <div style="text-align:center;padding:3rem;background:rgba(255,255,255,.02);border:2px dashed rgba(255,255,255,.07);border-radius:16px">
          <i class="fas fa-book-open" style="font-size:2.5rem;color:rgba(255,255,255,.12);display:block;margin-bottom:.75rem"></i>
          <p style="color:rgba(255,255,255,.35);font-size:.88rem;margin-bottom:.5rem">
            <?= $search ? 'No articles found for "'.e($search).'".' : 'No articles yet. Check back soon!' ?>
          </p>
          <?php if ($search || $catFilter): ?>
          <a href="<?= url('blog') ?>" style="font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;color:#a05e22;text-decoration:none">← View all articles</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
          <?php foreach ($displayPosts as $i => $post):
            [$clr,$bg,$icon] = catStyle($post['category']??'Safari Tips');
          ?>
          <a href="<?= url('blog/'.e($post['slug'])) ?>" class="bc reveal" style="transition-delay:<?= ($i%2)*60 ?>ms">
            <div style="position:relative;overflow:hidden">
              <img src="<?= e($post['image']) ?>" alt="<?= e($post['title']) ?>"
                   class="bc-img" loading="<?= $i<4?'eager':'lazy' ?>">
              <div style="position:absolute;top:.75rem;left:.75rem">
                <span class="cbadge" style="background:<?= $bg ?>;color:<?= $clr ?>;border:1px solid <?= $clr ?>30">
                  <i class="fas <?= $icon ?>" style="font-size:.48rem"></i><?= e($post['category']??'Tips') ?>
                </span>
              </div>
            </div>
            <div style="padding:1.1rem">
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.28)">
                <span><?= e($post['author']??'Team') ?></span>
                <span>·</span>
                <span><?= formatDate($post['created_at']) ?></span>
                <span>·</span>
                <span><?= readTime($post['content']??'') ?> min</span>
              </div>
              <h3 class="font-heading text-white font-bold leading-snug mb-2" style="font-size:.95rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                <?= e($post['title']) ?>
              </h3>
              <p style="color:rgba(255,255,255,.4);font-size:.78rem;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.85rem">
                <?= e($post['excerpt']??'') ?>
              </p>
              <span style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;color:#a05e22;display:flex;align-items:center;gap:.3rem">
                Read More <i class="fas fa-arrow-right" style="font-size:.55rem"></i>
              </span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div><!-- /left -->

      <!-- RIGHT: Sidebar -->
      <aside>
        <!-- Search -->
        <div class="sw reveal">
          <h3><i class="fas fa-search" style="color:#a05e22"></i> Search</h3>
          <form action="<?= url('blog') ?>" method="GET" style="position:relative">
            <i class="fas fa-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.22);font-size:.72rem;pointer-events:none"></i>
            <input type="text" name="search" class="s-inp" placeholder="Search articles…"
                   value="<?= e($search) ?>"
                   onkeydown="if(event.key==='Enter')this.form.submit()">
          </form>
        </div>

        <!-- Popular Topics -->
        <div class="sw reveal">
          <h3><i class="fas fa-tags" style="color:#fbbf24"></i> Popular Topics</h3>
          <div style="display:flex;flex-wrap:wrap;gap:.35rem">
            <?php foreach ($categories as $cat):
              [$clr] = catStyle($cat);
            ?>
            <a href="<?= url('blog?cat='.urlencode($cat)) ?>" class="tpill" style="<?= $catFilter===$cat?'background:rgba(160,94,34,.12);border-color:rgba(160,94,34,.3);color:#a05e22':'' ?>">
              <?= $cat ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="sw reveal" style="background:linear-gradient(135deg,rgba(160,94,34,.08),rgba(10,10,10,.5));border-color:rgba(160,94,34,.15)">
          <h3><i class="fas fa-envelope" style="color:#a05e22"></i> Newsletter</h3>
          <p style="font-size:.75rem;color:rgba(255,255,255,.4);margin-bottom:.85rem;line-height:1.6">
            Weekly safari tips, wildlife stories & exclusive deals.
          </p>
          <form id="blog-nl-form" onsubmit="blogNlSubmit(event)" style="display:flex;flex-direction:column;gap:.5rem">
            <input type="email" required placeholder="your@email.com" class="s-inp">
            <button type="submit" style="width:100%;padding:.72rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;cursor:pointer;transition:all .25s" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform=''">
              <i class="fas fa-paper-plane" style="font-size:.7rem;margin-right:.35rem"></i> Subscribe Free
            </button>
          </form>
          <p style="font-family:'Montserrat',sans-serif;font-size:.58rem;color:rgba(255,255,255,.2);margin-top:.5rem;text-align:center">No spam. Unsubscribe anytime.</p>
        </div>

        <!-- Recent Posts -->
        <div class="sw reveal">
          <h3><i class="fas fa-fire" style="color:#f97316"></i> Recent Articles</h3>
          <div>
            <?php foreach ($recentPosts as $rp): ?>
            <a href="<?= url('blog/'.e($rp['slug'])) ?>" class="rp">
              <img src="<?= e($rp['image']) ?>" alt="<?= e($rp['title']) ?>"
                   style="width:52px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;border:1px solid rgba(255,255,255,.07)">
              <div style="min-width:0">
                <div class="rp-title"><?= e(truncate($rp['title'], 55)) ?></div>
                <div class="rp-date"><?= formatDate($rp['created_at']) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($recentPosts)): ?>
            <p style="font-size:.78rem;color:rgba(255,255,255,.25);text-align:center;padding:.5rem">No articles yet</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Write for us / CTA -->
        <div class="sw reveal" style="background:linear-gradient(135deg,rgba(160,94,34,.06),rgba(10,10,10,.6));text-align:center;border-color:rgba(160,94,34,.12)">
          <div style="font-size:2rem;margin-bottom:.5rem">🦁</div>
          <h3 style="font-family:'Nanum Myeongjo',serif;font-size:.95rem;font-weight:700;color:#fff;margin-bottom:.5rem;justify-content:center">Plan Your Safari</h3>
          <p style="font-size:.75rem;color:rgba(255,255,255,.4);margin-bottom:.85rem;line-height:1.6">Ready to experience Tanzania? Our experts are here to help.</p>
          <button onclick="openBookingModal()" style="width:100%;padding:.75rem;border-radius:10px;background:linear-gradient(135deg,#7d4817,#a05e22);border:none;color:#fff;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.75rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.45rem">
            <i class="fas fa-compass" style="font-size:.7rem"></i> Book Safari
          </button>
        </div>
      </aside>

    </div><!-- /grid -->
  </div>
</section>

<style>
/* Featured grid responsive */
.fc-grid{display:grid;grid-template-columns:1fr 1fr}
@media(max-width:767px){
  .fc-grid{grid-template-columns:1fr;grid-template-rows:200px auto}
  .fc-img{height:200px !important;min-height:200px !important}
}
/* Posts grid responsive */
@media(max-width:639px){
  section div[style*="grid-template-columns:repeat(2,1fr)"]{grid-template-columns:1fr !important}
}
</style>

<script>
window.SITE_URL = '<?= SITE_URL ?>';
/* Newsletter submit */
function blogNlSubmit(e) {
  e.preventDefault();
  var btn = e.target.querySelector('button[type=submit]');
  btn.textContent = '✓ Subscribed!';
  btn.style.background = 'rgba(160,94,34,.2)';
  btn.style.color = '#a05e22';
  btn.style.border = '1px solid rgba(160,94,34,.3)';
  btn.disabled = true;
  e.target.querySelector('input').value = '';
  setTimeout(function(){
    btn.textContent = 'Subscribe Free';
    btn.style.background = '';
    btn.style.color = '#fff';
    btn.style.border = 'none';
    btn.disabled = false;
  }, 4000);
}
</script>
<?php require_once 'includes/dark_footer.php'; ?>

