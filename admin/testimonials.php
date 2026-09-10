<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();
try { $db->exec("ALTER TABLE testimonials ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'site'"); } catch (\Throwable $e) {}
try { $db->exec("ALTER TABLE testimonials ADD COLUMN IF NOT EXISTS source_url VARCHAR(500) NULL"); } catch (\Throwable $e) {}

$errors = [];
$sources = ['site' => 'Site', 'google' => 'Google', 'tripadvisor' => 'TripAdvisor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id        = sanitizeInt($_POST['id'] ?? 0, 0);
        $name      = sanitizeInput($_POST['customer_name'] ?? '');
        $country   = sanitizeInput($_POST['country']       ?? '');
        $rating    = max(1, min(5, sanitizeInt($_POST['rating'] ?? 5, 1, 5)));
        $review    = trim($_POST['review'] ?? '');
        $tourName  = sanitizeInput($_POST['tour_name']      ?? '');
        $source    = in_array($_POST['source'] ?? 'site', array_keys($sources), true) ? $_POST['source'] : 'site';
        $sourceUrl = trim($_POST['source_url'] ?? '');
        $approved  = isset($_POST['approved']) ? 1 : 0;
        $existing  = sanitizeInput($_POST['existing_photo'] ?? '');
        $urlPhoto  = sanitizeInput($_POST['photo_url'] ?? '');

        if (empty($name))    $errors[] = 'Customer name is required.';
        if (empty($country)) $errors[] = 'Country is required.';
        if (empty($review))  $errors[] = 'Review text is required.';

        $photoResult = handleImageUpload('photo_file', $urlPhoto ?: $existing);
        if (isset($photoResult['error'])) $errors[] = $photoResult['error'];
        $photoUrl = $photoResult['url'] ?? $existing;

        if (empty($errors)) {
            if ($id) {
                $db->prepare("UPDATE testimonials SET customer_name=:n,country=:c,rating=:r,review=:rv,tour_name=:t,source=:s,source_url=:su,photo=:p,approved=:a WHERE id=:id")
                   ->execute([':n'=>$name,':c'=>$country,':r'=>$rating,':rv'=>$review,':t'=>$tourName,':s'=>$source,':su'=>$sourceUrl,':p'=>$photoUrl,':a'=>$approved,':id'=>$id]);
            } else {
                $db->prepare("INSERT INTO testimonials (customer_name,country,rating,review,tour_name,source,source_url,photo,approved) VALUES (:n,:c,:r,:rv,:t,:s,:su,:p,:a)")
                   ->execute([':n'=>$name,':c'=>$country,':r'=>$rating,':rv'=>$review,':t'=>$tourName,':s'=>$source,':su'=>$sourceUrl,':p'=>$photoUrl,':a'=>$approved]);
            }
            redirect(SITE_URL . '/admin/testimonials.php?msg=' . urlencode($id ? 'Review updated successfully.' : 'New review added!'));
        }
    }

    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM testimonials WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/testimonials.php?msg=Review+deleted.');
    }

    if ($action === 'toggle_approved') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("UPDATE testimonials SET approved = 1 - approved WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/testimonials.php');
    }
}

$msg     = sanitizeInput($_GET['msg'] ?? '');
$editId  = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing = null;
if (isset($_GET['edit']) && $editId) {
    $s = $db->prepare("SELECT * FROM testimonials WHERE id=?"); $s->execute([$editId]);
    $editing = $s->fetch();
}
$reviews   = $db->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();

$sourceColors = ['site'=>'#a05e22','google'=>'#4285F4','tripadvisor'=>'#34e0a1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    .edit-grid{display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start}
    @media(max-width:900px){.edit-grid{grid-template-columns:1fr}}
    .preview-img{width:100%;height:150px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.08);display:block;margin-bottom:.75rem}
    .img-tabs{display:flex;gap:0;margin-bottom:.85rem;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,.1)}
    .img-tab{flex:1;padding:.42rem;text-align:center;font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;cursor:pointer;color:rgba(255,255,255,.4);background:rgba(255,255,255,.03);border:none;transition:all .2s}
    .img-tab.active{background:rgba(16,185,129,.15);color:#10b981}
    .img-panel{display:none}.img-panel.active{display:block}
    .src-option{display:none}
    .src-label{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .85rem;border-radius:999px;border:1.5px solid rgba(255,255,255,.1);cursor:pointer;font-family:'Montserrat',sans-serif;font-size:.67rem;font-weight:600;transition:all .22s;color:rgba(255,255,255,.4);background:rgba(255,255,255,.03)}
    .src-option:checked + .src-label{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.12);color:#10b981}
    .rating-option{display:none}
    .rating-label{padding:.45rem .8rem;border-radius:8px;border:1.5px solid rgba(255,255,255,.1);cursor:pointer;font-family:'Montserrat',sans-serif;font-size:.8rem;font-weight:700;color:rgba(255,255,255,.4);transition:all .2s}
    .rating-option:checked + .rating-label{border-color:rgba(251,191,36,.5);background:rgba(251,191,36,.12);color:#fbbf24}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <!-- Header -->
    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;font-weight:700;display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-star" style="color:#10b981;font-size:1.1rem"></i>
          <?= isset($_GET['edit']) ? ($editing ? 'Edit Review' : 'New Review') : 'Guest Reviews' ?>
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">
          <?= isset($_GET['edit']) ? 'Add or edit a customer review shown on the homepage and /reviews page' : count($reviews).' reviews · shown on homepage & /reviews' ?>
        </p>
      </div>
      <div style="display:flex;gap:.5rem">
        <?php if (!isset($_GET['edit'])): ?>
        <a href="testimonials.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-plus" style="font-size:.6rem"></i> New Review
        </a>
        <?php else: ?>
        <a href="testimonials.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> All Reviews
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem">
      <i class="fas fa-check-circle"></i> <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
    <!-- ══════════════════════════ EDIT / ADD FORM ══════════════════════════ -->

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1.1rem">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="list-style:none;margin:0">
        <?php foreach ($errors as $e_): ?><li>· <?= e($e_) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">
      <input type="hidden" name="existing_photo" value="<?= e($editing['photo'] ?? '') ?>">

      <div class="edit-grid">

        <!-- ── Main column ── -->
        <div>
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-user" style="color:#60a5fa;font-size:.82rem;margin-right:.4rem"></i>Guest Details</h2></div>
            <div class="adm-card-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:.85rem">
                <div class="f-group" style="margin-bottom:0">
                  <label class="f-label">Customer Name</label>
                  <input type="text" class="f-input" name="customer_name" required
                         value="<?= e($editing['customer_name'] ?? '') ?>" placeholder="Sarah Mitchell">
                </div>
                <div class="f-group" style="margin-bottom:0">
                  <label class="f-label">Country</label>
                  <input type="text" class="f-input" name="country" required
                         value="<?= e($editing['country'] ?? '') ?>" placeholder="United Kingdom">
                </div>
              </div>
              <div class="f-group">
                <label class="f-label">Tour Name <span style="font-weight:400;color:rgba(255,255,255,.3)">(optional)</span></label>
                <input type="text" class="f-input" name="tour_name"
                       value="<?= e($editing['tour_name'] ?? '') ?>" placeholder="Serengeti Great Migration Safari">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Review Text</label>
                <textarea class="f-input" name="review" rows="5" required
                          placeholder="What did they say about their trip?"><?= e($editing['review'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <div class="adm-card">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-star" style="color:#fbbf24;font-size:.82rem;margin-right:.4rem"></i>Rating</h2></div>
            <div class="adm-card-body">
              <div style="display:flex;gap:.4rem">
                <?php $curRating = (int)($editing['rating'] ?? 5); for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" name="rating" value="<?= $i ?>" id="rating-<?= $i ?>"
                       class="rating-option" <?= $curRating === $i ? 'checked' : '' ?>>
                <label for="rating-<?= $i ?>" class="rating-label" style="<?= $curRating===$i?'border-color:rgba(251,191,36,.5);background:rgba(251,191,36,.12);color:#fbbf24':'' ?>">
                  <?= $i ?> <i class="fas fa-star" style="font-size:.65rem"></i>
                </label>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Side column ── -->
        <div>
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Publish</h2></div>
            <div class="adm-card-body">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .75rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;margin-bottom:.75rem">
                <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;color:rgba(255,255,255,.6)">Approved</span>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                  <div style="position:relative;width:40px;height:22px;flex-shrink:0">
                    <input type="checkbox" name="approved" value="1" id="app-toggle"
                           <?= ($editing['approved'] ?? 1) ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0;position:absolute"
                           onchange="document.getElementById('app-slider').style.background=this.checked?'#10b981':'rgba(255,255,255,.12)';document.getElementById('app-thumb').style.transform=this.checked?'translateX(18px)':'';document.getElementById('app-label').textContent=this.checked?'Approved':'Hidden';document.getElementById('app-label').style.color=this.checked?'#10b981':'rgba(255,255,255,.4)'">
                    <div id="app-slider" style="position:absolute;inset:0;border-radius:22px;background:<?= ($editing['approved']??1)?'#10b981':'rgba(255,255,255,.12)' ?>;cursor:pointer;transition:background .3s"></div>
                    <div id="app-thumb" style="position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform .3s;<?= ($editing['approved']??1)?'transform:translateX(18px)':'' ?>"></div>
                  </div>
                  <span id="app-label" style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:600;color:<?= ($editing['approved']??1)?'#10b981':'rgba(255,255,255,.4)' ?>"><?= ($editing['approved']??1)?'Approved':'Hidden' ?></span>
                </label>
              </div>
              <button type="submit" class="btn btn--primary w-full" style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.8rem;font-size:.8rem">
                <i class="fas fa-save" style="font-size:.7rem"></i>
                <?= $editing ? 'Update Review' : 'Add Review' ?>
              </button>
              <a href="testimonials.php" class="btn btn--outline w-full" style="display:flex;align-items:center;justify-content:center;gap:.4rem;margin-top:.45rem;font-size:.78rem">
                Cancel
              </a>
            </div>
          </div>

          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-globe" style="color:#34e0a1;font-size:.82rem;margin-right:.4rem"></i>Source</h2></div>
            <div class="adm-card-body">
              <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.85rem">
                <?php $curSource = $editing['source'] ?? 'site'; foreach ($sources as $key => $label): ?>
                <input type="radio" name="source" value="<?= $key ?>" id="src-<?= $key ?>"
                       class="src-option" <?= $curSource === $key ? 'checked' : '' ?>>
                <label for="src-<?= $key ?>" class="src-label" style="<?= $curSource===$key?'border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.12);color:#10b981':'' ?>">
                  <?php if ($key === 'google'): ?><i class="fab fa-google" style="font-size:.6rem"></i>
                  <?php elseif ($key === 'tripadvisor'): ?><img src="<?= SITE_URL ?>/assets/images/tripadvisor-icon.svg" alt="" style="width:.7rem;height:.7rem;vertical-align:-1px">
                  <?php else: ?><i class="fas fa-globe" style="font-size:.6rem"></i><?php endif; ?>
                  <?= $label ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Original review link <span style="font-weight:400;color:rgba(255,255,255,.3)">(optional)</span></label>
                <input type="url" class="f-input" name="source_url"
                       value="<?= e($editing['source_url'] ?? '') ?>" placeholder="https://...">
              </div>
            </div>
          </div>

          <div class="adm-card">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-image" style="color:#f97316;font-size:.82rem;margin-right:.4rem"></i>Guest Photo</h2></div>
            <div class="adm-card-body">
              <div id="img-preview-wrap" style="<?= empty($editing['photo']) ? 'display:none' : '' ?>">
                <img src="<?= e($editing['photo'] ?? '') ?>" alt="" class="preview-img" id="img-preview">
              </div>
              <div class="img-tabs">
                <button type="button" class="img-tab active" onclick="switchImgTab('url',this)">
                  <i class="fas fa-link" style="font-size:.6rem;margin-right:.3rem"></i>URL
                </button>
                <button type="button" class="img-tab" onclick="switchImgTab('upload',this)">
                  <i class="fas fa-upload" style="font-size:.6rem;margin-right:.3rem"></i>Upload
                </button>
              </div>
              <div class="img-panel active" id="img-panel-url">
                <input type="url" class="f-input" name="photo_url" id="photo-url-input"
                       value="<?= e($editing['photo'] ?? '') ?>"
                       placeholder="https://... photo URL" style="margin-bottom:0">
              </div>
              <div class="img-panel" id="img-panel-upload">
                <input type="file" class="f-input" name="photo_file" id="photo-file-input" accept="image/jpeg,image/png,image/webp">
                <div class="f-hint">JPG / PNG / WebP · Max 8 MB · Leave blank to use an auto-generated avatar</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ══════════════════════════ REVIEWS LIST ══════════════════════════ -->

    <div class="adm-stats" style="margin-bottom:1.25rem">
      <?php
      $total    = count($reviews);
      $approved = count(array_filter($reviews, fn($r) => $r['approved']));
      $googleC  = count(array_filter($reviews, fn($r) => ($r['source'] ?? '') === 'google'));
      $tripC    = count(array_filter($reviews, fn($r) => ($r['source'] ?? '') === 'tripadvisor'));
      foreach ([
        ['fa-star',        'rgba(16,185,129,.15)','#10b981', $total,    'Total Reviews'],
        ['fa-check-circle','rgba(52,211,153,.15)','#34d399', $approved, 'Approved / Live'],
        ['fa-google',      'rgba(66,133,244,.15)','#8ab4f8', $googleC,  'From Google'],
        ['img',            'rgba(52,224,161,.15)','#34e0a1', $tripC,    'From TripAdvisor'],
      ] as $w): ?>
      <div class="adm-stat">
        <div class="adm-stat-icon" style="background:<?= $w[1] ?>">
          <?php if ($w[0] === 'img'): ?>
          <img src="<?= SITE_URL ?>/assets/images/tripadvisor-icon.svg" alt="" style="width:.9rem;height:.9rem">
          <?php else: ?>
          <i class="fas <?= $w[0] ?>" style="color:<?= $w[2] ?>;font-size:.9rem"></i>
          <?php endif; ?>
        </div>
        <div><div class="adm-stat-val"><?= $w[3] ?></div><div class="adm-stat-lbl"><?= $w[4] ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="adm-card">
      <div class="adm-card-header">
        <h2 class="adm-card-title">All Reviews (<?= $total ?>)</h2>
        <a href="testimonials.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-plus" style="font-size:.6rem"></i> New Review
        </a>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th style="width:56px">Photo</th>
              <th>Guest</th>
              <th>Review</th>
              <th>Rating</th>
              <th>Source</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reviews)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:rgba(255,255,255,.25)">
              <i class="fas fa-star" style="font-size:2rem;display:block;margin-bottom:.65rem;color:rgba(255,255,255,.1)"></i>
              No reviews yet. <a href="testimonials.php?edit=0" style="color:#10b981;text-decoration:none">Add your first review →</a>
            </td></tr>
            <?php else: foreach ($reviews as $r):
              $rSource = $r['source'] ?? 'site';
              $rClr    = $sourceColors[$rSource] ?? '#a05e22';
              $photoSrc = $r['photo'] ?: ('https://ui-avatars.com/api/?name=' . urlencode($r['customer_name']) . '&background=10b981&color=fff');
            ?>
            <tr>
              <td><img src="<?= e($photoSrc) ?>" alt="" class="img-small" style="width:40px;height:40px;border-radius:50%;object-fit:cover"></td>
              <td style="max-width:180px">
                <div style="font-weight:600;color:#fff;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($r['customer_name']) ?></div>
                <div style="font-size:.68rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif;margin-top:.1rem"><?= e($r['country']) ?></div>
              </td>
              <td style="max-width:320px">
                <div style="font-size:.78rem;color:rgba(255,255,255,.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e(truncate($r['review'], 80)) ?></div>
              </td>
              <td style="font-size:.78rem;color:#fbbf24;white-space:nowrap"><?= str_repeat('★', (int)$r['rating']) ?></td>
              <td>
                <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;padding:.18rem .65rem;border-radius:999px;color:<?= $rClr ?>;background:<?= $rClr ?>18;border:1px solid <?= $rClr ?>30;white-space:nowrap">
                  <?= e($sources[$rSource] ?? 'Site') ?>
                </span>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle_approved">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="badge <?= $r['approved']?'badge-confirmed':'badge-pending' ?>" style="cursor:pointer;border:none">
                    <?= $r['approved'] ? '● Approved' : '○ Hidden' ?>
                  </button>
                </form>
              </td>
              <td style="white-space:nowrap">
                <a href="testimonials.php?edit=<?= $r['id'] ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this review permanently?')">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>

  </main>
</div>

<script>
function switchImgTab(tab, btn) {
  document.querySelectorAll('.img-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.img-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('img-panel-' + tab).classList.add('active');
}

var photoUrlInput  = document.getElementById('photo-url-input');
var photoFileInput = document.getElementById('photo-file-input');
var imgPreview      = document.getElementById('img-preview');
var imgPreviewWrap  = document.getElementById('img-preview-wrap');

function setPhotoPreview(src) {
  if (!imgPreview) return;
  imgPreview.src = src;
  imgPreviewWrap.style.display = 'block';
}
if (photoUrlInput) {
  photoUrlInput.addEventListener('blur', function(){ if (this.value.trim()) setPhotoPreview(this.value.trim()); });
}
if (photoFileInput) {
  photoFileInput.addEventListener('change', function(){
    if (this.files[0]) {
      setPhotoPreview(URL.createObjectURL(this.files[0]));
      if (photoUrlInput) photoUrlInput.value = '';
    }
  });
}
</script>
</body>
</html>
