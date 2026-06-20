<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once 'includes/auth_guard.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* Ensure email settings exist in site_settings */
$emailKeys = ['smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'','smtp_from_email'=>SITE_EMAIL,'smtp_from_name'=>SITE_NAME,'admin_notify_email'=>SITE_EMAIL,'notify_on_booking'=>'1','notify_on_contact'=>'1','notify_customer'=>'1','ga4_measurement_id'=>'','google_site_verification'=>'','favicon_url'=>'','social_facebook'=>'','social_instagram'=>'','social_twitter'=>'','social_youtube'=>'','social_tiktok'=>'','social_tripadvisor'=>'','tawkto_widget_id'=>''];
$ins = $db->prepare("INSERT IGNORE INTO site_settings (setting_key,setting_value) VALUES (?,?)");
foreach ($emailKeys as $k => $v) { try { $ins->execute([$k, $v]); } catch (\Throwable $e) {} }

/* ─── Save settings ───────────────────────────────── */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $_POST[CSRF_TOKEN_NAME])) {
        http_response_code(403); die('Invalid CSRF token.');
    }

    $siteName    = trim($_POST['site_name'] ?? 'Jambo Masai Tours');
    $siteTagline = trim($_POST['site_tagline'] ?? 'Tanzania Safari Experts');
    $logoWidth   = max(60, min(400, (int)($_POST['logo_width'] ?? 160)));

    /* Email notification settings */
    $smtpHost         = trim($_POST['smtp_host']           ?? '');
    $smtpPort         = trim($_POST['smtp_port']           ?? '587');
    $smtpUser         = trim($_POST['smtp_user']           ?? '');
    $smtpPass         = trim($_POST['smtp_pass']           ?? '');
    $smtpFromEmail    = trim($_POST['smtp_from_email']     ?? SITE_EMAIL);
    $smtpFromName     = trim($_POST['smtp_from_name']      ?? SITE_NAME);
    $adminNotifyEmail = trim($_POST['admin_notify_email']  ?? SITE_EMAIL);
    $notifyBooking      = isset($_POST['notify_on_booking']) ? '1' : '0';
    $notifyContact      = isset($_POST['notify_on_contact']) ? '1' : '0';
    $notifyCustomer     = isset($_POST['notify_customer'])   ? '1' : '0';
    $ga4Id              = trim($_POST['ga4_measurement_id']    ?? '');
    $gVerification      = trim($_POST['google_site_verification'] ?? '');
    $tawktoId           = trim($_POST['tawkto_widget_id'] ?? '');

    /* Social media links */
    $socialFacebook     = trim($_POST['social_facebook']    ?? '');
    $socialInstagram    = trim($_POST['social_instagram']   ?? '');
    $socialTwitter      = trim($_POST['social_twitter']     ?? '');
    $socialYoutube      = trim($_POST['social_youtube']     ?? '');
    $socialTiktok       = trim($_POST['social_tiktok']      ?? '');
    $socialTripadvisor  = trim($_POST['social_tripadvisor'] ?? '');

    $existingFavicon    = trim($_POST['existing_favicon'] ?? '');
    $urlFavicon         = trim($_POST['favicon_url_input'] ?? '');

    /* Handle favicon upload */
    $faviconResult = handleImageUpload('favicon_file', $urlFavicon ?: $existingFavicon);
    $faviconUrl    = $faviconResult['url'] ?? $existingFavicon;

    /* Handle logo upload */
    $existingLogo = trim($_POST['existing_logo'] ?? '');
    $urlLogo      = trim($_POST['logo_url_input'] ?? '');

    /* Clear logo? */
    if (isset($_POST['clear_logo'])) {
        $logoUrl = '';
    } else {
        $result = handleImageUpload('logo_file', $urlLogo ?: $existingLogo);
        if (isset($result['error'])) {
            $msg = '<div class="admin-alert admin-alert--error">' . e($result['error']) . '</div>';
        } else {
            $logoUrl = $result['url'];
        }
    }

    if (!$msg) {
        $upsert = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $upsert->execute(['logo_url',          $logoUrl]);
        $upsert->execute(['logo_width',        (string)$logoWidth]);
        $upsert->execute(['site_name',         $siteName]);
        $upsert->execute(['site_tagline',      $siteTagline]);
        /* Email settings */
        $upsert->execute(['smtp_host',         $smtpHost]);
        $upsert->execute(['smtp_port',         $smtpPort]);
        $upsert->execute(['smtp_user',         $smtpUser]);
        /* Only update password if not blank (preserve existing) */
        if ($smtpPass !== '') {
            $upsert->execute(['smtp_pass',     $smtpPass]);
        }
        $upsert->execute(['smtp_from_email',   $smtpFromEmail]);
        $upsert->execute(['smtp_from_name',    $smtpFromName]);
        $upsert->execute(['admin_notify_email',$adminNotifyEmail]);
        $upsert->execute(['notify_on_booking',        $notifyBooking]);
        $upsert->execute(['notify_on_contact',        $notifyContact]);
        $upsert->execute(['notify_customer',          $notifyCustomer]);
        $upsert->execute(['ga4_measurement_id',       $ga4Id]);
        $upsert->execute(['google_site_verification', $gVerification]);
        $upsert->execute(['tawkto_widget_id',         $tawktoId]);
        $upsert->execute(['favicon_url',              $faviconUrl]);
        /* Social media links */
        $upsert->execute(['social_facebook',    $socialFacebook]);
        $upsert->execute(['social_instagram',   $socialInstagram]);
        $upsert->execute(['social_twitter',     $socialTwitter]);
        $upsert->execute(['social_youtube',     $socialYoutube]);
        $upsert->execute(['social_tiktok',      $socialTiktok]);
        $upsert->execute(['social_tripadvisor', $socialTripadvisor]);

        unset($_SESSION[CSRF_TOKEN_NAME]);
        header('Location: settings.php?saved=1');
        exit;
    }
}

if (!$msg && isset($_GET['saved'])) {
    $msg = '<div class="admin-alert admin-alert--success">Settings saved successfully.</div>';
}

/* ─── Load current values ─────────────────────────── */
$rows = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$currentLogo     = $rows['logo_url']     ?? '';
$currentWidth    = $rows['logo_width']   ?? '160';
$currentName     = $rows['site_name']    ?? 'Jambo Masai Tours';
$currentTagline  = $rows['site_tagline'] ?? 'Tanzania Safari Experts';
/* Email settings */
$cSmtpHost         = $rows['smtp_host']           ?? '';
$cSmtpPort         = $rows['smtp_port']           ?? '587';
$cSmtpUser         = $rows['smtp_user']           ?? '';
$cSmtpFromEmail    = $rows['smtp_from_email']     ?? SITE_EMAIL;
$cSmtpFromName     = $rows['smtp_from_name']      ?? SITE_NAME;
$cAdminNotifyEmail = $rows['admin_notify_email']  ?? SITE_EMAIL;
$cNotifyBooking    = $rows['notify_on_booking']   ?? '1';
$cNotifyContact    = $rows['notify_on_contact']   ?? '1';
$cNotifyCustomer   = $rows['notify_customer']     ?? '1';
$cGa4Id            = $rows['ga4_measurement_id']      ?? '';
$cGVerification    = $rows['google_site_verification'] ?? '';
$cTawktoId         = $rows['tawkto_widget_id']         ?? '';
$cFaviconUrl       = $rows['favicon_url']              ?? '';
/* Social media links */
$cSocialFacebook    = $rows['social_facebook']    ?? '';
$cSocialInstagram   = $rows['social_instagram']   ?? '';
$cSocialTwitter     = $rows['social_twitter']     ?? '';
$cSocialYoutube     = $rows['social_youtube']     ?? '';
$cSocialTiktok      = $rows['social_tiktok']      ?? '';
$cSocialTripadvisor = $rows['social_tripadvisor'] ?? '';

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Site Settings — Jambo Masai Admin</title>
<link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:wght@700&family=Poppins:wght@400;500;600&family=Montserrat:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
<style>
  body { background: var(--color-gray-light); margin: 0; }
  .admin-alert { padding: .75rem 1.25rem; border-radius: var(--radius-md); margin-bottom: var(--space-6); font-size: .9rem; }
  .admin-alert--success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #16a34a; }
  .admin-alert--error   { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25); color: #dc2626; }
  .card { background: var(--color-white); border-radius: var(--radius-lg); padding: var(--space-8); box-shadow: var(--shadow-sm); margin-bottom: var(--space-6); }
  .card h3 { font-family: var(--font-heading); font-size: 1.05rem; color: var(--color-brown); margin: 0 0 var(--space-6); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-beige-dark); }
  .upload-area { border: 2px dashed var(--color-beige-dark); border-radius: var(--radius-md); padding: var(--space-8); text-align: center; cursor: pointer; transition: border-color .2s; }
  .upload-area:hover { border-color: var(--color-gold); }
  .logo-preview-wrap { background: repeating-conic-gradient(#e0e0e0 0% 25%, #fff 0% 50%) 0 0 / 20px 20px; border-radius: var(--radius-md); padding: var(--space-4); display: flex; align-items: center; justify-content: center; min-height: 80px; margin-bottom: var(--space-4); }
  .btn-save { background: linear-gradient(135deg,var(--color-gold),var(--color-orange)); color: var(--color-black); font-family: var(--font-nav); font-weight: 700; font-size: .85rem; padding: .85rem 2.5rem; border: none; border-radius: var(--radius-md); cursor: pointer; letter-spacing: .04em; }
  .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
  .preview-navbar { background: linear-gradient(to right,rgba(0,0,0,.75),#0a0a0a); padding: var(--space-4) var(--space-8); border-radius: var(--radius-md); display: flex; align-items: center; gap: var(--space-3); margin-top: var(--space-4); border:1px solid rgba(255,255,255,.08); }
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:1.6rem;margin:0;">⚙️ Site Settings</h1>
        <p style="color:var(--text-muted);font-size:.85rem;margin:.25rem 0 0;">Manage logo, site name and branding</p>
      </div>
    </div>

    <?= $msg ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
      <input type="hidden" name="existing_logo" value="<?= e($currentLogo) ?>">

      <!-- Logo Card -->
      <div class="card">
        <h3>🖼️ Logo</h3>

        <!-- Current logo preview on checkerboard -->
        <p class="form-label">Current Logo</p>
        <div class="logo-preview-wrap" id="logo-preview-wrap">
          <?php if ($currentLogo): ?>
            <img src="<?= e($currentLogo) ?>" alt="Current logo" id="logo-preview-img"
                 style="max-height:80px;max-width:300px;object-fit:contain;">
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:.85rem;" id="logo-preview-placeholder">No logo uploaded — text fallback in use</span>
            <img id="logo-preview-img" src="" alt="" style="max-height:80px;max-width:300px;object-fit:contain;display:none;">
          <?php endif; ?>
        </div>

        <?php if ($currentLogo): ?>
        <label style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);cursor:pointer;">
          <input type="checkbox" name="clear_logo" value="1">
          <span style="font-size:.85rem;color:#dc2626;">Remove current logo (revert to text)</span>
        </label>
        <?php endif; ?>

        <!-- Upload new -->
        <label class="form-label">Upload New Logo</label>
        <div class="upload-area" id="logo-drop" onclick="document.getElementById('logo_file').click()">
          <div style="font-size:2rem;margin-bottom:var(--space-2);">🖼️</div>
          <p style="font-size:.85rem;color:var(--text-muted);margin:0;">Click to upload or drag & drop</p>
          <p style="font-size:.75rem;color:var(--text-muted);margin:var(--space-1) 0 0;">PNG with transparent background recommended — JPG, WebP accepted. Max 8MB.</p>
        </div>
        <input type="file" name="logo_file" id="logo_file" accept="image/*" style="display:none;">

        <div style="margin-top:var(--space-4);">
          <label class="form-label">— OR paste logo URL —</label>
          <input type="text" name="logo_url_input" id="logo_url_input" class="form-control" placeholder="https://..." value="">
        </div>

        <div style="margin-top:var(--space-6);">
          <label class="form-label">Logo Display Width (px)</label>
          <input type="number" name="logo_width" class="form-control" value="<?= (int)$currentWidth ?>"
                 min="60" max="400" style="max-width:150px;">
          <small style="color:var(--text-muted);font-size:.75rem;">How wide to display the logo in the navbar. Height scales automatically.</small>
        </div>

        <!-- Live preview in navbar context -->
        <div style="margin-top:var(--space-6);">
          <p class="form-label">Preview in Navbar</p>
          <div class="preview-navbar">
            <div id="preview-logo-wrap" style="display:flex;align-items:center;gap:var(--space-3);">
              <?php if ($currentLogo): ?>
                <img id="preview-logo" src="<?= e($currentLogo) ?>" alt="logo preview"
                     style="height:44px;object-fit:contain;max-width:200px;">
              <?php else: ?>
                <span style="font-size:1.5rem;">🦁</span>
                <div>
                  <div id="preview-name" style="font-family:'Nanum Myeongjo',serif;font-size:1rem;color:#fff;line-height:1.2;"><?= e($currentName) ?></div>
                  <div id="preview-tag"  style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:var(--color-gold);letter-spacing:.1em;text-transform:uppercase;"><?= e($currentTagline) ?></div>
                </div>
              <?php endif; ?>
            </div>
            <div style="margin-left:auto;display:flex;gap:1rem;">
              <span style="color:rgba(255,255,255,.5);font-size:.8rem;">Home</span>
              <span style="color:rgba(255,255,255,.5);font-size:.8rem;">Tours</span>
              <span style="color:rgba(255,255,255,.5);font-size:.8rem;">Destinations</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Favicon Card -->
      <div class="card" style="margin-bottom:var(--space-6)">
        <h3>🌐 Favicon (Browser Tab Icon)</h3>
        <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:var(--space-4)">
          Icon inayoonekana kwenye browser tab na bookmarks. Tumia picha ya mraba (PNG) — 32×32px au 64×64px.
        </p>
        <input type="hidden" name="existing_favicon" value="<?= e($cFaviconUrl) ?>">
        <!-- Current favicon preview -->
        <?php if ($cFaviconUrl): ?>
        <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-4);padding:var(--space-3);background:var(--color-beige);border-radius:var(--radius-md)">
          <img src="<?= e($cFaviconUrl) ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;border-radius:4px;border:1px solid var(--border)">
          <div>
            <div style="font-size:.8rem;font-weight:600;color:var(--text)">Current Favicon</div>
            <div style="font-size:.72rem;color:var(--text-muted)">Inaonekana kwenye browser tab</div>
          </div>
        </div>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4)">
          <div class="form-group">
            <label class="form-label">Upload Favicon</label>
            <input type="file" class="form-control" name="favicon_file" accept="image/png,image/x-icon,image/jpeg,image/webp">
            <small style="color:var(--text-muted);font-size:.72rem">PNG/ICO · Recommended: 64×64px</small>
          </div>
          <div class="form-group">
            <label class="form-label">Or Paste URL</label>
            <input type="url" class="form-control" name="favicon_url_input"
                   value="<?= e($cFaviconUrl) ?>" placeholder="https://...">
          </div>
        </div>
      </div>

      <!-- Site Name Card -->
      <div class="card">
        <h3>✏️ Site Name & Tagline</h3>
        <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:var(--space-6);">Used as the text fallback when no logo image is uploaded, and in page titles.</p>
        <div class="field-grid">
          <div>
            <label class="form-label">Site Name</label>
            <input type="text" name="site_name" class="form-control" value="<?= e($currentName) ?>"
                   id="site-name-input" placeholder="Jambo Masai Tours">
          </div>
          <div>
            <label class="form-label">Tagline</label>
            <input type="text" name="site_tagline" class="form-control" value="<?= e($currentTagline) ?>"
                   id="site-tagline-input" placeholder="Tanzania Safari Experts">
          </div>
        </div>
      </div>

      <!-- Social Media Links Card -->
      <div class="card">
        <h3>🔗 Social Media Links</h3>
        <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:var(--space-6);">Weka link kamili (mfano: https://facebook.com/jambomasaitours). Ikiachwa wazi, icon itaonekana lakini haita-link popote.</p>
        <div class="field-grid">
          <div class="form-group">
            <label class="form-label"><i class="fab fa-facebook-f" style="color:#3b5998;margin-right:.4rem"></i>Facebook</label>
            <input type="url" class="form-control" name="social_facebook" value="<?= e($cSocialFacebook) ?>" placeholder="https://facebook.com/yourpage">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fab fa-instagram" style="color:#e1306c;margin-right:.4rem"></i>Instagram</label>
            <input type="url" class="form-control" name="social_instagram" value="<?= e($cSocialInstagram) ?>" placeholder="https://instagram.com/yourpage">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fab fa-twitter" style="color:#1da1f2;margin-right:.4rem"></i>Twitter / X</label>
            <input type="url" class="form-control" name="social_twitter" value="<?= e($cSocialTwitter) ?>" placeholder="https://twitter.com/yourpage">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fab fa-youtube" style="color:#ff0000;margin-right:.4rem"></i>YouTube</label>
            <input type="url" class="form-control" name="social_youtube" value="<?= e($cSocialYoutube) ?>" placeholder="https://youtube.com/@yourchannel">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fab fa-tiktok" style="color:#000;margin-right:.4rem"></i>TikTok</label>
            <input type="url" class="form-control" name="social_tiktok" value="<?= e($cSocialTiktok) ?>" placeholder="https://tiktok.com/@yourpage">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-star" style="color:#34e0a1;margin-right:.4rem"></i>TripAdvisor</label>
            <input type="url" class="form-control" name="social_tripadvisor" value="<?= e($cSocialTripadvisor) ?>" placeholder="https://tripadvisor.com/yourlisting">
          </div>
        </div>
      </div>

      <!-- ══ EMAIL NOTIFICATION SETTINGS ════════════════ -->
      <div class="form-section" style="margin-top:1.5rem">
        <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.05rem;color:#fff;font-weight:700;margin:0 0 1.1rem;padding-bottom:.85rem;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-envelope" style="color:#10b981;font-size:.9rem"></i> Email Notifications
        </h3>

        <!-- SMTP Config -->
        <div style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.15);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.1rem">
          <p style="font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#10b981;margin:0 0 .5rem">SMTP Configuration</p>
          <p style="font-family:'Inter',sans-serif;font-size:.78rem;color:rgba(255,255,255,.45);margin:0">
            Gmail: smtp.gmail.com port 587 · Use an <strong style="color:rgba(255,255,255,.7)">App Password</strong> (not your main password).<br>
            Leave SMTP Host blank to use PHP mail() (requires local mailserver).
          </p>
        </div>

        <div class="field-grid" style="margin-bottom:1rem">
          <div class="form-group">
            <label class="form-label">SMTP Host</label>
            <input type="text" class="form-control" name="smtp_host" placeholder="smtp.gmail.com" value="<?= e($cSmtpHost) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">SMTP Port</label>
            <select class="form-control" name="smtp_port">
              <option value="587" <?= $cSmtpPort==='587'?'selected':'' ?>>587 (TLS — Recommended)</option>
              <option value="465" <?= $cSmtpPort==='465'?'selected':'' ?>>465 (SSL)</option>
              <option value="25"  <?= $cSmtpPort==='25' ?'selected':'' ?>>25 (Plain)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">SMTP Username</label>
            <input type="email" class="form-control" name="smtp_user" placeholder="your@gmail.com" value="<?= e($cSmtpUser) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">SMTP Password / App Password</label>
            <input type="password" class="form-control" name="smtp_pass" placeholder="Leave blank to keep current">
          </div>
          <div class="form-group">
            <label class="form-label">From Email</label>
            <input type="email" class="form-control" name="smtp_from_email" value="<?= e($cSmtpFromEmail) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">From Name</label>
            <input type="text" class="form-control" name="smtp_from_name" value="<?= e($cSmtpFromName) ?>">
          </div>
        </div>

        <!-- Notification targets -->
        <div class="form-group">
          <label class="form-label">Admin Notification Email <small style="font-weight:400;text-transform:none;letter-spacing:0;color:rgba(255,255,255,.35)">(where booking alerts are sent)</small></label>
          <input type="email" class="form-control" name="admin_notify_email" value="<?= e($cAdminNotifyEmail) ?>" placeholder="info@jambomasaitours.com">
        </div>

        <!-- Toggles -->
        <div style="display:flex;flex-direction:column;gap:.65rem;margin-top:1rem">
          <?php foreach ([
            ['notify_on_booking', $cNotifyBooking, '📋 Notify admin when a new booking is submitted'],
            ['notify_on_contact', $cNotifyContact, '📩 Notify admin when a contact/enquiry form is submitted'],
            ['notify_customer',   $cNotifyCustomer,'✅ Send confirmation email to customer after booking'],
          ] as $tog): ?>
          <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;padding:.65rem .85rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px">
            <div style="position:relative;width:40px;height:22px;flex-shrink:0">
              <input type="checkbox" name="<?= $tog[0] ?>" value="1" <?= $tog[1]==='1'?'checked':'' ?>
                     style="opacity:0;width:0;height:0;position:absolute"
                     onchange="this.closest('label').querySelector('.tog-slider').style.background=this.checked?'#10b981':'rgba(255,255,255,.12)'">
              <div class="tog-slider" style="position:absolute;inset:0;border-radius:22px;background:<?= $tog[1]==='1'?'#10b981':'rgba(255,255,255,.12)' ?>;transition:background .3s;cursor:pointer"></div>
            </div>
            <span style="font-family:'Inter',sans-serif;font-size:.83rem;color:rgba(255,255,255,.7)"><?= $tog[2] ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Test email button -->
        <div style="margin-top:1.1rem;padding-top:1.1rem;border-top:1px solid rgba(255,255,255,.08)">
          <p style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.3);margin:0 0 .65rem">Test Email Connection</p>
          <a href="settings.php?test_email=1" class="btn-sm btn-toggle" style="text-decoration:none;display:inline-flex;align-items:center;gap:.4rem">
            <i class="fas fa-paper-plane" style="font-size:.65rem"></i> Send Test Email to Admin Address
          </a>
          <?php
          if (isset($_GET['test_email'])) {
              require_once '../includes/mailer.php';
              require_once '../vendor/phpmailer/PHPMailer.php';
              require_once '../vendor/phpmailer/SMTP.php';
              require_once '../vendor/phpmailer/Exception.php';
              /* Capture PHPMailer error details */
              $testError = '';
              try {
                  $host  = getSetting('smtp_host');
                  $port  = (int)getSetting('smtp_port','587');
                  $user  = getSetting('smtp_user');
                  $pass  = getSetting('smtp_pass');
                  $from  = getSetting('smtp_from_email', SITE_EMAIL);
                  $fname = getSetting('smtp_from_name', SITE_NAME);
                  $to    = getSetting('admin_notify_email', SITE_EMAIL);

                  if (empty($host) || empty($user)) {
                      $testError = 'SMTP Host na Username lazima zijazwe kwanza.';
                  } elseif (empty($pass)) {
                      $testError = 'SMTP Password haikuwekwa — jaza na uhifadhi kwanza.';
                  } else {
                      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                      $mail->isSMTP();
                      $mail->Host       = $host;
                      $mail->SMTPAuth   = true;
                      $mail->Username   = $user;
                      $mail->Password   = $pass;
                      $mail->SMTPSecure = $port === 465 ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                      $mail->Port       = $port;
                      $mail->CharSet    = 'UTF-8';
                      $mail->setFrom($from, $fname);
                      $mail->addAddress($to, 'Admin');
                      $mail->isHTML(true);
                      $mail->Subject = 'Test Email — ' . SITE_NAME;
                      $mail->Body    = '<p>Test email from Jambo Masai Tours. SMTP is working!</p>';
                      $mail->send();
                      $testError = 'OK';
                  }
              } catch (\Throwable $ex) {
                  $testError = $ex->getMessage();
              }

              if ($testError === 'OK') {
                  echo '<div style="margin-top:.75rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:8px;padding:.6rem 1rem;color:#34d399;font-size:.8rem">✓ Test email sent to <strong>' . e(getSetting('admin_notify_email')) . '</strong> — check your inbox!</div>';
              } else {
                  echo '<div style="margin-top:.75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.6rem 1rem;color:#f87171;font-size:.78rem"><strong>✗ Error:</strong> ' . e($testError) . '</div>';
              }
          }
          ?>
        </div>
      </div>

      <!-- Analytics & Search Console -->
      <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-6);box-shadow:var(--shadow-sm);margin-top:1.25rem">
        <p style="font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#10b981;margin:0 0 .5rem">
          <i class="fas fa-chart-line" style="margin-right:.35rem"></i>Google Analytics & Search Console
        </p>
        <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem">
          Add your GA4 Measurement ID (G-XXXXXXXXXX) to enable analytics. The verification code is found in Google Search Console → Verify → HTML tag method.
        </p>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">GA4 Measurement ID</label>
            <input type="text" class="form-control" name="ga4_measurement_id"
                   value="<?= e($cGa4Id) ?>" placeholder="G-XXXXXXXXXX">
            <small style="color:var(--text-muted);font-size:.72rem">Leave blank to disable Google Analytics</small>
          </div>
          <div class="form-group">
            <label class="form-label">Google Site Verification Code</label>
            <input type="text" class="form-control" name="google_site_verification"
                   value="<?= e($cGVerification) ?>" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            <small style="color:var(--text-muted);font-size:.72rem">From Google Search Console → Settings → Ownership verification</small>
          </div>
        </div>
      </div>

      <!-- Live Chat (Tawk.to) -->
      <div style="background:var(--color-white);border-radius:var(--radius-xl);padding:var(--space-6);box-shadow:var(--shadow-sm);margin-top:1.25rem">
        <p style="font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#10b981;margin:0 0 .5rem">
          <i class="fas fa-comments" style="margin-right:.35rem"></i>Live Chat (Tawk.to)
        </p>
        <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem">
          Get your widget code from Tawk.to Dashboard → Administration → Channels → Chat Widget.
          Paste either just the <strong>PROPERTY_ID/WIDGET_ID</strong> (e.g. <code>60f1a2b3c4d5e6f7a8b9c0d1/default</code>)
          or the full embed <code>&lt;script&gt;</code> snippet Tawk.to gives you — both work.
        </p>
        <div class="form-row">
          <div class="form-group" style="flex:1 1 100%">
            <label class="form-label">Tawk.to Property ID / Widget ID or Embed Code</label>
            <textarea class="form-control" name="tawkto_widget_id" rows="3"
                      placeholder="60f1a2b3c4d5e6f7a8b9c0d1/default"><?= e($cTawktoId) ?></textarea>
            <small style="color:var(--text-muted);font-size:.72rem">Leave blank to disable the chat widget on the site</small>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:1rem">
        <button type="submit" class="btn-save">💾 Save All Settings</button>
      </div>
    </form>

  </main>
</div>

<script>
/* Image file → preview */
const fileInput  = document.getElementById('logo_file');
const previewImg = document.getElementById('logo-preview-img');
const previewPH  = document.getElementById('logo-preview-placeholder');
const previewNav = document.getElementById('preview-logo');
const urlInput   = document.getElementById('logo_url_input');

function setPreview(src) {
  if (previewImg) {
    previewImg.src = src;
    previewImg.style.display = 'block';
    if (previewPH) previewPH.style.display = 'none';
  }
  if (previewNav) {
    previewNav.src = src;
  } else {
    const wrap = document.getElementById('preview-logo-wrap');
    if (wrap) {
      wrap.innerHTML = '<img src="' + src + '" alt="logo preview" style="height:44px;object-fit:contain;max-width:200px;">';
    }
  }
}

if (fileInput) {
  fileInput.addEventListener('change', function() {
    if (this.files[0]) {
      setPreview(URL.createObjectURL(this.files[0]));
      if (urlInput) urlInput.value = '';
    }
  });
}

/* URL input → preview */
if (urlInput) {
  urlInput.addEventListener('blur', function() {
    if (this.value.trim()) setPreview(this.value.trim());
  });
}

/* Drag and drop */
const dropZone = document.getElementById('logo-drop');
if (dropZone && fileInput) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--color-gold)'; });
  dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = ''; });
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = '';
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      fileInput.files = dt.files;
      setPreview(URL.createObjectURL(e.dataTransfer.files[0]));
      if (urlInput) urlInput.value = '';
    }
  });
}

/* Live name/tagline preview */
const nameInput    = document.getElementById('site-name-input');
const taglineInput = document.getElementById('site-tagline-input');
const previewName  = document.getElementById('preview-name');
const previewTag   = document.getElementById('preview-tag');
if (nameInput && previewName) {
  nameInput.addEventListener('input', () => { previewName.textContent = nameInput.value; });
}
if (taglineInput && previewTag) {
  taglineInput.addEventListener('input', () => { previewTag.textContent = taglineInput.value; });
}
</script>
</body>
</html>
