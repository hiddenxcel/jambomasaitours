<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS tour_image_library (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    image       VARCHAR(500) NOT NULL,
    caption     VARCHAR(255) NOT NULL DEFAULT '',
    destination VARCHAR(200) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $captions = $_POST['captions'] ?? [];
        $destination = sanitizeInput($_POST['destination'] ?? '');
        $uploaded = 0;

        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['name'] as $i => $fname) {
                if (!$fname) continue;
                $_FILES['_lib'] = [
                    'name' => $_FILES['photos']['name'][$i],
                    'type' => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error' => $_FILES['photos']['error'][$i],
                    'size' => $_FILES['photos']['size'][$i],
                ];
                $r = handleImageUpload('_lib', '');
                if (isset($r['url'])) {
                    $caption = sanitizeInput($captions[$i] ?? '');
                    $db->prepare("INSERT INTO tour_image_library (image, caption, destination) VALUES (?,?,?)")
                       ->execute([$r['url'], $caption, $destination]);
                    $uploaded++;
                }
            }
        }
        redirect(SITE_URL . '/admin/image-library.php?msg=' . urlencode($uploaded . ' photo(s) added to library.'));
    }

    if ($action === 'update_caption') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        $caption = sanitizeInput($_POST['caption'] ?? '');
        $destination = sanitizeInput($_POST['destination'] ?? '');
        if ($id) {
            $db->prepare("UPDATE tour_image_library SET caption=?, destination=? WHERE id=?")
               ->execute([$caption, $destination, $id]);
        }
        redirect(SITE_URL . '/admin/image-library.php?msg=Caption+updated.');
    }

    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM tour_image_library WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/image-library.php?msg=Photo+deleted.');
    }
}

$msg = sanitizeInput($_GET['msg'] ?? '');
$images = $db->query("SELECT * FROM tour_image_library ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();
$missingCaption = count(array_filter($images, fn($i) => empty($i['caption'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Image Library | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;font-weight:700">Image Library</h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.1rem">
          Shared photo pool used by AI to auto-pick matching images for itinerary PDFs — <?= count($images) ?> photo<?= count($images)!==1?'s':'' ?>, <?= $missingCaption ?> missing a caption
        </p>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <div style="background:rgba(96,165,250,.06);border:1px solid rgba(96,165,250,.15);border-radius:var(--radius-lg);padding:.9rem 1.1rem;margin-bottom:1.25rem;font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.5);line-height:1.6">
      <i class="fas fa-info-circle" style="color:#60a5fa;margin-right:.3rem"></i>
      Write a short, descriptive caption for each photo (e.g. "savanna sunset with wildebeest", "luxury tented camp bedroom", "Maasai village cultural dance"). When a traveller downloads an itinerary PDF, AI reads each day's description and picks the best-matching photos from this library automatically.
    </div>

    <!-- Upload form -->
    <div class="adm-card" style="padding:1.25rem;margin-bottom:1.5rem">
      <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1rem;color:#fff;margin-bottom:1rem">Add Photos</h3>
      <form method="POST" enctype="multipart/form-data" id="lib-upload-form">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="upload">

        <div class="f-group">
          <label class="f-label">Destination / Region <span class="f-hint" style="text-transform:none;letter-spacing:0">(optional, helps narrow matching)</span></label>
          <input type="text" class="f-input" name="destination" placeholder="e.g. Serengeti, Zanzibar, Ngorongoro" style="max-width:320px">
        </div>

        <div class="f-group">
          <label class="f-label">Photos</label>
          <input type="file" class="f-input" name="photos[]" id="lib-files" multiple accept="image/jpeg,image/png,image/webp" required>
          <div class="f-hint">JPG/PNG/WebP — Max 8MB each. After choosing files, caption fields will appear below.</div>
        </div>

        <div id="lib-captions" style="display:flex;flex-direction:column;gap:.5rem;margin:1rem 0"></div>

        <button type="submit" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-upload" style="font-size:.6rem"></i> Upload Photos
        </button>
      </form>
    </div>

    <!-- Gallery -->
    <?php if (empty($images)): ?>
    <div style="text-align:center;padding:2.5rem;background:rgba(255,255,255,.02);border:2px dashed rgba(255,255,255,.07);border-radius:var(--radius-lg)">
      <i class="fas fa-images" style="font-size:2rem;color:rgba(255,255,255,.12);margin-bottom:.5rem;display:block"></i>
      <p style="color:rgba(255,255,255,.3);font-size:.82rem">No photos in the library yet.</p>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.85rem">
      <?php foreach ($images as $img): ?>
      <div class="adm-card" style="overflow:hidden">
        <img src="<?= e($img['image']) ?>" alt="" style="width:100%;height:140px;object-fit:cover;display:block">
        <form method="POST" style="padding:.75rem .85rem">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="update_caption">
          <input type="hidden" name="id" value="<?= $img['id'] ?>">
          <input type="text" class="f-input" name="caption" value="<?= e($img['caption']) ?>" placeholder="Describe this photo…" style="font-size:.72rem;padding:.5rem .65rem;margin-bottom:.4rem<?= empty($img['caption']) ? ';border-color:rgba(239,68,68,.4)' : '' ?>">
          <input type="text" class="f-input" name="destination" value="<?= e($img['destination']) ?>" placeholder="Destination (optional)" style="font-size:.68rem;padding:.4rem .65rem;margin-bottom:.5rem">
          <div style="display:flex;gap:.4rem">
            <button type="submit" class="btn btn--outline btn--sm" style="flex:1;font-size:.62rem;padding:.35rem">
              <i class="fas fa-save" style="font-size:.55rem"></i> Save
            </button>
          </div>
        </form>
        <form method="POST" style="padding:0 .85rem .75rem" onsubmit="return confirm('Delete this photo?')">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $img['id'] ?>">
          <button type="submit" class="btn btn--danger btn--sm" style="width:100%;font-size:.62rem;padding:.35rem">
            <i class="fas fa-trash" style="font-size:.55rem"></i> Delete
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
const libFiles = document.getElementById('lib-files');
const libCaptions = document.getElementById('lib-captions');
libFiles.addEventListener('change', () => {
  libCaptions.innerHTML = '';
  Array.from(libFiles.files).forEach((file, i) => {
    const row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;gap:.6rem';
    row.innerHTML = `
      <span style="font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.4);width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0">${file.name}</span>
      <input type="text" class="f-input" name="captions[${i}]" placeholder="Describe this photo (e.g. savanna sunset with wildebeest)" style="font-size:.75rem">
    `;
    libCaptions.appendChild(row);
  });
});
</script>
</body>
</html>
