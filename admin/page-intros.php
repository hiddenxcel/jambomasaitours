<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = sanitizeInt($_POST['id'] ?? 0, 0);
        $slug        = sanitizeInput($_POST['page_slug'] ?? '');
        $title       = sanitizeInput($_POST['title']     ?? '');
        $subtitle    = sanitizeInput($_POST['subtitle']  ?? '');
        $active      = isset($_POST['active']) ? 1 : 0;
        $existingBG  = sanitizeInput($_POST['existing_bg'] ?? '');
        $urlBG       = sanitizeInput($_POST['bg_image']    ?? '');

        if (empty($slug))  $errors[] = 'Page slug is required.';
        if (empty($title)) $errors[] = 'Title is required.';

        $bgResult = handleImageUpload('bg_file', $urlBG ?: $existingBG);
        if (isset($bgResult['error'])) $errors[] = $bgResult['error'];
        $bgImage = $bgResult['url'] ?? $existingBG;

        if (empty($errors)) {
            if ($id) {
                $db->prepare("UPDATE page_intros SET page_slug=:slug,title=:t,subtitle=:s,bg_image=:bg,active=:a WHERE id=:id")
                   ->execute([':slug'=>$slug,':t'=>$title,':s'=>$subtitle,':bg'=>$bgImage,':a'=>$active,':id'=>$id]);
                $success = 'Page intro updated.';
            } else {
                $db->prepare("INSERT INTO page_intros (page_slug,title,subtitle,bg_image,active) VALUES (:slug,:t,:s,:bg,:a)")
                   ->execute([':slug'=>$slug,':t'=>$title,':s'=>$subtitle,':bg'=>$bgImage,':a'=>$active]);
                $success = 'Page intro added.';
            }
            redirect(SITE_URL . '/admin/page-intros.php?msg=' . urlencode($success));
        }
    }

    if ($action === 'toggle') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("UPDATE page_intros SET active = 1 - active WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/page-intros.php');
    }
}

$msg      = sanitizeInput($_GET['msg'] ?? '');
$editId   = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing  = null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM page_intros WHERE id=?");
    $s->execute([$editId]);
    $editing = $s->fetch();
}
$intros    = $db->query("SELECT * FROM page_intros ORDER BY page_slug ASC")->fetchAll();
$csrfToken = generateCsrfToken();

$knownPages = ['tours','destinations','about','gallery','blog','booking','contact'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Intros | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">

  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Page Intros</h1>
        <p style="color:var(--text-muted);font-size:.9rem;">Manage the cinematic banner at the top of each page</p>
      </div>
      <a href="<?= url() ?>" class="btn btn--outline btn--sm" target="_blank">View Site</a>
    </div>

    <?php if ($msg): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#166534;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      <?= e($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      <?php foreach ($errors as $err): ?><div>— <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 400px;gap:var(--space-8);align-items:start;">

      <!-- Page Intros List -->
      <div>
        <table class="data-table" style="width:100%;">
          <thead>
            <tr>
              <th>Page</th>
              <th>Title</th>
              <th>BG Image</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($intros as $intro): ?>
            <tr>
              <td>
                <span style="font-family:var(--font-nav);font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--color-brown);">
                  <?= e($intro['page_slug']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight:600;color:var(--text-dark);"><?= e($intro['title']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);"><?= e(truncate($intro['subtitle'] ?? '', 55)) ?></div>
              </td>
              <td>
                <?php if ($intro['bg_image']): ?>
                <img src="<?= e($intro['bg_image']) ?>" alt="" style="width:60px;height:38px;object-fit:cover;border-radius:var(--radius-sm);" loading="lazy">
                <?php else: ?>
                <span style="font-size:.78rem;color:var(--text-muted);">Default</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $intro['id'] ?>">
                  <button type="submit" class="badge <?= $intro['active'] ? 'badge--success' : 'badge--warning' ?>" style="cursor:pointer;border:none;background:none;">
                    <?= $intro['active'] ? 'Active' : 'Hidden' ?>
                  </button>
                </form>
              </td>
              <td>
                <a href="?edit=<?= $intro['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($intros)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:var(--space-8);">No page intros configured. Add one using the form.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Edit / Add Form -->
      <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-md);position:sticky;top:100px;">
        <h2 style="font-family:var(--font-heading);font-size:1.2rem;color:var(--color-brown);margin-bottom:var(--space-6);">
          <?= $editing ? 'Edit Page Intro' : 'Add Page Intro' ?>
        </h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">
          <input type="hidden" name="existing_bg" value="<?= e($editing['bg_image'] ?? '') ?>">

          <div class="form-group">
            <label class="form-label">Page Slug <span style="color:#ef4444;">*</span></label>
            <?php if ($editing): ?>
            <input type="text" class="form-control" name="page_slug" required value="<?= e($editing['page_slug']) ?>">
            <?php else: ?>
            <select class="form-control" name="page_slug" required>
              <option value="">— Select page —</option>
              <?php foreach ($knownPages as $pg): ?>
              <option value="<?= $pg ?>"><?= $pg ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label">Title <span style="color:#ef4444;">*</span></label>
            <input type="text" class="form-control" name="title" required
                   value="<?= e($editing['title'] ?? '') ?>"
                   placeholder="Our Safari Tours">
          </div>

          <div class="form-group">
            <label class="form-label">Subtitle</label>
            <textarea class="form-control" name="subtitle" rows="2"
                      placeholder="Handcrafted journeys across Tanzania..."><?= e($editing['subtitle'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Background Image</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-2);">
              <div>
                <label class="form-label" style="font-size:.78rem;">Upload from computer</label>
                <input type="file" class="form-control" name="bg_file" accept="image/jpeg,image/png,image/webp,image/gif" id="pi-file">
                <small style="color:var(--text-muted);font-size:.72rem;">JPG/PNG/WebP — Max 8MB</small>
              </div>
              <div>
                <label class="form-label" style="font-size:.78rem;">Or paste URL</label>
                <input type="url" class="form-control" name="bg_image" id="pi-url"
                       value="<?= e($editing['bg_image'] ?? '') ?>"
                       placeholder="https://images.unsplash.com/...">
              </div>
            </div>
            <small style="color:var(--text-muted);font-size:.72rem;">Recommended: 1920–600px. Leave empty for default.</small>
          </div>

          <?php if (!empty($editing['bg_image'])): ?>
          <div style="margin-bottom:var(--space-4);">
            <img src="<?= e($editing['bg_image']) ?>" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:var(--radius-md);">
          </div>
          <?php endif; ?>
          <script>
          const pf=document.getElementById('pi-file'),pu=document.getElementById('pi-url');
          if(pf&&pu) pf.addEventListener('change',()=>{if(pf.files.length) pu.value='';});
          </script>

          <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-6);">
            <input type="checkbox" id="intro-active" name="active" value="1"
                   <?= (!$editing || $editing['active']) ? 'checked' : '' ?>>
            <label class="form-label" for="intro-active" style="margin-bottom:0;">Active (show on page)</label>
          </div>

          <div style="display:flex;gap:var(--space-3);">
            <button type="submit" class="btn btn--gold w-full"><?= $editing ? 'Update' : 'Add Page Intro' ?></button>
            <?php if ($editing): ?>
            <a href="page-intros.php" class="btn btn--outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>
</body>
</html>
