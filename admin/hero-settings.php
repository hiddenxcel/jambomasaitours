<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db     = getDB();
$errors = [];

/* -- Video upload handler ----------------------- */
function handleVideoUpload(string $fieldName, string $existingUrl = ''): array {
    if (empty($_FILES[$fieldName]['name'])) {
        return ['url' => $existingUrl];
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [1=>'File too large (server limit: ' . ini_get('upload_max_filesize') . ')',
                 2=>'File too large',3=>'Partial upload',4=>'No file uploaded',
                 6=>'No temp folder',7=>'Cannot write to disk'];
        return ['error' => $msgs[$file['error']] ?? 'Upload error ' . $file['error']];
    }
    $maxBytes = 40 * 1024 * 1024; // 40 MB
    if ($file['size'] > $maxBytes) {
        return ['error' => 'Video too large. Maximum 40MB allowed.'];
    }
    $allowedMimes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    /* some systems detect mp4 as application/octet-stream — fall back to extension check */
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['mp4', 'webm', 'ogv', 'ogg', 'mov'];
    if (!in_array($mime, $allowedMimes) && !in_array($ext, $allowedExts)) {
        return ['error' => 'Only MP4, WebM, OGG or MOV video files are allowed.'];
    }
    $safeExt  = in_array($ext, $allowedExts) ? $ext : 'mp4';
    $filename = 'video_' . uniqid('', true) . '.' . $safeExt;
    $destPath = UPLOADS_PATH . $filename;
    if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Could not save the video file. Check uploads/ folder permissions.'];
    }
    return ['url' => UPLOADS_URL . $filename];
}

/* -- POST handler ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }

    $existingVideo = sanitizeInput($_POST['existing_video']    ?? '');
    $existingFB    = sanitizeInput($_POST['existing_fallback'] ?? '');
    $urlVideo      = sanitizeInput($_POST['video_url']         ?? '');
    $urlFB         = sanitizeInput($_POST['fallback_image']    ?? '');
    $clearVideo    = isset($_POST['clear_video']);

    /* Handle video — file upload takes priority, then URL, then existing (or clear) */
    if ($clearVideo) {
        $videoUrl = '';
    } else {
        $videoResult = handleVideoUpload('video_file', $urlVideo ?: $existingVideo);
        if (isset($videoResult['error'])) $errors[] = $videoResult['error'];
        $videoUrl = $videoResult['url'] ?? $existingVideo;
    }

    /* Handle fallback image */
    $fbResult = handleImageUpload('fallback_file', $urlFB ?: $existingFB);
    if (isset($fbResult['error'])) $errors[] = $fbResult['error'];
    $fallbackImage = $fbResult['url'] ?? $existingFB;

    if (empty($fallbackImage)) $errors[] = 'Fallback image is required.';

    if (empty($errors)) {
        $existing = $db->query("SELECT id FROM hero_video LIMIT 1")->fetch();
        if ($existing) {
            $db->prepare("UPDATE hero_video SET video_url=:v, fallback_image=:f WHERE id=:id")
               ->execute([':v' => $videoUrl, ':f' => $fallbackImage, ':id' => $existing['id']]);
        } else {
            $db->prepare("INSERT INTO hero_video (video_url, fallback_image) VALUES (:v, :f)")
               ->execute([':v' => $videoUrl, ':f' => $fallbackImage]);
        }
        redirect(SITE_URL . '/admin/hero-settings.php?msg=Settings+saved+successfully.');
    }
}

$msg      = sanitizeInput($_GET['msg'] ?? '');
$settings = $db->query("SELECT * FROM hero_video LIMIT 1")->fetch();
$csrfToken = generateCsrfToken();

$phpUploadLimit = ini_get('upload_max_filesize');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hero Video Settings | Admin</title>
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
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--color-brown);">Hero Video Settings</h1>
        <p style="color:var(--text-muted);font-size:.9rem;">Upload or set the homepage hero background video and fallback image</p>
      </div>
      <a href="<?= url() ?>" class="btn btn--outline btn--sm" target="_blank">View Site</a>
    </div>

    <?php if ($msg): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#166534;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      ? <?= e($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;padding:var(--space-4) var(--space-6);border-radius:var(--radius-md);margin-bottom:var(--space-6);">
      <?php foreach ($errors as $err): ?><div>— <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="max-width:720px;">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="existing_video"    value="<?= e($settings['video_url']      ?? '') ?>">
        <input type="hidden" name="existing_fallback" value="<?= e($settings['fallback_image'] ?? '') ?>">

        <!-- --- VIDEO SECTION ---------------------------------------- -->
        <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-md);margin-bottom:var(--space-6);">
          <h2 style="font-family:var(--font-heading);font-size:1.1rem;color:var(--color-brown);margin-bottom:var(--space-2);">Background Video <span style="font-family:var(--font-body);font-size:.8rem;color:var(--text-muted);font-weight:400;">(optional)</span></h2>
          <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:var(--space-6);">If no video is set, the fallback image will fill the full hero. Video plays muted and loops automatically.</p>

          <!-- Current video preview -->
          <?php if (!empty($settings['video_url'])): ?>
          <div style="margin-bottom:var(--space-6);background:var(--color-black-soft);border-radius:var(--radius-md);overflow:hidden;">
            <video controls muted style="width:100%;max-height:240px;display:block;object-fit:cover;">
              <source src="<?= e($settings['video_url']) ?>" type="video/mp4">
            </video>
            <div style="padding:var(--space-3) var(--space-4);font-size:.78rem;color:rgba(255,255,255,.5);">
              Current: <?= e(basename($settings['video_url'])) ?>
              &nbsp;—&nbsp;
              <a href="<?= e($settings['video_url']) ?>" target="_blank" style="color:var(--color-gold);">Open</a>
            </div>
          </div>
          <?php endif; ?>

          <!-- Upload -->
          <div style="border:2px dashed rgba(201,168,76,.4);border-radius:var(--radius-lg);padding:var(--space-8);text-align:center;margin-bottom:var(--space-5);cursor:pointer;" id="video-drop-zone">
            <div style="font-size:2rem;margin-bottom:var(--space-3);">??</div>
            <div style="font-weight:600;color:var(--text-dark);margin-bottom:var(--space-2);">Upload video from your computer</div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:var(--space-4);">MP4, WebM or MOV — Max <?= $phpUploadLimit ?></div>
            <input type="file" name="video_file" id="video-file-input"
                   accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.mov,.ogv"
                   style="display:none;">
            <button type="button" class="btn btn--outline" onclick="document.getElementById('video-file-input').click()">
              Choose Video File
            </button>
            <div id="video-chosen" style="margin-top:var(--space-3);font-size:.85rem;color:var(--color-gold);display:none;"></div>
          </div>

          <!-- Progress bar (shown while uploading) -->
          <div id="upload-progress" style="display:none;margin-bottom:var(--space-4);">
            <div style="height:6px;background:var(--color-beige-dark);border-radius:3px;overflow:hidden;">
              <div id="upload-bar" style="height:100%;width:0%;background:var(--color-gold);transition:width .2s;"></div>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:var(--space-2);">Uploading— please wait</div>
          </div>

          <div style="text-align:center;color:var(--text-muted);font-size:.82rem;margin:var(--space-4) 0;">— OR —</div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Paste Video URL (MP4 direct link)</label>
            <input type="url" class="form-control" name="video_url" id="video-url-input"
                   value="<?= e($settings['video_url'] ?? '') ?>"
                   placeholder="https://example.com/safari-hero.mp4">
            <small style="color:var(--text-muted);font-size:.78rem;">Use a direct .mp4 link from your hosting or any CDN. Leave empty to use image only.</small>
          </div>

          <?php if (!empty($settings['video_url'])): ?>
          <div style="margin-top:var(--space-4);">
            <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;font-size:.85rem;color:#dc2626;">
              <input type="checkbox" name="clear_video" value="1"> Remove current video (show image only)
            </label>
          </div>
          <?php endif; ?>
        </div>

        <!-- --- FALLBACK IMAGE SECTION ------------------------------ -->
        <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-md);margin-bottom:var(--space-6);">
          <h2 style="font-family:var(--font-heading);font-size:1.1rem;color:var(--color-brown);margin-bottom:var(--space-2);">Fallback Image <span style="color:#ef4444;font-size:.8rem;">*</span></h2>
          <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:var(--space-6);">Shown immediately while video loads, on mobile, or when no video is set. Min 1920–1080px recommended.</p>

          <?php if (!empty($settings['fallback_image'])): ?>
          <div style="margin-bottom:var(--space-5);">
            <img src="<?= e($settings['fallback_image']) ?>" alt="Current fallback"
                 style="width:100%;height:180px;object-fit:cover;border-radius:var(--radius-md);border:1px solid var(--color-beige-dark);">
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:var(--space-2);">Current fallback image</div>
          </div>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
            <div>
              <label class="form-label" style="font-size:.85rem;">Upload from computer</label>
              <input type="file" class="form-control" name="fallback_file" id="fb-file"
                     accept="image/jpeg,image/png,image/webp,image/gif">
              <small style="color:var(--text-muted);font-size:.72rem;">JPG/PNG/WebP — Max 8MB</small>
            </div>
            <div>
              <label class="form-label" style="font-size:.85rem;">Or paste image URL</label>
              <input type="url" class="form-control" name="fallback_image" id="fb-url"
                     value="<?= e($settings['fallback_image'] ?? IMG_HERO) ?>"
                     placeholder="https://images.unsplash.com/...">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn--gold btn--lg" id="save-btn">Save Settings</button>

      </form>

      <!-- Tips -->
      <div style="margin-top:var(--space-6);background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.2);border-radius:var(--radius-md);padding:var(--space-5);">
        <h3 style="font-size:.9rem;font-weight:600;color:var(--color-brown);margin-bottom:var(--space-3);">Tips for Best Results</h3>
        <ul style="font-size:.83rem;color:var(--text-muted);line-height:1.9;margin:0;padding-left:1.2rem;">
          <li><strong>Duration:</strong> 10–30 second loop works best for hero backgrounds.</li>
          <li><strong>File size:</strong> Keep under 20MB for fast page loads (current PHP limit: <?= $phpUploadLimit ?>).</li>
          <li><strong>Format:</strong> MP4 (H.264) is supported by all browsers.</li>
          <li><strong>Resolution:</strong> 1280–720px is enough — video is blurred by the overlay anyway.</li>
          <li><strong>Audio:</strong> Video plays muted automatically (browser requirement).</li>
          <li><strong>Large files:</strong> If upload fails, host the video on Google Drive or Dropbox and paste the direct link.</li>
        </ul>
      </div>
    </div>
  </main>
</div>

<script>
/* File chosen — show filename, clear URL field */
const vfi = document.getElementById('video-file-input');
const vui = document.getElementById('video-url-input');
const vch = document.getElementById('video-chosen');
const vdz = document.getElementById('video-drop-zone');

if (vfi) {
  vfi.addEventListener('change', () => {
    if (vfi.files.length) {
      const f = vfi.files[0];
      vch.textContent = '? ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(1) + ' MB)';
      vch.style.display = 'block';
      vui.value = '';
    }
  });
}

/* Drag-and-drop onto the drop zone */
if (vdz && vfi) {
  vdz.addEventListener('dragover', e => { e.preventDefault(); vdz.style.borderColor = 'var(--color-gold)'; vdz.style.background = 'rgba(201,168,76,.05)'; });
  vdz.addEventListener('dragleave', () => { vdz.style.borderColor = ''; vdz.style.background = ''; });
  vdz.addEventListener('drop', e => {
    e.preventDefault(); vdz.style.borderColor = ''; vdz.style.background = '';
    const dt = e.dataTransfer;
    if (dt.files.length) { vfi.files = dt.files; vfi.dispatchEvent(new Event('change')); }
  });
}

/* Fallback image: clear URL when file chosen */
const ff = document.getElementById('fb-file');
const fu = document.getElementById('fb-url');
if (ff && fu) ff.addEventListener('change', () => { if (ff.files.length) fu.value = ''; });

/* Show fake progress bar while submitting (video upload can be slow) */
document.querySelector('form').addEventListener('submit', function() {
  if (vfi && vfi.files.length) {
    document.getElementById('upload-progress').style.display = 'block';
    document.getElementById('save-btn').disabled = true;
    document.getElementById('save-btn').textContent = 'Uploading—';
    let w = 0;
    const bar = document.getElementById('upload-bar');
    const t = setInterval(() => { w = Math.min(w + Math.random() * 8, 90); bar.style.width = w + '%'; }, 300);
  }
});
</script>
</body>
</html>
