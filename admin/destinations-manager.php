<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* Ensure table exists */
try {
    $db->exec("CREATE TABLE IF NOT EXISTS destination_pages_full (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(80) UNIQUE NOT NULL,
        title VARCHAR(150) NOT NULL,
        subtitle VARCHAR(200) DEFAULT '',
        region VARCHAR(100) DEFAULT '',
        area VARCHAR(60) DEFAULT '',
        best_time VARCHAR(80) DEFAULT '',
        altitude VARCHAR(60) DEFAULT '',
        image_url TEXT DEFAULT '',
        og_title VARCHAR(200) DEFAULT '',
        meta_desc VARCHAR(300) DEFAULT '',
        intro TEXT DEFAULT '',
        description TEXT DEFAULT '',
        highlights JSON DEFAULT NULL,
        wildlife TEXT DEFAULT '',
        faqs JSON DEFAULT NULL,
        geo_lat VARCHAR(20) DEFAULT '',
        geo_lon VARCHAR(20) DEFAULT '',
        search_term VARCHAR(60) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $e) {}

$errors = [];
$msg = '';

function destSlug(string $t): string {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9\s-]/', '', $t);
    return trim(preg_replace('/[\s-]+/', '-', $t), '-');
}

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { http_response_code(403); die('Invalid CSRF.'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = sanitizeInt($_POST['id'] ?? 0, 0);
        $title       = sanitizeInput($_POST['title']       ?? '');
        $slug        = destSlug(sanitizeInput($_POST['slug'] ?? '') ?: $title);
        $subtitle    = sanitizeInput($_POST['subtitle']    ?? '');
        $region      = sanitizeInput($_POST['region']      ?? '');
        $area        = sanitizeInput($_POST['area']        ?? '');
        $best_time   = sanitizeInput($_POST['best_time']   ?? '');
        $altitude    = sanitizeInput($_POST['altitude']    ?? '');
        $og_title    = sanitizeInput($_POST['og_title']    ?? '');
        $meta_desc   = sanitizeInput($_POST['meta_desc']   ?? '');
        $intro       = sanitizeInput($_POST['intro']       ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $wildlife_raw= sanitizeInput($_POST['wildlife']    ?? '');
        $geo_lat     = sanitizeInput($_POST['geo_lat']     ?? '');
        $geo_lon     = sanitizeInput($_POST['geo_lon']     ?? '');
        $search_term = sanitizeInput($_POST['search_term'] ?? $slug);
        $active      = isset($_POST['active']) ? 1 : 0;
        $sort_order  = sanitizeInt($_POST['sort_order'] ?? 0, 0);
        $existing_img= sanitizeInput($_POST['existing_image'] ?? '');
        $url_img     = sanitizeInput($_POST['image_url_input'] ?? '');

        /* Highlights */
        $highlights = [];
        $hIcons = $_POST['h_icon'] ?? [];
        $hTitles= $_POST['h_title'] ?? [];
        $hDescs = $_POST['h_desc'] ?? [];
        foreach ($hTitles as $i => $ht) {
            if (trim($ht)) $highlights[] = ['icon'=>$hIcons[$i]??'fa-star','title'=>trim($ht),'desc'=>trim($hDescs[$i]??'')];
        }

        /* FAQs */
        $faqs = [];
        $fQs = $_POST['faq_q'] ?? [];
        $fAs = $_POST['faq_a'] ?? [];
        foreach ($fQs as $i => $fq) {
            if (trim($fq)) $faqs[] = ['q'=>trim($fq),'a'=>trim($fAs[$i]??'')];
        }

        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($intro))  $errors[] = 'Intro is required.';

        /* Image */
        $imgR = handleImageUpload('image_file', $url_img ?: $existing_img);
        if (isset($imgR['error'])) $errors[] = $imgR['error'];
        $imageUrl = $imgR['url'] ?? $existing_img;

        /* Convert wildlife comma string → JSON array */
        $wildlifeJson = json_encode(
            array_values(array_filter(array_map('trim', explode(',', $wildlife_raw)))),
            JSON_UNESCAPED_UNICODE
        );

        if (empty($errors)) {
            /* Check slug unique */
            $chk = $db->prepare("SELECT id FROM destination_pages_full WHERE slug=? AND id!=? LIMIT 1");
            $chk->execute([$slug, $id]);
            if ($chk->fetch()) $slug .= '-' . substr(time(), -4);

            $data = [
                ':slug'=>$slug, ':title'=>$title, ':subtitle'=>$subtitle,
                ':region'=>$region, ':area'=>$area, ':best_time'=>$best_time,
                ':altitude'=>$altitude, ':image_url'=>$imageUrl,
                ':og_title'=>$og_title ?: $title.' | Jambo Masai Tours',
                ':meta_desc'=>$meta_desc, ':intro'=>$intro,
                ':description'=>$description,
                ':highlights'=>json_encode($highlights, JSON_UNESCAPED_UNICODE),
                ':wildlife'=>$wildlifeJson,
                ':faqs'=>json_encode($faqs, JSON_UNESCAPED_UNICODE),
                ':geo_lat'=>$geo_lat, ':geo_lon'=>$geo_lon,
                ':search_term'=>$search_term ?: $slug,
                ':active'=>$active, ':sort_order'=>$sort_order,
            ];
            if ($id) {
                $data[':id'] = $id;
                $db->prepare("UPDATE destination_pages_full SET
                    slug=:slug,title=:title,subtitle=:subtitle,region=:region,area=:area,
                    best_time=:best_time,altitude=:altitude,image_url=:image_url,
                    og_title=:og_title,meta_desc=:meta_desc,intro=:intro,description=:description,
                    highlights=:highlights,wildlife=:wildlife,faqs=:faqs,
                    geo_lat=:geo_lat,geo_lon=:geo_lon,search_term=:search_term,
                    active=:active,sort_order=:sort_order WHERE id=:id")->execute($data);
            } else {
                $db->prepare("INSERT INTO destination_pages_full
                    (slug,title,subtitle,region,area,best_time,altitude,image_url,og_title,
                    meta_desc,intro,description,highlights,wildlife,faqs,geo_lat,geo_lon,
                    search_term,active,sort_order) VALUES
                    (:slug,:title,:subtitle,:region,:area,:best_time,:altitude,:image_url,:og_title,
                    :meta_desc,:intro,:description,:highlights,:wildlife,:faqs,:geo_lat,:geo_lon,
                    :search_term,:active,:sort_order)")->execute($data);
            }
            redirect(SITE_URL.'/admin/destinations-manager.php?msg='.urlencode($id?'Destination updated.':'Destination added.'));
        }
    }

    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM destination_pages_full WHERE id=?")->execute([$id]);
        redirect(SITE_URL.'/admin/destinations-manager.php?msg=Destination+deleted.');
    }

    if ($action === 'toggle') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("UPDATE destination_pages_full SET active=1-active WHERE id=?")->execute([$id]);
        redirect(SITE_URL.'/admin/destinations-manager.php');
    }
}

$msg    = sanitizeInput($_GET['msg'] ?? '');
$editId = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing= null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM destination_pages_full WHERE id=?");
    $s->execute([$editId]);
    $editing = $s->fetch();
}

$destinations = $db->query("SELECT * FROM destination_pages_full ORDER BY sort_order ASC, id ASC")->fetchAll();
$csrfToken    = generateCsrfToken();

/* Parse editing JSON fields */
$editHighlights = [];
$editFaqs = [];
if ($editing) {
    $editHighlights = json_decode($editing['highlights'] ?? '[]', true) ?: [];
    $editFaqs       = json_decode($editing['faqs']       ?? '[]', true) ?: [];
}
/* Ensure at least 3 highlight rows and 3 FAQ rows for the form */
while (count($editHighlights) < 3) $editHighlights[] = ['icon'=>'fa-star','title'=>'','desc'=>''];
while (count($editFaqs)       < 3) $editFaqs[]       = ['q'=>'','a'=>''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Destinations Manager | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    .dest-card{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;display:flex;align-items:stretch;transition:border-color .2s}
    .dest-card:hover{border-color:rgba(16,185,129,.2)}
    .dest-img{width:110px;height:90px;object-fit:cover;flex-shrink:0}
    .dest-img-placeholder{width:110px;height:90px;flex-shrink:0;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center}
    .faq-row,.hl-row{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.75rem;margin-bottom:.5rem}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <div class="admin-header">
      <div>
        <h1 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:#fff;display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-map-marker-alt" style="color:#10b981"></i>
          <?= isset($_GET['edit']) ? ($editing ? 'Edit: '.e($editing['title']) : 'New Destination') : 'Destinations Manager' ?>
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">
          <?= isset($_GET['edit']) ? 'Fill in details — page goes live at /destination/'.(e($editing['slug']??'slug')) : count($destinations).' destinations' ?>
        </p>
      </div>
      <div style="display:flex;gap:.5rem">
        <?php if (isset($_GET['edit'])): ?>
        <?php if ($editing): ?>
        <a href="<?= url('destination/'.e($editing['slug'])) ?>" target="_blank" class="btn btn--outline btn--sm">
          <i class="fas fa-eye" style="font-size:.6rem"></i> Preview
        </a>
        <?php endif; ?>
        <a href="destinations-manager.php" class="btn btn--outline btn--sm">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> All
        </a>
        <?php else: ?>
        <a href="destinations-manager.php?edit=0" class="btn btn--primary btn--sm">
          <i class="fas fa-plus" style="font-size:.6rem"></i> Add Destination
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1.1rem">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="list-style:none;margin:0"><?php foreach ($errors as $er): ?><li>· <?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
    <!-- ══════════════════ EDIT / ADD FORM ══════════════════ -->
    <form method="POST" enctype="multipart/form-data" id="dest-form">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">
      <input type="hidden" name="existing_image" value="<?= e($editing['image_url'] ?? '') ?>">

      <div style="display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start" class="edit-grid">

        <!-- LEFT: Main content -->
        <div>

          <!-- Basic info -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Basic Information</h2></div>
            <div class="adm-card-body">
              <div class="f-grid-2">
                <div class="f-group">
                  <label class="f-label">Title <span>*</span></label>
                  <input type="text" class="f-input" name="title" id="dest-title" required
                         value="<?= e($editing['title'] ?? '') ?>" placeholder="Serengeti National Park"
                         style="font-family:'Playfair Display',serif;font-size:1rem">
                </div>
                <div class="f-group">
                  <label class="f-label">URL Slug</label>
                  <div style="position:relative">
                    <span style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);font-size:.65rem;color:rgba(255,255,255,.25)">/destination/</span>
                    <input type="text" class="f-input" name="slug" id="dest-slug"
                           value="<?= e($editing['slug'] ?? '') ?>" placeholder="serengeti"
                           style="padding-left:6.5rem">
                  </div>
                </div>
                <div class="f-group">
                  <label class="f-label">Subtitle</label>
                  <input type="text" class="f-input" name="subtitle"
                         value="<?= e($editing['subtitle'] ?? '') ?>" placeholder="The Greatest Show on Earth">
                </div>
                <div class="f-group">
                  <label class="f-label">Region</label>
                  <input type="text" class="f-input" name="region"
                         value="<?= e($editing['region'] ?? '') ?>" placeholder="Northern Tanzania">
                </div>
                <div class="f-group">
                  <label class="f-label">Area</label>
                  <input type="text" class="f-input" name="area"
                         value="<?= e($editing['area'] ?? '') ?>" placeholder="14,763 km²">
                </div>
                <div class="f-group">
                  <label class="f-label">Best Time to Visit</label>
                  <input type="text" class="f-input" name="best_time"
                         value="<?= e($editing['best_time'] ?? '') ?>" placeholder="June – October">
                </div>
                <div class="f-group">
                  <label class="f-label">Altitude</label>
                  <input type="text" class="f-input" name="altitude"
                         value="<?= e($editing['altitude'] ?? '') ?>" placeholder="920 – 1,850 m">
                </div>
                <div class="f-group">
                  <label class="f-label">Search Term <span style="font-weight:400;font-size:.6rem">(lowercase keyword)</span></label>
                  <input type="text" class="f-input" name="search_term"
                         value="<?= e($editing['search_term'] ?? '') ?>" placeholder="serengeti">
                </div>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Content</h2></div>
            <div class="adm-card-body">
              <div class="f-group">
                <label class="f-label">Intro Paragraph <span>*</span> <span style="font-weight:400;font-size:.6rem">(2–3 sentences, shown first)</span></label>
                <textarea class="f-input" name="intro" rows="3" required
                          style="min-height:90px"><?= e($editing['intro'] ?? '') ?></textarea>
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Full Description <span style="font-weight:400;font-size:.6rem">(HTML allowed)</span></label>
                <textarea class="f-input" name="description" rows="8"
                          style="min-height:200px;font-family:'Fira Code','Courier New',monospace;font-size:.82rem"><?= e($editing['description'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Highlights -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header">
              <h2 class="adm-card-title">Highlights</h2>
              <button type="button" class="btn btn--outline btn--sm" onclick="addHighlight()">+ Add</button>
            </div>
            <div class="adm-card-body">
              <div id="highlights-wrap">
                <?php foreach ($editHighlights as $hi => $hl): ?>
                <div class="hl-row">
                  <div style="display:grid;grid-template-columns:120px 1fr 1fr auto;gap:.5rem;align-items:start">
                    <div>
                      <div class="f-label" style="font-size:.55rem">Icon (FA class)</div>
                      <input type="text" class="f-input" name="h_icon[]"
                             value="<?= e($hl['icon']) ?>" placeholder="fa-star" style="font-size:.78rem">
                    </div>
                    <div>
                      <div class="f-label" style="font-size:.55rem">Title</div>
                      <input type="text" class="f-input" name="h_title[]"
                             value="<?= e($hl['title']) ?>" placeholder="Big Five" style="font-size:.78rem">
                    </div>
                    <div>
                      <div class="f-label" style="font-size:.55rem">Description</div>
                      <input type="text" class="f-input" name="h_desc[]"
                             value="<?= e($hl['desc']) ?>" placeholder="Short description" style="font-size:.78rem">
                    </div>
                    <button type="button" onclick="this.closest('.hl-row').remove()"
                            style="margin-top:1.35rem;padding:.35rem .55rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:7px;cursor:pointer;font-size:.75rem">✕</button>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="f-hint"><i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>FA icons: fa-paw, fa-water, fa-mountain, fa-tree, fa-star, fa-camera, fa-fish...</div>
            </div>
          </div>

          <!-- Wildlife -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Wildlife</h2></div>
            <div class="adm-card-body">
              <textarea class="f-input" name="wildlife" rows="3"
                        placeholder="Lion, Elephant, Leopard, Cheetah, Buffalo, Wildebeest, Zebra, Giraffe"
                        style="min-height:80px"><?php
                        $wl = $editing['wildlife'] ?? '';
                        /* If stored as JSON array, convert back to comma string for display */
                        if ($wl && $wl[0] === '[') {
                            $arr = json_decode($wl, true);
                            $wl = is_array($arr) ? implode(', ', $arr) : $wl;
                        }
                        echo e($wl);
                        ?></textarea>
              <div class="f-hint">Andika majina ya wanyama yakitenganishwa na koma (,)</div>
            </div>
          </div>

          <!-- FAQs -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header">
              <h2 class="adm-card-title">FAQs</h2>
              <button type="button" class="btn btn--outline btn--sm" onclick="addFaq()">+ Add FAQ</button>
            </div>
            <div class="adm-card-body">
              <div id="faqs-wrap">
                <?php foreach ($editFaqs as $fi => $fq): ?>
                <div class="faq-row">
                  <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.4rem">
                    <input type="text" class="f-input" name="faq_q[]"
                           value="<?= e($fq['q']) ?>" placeholder="Question..." style="font-size:.82rem">
                    <button type="button" onclick="this.closest('.faq-row').remove()"
                            style="padding:.35rem .55rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:7px;cursor:pointer;font-size:.75rem">✕</button>
                  </div>
                  <textarea class="f-input" name="faq_a[]" rows="2"
                            style="min-height:60px;font-size:.8rem"
                            placeholder="Answer..."><?= e($fq['a']) ?></textarea>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- SEO -->
          <div class="adm-card">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-search" style="color:#fbbf24;margin-right:.4rem"></i>SEO</h2></div>
            <div class="adm-card-body">
              <div class="f-group">
                <label class="f-label">OG/Page Title</label>
                <input type="text" class="f-input" name="og_title"
                       value="<?= e($editing['og_title'] ?? '') ?>"
                       placeholder="Serengeti Safari Tanzania | Best Time, Wildlife & Tours — Jambo Masai Tours">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Meta Description <span style="font-weight:400;font-size:.6rem">(max 160 chars)</span></label>
                <textarea class="f-input" name="meta_desc" rows="2"
                          style="min-height:65px"><?= e($editing['meta_desc'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT: Sidebar -->
        <div>

          <!-- Publish -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Publish</h2></div>
            <div class="adm-card-body">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem .7rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;margin-bottom:.75rem">
                <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;color:rgba(255,255,255,.55)">Status</span>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                  <div style="position:relative;width:40px;height:22px">
                    <input type="checkbox" name="active" value="1" id="active-tog"
                           <?= ($editing['active'] ?? 1) ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0;position:absolute">
                    <div id="act-slider" style="position:absolute;inset:0;border-radius:22px;background:<?= ($editing['active']??1)?'#10b981':'rgba(255,255,255,.12)' ?>;cursor:pointer;transition:background .3s"></div>
                    <div style="position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform .3s;<?= ($editing['active']??1)?'transform:translateX(18px)':'' ?>"></div>
                  </div>
                  <span id="act-lbl" style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:600;color:<?= ($editing['active']??1)?'#10b981':'rgba(255,255,255,.4)' ?>"><?= ($editing['active']??1)?'Active':'Hidden' ?></span>
                </label>
              </div>
              <div class="f-group" style="margin-bottom:.75rem">
                <label class="f-label">Sort Order</label>
                <input type="number" class="f-input" name="sort_order" min="0"
                       value="<?= (int)($editing['sort_order'] ?? 0) ?>" style="width:80px">
              </div>
              <button type="submit" class="btn btn--primary w-full" style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.8rem">
                <i class="fas fa-save" style="font-size:.7rem"></i>
                <?= $editing ? 'Update Destination' : 'Add Destination' ?>
              </button>
              <a href="destinations-manager.php" class="btn btn--outline w-full" style="display:flex;align-items:center;justify-content:center;margin-top:.4rem;font-size:.78rem">Cancel</a>
            </div>
          </div>

          <!-- Image -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-image" style="color:#f97316;margin-right:.4rem"></i>Hero Image</h2></div>
            <div class="adm-card-body">
              <?php if (!empty($editing['image_url'])): ?>
              <img src="<?= e($editing['image_url']) ?>" alt="" id="img-prev"
                   style="width:100%;height:130px;object-fit:cover;border-radius:10px;margin-bottom:.65rem">
              <?php endif; ?>
              <div class="f-group">
                <label class="f-label" style="font-size:.58rem">Upload file</label>
                <input type="file" class="f-input" name="image_file" id="img-file" accept="image/jpeg,image/png,image/webp">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label" style="font-size:.58rem">Or paste URL</label>
                <input type="url" class="f-input" name="image_url_input" id="img-url"
                       value="<?= e($editing['image_url'] ?? '') ?>"
                       placeholder="https://images.unsplash.com/...">
              </div>
            </div>
          </div>

          <!-- Coordinates -->
          <div class="adm-card">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-map-pin" style="color:#60a5fa;margin-right:.4rem"></i>Coordinates</h2></div>
            <div class="adm-card-body">
              <div class="f-group">
                <label class="f-label" style="font-size:.58rem">Latitude</label>
                <input type="text" class="f-input" name="geo_lat"
                       value="<?= e($editing['geo_lat'] ?? '') ?>" placeholder="-2.3333">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label" style="font-size:.58rem">Longitude</label>
                <input type="text" class="f-input" name="geo_lon"
                       value="<?= e($editing['geo_lon'] ?? '') ?>" placeholder="34.8333">
              </div>
              <div class="f-hint">Pata coordinates kutoka Google Maps</div>
            </div>
          </div>

        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ══════════════════ DESTINATION LIST ══════════════════ -->
    <div class="adm-stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
      <?php
      $total  = count($destinations);
      $active = count(array_filter($destinations, fn($d) => $d['active']));
      foreach ([
        ['fa-map-marker-alt','rgba(16,185,129,.15)','#10b981',$total,  'Total'],
        ['fa-eye',           'rgba(52,211,153,.15)', '#34d399',$active, 'Active'],
        ['fa-eye-slash',     'rgba(245,158,11,.15)', '#fbbf24',$total-$active,'Hidden'],
      ] as $w): ?>
      <div class="adm-stat">
        <div class="adm-stat-icon" style="background:<?= $w[1] ?>"><i class="fas <?= $w[0] ?>" style="color:<?= $w[2] ?>;font-size:.9rem"></i></div>
        <div><div class="adm-stat-val"><?= $w[3] ?></div><div class="adm-stat-lbl"><?= $w[4] ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="adm-card">
      <div class="adm-card-header">
        <h2 class="adm-card-title">All Destinations (<?= $total ?>)</h2>
        <a href="destinations-manager.php?edit=0" class="btn btn--primary btn--sm">
          <i class="fas fa-plus" style="font-size:.6rem"></i> Add New
        </a>
      </div>
      <div style="display:flex;flex-direction:column;gap:.5rem;padding:1rem">
        <?php foreach ($destinations as $dest): ?>
        <div class="dest-card">
          <?php if ($dest['image_url']): ?>
          <img src="<?= e($dest['image_url']) ?>" alt="" class="dest-img">
          <?php else: ?>
          <div class="dest-img-placeholder"><i class="fas fa-image" style="color:rgba(255,255,255,.15);font-size:1.2rem"></i></div>
          <?php endif; ?>
          <div style="flex:1;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem">
            <div>
              <div style="font-weight:600;color:#fff;font-size:.88rem"><?= e($dest['title']) ?></div>
              <div style="font-size:.68rem;color:rgba(255,255,255,.35);font-family:'Montserrat',sans-serif;margin-top:.15rem">/destination/<?= e($dest['slug']) ?> · <?= e($dest['region']) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">
              <span class="badge <?= $dest['active']?'badge-confirmed':'badge-pending' ?>" style="font-size:.58rem">
                <?= $dest['active']?'Active':'Hidden' ?>
              </span>
              <a href="destinations-manager.php?edit=<?= $dest['id'] ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .65rem">
                <i class="fas fa-pen"></i> Edit
              </a>
              <a href="<?= url('destination/'.e($dest['slug'])) ?>" target="_blank" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem">
                <i class="fas fa-eye"></i>
              </a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete destination permanently?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $dest['id'] ?>">
                <button class="btn btn--danger btn--sm" style="font-size:.62rem;padding:.28rem .6rem">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($destinations)): ?>
        <div style="text-align:center;padding:2.5rem;color:rgba(255,255,255,.25)">
          <i class="fas fa-map-marker-alt" style="font-size:2rem;display:block;margin-bottom:.65rem;color:rgba(255,255,255,.1)"></i>
          No destinations yet. <a href="destinations-manager.php?edit=0" style="color:#10b981;text-decoration:none">Add your first →</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
/* Auto slug */
var dt=document.getElementById('dest-title'),ds=document.getElementById('dest-slug');
if(dt&&ds&&!ds.value){
  dt.addEventListener('input',function(){ds.value=this.value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');});
}
/* Image preview */
var imgF=document.getElementById('img-file'),imgU=document.getElementById('img-url'),imgP=document.getElementById('img-prev');
if(imgF)imgF.addEventListener('change',function(){if(this.files[0]){if(imgU)imgU.value='';if(!imgP){imgP=document.createElement('img');imgP.id='img-prev';imgP.style='width:100%;height:130px;object-fit:cover;border-radius:10px;margin-bottom:.65rem';this.parentElement.insertAdjacentElement('beforebegin',imgP);}imgP.src=URL.createObjectURL(this.files[0]);}});
if(imgU){var _t;imgU.addEventListener('input',function(){clearTimeout(_t);var v=this.value.trim();_t=setTimeout(function(){if(v){if(!imgP){imgP=document.createElement('img');imgP.id='img-prev';imgP.style='width:100%;height:130px;object-fit:cover;border-radius:10px;margin-bottom:.65rem';imgU.parentElement.insertAdjacentElement('beforebegin',imgP);}imgP.src=v;}},800);});}
/* Toggle */
var actTog=document.getElementById('active-tog'),actSlider=document.getElementById('act-slider'),actLbl=document.getElementById('act-lbl');
if(actTog)actTog.addEventListener('change',function(){var th=document.querySelector('#act-slider+div');if(actSlider)actSlider.style.background=this.checked?'#10b981':'rgba(255,255,255,.12)';if(actLbl){actLbl.textContent=this.checked?'Active':'Hidden';actLbl.style.color=this.checked?'#10b981':'rgba(255,255,255,.4)';}if(th)th.style.transform=this.checked?'translateX(18px)':'';});
/* Add highlight row */
function addHighlight(){
  var wrap=document.getElementById('highlights-wrap');
  var div=document.createElement('div');div.className='hl-row';
  div.innerHTML='<div style="display:grid;grid-template-columns:120px 1fr 1fr auto;gap:.5rem;align-items:start"><div><div class="f-label" style="font-size:.55rem">Icon (FA class)</div><input type="text" class="f-input" name="h_icon[]" placeholder="fa-star" style="font-size:.78rem"></div><div><div class="f-label" style="font-size:.55rem">Title</div><input type="text" class="f-input" name="h_title[]" placeholder="Big Five" style="font-size:.78rem"></div><div><div class="f-label" style="font-size:.55rem">Description</div><input type="text" class="f-input" name="h_desc[]" placeholder="Short description" style="font-size:.78rem"></div><button type="button" onclick="this.closest(\'.hl-row\').remove()" style="margin-top:1.35rem;padding:.35rem .55rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:7px;cursor:pointer;font-size:.75rem">✕</button></div>';
  wrap.appendChild(div);
}
/* Add FAQ row */
function addFaq(){
  var wrap=document.getElementById('faqs-wrap');
  var div=document.createElement('div');div.className='faq-row';
  div.innerHTML='<div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.4rem"><input type="text" class="f-input" name="faq_q[]" placeholder="Question..." style="font-size:.82rem"><button type="button" onclick="this.closest(\'.faq-row\').remove()" style="padding:.35rem .55rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:7px;cursor:pointer;font-size:.75rem">✕</button></div><textarea class="f-input" name="faq_a[]" rows="2" style="min-height:60px;font-size:.8rem" placeholder="Answer..."></textarea>';
  wrap.appendChild(div);
}
/* Responsive grid */
document.head.insertAdjacentHTML('beforeend','<style>.edit-grid{display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start}@media(max-width:900px){.edit-grid{grid-template-columns:1fr}}</style>');
</script>
</body>
</html>
