<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once 'includes/auth_guard.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* --- Helpers --------------------------------------- */
function destSlug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

/* --- POST handler ---------------------------------- */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $_POST[CSRF_TOKEN_NAME])) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    /* SAVE (add or edit) */
    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $slug        = destSlug(trim($_POST['slug'] ?? '') ?: $title);
        $country     = trim($_POST['country'] ?? 'Tanzania');
        $region      = trim($_POST['region'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $best_season = trim($_POST['best_season'] ?? '');
        $climate     = trim($_POST['climate'] ?? '');
        $visa_info   = trim($_POST['visa_info'] ?? '');
        $sort_order  = (int)($_POST['sort_order'] ?? 0);
        $active      = isset($_POST['active']) ? 1 : 0;

        // highlights: one per line ? pipe-separated
        $hlRaw   = trim($_POST['highlights'] ?? '');
        $highlights = implode('|', array_filter(array_map('trim', explode("\n", str_replace("\r", '', $hlRaw)))));

        // image upload or URL
        $existingImg = trim($_POST['existing_image'] ?? '');
        $urlImg      = trim($_POST['image_url'] ?? '');
        $uploadResult = handleImageUpload('image_file', $urlImg ?: $existingImg);
        if (isset($uploadResult['error'])) {
            $msg = '<div class="admin-alert admin-alert--error">Image error: ' . e($uploadResult['error']) . '</div>';
        } else {
            $image = $uploadResult['url'];

            if ($id) {
                $stmt = $db->prepare("UPDATE destinations SET title=?,slug=?,country=?,region=?,description=?,best_season=?,climate=?,visa_info=?,highlights=?,image=?,sort_order=?,active=? WHERE id=?");
                $stmt->execute([$title,$slug,$country,$region,$description,$best_season,$climate,$visa_info,$highlights,$image,$sort_order,$active,$id]);
                $msg = '<div class="admin-alert admin-alert--success">Destination updated successfully.</div>';
            } else {
                $stmt = $db->prepare("INSERT INTO destinations (title,slug,country,region,description,best_season,climate,visa_info,highlights,image,sort_order,active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$title,$slug,$country,$region,$description,$best_season,$climate,$visa_info,$highlights,$image,$sort_order,$active]);
                $msg = '<div class="admin-alert admin-alert--success">Destination added successfully.</div>';
            }
            unset($_SESSION[CSRF_TOKEN_NAME]);
            header('Location: destinations.php?msg=' . urlencode(strip_tags($msg)));
            exit;
        }
    }

    /* TOGGLE ACTIVE */
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE destinations SET active = 1 - active WHERE id = ?")->execute([$id]);
        header('Location: destinations.php');
        exit;
    }

    /* DELETE */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM destinations WHERE id = ?")->execute([$id]);
        header('Location: destinations.php');
        exit;
    }
}

/* --- Flash message from redirect ------------------ */
if (!$msg && isset($_GET['msg'])) {
    $msg = '<div class="admin-alert admin-alert--success">' . e($_GET['msg']) . '</div>';
}

/* --- Load for edit --------------------------------- */
$editing = null;
if (isset($_GET['edit'])) {
    $editing = $db->prepare("SELECT * FROM destinations WHERE id = ?")->execute([(int)$_GET['edit']]) ? $db->prepare("SELECT * FROM destinations WHERE id = ?")->execute([(int)$_GET['edit']]) : null;
    $stmt2 = $db->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt2->execute([(int)$_GET['edit']]);
    $editing = $stmt2->fetch();
}

/* --- List all -------------------------------------- */
$destinations = $db->query("SELECT * FROM destinations ORDER BY sort_order ASC, id ASC")->fetchAll();

/* --- CSRF token ------------------------------------ */
$csrf = generateCsrfToken();
$pageTitle = 'Destinations — Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> | Jambo Masai Admin</title>
<link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
<style>
  /* Destination-specific overrides */
  .form-section{background:#1a1d27;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
  .form-section h3{font-family:'Nanum Myeongjo',serif;font-size:1rem;color:#fff;font-weight:700;margin:0 0 1.1rem;padding-bottom:.85rem;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:.5rem}
  .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
  .field-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
  @media(max-width:639px){.field-grid,.field-grid-3{grid-template-columns:1fr}}
  .upload-area{border:2px dashed rgba(255,255,255,.1);border-radius:12px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .25s;background:rgba(255,255,255,.02)}
  .upload-area:hover{border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.04)}
  .thumb-preview{max-width:100%;max-height:160px;border-radius:10px;object-fit:cover;display:block;margin:.75rem auto 0;border:1px solid rgba(255,255,255,.08)}
  .dest-thumb{width:64px;height:44px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.08)}
  .hl-list{display:flex;flex-wrap:wrap;gap:4px}
  .hl-pill{background:rgba(16,185,129,.1);color:#34d399;font-size:.62rem;padding:.18rem .55rem;border-radius:999px;font-family:'Montserrat',sans-serif;font-weight:600;border:1px solid rgba(16,185,129,.2)}
  .btn-sm{padding:.35rem .85rem;font-size:.7rem;border-radius:8px;font-family:'Montserrat',sans-serif;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;transition:all .2s;letter-spacing:.04em}
  .btn-edit{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.2)}
  .btn-edit:hover{background:rgba(16,185,129,.22);color:#34d399}
  .btn-toggle{background:rgba(34,197,94,.1);color:#34d399;border:1px solid rgba(34,197,94,.2)}
  .btn-toggle.inactive{background:rgba(107,114,128,.1);color:rgba(255,255,255,.35);border:1px solid rgba(255,255,255,.08)}
  .btn-delete{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.18)}
  .btn-delete:hover{background:rgba(239,68,68,.2)}
  .admin-alert{padding:.75rem 1.1rem;border-radius:10px;margin-bottom:1.1rem;font-size:.85rem;display:flex;align-items:center;gap:.6rem}
  .admin-alert--success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34d399}
  .admin-alert--error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#f87171}
  .btn-delete:hover { background: #dc2626; color: #fff; }
  .btn-primary { background: linear-gradient(135deg, #10b981, var(--color-orange)); color: var(--color-black); font-family: var(--font-nav); font-weight: 700; font-size: .82rem; padding: .7rem 1.5rem; border: none; border-radius: var(--radius-md); cursor: pointer; letter-spacing: .04em; }
  .btn-secondary { background: rgba(255,255,255,.04); color: #10b981; font-family: var(--font-nav); font-weight: 600; font-size: .82rem; padding: .7rem 1.5rem; border: 2px solid rgba(255,255,255,.08); border-radius: var(--radius-md); cursor: pointer; text-decoration: none; }
  .sort-num{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:rgba(16,185,129,.12);border-radius:8px;font-size:.8rem;font-weight:700;color:#10b981;font-family:'Montserrat',sans-serif}
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;font-weight:700;display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-map-marked-alt" style="color:#10b981;font-size:1.1rem"></i>
          Destinations
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">Manage the destinations shown on your website</p>
      </div>
      <a href="?add=1" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem;text-decoration:none">
        <i class="fas fa-plus" style="font-size:.6rem"></i> Add Destination
      </a>
    </div>

    <?= $msg ?>

    <?php if (isset($_GET['add']) || $editing): ?>
    <!-- --- ADD / EDIT FORM --------------------------- -->
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
      <input type="hidden" name="existing_image" value="<?= e($editing['image'] ?? '') ?>">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.65rem">
        <h2 style="font-family:'Nanum Myeongjo',serif;font-size:1.1rem;color:#fff;font-weight:700;margin:0">
          <?= $editing ? 'Edit: '.e($editing['title']) : 'Add New Destination' ?>
        </h2>
        <a href="destinations.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem;text-decoration:none">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> Back to List
        </a>
      </div>

      <div class="form-section">
        <h3>Basic Information</h3>
        <div class="field-grid" style="margin-bottom:1rem;">
          <div>
            <label class="f-label">Destination Title <span>*</span></label>
            <input type="text" name="title" id="dest-title" class="f-input" required value="<?= e($editing['title'] ?? '') ?>" placeholder="e.g. Serengeti National Park">
          </div>
          <div>
            <label class="f-label">URL Slug <span>*</span></label>
            <input type="text" name="slug" id="dest-slug" class="f-input" required value="<?= e($editing['slug'] ?? '') ?>" placeholder="serengeti-national-park">
            <small style="color:rgba(255,255,255,.45);font-size:.75rem;">Auto-generated from title. Used in URLs.</small>
          </div>
        </div>
        <div class="field-grid-3" style="margin-bottom:1rem;">
          <div>
            <label class="f-label">Country</label>
            <input type="text" name="country" class="f-input" value="<?= e($editing['country'] ?? 'Tanzania') ?>">
          </div>
          <div>
            <label class="f-label">Region</label>
            <input type="text" name="region" class="f-input" value="<?= e($editing['region'] ?? '') ?>" placeholder="e.g. Mara Region">
          </div>
          <div>
            <label class="f-label">Sort Order</label>
            <input type="number" name="sort_order" class="f-input" value="<?= (int)($editing['sort_order'] ?? 0) ?>" min="0" max="99">
          </div>
        </div>
        <div style="margin-bottom:1rem;">
          <label class="f-label">Description <span>*</span></label>
          <textarea name="description" class="f-input" rows="5" placeholder="Describe this destination — landscape, wildlife, culture, unique features..."><?= e($editing['description'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;align-items:center;gap:var(--space-3);">
          <input type="checkbox" name="active" id="dest-active" value="1" <?= ($editing['active'] ?? 1) ? 'checked' : '' ?>>
          <label for="dest-active" class="f-label" style="margin:0;cursor:pointer;">Active (visible on website)</label>
        </div>
      </div>

      <div class="form-section">
        <h3>Travel Information</h3>
        <div class="field-grid" style="margin-bottom:1rem;">
          <div>
            <label class="f-label">Best Season to Visit</label>
            <input type="text" name="best_season" class="f-input" value="<?= e($editing['best_season'] ?? '') ?>" placeholder="e.g. June – October">
          </div>
          <div>
            <label class="f-label">Climate</label>
            <input type="text" name="climate" class="f-input" value="<?= e($editing['climate'] ?? '') ?>" placeholder="e.g. Warm and dry. Average 26°C.">
          </div>
        </div>
        <div>
          <label class="f-label">Visa Information</label>
          <input type="text" name="visa_info" class="f-input" value="<?= e($editing['visa_info'] ?? '') ?>" placeholder="e.g. Tourist e-visa available online. $50 USD.">
        </div>
      </div>

      <div class="form-section">
        <h3>Highlights</h3>
        <label class="f-label">Highlights (one per line)</label>
        <textarea name="highlights" class="f-input" rows="7" placeholder="Great Migration&#10;Big Five Safari&#10;Hot Air Balloon Rides&#10;Luxury Tented Camps"><?= e(str_replace('|', "\n", $editing['highlights'] ?? '')) ?></textarea>
        <small style="color:rgba(255,255,255,.45);font-size:.75rem;">Enter each highlight on its own line. These appear as badges on the destination card.</small>
      </div>

      <div class="form-section">
        <h3>Featured Image</h3>
        <?php if (!empty($editing['image'])): ?>
        <div style="margin-bottom:1rem;">
          <p style="font-size:.8rem;color:rgba(255,255,255,.45);margin-bottom:var(--space-2);">Current image:</p>
          <img src="<?= e($editing['image']) ?>" alt="current" class="thumb-preview" style="max-height:200px;width:auto;">
        </div>
        <?php endif; ?>
        <label class="f-label">Upload New Image</label>
        <div class="upload-area" id="img-drop" onclick="document.getElementById('image_file').click()">
          <div style="font-size:2rem;margin-bottom:var(--space-2);">??</div>
          <p style="font-size:.85rem;color:rgba(255,255,255,.45);margin:0;">Click to upload or drag & drop</p>
          <p style="font-size:.75rem;color:rgba(255,255,255,.45);margin:var(--space-1) 0 0;">JPG, PNG, WebP — max 8MB</p>
          <img id="img-preview" src="" alt="" class="thumb-preview" style="display:none;">
        </div>
        <input type="file" name="image_file" id="image_file" accept="image/*" style="display:none;">
        <div style="margin-top:1rem;">
          <label class="f-label">— OR paste an image URL —</label>
          <input type="text" name="image_url" id="image_url" class="f-input" value="" placeholder="https://images.unsplash.com/...">
          <small style="color:rgba(255,255,255,.45);font-size:.75rem;">Uploading a file takes priority over the URL field.</small>
        </div>
      </div>

      <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
        <a href="destinations.php" class="btn btn--outline btn--sm">Cancel</a>
        <button type="submit" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-save" style="font-size:.6rem"></i> <?= $editing ? 'Update Destination' : 'Add Destination' ?>
        </button>
      </div>
    </form>

    <?php else: ?>
    <!-- --- DESTINATIONS LIST --------------------------- -->
    <div class="adm-card">
      <?php if (empty($destinations)): ?>
      <div style="padding:3rem;text-align:center;color:rgba(255,255,255,.3)">
        <i class="fas fa-map-marked-alt" style="font-size:2.5rem;margin-bottom:.75rem;display:block;color:rgba(255,255,255,.12)"></i>
        <p style="margin-bottom:.75rem">No destinations yet.</p>
        <a href="?add=1" style="color:#10b981;font-weight:600;font-family:'Montserrat',sans-serif;font-size:.82rem">Add your first destination ?</a>
      </div>
      <?php else: ?>
      <table class="adm-table" style="min-width:900px;">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th style="width:80px;">Image</th>
            <th>Title</th>
            <th>Region</th>
            <th>Season</th>
            <th>Highlights</th>
            <th style="width:80px;">Status</th>
            <th style="width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($destinations as $dest): ?>
          <tr class="dest-row">
            <td><span class="sort-num"><?= (int)$dest['sort_order'] ?></span></td>
            <td>
              <?php if ($dest['image']): ?>
              <img src="<?= e($dest['image']) ?>" alt="" class="dest-thumb">
              <?php else: ?>
              <div style="width:64px;height:44px;background:rgba(255,255,255,.04);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">???</div>
              <?php endif; ?>
            </td>
            <td>
              <strong style="font-family:var(--font-heading);color:#10b981;"><?= e($dest['title']) ?></strong>
              <div style="font-size:.75rem;color:rgba(255,255,255,.45);"><?= e($dest['country']) ?> <?= $dest['region'] ? '· '.e($dest['region']) : '' ?></div>
              <div style="font-size:.72rem;color:rgba(255,255,255,.45);font-family:var(--font-nav);">/<?= e($dest['slug']) ?></div>
            </td>
            <td style="font-size:.85rem;"><?= e($dest['region']) ?></td>
            <td style="font-size:.82rem;color:#10b981;"><?= e($dest['best_season']) ?></td>
            <td>
              <div class="hl-list">
                <?php foreach (array_slice(explode('|', $dest['highlights']), 0, 3) as $hl): ?>
                <span class="hl-pill"><?= e($hl) ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <?php if ($dest['active']): ?>
              <span class="badge badge--confirmed">Active</span>
              <?php else: ?>
              <span class="badge badge--cancelled">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;">
                <a href="?edit=<?= $dest['id'] ?>" class="btn-sm btn-edit">?? Edit</a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $dest['id'] ?>">
                  <button type="submit" class="btn-sm btn-toggle <?= $dest['active'] ? '' : 'inactive' ?>">
                    <?= $dest['active'] ? '? Deactivate' : '? Activate' ?>
                  </button>
                </form>
                <form method="POST" onsubmit="return confirm('Delete «<?= e(addslashes($dest['title'])) ?>»? This cannot be undone.');">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $dest['id'] ?>">
                  <button type="submit" class="btn-sm btn-delete">??</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
/* Auto-slug from title */
const titleEl = document.getElementById('dest-title');
const slugEl  = document.getElementById('dest-slug');
if (titleEl && slugEl) {
  titleEl.addEventListener('input', function () {
    if (slugEl.dataset.manual) return;
    slugEl.value = this.value.toLowerCase().trim()
      .replace(/[^a-z0-9\s\-]/g, '')
      .replace(/[\s\-]+/g, '-')
      .replace(/^-+|-+$/g, '');
  });
  slugEl.addEventListener('input', () => { slugEl.dataset.manual = '1'; });
}

/* Image preview */
const fileInput = document.getElementById('image_file');
const preview   = document.getElementById('img-preview');
const urlInput  = document.getElementById('image_url');
if (fileInput) {
  fileInput.addEventListener('change', function () {
    if (this.files[0]) {
      preview.src = URL.createObjectURL(this.files[0]);
      preview.style.display = 'block';
      if (urlInput) urlInput.value = '';
    }
  });
}

/* Drag and drop */
const dropZone = document.getElementById('img-drop');
if (dropZone && fileInput) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#10b981'; });
  dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = ''; });
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = '';
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      fileInput.files = dt.files;
      preview.src = URL.createObjectURL(e.dataTransfer.files[0]);
      preview.style.display = 'block';
      if (urlInput) urlInput.value = '';
    }
  });
}
</script>
</body>
</html>
