<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$db = getDB();
$token = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token'] ?? '');

if (!$token) { redirect(SITE_URL . '/tours'); }

$sStmt = $db->prepare("SELECT * FROM itinerary_shares WHERE token = ? LIMIT 1");
$sStmt->execute([$token]);
$share = $sStmt->fetch();

if (!$share || ($share['expires_at'] && strtotime($share['expires_at']) < time())) {
    http_response_code(404);
    ?>
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Link Expired | Jambo Masai Tours</title><meta name="robots" content="noindex">
    <style>body{font-family:'Segoe UI',Arial,sans-serif;background:#0a1a0f;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;padding:2rem}
    a{color:#a05e22;font-weight:600;text-decoration:none}</style></head>
    <body><div><h1 style="color:#a05e22">Link not found or expired</h1>
    <p>This itinerary link is no longer valid.</p>
    <p><a href="<?= SITE_URL ?>/tours">Browse our safari tours →</a></p></div></body></html>
    <?php
    exit;
}

$tStmt = $db->prepare("SELECT * FROM tours WHERE id = ? LIMIT 1");
$tStmt->execute([$share['tour_id']]);
$tour = $tStmt->fetch();
if (!$tour) { redirect(SITE_URL . '/tours'); }

$db->prepare("UPDATE itinerary_shares SET view_count = view_count + 1, last_viewed_at = NOW() WHERE id = ?")
   ->execute([$share['id']]);

$iStmt = $db->prepare("SELECT * FROM tour_itinerary WHERE tour_id = ? ORDER BY day_number ASC");
$iStmt->execute([$tour['id']]);
$days = $iStmt->fetchAll();

$dcPalette = [
    ['#a05e22','rgba(160,94,34,.18)'],['#f59e0b','rgba(245,158,11,.18)'],
    ['#3b82f6','rgba(59,130,246,.18)'],['#8b5cf6','rgba(139,92,246,.18)'],
    ['#f97316','rgba(249,115,22,.18)'],['#ec4899','rgba(236,72,153,.18)'],
    ['#06b6d4','rgba(6,182,212,.18)'],
];

$pdfUrl = $share['pdf_filename'] ? SITE_URL . '/uploads/itineraries/' . $share['pdf_filename'] : null;
$waLink = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . urlencode('Hi! I\'m looking at the itinerary for ' . $tour['name'] . ' and would like more details.');
$firstName = trim(explode(' ', $share['name'] ?? '')[0] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($tour['name']) ?> — Itinerary | Jambo Masai Tours</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#0a1a0f;color:#e5e7eb;font-family:'Inter',sans-serif;line-height:1.6}
  .wrap{max-width:760px;margin:0 auto;padding:2rem 1.25rem 4rem}
  .hero{text-align:center;padding:1.5rem 0 2rem;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:2rem}
  .hero .brand{font-family:'Montserrat',sans-serif;font-weight:700;letter-spacing:.15em;text-transform:uppercase;font-size:.7rem;color:#a05e22;margin-bottom:.75rem}
  .hero h1{font-family:'Nanum Myeongjo',serif;font-size:1.6rem;color:#fff;margin:0 0 .5rem;line-height:1.3}
  .hero .meta{color:rgba(255,255,255,.4);font-size:.85rem}
  .greet{color:rgba(255,255,255,.55);font-size:.9rem;margin-bottom:1.5rem;text-align:center}
  .actions{display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;margin-bottom:2.5rem}
  .btn{display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.8rem;padding:.85rem 1.5rem;border-radius:10px;text-decoration:none;transition:all .25s}
  .btn-pdf{background:linear-gradient(135deg,#a05e22,#c47a35);color:#fff;box-shadow:0 4px 14px rgba(160,94,34,.3)}
  .btn-pdf:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(160,94,34,.4)}
  .btn-wa{background:#25D366;color:#fff}
  .btn-wa:hover{transform:translateY(-2px)}
  .day{display:flex;gap:1rem;margin-bottom:1.25rem;position:relative}
  .day-num{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.8rem;flex-shrink:0}
  .day-content{flex:1;background:#12180f14;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:1.1rem 1.3rem}
  .day-content h3{font-family:'Nanum Myeongjo',serif;font-size:1.05rem;color:#fff;margin:0 0 .5rem}
  .route{font-size:.75rem;color:rgba(255,255,255,.4);margin-bottom:.5rem}
  .desc{color:rgba(255,255,255,.55);font-size:.88rem;margin:.5rem 0}
  .chips{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem}
  .chip{display:inline-flex;align-items:center;gap:.3rem;font-size:.65rem;font-weight:600;padding:.25rem .7rem;border-radius:999px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.1)}
  .chip-amber{background:rgba(245,158,11,.1);color:#fbbf24;border-color:rgba(245,158,11,.18)}
  .chip-blue{background:rgba(96,165,250,.1);color:#93c5fd;border-color:rgba(96,165,250,.18)}
  .notes{margin-top:.6rem;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.18);border-radius:8px;padding:.55rem .9rem;font-size:.8rem;color:#fbbf24}
  .empty{text-align:center;padding:3rem 1.5rem;color:rgba(255,255,255,.4)}
  .footer{text-align:center;margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.3);font-size:.75rem}
  .footer a{color:#a05e22;text-decoration:none}
  .quote-card{background:rgba(160,94,34,.06);border:1px solid rgba(160,94,34,.2);border-radius:14px;padding:1.25rem 1.4rem;margin-bottom:2rem}
  .quote-row{display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;font-size:.85rem;color:rgba(255,255,255,.6)}
  .quote-row.total{border-top:1px solid rgba(255,255,255,.1);margin-top:.4rem;padding-top:.7rem;font-size:1.05rem;font-weight:700;color:#fff}
  .quote-strike{text-decoration:line-through;color:rgba(255,255,255,.3);margin-right:.4rem;font-size:.8rem}
  .quote-badge{display:inline-block;background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25);border-radius:999px;padding:.2rem .7rem;font-size:.68rem;font-weight:600;margin-top:.5rem}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <div class="brand">Jambo Masai Tours</div>
    <h1><?= e($tour['name']) ?></h1>
    <div class="meta"><?= e($tour['destination'] ?? '') ?> · <?= e($tour['duration'] ?? '') ?></div>
  </div>

  <?php if ($firstName): ?>
  <p class="greet">Prepared for <strong style="color:#fff"><?= e($firstName) ?></strong> · <?= count($days) ?> day itinerary</p>
  <?php endif; ?>

  <?php if ((int)$share['travelers'] > 0): ?>
  <div class="quote-card">
    <div class="quote-row">
      <span><?= (int)$share['travelers'] ?> traveller(s) &times; per person</span>
      <span>
        <?php if ((float)$share['discount_percent'] > 0): ?>
        <span class="quote-strike"><?= formatPrice((float)$tour['price']) ?></span>
        <?php endif; ?>
        <?= formatPrice((float)$share['price_per_person']) ?>
      </span>
    </div>
    <?php if ((float)$share['discount_percent'] > 0): ?>
    <div class="quote-badge"><i class="fas fa-tag" style="font-size:.6rem"></i> <?= rtrim(rtrim(number_format((float)$share['discount_percent'],2),'0'),'.') ?>% group discount applied</div>
    <?php endif; ?>
    <div class="quote-row total">
      <span>Total (USD)</span>
      <span><?= formatPrice((float)$share['total_price']) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <div class="actions">
    <?php if ($pdfUrl): ?>
    <a href="<?= e($pdfUrl) ?>" class="btn btn-pdf" download><i class="fas fa-file-pdf"></i> Download PDF</a>
    <?php endif; ?>
    <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
  </div>

  <?php if (empty($days)): ?>
  <div class="empty">Itinerary details are being finalised. Please contact us on WhatsApp for the full plan.</div>
  <?php else: ?>
  <?php foreach ($days as $day):
      [$clr, $clrBg] = $dcPalette[($day['day_number']-1) % count($dcPalette)];
      $meals = array_filter(array_map('trim', explode(',', $day['meals'] ?? '')));
      $highlights = array_filter(explode('|', $day['highlights'] ?? ''));
  ?>
  <div class="day">
    <div class="day-num" style="background:<?= $clrBg ?>;color:<?= $clr ?>;border:1.5px solid <?= $clr ?>40">
      <?= str_pad($day['day_number'],2,'0',STR_PAD_LEFT) ?>
    </div>
    <div class="day-content">
      <?php if ($day['departure_location'] || $day['arrival_location']): ?>
      <div class="route"><?= e($day['departure_location']) ?><?= ($day['departure_location'] && $day['arrival_location']) ? ' &rarr; ' : '' ?><?= e($day['arrival_location']) ?></div>
      <?php endif; ?>
      <h3><?= e($day['title']) ?></h3>
      <p class="desc"><?= nl2br(e($day['description'])) ?></p>
      <div class="chips">
        <?php foreach ($meals as $m): ?><span class="chip chip-amber"><i class="fas fa-utensils" style="font-size:.55rem"></i> <?= e($m) ?></span><?php endforeach; ?>
        <?php if ($day['accommodation']): ?><span class="chip chip-blue"><i class="fas fa-bed" style="font-size:.55rem"></i> <?= e($day['accommodation']) ?></span><?php endif; ?>
      </div>
      <?php if (!empty($highlights)): ?>
      <div class="chips">
        <?php foreach ($highlights as $h): ?><span class="chip"><?= e(trim($h)) ?></span><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($day['notes']): ?><div class="notes"><i class="fas fa-exclamation-triangle"></i> <?= e($day['notes']) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <div class="footer">
    <p><?= SITE_NAME ?> · Arusha, Tanzania · <a href="tel:<?= e(SITE_PHONE) ?>"><?= e(SITE_PHONE) ?></a></p>
    <p><a href="<?= SITE_URL ?>/tour/<?= e($tour['slug']) ?>">View full tour page →</a></p>
  </div>
</div>
</body>
</html>
