<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();
$errors  = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = sanitizeInt($_POST['id'] ?? 0, 0);
        $headline = sanitizeInput($_POST['headline'] ?? '');
        $subtitle = sanitizeInput($_POST['subtitle'] ?? '');
        $btn1_text = sanitizeInput($_POST['btn1_text'] ?? '');
        $btn1_link = sanitizeInput($_POST['btn1_link'] ?? '');
        $btn2_text = sanitizeInput($_POST['btn2_text'] ?? '');
        $btn2_link = sanitizeInput($_POST['btn2_link'] ?? '');
        $sort_order = sanitizeInt($_POST['sort_order'] ?? 0, 0, 99);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($headline)) $errors[] = 'Headline is required.';

        if (empty($errors)) {
            if ($id) {
                $stmt = $db->prepare("UPDATE hero_slides SET headline=:h,subtitle=:s,btn1_text=:b1t,btn1_link=:b1l,btn2_text=:b2t,btn2_link=:b2l,sort_order=:so,active=:a WHERE id=:id");
                $stmt->execute([':h'=>$headline,':s'=>$subtitle,':b1t'=>$btn1_text,':b1l'=>$btn1_link,':b2t'=>$btn2_text,':b2l'=>$btn2_link,':so'=>$sort_order,':a'=>$active,':id'=>$id]);
                $success = 'Slide updated.';
            } else {
                $stmt = $db->prepare("INSERT INTO hero_slides (headline,subtitle,btn1_text,btn1_link,btn2_text,btn2_link,sort_order,active) VALUES (:h,:s,:b1t,:b1l,:b2t,:b2l,:so,:a)");
                $stmt->execute([':h'=>$headline,':s'=>$subtitle,':b1t'=>$btn1_text,':b1l'=>$btn1_link,':b2t'=>$btn2_text,':b2l'=>$btn2_link,':so'=>$sort_order,':a'=>$active]);
                $success = 'Slide added.';
            }
            redirect(SITE_URL . '/admin/hero-slides.php?msg=' . urlencode($success));
        }
    }

    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) {
            $db->prepare("DELETE FROM hero_slides WHERE id=?")->execute([$id]);
        }
        redirect(SITE_URL . '/admin/hero-slides.php?msg=Slide+deleted.');
    }

    if ($action === 'toggle') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) {
            $db->prepare("UPDATE hero_slides SET active = 1 - active WHERE id=?")->execute([$id]);
        }
        redirect(SITE_URL . '/admin/hero-slides.php');
    }
}

$msg      = sanitizeInput($_GET['msg'] ?? '');
$editId   = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing  = null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM hero_slides WHERE id=?");
    $s->execute([$editId]);
    $editing = $s->fetch();
}
$slides = $db->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC")->fetchAll();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hero Slides | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <?php include 'includes/admin_sidebar.php'; ?>

  <!-- Main -->
  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Hero Slides</h1>
        <p style="color:var(--text-muted);font-size:.9rem;">Manage homepage hero slideshow text and buttons</p>
      </div>
      <a href="<?= url() ?>" class="btn btn--outline btn--sm" target="_blank">View Site</a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert--success" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#166534;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      <?= e($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="alert alert--error" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      <?php foreach ($errors as $err): ?><div>— <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:var(--space-8);align-items:start;">

      <!-- Slides List -->
      <div>
        <table class="data-table" style="width:100%;">
          <thead>
            <tr>
              <th>Order</th>
              <th>Headline</th>
              <th>Buttons</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($slides as $sl): ?>
            <tr>
              <td style="color:var(--text-muted);text-align:center;"><?= e($sl['sort_order']) ?></td>
              <td>
                <div style="font-weight:600;color:var(--text-dark);"><?= e($sl['headline']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);"><?= e(truncate($sl['subtitle'] ?? '', 60)) ?></div>
              </td>
              <td style="font-size:.8rem;color:var(--text-muted);">
                <?php if ($sl['btn1_text']): ?><div>1: <?= e($sl['btn1_text']) ?></div><?php endif; ?>
                <?php if ($sl['btn2_text']): ?><div>2: <?= e($sl['btn2_text']) ?></div><?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                  <button type="submit" class="badge <?= $sl['active'] ? 'badge--success' : 'badge--warning' ?>" style="cursor:pointer;border:none;background:none;">
                    <?= $sl['active'] ? 'Active' : 'Hidden' ?>
                  </button>
                </form>
              </td>
              <td style="white-space:nowrap;">
                <a href="?edit=<?= $sl['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this slide?');">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                  <button type="submit" class="btn btn--sm" style="background:#ef4444;color:#fff;border:none;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($slides)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:var(--space-8);">No slides yet. Add one using the form.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Add / Edit Form -->
      <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-md);position:sticky;top:100px;">
        <h2 style="font-family:var(--font-heading);font-size:1.2rem;color:var(--color-brown);margin-bottom:var(--space-6);">
          <?= $editing ? 'Edit Slide' : 'Add New Slide' ?>
        </h2>
        <form method="POST">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">

          <div class="form-group">
            <label class="form-label">Headline <span style="color:#ef4444;">*</span></label>
            <input type="text" class="form-control" name="headline" required
                   value="<?= e($editing['headline'] ?? '') ?>"
                   placeholder="Experience Tanzania Like Never Before">
          </div>
          <div class="form-group">
            <label class="form-label">Subtitle</label>
            <textarea class="form-control" name="subtitle" rows="2"
                      placeholder="Luxury Safaris, Maasai Culture..."><?= e($editing['subtitle'] ?? '') ?></textarea>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-4);">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Button 1 Text</label>
              <input type="text" class="form-control" name="btn1_text"
                     value="<?= e($editing['btn1_text'] ?? '') ?>" placeholder="Explore Safaris">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Button 1 Link</label>
              <input type="text" class="form-control" name="btn1_link"
                     value="<?= e($editing['btn1_link'] ?? '') ?>" placeholder="tours.php">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-4);">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Button 2 Text</label>
              <input type="text" class="form-control" name="btn2_text"
                     value="<?= e($editing['btn2_text'] ?? '') ?>" placeholder="Book Safari">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Button 2 Link</label>
              <input type="text" class="form-control" name="btn2_link"
                     value="<?= e($editing['btn2_link'] ?? '') ?>" placeholder="booking.php or https://...">
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-6);">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" min="0" max="99"
                     value="<?= e($editing['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;gap:var(--space-2);">
              <input type="checkbox" id="slide-active" name="active" value="1"
                     <?= (!$editing || $editing['active']) ? 'checked' : '' ?>>
              <label class="form-label" for="slide-active" style="margin-bottom:0;">Active</label>
            </div>
          </div>

          <div style="display:flex;gap:var(--space-3);">
            <button type="submit" class="btn btn--gold w-full"><?= $editing ? 'Update Slide' : 'Add Slide' ?></button>
            <?php if ($editing): ?>
            <a href="hero-slides.php" class="btn btn--outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>
</body>
</html>
