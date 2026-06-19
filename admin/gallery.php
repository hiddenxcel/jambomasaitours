<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db     = getDB();
$errors = [];
$msg    = sanitizeInput($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('Invalid token.'); }
    $action = $_POST['action'] ?? '';

    /* ── Add image ── */
    if ($action === 'add') {
        $title    = sanitizeInput($_POST['title']    ?? '');
        $urlInput = sanitizeInput($_POST['image_url'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? 'wildlife');
        $validCats = ['wildlife','landscapes','culture','safari-life','zanzibar','birds'];

        if (empty($title))    $errors[] = 'Title is required.';
        if (!in_array($category, $validCats)) $errors[] = 'Invalid category.';

        $imgResult = handleImageUpload('image_file', $urlInput);
        if (isset($imgResult['error'])) $errors[] = $imgResult['error'];
        $imageUrl = $imgResult['url'] ?? '';
        if (empty($imageUrl)) $errors[] = 'Please upload an image or provide a URL.';

        if (empty($errors)) {
            $db->prepare("INSERT INTO gallery (title, image, category) VALUES (?,?,?)")->execute([$title, $imageUrl, $category]);
            redirect(SITE_URL . '/admin/gallery.php?msg=Image+added.');
        }
    }

    /* ── Delete ── */
    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM gallery WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/gallery.php?msg=Image+deleted.');
    }
}

$gallery   = $db->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <div class="admin-header">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Gallery</h1>
        <p style="color:var(--text-muted);font-size:.9rem;"><?= count($gallery) ?> images</p>
      </div>
    </div>

    <?php if ($msg): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#166534;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Add Image Form -->
    <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-md);margin-bottom:var(--space-8);">
      <h2 style="font-family:var(--font-heading);font-size:1.1rem;color:var(--color-brown);margin-bottom:var(--space-6);">Add Image</h2>

      <?php if (!empty($errors)): ?>
      <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;padding:var(--space-4) var(--space-5);border-radius:var(--radius-md);margin-bottom:var(--space-5);">
        <?php foreach ($errors as $e_): ?><div>• <?= e($e_) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="add">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:var(--space-4);align-items:end;">
          <div>
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Lion at Sunset" required>
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
              <?php foreach (['wildlife','landscapes','culture','safari-life','zanzibar','birds'] as $cat): ?>
              <option value="<?= $cat ?>"><?= ucwords(str_replace('-',' ',$cat)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Upload File</label>
            <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" id="gal-file">
            <small style="color:var(--text-muted);font-size:.72rem;">Max 8MB</small>
          </div>
          <button type="submit" class="btn btn--gold" style="height:42px;">Add</button>
        </div>
        <div style="margin-top:var(--space-3);">
          <label class="form-label" style="font-size:.8rem;">Or paste image URL instead</label>
          <input type="url" name="image_url" class="form-control" id="gal-url" placeholder="https://images.unsplash.com/..." style="max-width:540px;">
        </div>
      </form>
    </div>

    <!-- Gallery Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:var(--space-4);">
      <?php foreach ($gallery as $g): ?>
      <div style="background:var(--color-white);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);">
        <img src="<?= e($g['image']) ?>" alt="<?= e($g['title']) ?>"
             style="width:100%;height:130px;object-fit:cover;" loading="lazy">
        <div style="padding:var(--space-3);">
          <div style="font-size:.8rem;font-weight:600;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($g['title']) ?></div>
          <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:var(--space-3);"><?= e($g['category']) ?></div>
          <form method="POST">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($g['id']) ?>">
            <button type="submit" class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:none;width:100%;" onclick="return confirm('Delete this image?')">Delete</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($gallery)): ?>
      <p style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:var(--space-10);">No images yet. Add one above.</p>
      <?php endif; ?>
    </div>

  </main>
</div>
<script>
const gf = document.getElementById('gal-file');
const gu = document.getElementById('gal-url');
if (gf && gu) gf.addEventListener('change', () => { if (gf.files.length) gu.value = ''; });
</script>
</body>
</html>
