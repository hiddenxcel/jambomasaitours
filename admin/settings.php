<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once 'includes/auth_guard.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* Ensure email settings exist in site_settings */
$emailKeys = ['smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'','smtp_from_email'=>SITE_EMAIL,'smtp_from_name'=>SITE_NAME,'admin_notify_email'=>SITE_EMAIL,'notify_on_booking'=>'1','notify_on_contact'=>'1','notify_customer'=>'1','ga4_measurement_id'=>'','google_site_verification'=>'','favicon_url'=>'','social_facebook'=>'','social_instagram'=>'','social_twitter'=>'','social_youtube'=>'','social_tiktok'=>'','social_tripadvisor'=>'','tawkto_widget_id'=>'','groq_api_key'=>'','google_review_url'=>''];
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
    if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $gVerification, $gvm)) { $gVerification = $gvm[1]; } /* mtumiaji akibandika <meta ...> nzima, tunabakiza code tu */
    $tawktoId           = trim($_POST['tawkto_widget_id'] ?? '');
    $groqApiKey         = trim($_POST['groq_api_key'] ?? '');

    /* Social media links */
    $socialFacebook     = trim($_POST['social_facebook']    ?? '');
    $socialInstagram    = trim($_POST['social_instagram']   ?? '');
    $socialTwitter      = trim($_POST['social_twitter']     ?? '');
    $socialYoutube      = trim($_POST['social_youtube']     ?? '');
    $socialTiktok       = trim($_POST['social_tiktok']      ?? '');
    $socialTripadvisor  = trim($_POST['social_tripadvisor'] ?? '');
    $googleReviewUrl    = trim($_POST['google_review_url'] ?? '');
    $guidemeetCode      = trim($_POST['guidemeet_badge_code'] ?? '');

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
        $upsert->execute(['groq_api_key',             $groqApiKey]);
        $upsert->execute(['favicon_url',              $faviconUrl]);
        /* Social media links */
        $upsert->execute(['social_facebook',    $socialFacebook]);
        $upsert->execute(['social_instagram',   $socialInstagram]);
        $upsert->execute(['social_twitter',     $socialTwitter]);
        $upsert->execute(['social_youtube',     $socialYoutube]);
        $upsert->execute(['social_tiktok',      $socialTiktok]);
        $upsert->execute(['social_tripadvisor', $socialTripadvisor]);
        $upsert->execute(['google_review_url',  $googleReviewUrl]);
        $upsert->execute(['guidemeet_badge_code', $guidemeetCode]);

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
$cGroqApiKey       = $rows['groq_api_key']             ?? '';
$cFaviconUrl       = $rows['favicon_url']              ?? '';
/* Social media links */
$cSocialFacebook    = $rows['social_facebook']    ?? '';
$cSocialInstagram   = $rows['social_instagram']   ?? '';
$cSocialTwitter     = $rows['social_twitter']     ?? '';
$cSocialYoutube     = $rows['social_youtube']     ?? '';
$cSocialTiktok      = $rows['social_tiktok']      ?? '';
$cSocialTripadvisor = $rows['social_tripadvisor'] ?? '';
$cGoogleReviewUrl   = $rows['google_review_url']  ?? '';
$cGuidemeetCode     = $rows['guidemeet_badge_code'] ?? '';
$cGuidemeetPreview  = guidemeetBadgeHtml($cGuidemeetCode);

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
  .admin-alert { padding: .8rem 1.1rem; border-radius: var(--radius); margin-bottom: 1.25rem; font-size: .88rem; }
  .admin-alert--success { background: rgba(74,222,128,.08); border: 1px solid rgba(74,222,128,.3); color: var(--ok); }
  .admin-alert--error   { background: rgba(248,113,113,.08); border: 1px solid rgba(248,113,113,.3); color: var(--danger); }
  .st-sec { margin-bottom: 1.25rem; scroll-margin-top: 70px; }
  .st-ico { color: var(--primary-light); margin-right: .5rem; font-size: .9rem; }
  .st-h { font-size: .95rem; font-weight: 650; color: #fff; margin: 0 0 .75rem; }
  .st-p { font-size: .86rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.55; }
  .st-hr { border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0; }
  .st-check { display: flex; align-items: center; gap: .6rem; margin: -.25rem 0 1rem; cursor: pointer; font-size: .84rem; color: var(--danger); }
  .st-current { display: flex; align-items: center; gap: .75rem; padding: .6rem .8rem; background: var(--bg-card2); border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: .82rem; color: var(--text-muted); }
  .st-toggles { display: grid; gap: .55rem; margin-top: .5rem; }
  .st-toggle { display: flex; align-items: center; gap: .85rem; padding: .7rem .9rem; background: rgba(255,255,255,.03); border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; font-size: .86rem; color: var(--text); }
  .st-savebar { position: sticky; bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .9rem 1.1rem; margin-top: 1rem; background: rgba(13,20,17,.92); backdrop-filter: blur(10px); border: 1px solid var(--border-strong); border-radius: var(--radius-lg); font-size: .82rem; color: var(--text-muted); z-index: 20; }
  .upload-area { border: 2px dashed var(--border-strong); border-radius: var(--radius); padding: 1.5rem; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; }
  .upload-area:hover { border-color: var(--primary); background: rgba(193,122,58,.05); }
  .logo-preview-wrap { background: repeating-conic-gradient(#e6e6e6 0% 25%, #fff 0% 50%) 0 0 / 20px 20px; border-radius: var(--radius); padding: 1rem; display: flex; align-items: center; justify-content: center; min-height: 80px; margin-bottom: 1rem; }
  .preview-navbar { background: linear-gradient(to right, rgba(0,0,0,.75), #0a0a0a); padding: 1rem 1.5rem; border-radius: var(--radius); display: flex; align-items: center; gap: .75rem; border: 1px solid var(--border); }
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <?php adm_pagehead('Settings', 'Your logo, contact channels, email, analytics and live chat. Press <strong style="color:#fff">Save all settings</strong> at the bottom after you change anything.', [
      'icon' => 'fa-gear',
      'help' => [
        'key' => 'settings', 'title' => 'What each section does',
        'steps' => [
          '<strong>Branding:</strong> logo, browser-tab icon, site name and tagline. The logo appears in the website menu.',
          '<strong>Social &amp; reviews:</strong> your social pages and the Google "write a review" link. These show in the website footer and on the reviews page.',
          '<strong>Email:</strong> connect your email so booking and enquiry alerts reach your inbox. Use <em>Send test email</em> to check.',
          '<strong>Analytics, chat and AI:</strong> optional tools. Leave a field empty to switch that tool off.',
          'Press <strong>Save all settings</strong> once at the bottom. All sections are saved together.',
        ],
        'tips' => [
          'Changes appear on the live website straight after saving.',
          'Passwords and keys are hidden. If you leave the email password empty, the old one is kept.',
          'Never share the Groq key or the email password with anyone.',
          'Not sure about a field? Leave it as it is.',
        ],
      ],
    ]); ?>

    <?= $msg ?>

    <nav class="adm-subnav" aria-label="Settings sections">
      <a href="#branding"><i class="fas fa-palette"></i> Branding</a>
      <a href="#social"><i class="fas fa-share-nodes"></i> Social &amp; reviews</a>
      <a href="#email"><i class="fas fa-envelope"></i> Email</a>
      <a href="#analytics"><i class="fas fa-chart-line"></i> Analytics</a>
      <a href="#chat"><i class="fas fa-comments"></i> Live chat</a>
      <a href="#badges"><i class="fas fa-award"></i> Badge</a>
      <a href="#ai"><i class="fas fa-robot"></i> AI</a>
    </nav>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
      <input type="hidden" name="existing_logo" value="<?= e($currentLogo) ?>">

      <!-- ══ BRANDING ══ -->
      <section class="adm-card st-sec" id="branding">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-palette st-ico"></i> Branding</h2></div>
        <div class="adm-card-body">

          <h3 class="st-h">Logo</h3>
          <div class="logo-preview-wrap" id="logo-preview-wrap">
            <?php if ($currentLogo): ?>
              <img src="<?= e($currentLogo) ?>" alt="Current logo" id="logo-preview-img" style="max-height:80px;max-width:300px;object-fit:contain">
            <?php else: ?>
              <span style="color:#555;font-size:.85rem" id="logo-preview-placeholder">No logo uploaded. The site name is shown as text.</span>
              <img id="logo-preview-img" src="" alt="" style="max-height:80px;max-width:300px;object-fit:contain;display:none">
            <?php endif; ?>
          </div>
          <?php if ($currentLogo): ?>
          <label class="st-check"><input type="checkbox" name="clear_logo" value="1"><span>Remove the current logo (show the site name as text instead)</span></label>
          <?php endif; ?>

          <div class="f-group">
            <label class="f-label">Upload a new logo</label>
            <div class="upload-area" id="logo-drop" onclick="document.getElementById('logo_file').click()">
              <i class="fas fa-cloud-arrow-up" style="font-size:1.5rem;color:var(--text-dim);margin-bottom:.4rem"></i>
              <p style="margin:0;color:var(--text)">Click to choose a file, or drag it here</p>
              <p style="margin:.25rem 0 0;font-size:.78rem;color:var(--text-dim)">PNG with a transparent background is best. JPG and WebP also work. Maximum 8 MB.</p>
            </div>
            <input type="file" name="logo_file" id="logo_file" accept="image/*" style="display:none">
          </div>
          <div class="f-group">
            <label class="f-label">Or paste a logo link</label>
            <input type="text" name="logo_url_input" id="logo_url_input" class="f-input" placeholder="https://..." value="">
            <div class="f-hint">Only needed if the logo is already online. Leave empty if you uploaded a file above.</div>
          </div>
          <div class="f-group">
            <label class="f-label">Logo width in the menu (pixels)</label>
            <input type="number" name="logo_width" class="f-input" value="<?= (int)$currentWidth ?>" min="60" max="400" style="max-width:150px">
            <div class="f-hint">Between 60 and 400. The height adjusts by itself. About 160 works well.</div>
          </div>
          <div class="f-group" style="margin-bottom:0">
            <label class="f-label">Preview in the menu</label>
            <div class="preview-navbar">
              <div id="preview-logo-wrap" style="display:flex;align-items:center;gap:.75rem">
                <?php if ($currentLogo): ?>
                  <img id="preview-logo" src="<?= e($currentLogo) ?>" alt="logo preview" style="height:44px;object-fit:contain;max-width:200px">
                <?php else: ?>
                  <div>
                    <div id="preview-name" style="font-weight:700;font-size:1rem;color:#fff;line-height:1.2"><?= e($currentName) ?></div>
                    <div id="preview-tag" style="font-size:.62rem;color:var(--primary-light);letter-spacing:.1em;text-transform:uppercase"><?= e($currentTagline) ?></div>
                  </div>
                <?php endif; ?>
              </div>
              <div style="margin-left:auto;display:flex;gap:1rem">
                <span style="color:rgba(255,255,255,.5);font-size:.8rem">Home</span>
                <span style="color:rgba(255,255,255,.5);font-size:.8rem">Tours</span>
                <span style="color:rgba(255,255,255,.5);font-size:.8rem">Destinations</span>
              </div>
            </div>
          </div>

          <hr class="st-hr">
          <h3 class="st-h">Browser tab icon (favicon)</h3>
          <p class="st-p">The small picture shown on the browser tab and in bookmarks. Use a square PNG, 64 x 64 pixels.</p>
          <input type="hidden" name="existing_favicon" value="<?= e($cFaviconUrl) ?>">
          <?php if ($cFaviconUrl): ?>
          <div class="st-current"><img src="<?= e($cFaviconUrl) ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;border-radius:4px"><span>Current favicon</span></div>
          <?php endif; ?>
          <div class="f-grid-2">
            <div class="f-group">
              <label class="f-label">Upload favicon</label>
              <input type="file" class="f-input" name="favicon_file" accept="image/png,image/x-icon,image/jpeg,image/webp">
              <div class="f-hint">PNG or ICO, 64 x 64 pixels.</div>
            </div>
            <div class="f-group">
              <label class="f-label">Or paste a link</label>
              <input type="url" class="f-input" name="favicon_url_input" value="<?= e($cFaviconUrl) ?>" placeholder="https://...">
            </div>
          </div>

          <hr class="st-hr">
          <h3 class="st-h">Site name and tagline</h3>
          <p class="st-p">Shown as text when there is no logo, and in some page titles.</p>
          <div class="f-grid-2">
            <div class="f-group" style="margin-bottom:0">
              <label class="f-label">Site name</label>
              <input type="text" name="site_name" class="f-input" value="<?= e($currentName) ?>" id="site-name-input" placeholder="Jambo Masai Tours">
            </div>
            <div class="f-group" style="margin-bottom:0">
              <label class="f-label">Tagline</label>
              <input type="text" name="site_tagline" class="f-input" value="<?= e($currentTagline) ?>" id="site-tagline-input" placeholder="Tanzania Safari Experts">
            </div>
          </div>
        </div>
      </section>

      <!-- ══ SOCIAL ══ -->
      <section class="adm-card st-sec" id="social">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-share-nodes st-ico"></i> Social media &amp; reviews</h2></div>
        <div class="adm-card-body">
          <p class="st-p">Paste the full link to each page (for example <code>https://facebook.com/jambomasaitours</code>). If a field is empty, the icon shows but goes nowhere.</p>
          <div class="f-grid-2">
            <?php foreach ([
              ['fab fa-facebook-f', 'Facebook',    'social_facebook',    $cSocialFacebook,    'https://facebook.com/yourpage'],
              ['fab fa-instagram',  'Instagram',   'social_instagram',   $cSocialInstagram,   'https://instagram.com/yourpage'],
              ['fab fa-x-twitter',  'X (Twitter)', 'social_twitter',     $cSocialTwitter,     'https://x.com/yourpage'],
              ['fab fa-youtube',    'YouTube',     'social_youtube',     $cSocialYoutube,     'https://youtube.com/@yourchannel'],
              ['fab fa-tiktok',     'TikTok',      'social_tiktok',      $cSocialTiktok,      'https://tiktok.com/@yourpage'],
              ['fas fa-star',       'TripAdvisor', 'social_tripadvisor', $cSocialTripadvisor, 'https://tripadvisor.com/yourlisting'],
            ] as $sm): ?>
            <div class="f-group">
              <label class="f-label"><i class="<?= $sm[0] ?>" style="color:var(--primary-light);margin-right:.4rem;width:14px;text-align:center"></i><?= e($sm[1]) ?></label>
              <input type="url" class="f-input" name="<?= $sm[2] ?>" value="<?= e($sm[3]) ?>" placeholder="<?= e($sm[4]) ?>">
            </div>
            <?php endforeach; ?>
            <div class="f-group" style="grid-column:1/-1">
              <label class="f-label"><i class="fab fa-google" style="color:var(--primary-light);margin-right:.4rem;width:14px;text-align:center"></i>Google reviews link ("Write a review")</label>
              <input type="url" class="f-input" name="google_review_url" value="<?= e($cGoogleReviewUrl) ?>" placeholder="https://g.page/r/xxxxx/review">
              <div class="f-hint">Get it from your Google Business Profile: <strong>Ask for reviews</strong>, then <strong>Copy link</strong>. Send this link to happy guests so they can leave a review.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ EMAIL ══ -->
      <section class="adm-card st-sec" id="email">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-envelope st-ico"></i> Email notifications</h2></div>
        <div class="adm-card-body">
          <?php adm_tip('<strong>Using Gmail?</strong> Host <code>smtp.gmail.com</code>, port <strong>587</strong>. Do not use your normal password: turn on 2-Step Verification in your Google account, then create an <strong>App password</strong> (Google account, Security, App passwords) and paste it below. If the host is empty, the website tries the server\'s default mail, which often fails.', 'brand'); ?>

          <div class="f-grid-2">
            <div class="f-group">
              <label class="f-label">SMTP host</label>
              <input type="text" class="f-input" name="smtp_host" placeholder="smtp.gmail.com" value="<?= e($cSmtpHost) ?>">
            </div>
            <div class="f-group">
              <label class="f-label">SMTP port</label>
              <select class="f-select" name="smtp_port">
                <option value="587" <?= $cSmtpPort==='587'?'selected':'' ?>>587 (TLS, recommended)</option>
                <option value="465" <?= $cSmtpPort==='465'?'selected':'' ?>>465 (SSL)</option>
                <option value="25"  <?= $cSmtpPort==='25' ?'selected':'' ?>>25 (plain, not recommended)</option>
              </select>
            </div>
            <div class="f-group">
              <label class="f-label">SMTP username</label>
              <input type="email" class="f-input" name="smtp_user" placeholder="your@gmail.com" value="<?= e($cSmtpUser) ?>">
            </div>
            <div class="f-group">
              <label class="f-label">SMTP password / app password</label>
              <input type="password" class="f-input" name="smtp_pass" placeholder="Leave empty to keep the current one" autocomplete="new-password">
            </div>
            <div class="f-group">
              <label class="f-label">"From" email</label>
              <input type="email" class="f-input" name="smtp_from_email" value="<?= e($cSmtpFromEmail) ?>">
              <div class="f-hint">The address customers see on emails from the website.</div>
            </div>
            <div class="f-group">
              <label class="f-label">"From" name</label>
              <input type="text" class="f-input" name="smtp_from_name" value="<?= e($cSmtpFromName) ?>">
            </div>
          </div>

          <div class="f-group">
            <label class="f-label">Send alerts to this email</label>
            <input type="email" class="f-input" name="admin_notify_email" value="<?= e($cAdminNotifyEmail) ?>" placeholder="info@jambomasaitours.com">
            <div class="f-hint">New booking and enquiry alerts arrive at this address.</div>
          </div>

          <div class="st-toggles">
            <?php foreach ([
              ['notify_on_booking', $cNotifyBooking, 'Email me when a new booking request arrives'],
              ['notify_on_contact', $cNotifyContact, 'Email me when someone sends a contact or enquiry form'],
              ['notify_customer',   $cNotifyCustomer,'Send the customer a confirmation email after they book'],
            ] as $tog): ?>
            <label class="st-toggle">
              <span class="toggle"><input type="checkbox" name="<?= $tog[0] ?>" value="1" <?= $tog[1]==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
              <span><?= e($tog[2]) ?></span>
            </label>
            <?php endforeach; ?>
          </div>

          <hr class="st-hr">
          <h3 class="st-h">Check that email works</h3>
          <p class="st-p">Save your settings first, then press the button. A test message is sent to the alert address above.</p>
          <a href="settings.php?test_email=1#email" class="btn btn--outline btn--sm"><i class="fas fa-paper-plane"></i> Send test email</a>
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
                      $testError = 'Fill in the SMTP host and username first, then save.';
                  } elseif (empty($pass)) {
                      $testError = 'No SMTP password is saved yet. Enter it, save, and try again.';
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
                      $mail->Subject = 'Test Email - ' . SITE_NAME;
                      $mail->Body    = '<p>Test email from Jambo Masai Tours. SMTP is working!</p>';
                      $mail->send();
                      $testError = 'OK';
                  }
              } catch (\Throwable $ex) {
                  $testError = $ex->getMessage();
              }

              if ($testError === 'OK') {
                  adm_tip('Test email sent to <strong>' . e(getSetting('admin_notify_email')) . '</strong>. Check the inbox (and the spam folder).', 'ok');
              } else {
                  adm_tip('<strong>The test email failed:</strong> ' . e($testError) . '<br>Check the host, port, username and app password, then save and try again.', 'danger');
              }
          }
          ?>
        </div>
      </section>

      <!-- ══ ANALYTICS ══ -->
      <section class="adm-card st-sec" id="analytics">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-chart-line st-ico"></i> Google Analytics &amp; Search Console</h2></div>
        <div class="adm-card-body">
          <div class="f-grid-2">
            <div class="f-group" style="margin-bottom:0">
              <label class="f-label">GA4 measurement ID</label>
              <input type="text" class="f-input" name="ga4_measurement_id" value="<?= e($cGa4Id) ?>" placeholder="G-XXXXXXXXXX">
              <div class="f-hint">In Google Analytics: Admin, Data streams, choose your website, copy the <strong>Measurement ID</strong>. Leave empty to switch analytics off.</div>
            </div>
            <div class="f-group" style="margin-bottom:0">
              <label class="f-label">Google site verification code</label>
              <input type="text" class="f-input" name="google_site_verification" value="<?= e($cGVerification) ?>" placeholder="paste the code, or the whole meta tag">
              <div class="f-hint">Only needed if Search Console asks for the "HTML tag" method. You can paste the whole tag, the website keeps just the code. Not needed if Search Console already shows your site as verified.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ LIVE CHAT ══ -->
      <section class="adm-card st-sec" id="chat">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-comments st-ico"></i> Live chat (Tawk.to)</h2></div>
        <div class="adm-card-body">
          <p class="st-p">Create a free Tawk.to account, then open <strong>Administration, Channels, Chat Widget</strong> and copy the widget ID (for example <code>60f1a2b3c4d5e6f7a8b9c0d1/default</code>). You can also paste the whole script snippet Tawk.to gives you.</p>
          <div class="f-group" style="margin-bottom:0">
            <label class="f-label">Tawk.to widget ID or embed code</label>
            <textarea class="f-textarea" name="tawkto_widget_id" rows="3" placeholder="60f1a2b3c4d5e6f7a8b9c0d1/default"><?= e($cTawktoId) ?></textarea>
            <div class="f-hint">Leave empty to hide the chat widget on the website.</div>
          </div>
        </div>
      </section>

      <!-- ══ BADGES ══ -->
      <section class="adm-card st-sec" id="badges">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-award st-ico"></i> Footer badge (GuideMeet)</h2></div>
        <div class="adm-card-body">
          <p class="st-p">On GuideMeet open <strong>Profile, Edit Guide Profile, Promote your profile, Copy badge code</strong>, then paste it below. The badge appears automatically at the bottom of every page (footer). You can paste the whole code or only the profile link.</p>
          <div class="f-group" style="margin-bottom:0">
            <label class="f-label">GuideMeet badge code</label>
            <textarea class="f-textarea" name="guidemeet_badge_code" rows="4" placeholder='<a href="https://www.guidemeet.it/guides/..."><img src="https://www.guidemeet.it/badge/guidemeet-badge.svg" ...></a>' style="font-family:ui-monospace,Consolas,monospace;font-size:.8rem"><?= e($cGuidemeetCode) ?></textarea>
            <div class="f-hint">Leave empty to hide the badge. Only guidemeet.it links are accepted; anything else is ignored for safety.</div>
          </div>
          <?php if ($cGuidemeetCode !== ''): ?>
            <div style="margin-top:1rem;padding:1rem;background:#0f172a;border-radius:10px;display:inline-block">
              <?php if ($cGuidemeetPreview): ?>
                <?= $cGuidemeetPreview ?>
              <?php else: ?>
                <span style="color:#f87171;font-size:.85rem">This code is not valid, so no badge is shown. Paste the code exactly as GuideMeet gave it.</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- ══ AI ══ -->
      <section class="adm-card st-sec" id="ai">
        <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-robot st-ico"></i> AI settings (Groq)</h2></div>
        <div class="adm-card-body">
          <p class="st-p">The Groq key powers the smart parts of the itinerary PDF: choosing pictures, tidying routes and planning each day. Get a free key at <strong>console.groq.com/keys</strong>.</p>
          <div class="f-group" style="margin-bottom:0">
            <label class="f-label">Groq API key</label>
            <div style="display:flex;gap:.5rem">
              <input type="password" class="f-input" name="groq_api_key" id="groq-key" autocomplete="off" value="<?= e($cGroqApiKey) ?>" placeholder="gsk_..." style="font-family:ui-monospace,Consolas,monospace">
              <button type="button" class="btn btn--outline btn--sm" onclick="var i=document.getElementById('groq-key');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'">Show</button>
            </div>
            <div class="f-hint">
              <?= $cGroqApiKey ? 'A key is saved. Paste a new one here to replace it.' : 'No key saved yet. The AI parts of the itinerary PDF will not work until you add one.' ?>
              If you leave it empty, the website uses the default key from its configuration (if there is one).
            </div>
          </div>
        </div>
      </section>

      <div class="st-savebar">
        <span>Changes in every section above are saved together.</span>
        <button type="submit" class="btn btn--primary" style="padding:.7rem 1.6rem"><i class="fas fa-floppy-disk"></i> Save all settings</button>
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
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--primary)'; });
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
