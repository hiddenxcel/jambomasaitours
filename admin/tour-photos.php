<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* Auto-create table */
$db->exec("CREATE TABLE IF NOT EXISTS tour_photos (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tour_id    INT NOT NULL,
    image      VARCHAR(500) NOT NULL,
    caption    VARCHAR(255) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$tourId = sanitizeInt($_GET['tour_id'] ?? 0, 1);
if (!$tourId) { redirect(SITE_URL . '/admin/tours.php'); }
$tStmt = $db->prepare("SELECT * FROM tours WHERE id=? LIMIT 1");
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();
if (!$tour) { redirect(SITE_URL . '/admin/tours.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { http_response_code(403); die('Invalid CSRF.'); }
    $action = $_POST['action'] ?? '';

    /* Upload multiple photos */
    if ($action === 'upload') {
        $urls      = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? '')));
        $captions  = $_POST['captions'] ?? [];
        $uploaded  = 0;

        /* Handle file uploads */
        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['name'] as $i => $fname) {
                if (!$fname) continue;
                $singleFile = [
                    'name'     => $_FILES['photos']['name'][$i],
                    'type'     => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error'    => $_FILES['photos']['error'][$i],
                    'size'     => $_FILES['photos']['size'][$i],
                ];
                /* Upload single via helper — temporarily set $_FILES */
                $_FILES['_single_photo'] = $singleFile;
                $res = handleImageUpload('_single_photo', '');
                if (isset($res['url'])) {
                    $sort = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM tour_photos WHERE tour_id=$tourId")->fetchColumn();
                    $db->prepare("INSERT INTO tour_photos (tour_id,image,caption,sort_order) VALUES (?,?,?,?)")
                       ->execute([$tourId, $res['url'], $captions[$i] ?? '', $sort]);
                    $uploaded++;
                }
            }
        }

        /* Handle URL inputs */
        foreach ($urls as $i => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
            $sort = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM tour_photos WHERE tour_id=$tourId")->fetchColumn();
            $db->prepare("INSERT INTO tour_photos (tour_id,image,caption,sort_order) VALUES (?,?,?,?)")
               ->execute([$tourId, $url, $captions[$i] ?? '', $sort]);
            $uploaded++;
        }

        redirect(SITE_URL . '/admin/tour-photos.php?tour_id='.$tourId.'&msg='.urlencode($uploaded.' photo(s) added.'));
    }

    /* Update caption */
    if ($action === 'update_caption') {
        $pid     = sanitizeInt($_POST['photo_id'] ?? 0, 1);
        $caption = sanitizeInput($_POST['caption'] ?? '');
        $db->prepare("UPDATE tour_photos SET caption=? WHERE id=? AND tour_id=?")->execute([$caption,$pid,$tourId]);
        redirect(SITE_URL . '/admin/tour-photos.php?tour_id='.$tourId.'&msg=Caption+updated.');
    }

    /* Delete */
    if ($action === 'delete') {
        $pid = sanitizeInt($_POST['photo_id'] ?? 0, 1);
        $db->prepare("DELETE FROM tour_photos WHERE id=? AND tour_id=?")->execute([$pid,$tourId]);
        redirect(SITE_URL . '/admin/tour-photos.php?tour_id='.$tourId.'&msg=Photo+deleted.');
    }

    /* Reorder (move up/down) */
    if ($action === 'move') {
        $pid = sanitizeInt($_POST['photo_id'] ?? 0, 1);
        $dir = $_POST['dir'] ?? '';
        $cur = $db->prepare("SELECT sort_order FROM tour_photos WHERE id=? AND tour_id=?");
        $cur->execute([$pid,$tourId]);
        $row = $cur->fetch();
        if ($row) {
            $curN = (int)$row['sort_order'];
            $newN = $dir === 'up' ? $curN - 1 : $curN + 1;
            $nb   = $db->prepare("SELECT id FROM tour_photos WHERE tour_id=? AND sort_order=?");
            $nb->execute([$tourId,$newN]);
            $nbRow = $nb->fetch();
            if ($nbRow) $db->prepare("UPDATE tour_photos SET sort_order=? WHERE id=?")->execute([$curN,$nbRow['id']]);
            $db->prepare("UPDATE tour_photos SET sort_order=? WHERE id=?")->execute([$newN,$pid]);
        }
        redirect(SITE_URL . '/admin/tour-photos.php?tour_id='.$tourId);
    }

    /* Delete all */
    if ($action === 'delete_all') {
        $db->prepare("DELETE FROM tour_photos WHERE tour_id=?")->execute([$tourId]);
        redirect(SITE_URL . '/admin/tour-photos.php?tour_id='.$tourId.'&msg=All+photos+deleted.');
    }
}

$msg    = sanitizeInput($_GET['msg'] ?? '');
$photos = $db->prepare("SELECT * FROM tour_photos WHERE tour_id=? ORDER BY sort_order ASC, id ASC");
$photos->execute([$tourId]);
$photos = $photos->fetchAll();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Photos: <?= e($tour['name']) ?> | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    body,html{background:#0f1117;color:#e5e7eb;font-family:'Inter',sans-serif}
    .admin-layout,.admin-main{background:#0f1117}
    .admin-main{padding:2rem}
    .card{background:#1a1d27;border:1px solid rgba(255,255,255,.08);border-radius:16px}
    .f-input,.f-textarea{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.72rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:all .2s}
    .f-input:focus,.f-textarea:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.04)}
    .f-textarea{resize:vertical;min-height:80px}
    .f-label{display:block;font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.4rem}
    .ab{display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;padding:.5rem 1.1rem;border-radius:8px;border:none;cursor:pointer;transition:all .25s;text-decoration:none}
    .ab-em{background:linear-gradient(135deg,#059669,#10b981);color:#fff;box-shadow:0 3px 10px rgba(16,185,129,.2)}
    .ab-em:hover{transform:translateY(-1px)}
    .ab-ol{background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.12)}
    .ab-ol:hover{background:rgba(255,255,255,.1);color:#fff}
    .ab-del{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.18)}
    .ab-del:hover{background:rgba(239,68,68,.18)}
    .ab-xs{padding:.28rem .55rem;font-size:.6rem;border-radius:6px;background:rgba(255,255,255,.05);color:rgba(255,255,255,.35);border:1px solid rgba(255,255,255,.07)}
    .ab-xs:hover{background:rgba(255,255,255,.09);color:rgba(255,255,255,.65)}
    .ab-sm{padding:.4rem .8rem;font-size:.65rem}
    .pg-header{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.5rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}
    .chip{display:inline-flex;align-items:center;gap:.3rem;font-size:.62rem;font-family:'Montserrat',sans-serif;font-weight:600;padding:.22rem .65rem;border-radius:999px}
    .chip-em{background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.2)}
    .chip-gray{background:rgba(255,255,255,.06);color:rgba(255,255,255,.45);border:1px solid rgba(255,255,255,.1)}

    /* Photo grid */
    .photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:1.5rem}
    .photo-card{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:12px;overflow:hidden;transition:all .3s;position:relative}
    .photo-card:hover{border-color:rgba(16,185,129,.25);box-shadow:0 8px 24px rgba(0,0,0,.4)}
    .photo-card img{width:100%;height:150px;object-fit:cover;display:block;transition:transform .4s}
    .photo-card:hover img{transform:scale(1.04)}
    .photo-actions{position:absolute;top:6px;right:6px;display:flex;gap:4px;opacity:0;transition:opacity .2s}
    .photo-card:hover .photo-actions{opacity:1}

    /* Drop zone */
    .drop-zone{border:2px dashed rgba(16,185,129,.25);border-radius:12px;padding:2rem;text-align:center;transition:all .3s;background:rgba(16,185,129,.03);cursor:pointer}
    .drop-zone:hover,.drop-zone.drag-over{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.07)}
    .drop-zone input[type=file]{display:none}

    /* Mosaic preview */
    .mosaic-preview{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:175px 175px;gap:3px;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.07)}
    .mosaic-main{grid-column:1;grid-row:1/3}
    .mosaic-preview img{width:100%;height:100%;object-fit:cover}
    .mosaic-more{background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.3rem;color:#fff;font-size:.72rem;font-family:'Montserrat',sans-serif;cursor:pointer}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <!-- Header -->
    <div class="pg-header">
      <div style="display:flex;align-items:flex-start;gap:1rem">
        <?php if ($tour['image']): ?>
        <img src="<?= e($tour['image']) ?>" alt="" style="width:66px;height:52px;object-fit:cover;border-radius:9px;flex-shrink:0;border:1px solid rgba(255,255,255,.07)">
        <?php endif; ?>
        <div>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.3rem">
            <span class="chip chip-em"><?= e($tour['tour_type']) ?></span>
            <span class="chip chip-gray"><i class="fas fa-map-marker-alt" style="font-size:.5rem"></i><?= e($tour['destination']) ?></span>
          </div>
          <h1 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:#fff;line-height:1.25"><?= e($tour['name']) ?></h1>
          <p style="font-size:.78rem;color:rgba(255,255,255,.3);margin-top:.2rem"><?= count($photos) ?> photo<?= count($photos)!==1?'s':'' ?> uploaded</p>
        </div>
      </div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="<?= SITE_URL ?>/tour-detail.php?slug=<?= e($tour['slug']) ?>" target="_blank" class="ab ab-ol ab-sm"><i class="fas fa-eye" style="font-size:.6rem"></i> Preview</a>
        <a href="tours.php" class="ab ab-ol ab-sm"><i class="fas fa-arrow-left" style="font-size:.6rem"></i> Tours</a>
      </div>
    </div>

    <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.22);color:#34d399;padding:.8rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;font-size:.875rem;display:flex;align-items:center;gap:.65rem">
      <i class="fas fa-check-circle"></i><?= e($msg) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

      <!-- Left: current photos -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
          <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:#fff">
            <i class="fas fa-images text-emerald-400 mr-2" style="color:#34d399;font-size:.85rem"></i>
            Tour Photos (<?= count($photos) ?>)
          </h2>
          <?php if (!empty($photos)): ?>
          <form method="POST" onsubmit="return confirm('Delete ALL photos for this tour?')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="ab ab-del ab-sm"><i class="fas fa-trash" style="font-size:.6rem"></i> Clear All</button>
          </form>
          <?php endif; ?>
        </div>

        <?php if (empty($photos)): ?>
        <div style="text-align:center;padding:3rem 2rem;background:#1a1d27;border-radius:14px;border:2px dashed rgba(255,255,255,.07)">
          <div style="font-size:2.5rem;margin-bottom:.75rem">📷</div>
          <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:#fff;margin-bottom:.4rem">No photos yet</h3>
          <p style="color:rgba(255,255,255,.3);font-size:.85rem">Upload photos to show visitors what this destination looks like.</p>
        </div>
        <?php else: ?>
        <div class="photo-grid">
          <?php foreach ($photos as $idx => $p): ?>
          <div class="photo-card">
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['caption']) ?>" loading="lazy">

            <!-- Hover actions -->
            <div class="photo-actions">
              <?php if ($idx > 0): ?>
              <form method="POST" style="display:inline"><input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="photo_id" value="<?= $p['id'] ?>"><input type="hidden" name="dir" value="up"><button type="submit" class="ab ab-xs" title="Move up" style="padding:.25rem .45rem"><i class="fas fa-chevron-left" style="font-size:.55rem"></i></button></form>
              <?php endif; ?>
              <?php if ($idx < count($photos)-1): ?>
              <form method="POST" style="display:inline"><input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="photo_id" value="<?= $p['id'] ?>"><input type="hidden" name="dir" value="down"><button type="submit" class="ab ab-xs" title="Move right" style="padding:.25rem .45rem"><i class="fas fa-chevron-right" style="font-size:.55rem"></i></button></form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this photo?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                <button type="submit" class="ab ab-del" style="padding:.25rem .45rem"><i class="fas fa-trash" style="font-size:.55rem"></i></button>
              </form>
            </div>

            <!-- Caption -->
            <div style="padding:.65rem .75rem">
              <form method="POST" style="display:flex;gap:.4rem;align-items:center">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="update_caption">
                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                <input type="text" name="caption" value="<?= e($p['caption']) ?>"
                       placeholder="Add caption…"
                       style="flex:1;background:transparent;border:none;border-bottom:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);font-size:.72rem;padding:.2rem .1rem;outline:none;font-family:'Inter',sans-serif"
                       onfocus="this.style.borderColor='rgba(16,185,129,.5)'"
                       onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                <button type="submit" style="background:transparent;border:none;color:#34d399;cursor:pointer;padding:.1rem .3rem;font-size:.65rem" title="Save"><i class="fas fa-check"></i></button>
              </form>
              <div style="font-size:.58rem;color:rgba(255,255,255,.2);margin-top:.25rem;font-family:'Montserrat',sans-serif">Photo #<?= $idx+1 ?> · <?= $idx===0?'<span style="color:#34d399">Main / Hero</span>':'Position '.$p['sort_order'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Mosaic preview -->
        <?php if (count($photos) >= 2): ?>
        <div style="margin-top:1.5rem;background:#1a1d27;border-radius:14px;padding:1.25rem;border:1px solid rgba(255,255,255,.07)">
          <p style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.3);margin-bottom:.85rem">
            <i class="fas fa-eye" style="color:#34d399;margin-right:.4rem"></i>Hero Mosaic Preview (how it looks on the tour page)
          </p>
          <div class="mosaic-preview">
            <div class="mosaic-main"><img src="<?= e($photos[0]['image']) ?>" alt="main"></div>
            <?php $sm = array_slice($photos, 1, 4); foreach ($sm as $si => $sp): ?>
            <?php if ($si === 3 && count($photos) > 5): ?>
            <div class="mosaic-more">
              <i class="fas fa-images" style="font-size:1.2rem;color:rgba(255,255,255,.6)"></i>
              <span>+<?= count($photos) - 4 ?> more</span>
            </div>
            <?php else: ?>
            <img src="<?= e($sp['image']) ?>" alt="">
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Right: upload form -->
      <div>
        <div class="card" style="padding:1.5rem">
          <h2 style="font-family:'Playfair Display',serif;font-size:1.05rem;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:.6rem">
            <span style="width:28px;height:28px;border-radius:50%;background:rgba(16,185,129,.13);color:#34d399;display:flex;align-items:center;justify-content:center;font-size:.7rem"><i class="fas fa-plus"></i></span>
            Add Photos
          </h2>

          <form method="POST" enctype="multipart/form-data" id="upload-form">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="upload">

            <!-- Drop zone -->
            <div class="drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
              <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:rgba(16,185,129,.5);margin-bottom:.65rem"></i>
              <p style="font-size:.85rem;color:rgba(255,255,255,.5);margin-bottom:.3rem">Click or drag & drop photos here</p>
              <p style="font-size:.72rem;color:rgba(255,255,255,.25)">JPG, PNG, WebP · Multiple allowed · Max 8MB each</p>
              <input type="file" name="photos[]" id="file-input" multiple accept="image/jpeg,image/png,image/webp">
            </div>

            <!-- Preview thumbnails -->
            <div id="file-preview" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.75rem"></div>

            <div style="margin:1rem 0;position:relative;text-align:center">
              <div style="height:1px;background:rgba(255,255,255,.07)"></div>
              <span style="position:absolute;top:-7px;left:50%;transform:translateX(-50%);background:#1a1d27;padding:0 .5rem;font-size:.65rem;color:rgba(255,255,255,.25);font-family:'Montserrat',sans-serif">OR</span>
            </div>

            <!-- URL input -->
            <div>
              <label class="f-label">Paste Image URLs <small style="text-transform:none;letter-spacing:0;font-size:.7rem;color:rgba(255,255,255,.2)">(one per line)</small></label>
              <textarea class="f-textarea" name="image_urls" rows="4"
                        placeholder="https://example.com/photo1.jpg&#10;https://example.com/photo2.jpg"></textarea>
            </div>

            <button type="submit" class="ab ab-em" style="width:100%;justify-content:center;margin-top:1rem">
              <i class="fas fa-upload" style="font-size:.65rem"></i> Upload Photos
            </button>
          </form>

          <!-- Tips -->
          <div style="margin-top:1.25rem;padding:1rem;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.12);border-radius:10px">
            <p style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#34d399;margin-bottom:.6rem">Tips</p>
            <ul style="space-y:0.3rem;color:rgba(255,255,255,.4);font-size:.75rem;line-height:1.7">
              <li>· First photo becomes the <strong style="color:rgba(255,255,255,.6)">hero image</strong> in the mosaic</li>
              <li>· Upload at least 5 photos for the full mosaic grid</li>
              <li>· Landscape photos (16:9) look best</li>
              <li>· Add captions to describe each photo</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
/* File input drag/drop + preview */
const dz  = document.getElementById('drop-zone');
const fi  = document.getElementById('file-input');
const fpr = document.getElementById('file-preview');

dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop',      e => { e.preventDefault(); dz.classList.remove('drag-over'); fi.files = e.dataTransfer.files; showPreviews(fi.files); });
fi.addEventListener('change',    () => showPreviews(fi.files));

function showPreviews(files) {
  fpr.innerHTML = '';
  Array.from(files).forEach(f => {
    const url = URL.createObjectURL(f);
    const img = document.createElement('img');
    img.src = url; img.style.cssText = 'width:64px;height:48px;object-fit:cover;border-radius:6px;border:1px solid rgba(255,255,255,.1)';
    fpr.appendChild(img);
  });
}
</script>
</body>
</html>
