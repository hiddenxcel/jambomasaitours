<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();
$errors = [];
$msg = '';

$destSlugs = [
    'serengeti'      => 'Serengeti National Park',
    'ngorongoro'     => 'Ngorongoro Crater',
    'kilimanjaro'    => 'Mount Kilimanjaro',
    'zanzibar'       => 'Zanzibar Island',
    'tarangire'      => 'Tarangire National Park',
    'maasai-heartland' => 'Maasai Cultural Heartland',
];

/* Ensure all destination rows exist */
$ins = $db->prepare("INSERT IGNORE INTO destination_pages (slug, intro, description) VALUES (?, '', '')");
foreach ($destSlugs as $sl => $nm) { try { $ins->execute([$sl]); } catch (\Throwable $e) {} }

$editSlug = sanitizeInput($_GET['slug'] ?? '');
if ($editSlug && !isset($destSlugs[$editSlug])) $editSlug = '';

/* Save */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { http_response_code(403); die('Invalid CSRF token.'); }
    $saveSlug = sanitizeInput($_POST['slug'] ?? '');
    if (isset($destSlugs[$saveSlug])) {
        $intro = sanitizeInput($_POST['intro'] ?? '');
        $desc  = sanitizeInput($_POST['description'] ?? '');
        $db->prepare("INSERT INTO destination_pages (slug,intro,description) VALUES (?,?,?) ON DUPLICATE KEY UPDATE intro=VALUES(intro),description=VALUES(description)")
           ->execute([$saveSlug, $intro, $desc]);
        redirect(SITE_URL . '/admin/destination-pages.php?slug=' . $saveSlug . '&saved=1');
    }
}

/* Load current data */
$rows = $db->query("SELECT * FROM destination_pages")->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

$editing = null;
if ($editSlug) {
    $editing = $rows[$editSlug] ?? ['slug'=>$editSlug,'intro'=>'','description'=>''];
}

if (isset($_GET['saved'])) $msg = 'Saved successfully.';
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Destination Pages | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-map-marker-alt" style="color:#10b981;font-size:1.1rem"></i>
          <?= $editSlug ? 'Edit: ' . e($destSlugs[$editSlug]) : 'Destination Pages' ?>
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">
          <?= $editSlug ? 'Edit intro and description shown on the destination page' : 'Manage destination page content — intro and descriptions' ?>
        </p>
      </div>
      <div style="display:flex;gap:.5rem">
        <?php if ($editSlug): ?>
        <a href="<?= url('destination/'.$editSlug) ?>" target="_blank" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-eye" style="font-size:.6rem"></i> Preview
        </a>
        <a href="destination-pages.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> All Destinations
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($editSlug && $editing !== null): ?>
    <!-- ── EDIT FORM ── -->
    <form method="POST">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="slug" value="<?= e($editSlug) ?>">

      <div class="adm-card" style="margin-bottom:1rem">
        <div class="adm-card-header">
          <h2 class="adm-card-title">Intro <span style="font-family:'Montserrat',sans-serif;font-weight:400;font-size:.7rem;color:rgba(255,255,255,.3)">(shown as first paragraph)</span></h2>
          <span style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.25)" id="intro-count">0 chars</span>
        </div>
        <div class="adm-card-body">
          <textarea class="f-input" name="intro" id="intro-field" rows="4"
                    style="min-height:110px;resize:vertical;line-height:1.7"
                    placeholder="Short intro paragraph — 2-3 sentences, appears first on the page..."><?= e($editing['intro'] ?? '') ?></textarea>
          <div class="f-hint"><i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>2–3 sentences. Keep it compelling and keyword-rich for SEO.</div>
        </div>
      </div>

      <div class="adm-card" style="margin-bottom:1rem">
        <div class="adm-card-header">
          <h2 class="adm-card-title">Full Description <span style="font-family:'Montserrat',sans-serif;font-weight:400;font-size:.7rem;color:rgba(255,255,255,.3)">(detailed text below intro)</span></h2>
          <span style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.25)" id="desc-count">0 chars</span>
        </div>
        <div class="adm-card-body">
          <div class="f-hint" style="margin-bottom:.65rem"><i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>HTML is allowed — use &lt;em&gt; for italics, &lt;strong&gt; for bold. 3–5 paragraphs recommended for SEO.</div>
          <textarea class="f-input" name="description" id="desc-field" rows="10"
                    style="min-height:240px;resize:vertical;font-family:'Fira Code','Courier New',monospace;font-size:.82rem;line-height:1.7"
                    placeholder="Detailed description of the destination. HTML allowed..."><?= e($editing['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div style="display:flex;gap:.6rem;align-items:center">
        <button type="submit" class="btn btn--primary btn--lg" style="display:inline-flex;align-items:center;gap:.4rem">
          <i class="fas fa-save" style="font-size:.7rem"></i> Save Changes
        </button>
        <a href="<?= url('destination/'.$editSlug) ?>" target="_blank" class="btn btn--outline btn--lg" style="display:inline-flex;align-items:center;gap:.4rem">
          <i class="fas fa-external-link-alt" style="font-size:.6rem"></i> View Live Page
        </a>
      </div>
    </form>

    <?php else: ?>
    <!-- ── DESTINATION LIST ── -->
    <div class="adm-card">
      <div class="adm-card-header">
        <h2 class="adm-card-title">All Destination Pages (<?= count($destSlugs) ?>)</h2>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Destination</th>
              <th>Intro</th>
              <th>Description</th>
              <th>Last Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($destSlugs as $sl => $nm):
              $row = $rows[$sl] ?? null;
              $hasIntro = !empty($row['intro'] ?? '');
              $hasDesc  = !empty($row['description'] ?? '');
            ?>
            <tr>
              <td>
                <div style="font-weight:600;color:#fff;font-size:.82rem"><?= e($nm) ?></div>
                <div style="font-size:.68rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif">/destination/<?= $sl ?></div>
              </td>
              <td>
                <span class="badge <?= $hasIntro ? 'badge-confirmed' : 'badge-pending' ?>">
                  <?= $hasIntro ? '✓ Set' : '○ Default' ?>
                </span>
              </td>
              <td>
                <span class="badge <?= $hasDesc ? 'badge-confirmed' : 'badge-pending' ?>">
                  <?= $hasDesc ? '✓ Set' : '○ Default' ?>
                </span>
              </td>
              <td style="font-size:.72rem;color:rgba(255,255,255,.3);font-family:'Montserrat',sans-serif">
                <?= $row && $row['updated_at'] ? date('d M Y', strtotime($row['updated_at'])) : '—' ?>
              </td>
              <td style="white-space:nowrap">
                <a href="destination-pages.php?slug=<?= $sl ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .7rem;display:inline-flex;align-items:center;gap:.3rem">
                  <i class="fas fa-pen"></i> Edit
                </a>
                <a href="<?= url('destination/'.$sl) ?>" target="_blank" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="View live">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
<script>
function updateCount(inputId, countId) {
  var el = document.getElementById(inputId);
  var cnt = document.getElementById(countId);
  if (!el || !cnt) return;
  el.addEventListener('input', function(){ cnt.textContent = this.value.length + ' chars'; });
  cnt.textContent = el.value.length + ' chars';
}
updateCount('intro-field', 'intro-count');
updateCount('desc-field', 'desc-count');
</script>
</body>
</html>
