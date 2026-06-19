<?php
/**
 * Reusable cinematic page intro/hero banner.
 * Call: renderPageIntro('tours', 'Tours', 'Browse all safaris', $breadcrumbs)
 * $breadcrumbs = [['label'=>'Home','url'=>url()], ['label'=>'Tours']]
 */
function renderPageIntro(string $slug, string $defaultTitle, string $defaultSubtitle = '', array $breadcrumbs = []): void {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM page_intros WHERE page_slug = ? AND active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $intro = $stmt->fetch();

    $title    = $intro ? $intro['title']    : $defaultTitle;
    $subtitle = $intro ? $intro['subtitle'] : $defaultSubtitle;
    $bgImage  = $intro ? $intro['bg_image'] : IMG_HERO;
    ?>
<section class="page-intro" style="position:relative;overflow:hidden;padding:calc(var(--space-24) + 70px) 0 var(--space-16);background:var(--color-black-soft);">

    <!-- Background Image -->
    <div style="position:absolute;inset:0;z-index:0;">
        <img src="<?= e($bgImage ?: IMG_HERO) ?>"
             alt="<?= e($title) ?>"
             style="width:100%;height:100%;object-fit:cover;object-position:center 40%;opacity:.45;"
             loading="eager" width="1920" height="600">
    </div>

    <!-- Gradient overlays -->
    <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg,rgba(74,44,23,.85) 0%,rgba(26,26,26,.7) 50%,rgba(107,66,38,.6) 100%);"></div>
    <div style="position:absolute;bottom:0;left:0;right:0;height:120px;z-index:1;background:linear-gradient(to top,var(--color-white),transparent);"></div>

    <!-- Gold decorative line -->
    <div style="position:absolute;top:0;left:0;right:0;height:4px;z-index:2;background:linear-gradient(90deg,transparent,var(--color-gold),var(--color-orange),var(--color-gold),transparent);"></div>

    <!-- Content -->
    <div class="container" style="position:relative;z-index:3;text-align:center;">

        <!-- Breadcrumb -->
        <?php if (!empty($breadcrumbs)): ?>
        <nav style="display:flex;align-items:center;justify-content:center;gap:.5rem;margin-bottom:var(--space-6);flex-wrap:wrap;" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?><span style="color:rgba(255,255,255,.3);font-size:.7rem;">›</span><?php endif; ?>
                <?php if (isset($crumb['url'])): ?>
                    <a href="<?= e($crumb['url']) ?>" style="font-family:var(--font-nav);font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gold);text-decoration:none;transition:opacity .2s;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><?= e($crumb['label']) ?></a>
                <?php else: ?>
                    <span style="font-family:var(--font-nav);font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.55);"><?= e($crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <!-- Badge -->
        <div style="display:inline-flex;align-items:center;gap:.5rem;font-family:var(--font-nav);font-size:.68rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--color-gold);background:rgba(201,168,76,.12);border:1px solid rgba(201,168,76,.3);padding:.3rem 1rem;border-radius:9999px;margin-bottom:var(--space-5);backdrop-filter:blur(8px);" data-aos="fade-down">
            ✦ Jambo Masai Tours ✦
        </div>

        <!-- Title -->
        <h1 style="font-family:var(--font-heading);font-size:clamp(2rem,5vw,3.8rem);font-weight:900;color:var(--color-white);line-height:1.1;margin-bottom:var(--space-4);text-shadow:0 2px 30px rgba(0,0,0,.4);" data-aos="fade-up" data-aos-delay="100">
            <?= e($title) ?>
        </h1>

        <?php if ($subtitle): ?>
        <!-- Subtitle -->
        <p style="font-size:clamp(1rem,2vw,1.2rem);color:rgba(255,255,255,.78);max-width:580px;margin:0 auto;line-height:1.7;" data-aos="fade-up" data-aos-delay="200">
            <?= e($subtitle) ?>
        </p>
        <?php endif; ?>

        <!-- Decorative divider -->
        <div style="display:flex;align-items:center;justify-content:center;gap:var(--space-4);margin-top:var(--space-8);" data-aos="fade-up" data-aos-delay="300">
            <div style="width:40px;height:1px;background:var(--color-gold);opacity:.6;"></div>
            <div style="width:8px;height:8px;border-radius:50%;background:var(--color-gold);"></div>
            <div style="width:40px;height:1px;background:var(--color-gold);opacity:.6;"></div>
        </div>

    </div>
</section>
    <?php
}
