<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* ─── Create / upgrade table ─── */
$db->exec("CREATE TABLE IF NOT EXISTS tour_itinerary (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    tour_id             INT NOT NULL,
    day_number          INT NOT NULL,
    title               VARCHAR(255) NOT NULL,
    description         TEXT,
    departure_location  VARCHAR(200) DEFAULT '',
    arrival_location    VARCHAR(200) DEFAULT '',
    distance            VARCHAR(50)  DEFAULT '',
    travel_time         VARCHAR(50)  DEFAULT '',
    accommodation       VARCHAR(200) DEFAULT '',
    hotel_url           VARCHAR(500) DEFAULT '',
    hotel_image         VARCHAR(500) DEFAULT '',
    meals               VARCHAR(200) DEFAULT '',
    highlights          TEXT         DEFAULT '',
    notes               TEXT         DEFAULT '',
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_day (tour_id, day_number)
)");
/* Add new columns to existing tables silently */
foreach ([
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS departure_location VARCHAR(200) DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS arrival_location   VARCHAR(200) DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS distance           VARCHAR(50)  DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS travel_time        VARCHAR(50)  DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS hotel_url          VARCHAR(500) DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS hotel_image        VARCHAR(500) DEFAULT ''",
    "ALTER TABLE tour_itinerary ADD COLUMN IF NOT EXISTS notes              TEXT         DEFAULT ''",
] as $sql) { try { $db->exec($sql); } catch (\Throwable $e) {} }

/* ─── Get tour ─── */
$tourId = sanitizeInt($_GET['tour_id'] ?? 0, 1);
if (!$tourId) { redirect(SITE_URL . '/admin/tours.php'); }
$tStmt = $db->prepare("SELECT * FROM tours WHERE id = ? LIMIT 1");
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();
if (!$tour) { redirect(SITE_URL . '/admin/tours.php'); }

$errors = [];

/* ─── POST handlers ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'save_day') {
        $dayId    = sanitizeInt($_POST['day_id'] ?? 0, 0);
        $dayNum   = sanitizeInt($_POST['day_number'] ?? 0, 1, 99);
        $title    = sanitizeInput($_POST['title']               ?? '');
        $desc     = sanitizeInput($_POST['description']         ?? '');
        $depLoc   = sanitizeInput($_POST['departure_location']  ?? '');
        $arrLoc   = sanitizeInput($_POST['arrival_location']    ?? '');
        $dist     = sanitizeInput($_POST['distance']            ?? '');
        $ttime    = sanitizeInput($_POST['travel_time']         ?? '');
        $accom    = sanitizeInput($_POST['accommodation']       ?? '');
        $hotelUrl = filter_var(trim($_POST['hotel_url'] ?? ''), FILTER_SANITIZE_URL);
        $meals    = sanitizeInput($_POST['meals']               ?? '');
        $rawHL    = sanitizeInput($_POST['highlights']          ?? '');
        $notes    = sanitizeInput($_POST['notes']               ?? '');
        $highlights = implode('|', array_filter(array_map('trim', explode("\n", $rawHL))));

        /* Hotel image: file upload > URL > existing */
        $existingHotelImg = sanitizeInput($_POST['existing_hotel_image'] ?? '');
        $hotelImgUrl      = sanitizeInput($_POST['hotel_image_url']      ?? '');
        $imgResult = handleImageUpload('hotel_image_file', $hotelImgUrl ?: $existingHotelImg);
        $hotelImage = $imgResult['url'] ?? $existingHotelImg;

        if (empty($title)) $errors[] = 'Day title is required.';
        if ($dayNum < 1)   $errors[] = 'Day number must be at least 1.';

        if (empty($errors)) {
            $fields = [$dayNum,$title,$desc,$depLoc,$arrLoc,$dist,$ttime,$accom,$hotelUrl,$hotelImage,$meals,$highlights,$notes];
            if ($dayId) {
                $db->prepare("UPDATE tour_itinerary SET day_number=?,title=?,description=?,departure_location=?,arrival_location=?,distance=?,travel_time=?,accommodation=?,hotel_url=?,hotel_image=?,meals=?,highlights=?,notes=? WHERE id=? AND tour_id=?")
                   ->execute([...$fields,$dayId,$tourId]);
            } else {
                $ex = $db->prepare("SELECT id FROM tour_itinerary WHERE tour_id=? AND day_number=?");
                $ex->execute([$tourId,$dayNum]);
                if ($ex->fetch()) {
                    $db->prepare("UPDATE tour_itinerary SET day_number=day_number+1 WHERE tour_id=? AND day_number>=?")
                       ->execute([$tourId,$dayNum]);
                }
                $db->prepare("INSERT INTO tour_itinerary (tour_id,day_number,title,description,departure_location,arrival_location,distance,travel_time,accommodation,hotel_url,hotel_image,meals,highlights,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$tourId,...$fields]);
            }
            redirect(SITE_URL . '/admin/tour-itinerary.php?tour_id='.$tourId.'&msg='.urlencode($dayId ? 'Day updated.' : 'Day added.'));
        }
    }

    if ($action === 'delete_day') {
        $dayId = sanitizeInt($_POST['day_id'] ?? 0, 1);
        if ($dayId) $db->prepare("DELETE FROM tour_itinerary WHERE id=? AND tour_id=?")->execute([$dayId,$tourId]);
        redirect(SITE_URL . '/admin/tour-itinerary.php?tour_id='.$tourId.'&msg=Day+deleted.');
    }

    if ($action === 'move_day') {
        $dayId = sanitizeInt($_POST['day_id'] ?? 0, 1);
        $dir   = $_POST['direction'] ?? '';
        $cur   = $db->prepare("SELECT day_number FROM tour_itinerary WHERE id=? AND tour_id=?");
        $cur->execute([$dayId,$tourId]);
        $row = $cur->fetch();
        if ($row) {
            $curNum = (int)$row['day_number'];
            $newNum = $dir === 'up' ? $curNum - 1 : $curNum + 1;
            if ($newNum >= 1) {
                $n = $db->prepare("SELECT id FROM tour_itinerary WHERE tour_id=? AND day_number=?");
                $n->execute([$tourId,$newNum]);
                $nr = $n->fetch();
                if ($nr) $db->prepare("UPDATE tour_itinerary SET day_number=? WHERE id=?")->execute([$curNum,$nr['id']]);
                $db->prepare("UPDATE tour_itinerary SET day_number=? WHERE id=?")->execute([$newNum,$dayId]);
            }
        }
        redirect(SITE_URL . '/admin/tour-itinerary.php?tour_id='.$tourId);
    }
}

$msg      = sanitizeInput($_GET['msg'] ?? '');
$editId   = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing  = null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM tour_itinerary WHERE id=? AND tour_id=?");
    $s->execute([$editId,$tourId]);
    $editing = $s->fetch();
}

$iStmt = $db->prepare("SELECT * FROM tour_itinerary WHERE tour_id=? ORDER BY day_number ASC");
$iStmt->execute([$tourId]);
$days     = $iStmt->fetchAll();
$nextDay  = count($days) + 1;
$csrfToken = generateCsrfToken();

function dc(int $n): array {
    $c=[['#10b981','rgba(16,185,129,.15)'],['#f59e0b','rgba(245,158,11,.15)'],['#3b82f6','rgba(59,130,246,.15)'],['#8b5cf6','rgba(139,92,246,.15)'],['#f97316','rgba(249,115,22,.15)'],['#ec4899','rgba(236,72,153,.15)'],['#06b6d4','rgba(6,182,212,.15)']];
    return $c[($n-1)%count($c)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Itinerary: <?= e($tour['name']) ?> | Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    body,html{background:#0f1117;color:#e5e7eb;font-family:'Inter',sans-serif}
    .admin-layout,.admin-main{background:#0f1117}
    .admin-main{padding:2rem}
    .card{background:#1a1d27;border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden}
    .card.highlight{border-color:rgba(16,185,129,.35);box-shadow:0 0 0 2px rgba(16,185,129,.1)}
    .f-input,.f-select,.f-textarea{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.72rem 1rem;font-family:'Inter',sans-serif;font-size:.875rem;outline:none;transition:all .2s;-webkit-appearance:none}
    .f-input:focus,.f-select:focus,.f-textarea:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.04)}
    .f-textarea{resize:vertical;min-height:80px}
    .f-select option{background:#1a1d27}
    .f-label{display:block;font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.4rem}
    .f-label span{color:#f87171}
    .f-hint{font-size:.7rem;color:rgba(255,255,255,.22);margin-top:.3rem}
    .ab{display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-weight:600;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;padding:.5rem 1.1rem;border-radius:8px;border:none;cursor:pointer;transition:all .25s;text-decoration:none}
    .ab-em{background:linear-gradient(135deg,#059669,#10b981);color:#fff;box-shadow:0 3px 10px rgba(16,185,129,.2)}
    .ab-em:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(16,185,129,.3)}
    .ab-ol{background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.12)}
    .ab-ol:hover{background:rgba(255,255,255,.1);color:#fff}
    .ab-del{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.18)}
    .ab-del:hover{background:rgba(239,68,68,.18)}
    .ab-sm{padding:.35rem .75rem;font-size:.63rem;border-radius:6px}
    .ab-xs{padding:.28rem .55rem;font-size:.6rem;border-radius:6px;background:rgba(255,255,255,.05);color:rgba(255,255,255,.35);border:1px solid rgba(255,255,255,.07)}
    .ab-xs:hover{background:rgba(255,255,255,.09);color:rgba(255,255,255,.65)}

    /* Page header */
    .pg-header{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.5rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}

    /* Day card */
    .day-card{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:14px;margin-bottom:.75rem;transition:all .3s;overflow:hidden}
    .day-card:hover{border-color:rgba(16,185,129,.2)}
    .day-header{display:flex;align-items:flex-start;gap:1rem;padding:1.2rem 1.4rem}
    .day-num{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.85rem;flex-shrink:0}
    .timeline-dot{position:absolute;left:20px;top:52px;bottom:-1px;width:2px}

    /* Meta chips */
    .chip{display:inline-flex;align-items:center;gap:.3rem;font-size:.63rem;font-family:'Montserrat',sans-serif;font-weight:600;padding:.22rem .65rem;border-radius:999px}
    .chip-amber{background:rgba(245,158,11,.1);color:#fbbf24;border:1px solid rgba(245,158,11,.18)}
    .chip-blue{background:rgba(96,165,250,.1);color:#93c5fd;border:1px solid rgba(96,165,250,.18)}
    .chip-em{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.18)}
    .chip-gray{background:rgba(255,255,255,.06);color:rgba(255,255,255,.45);border:1px solid rgba(255,255,255,.1)}
    .hl-pill{display:inline-block;font-size:.62rem;font-family:'Montserrat',sans-serif;padding:.18rem .6rem;border-radius:999px;background:rgba(16,185,129,.07);color:#34d399;border:1px solid rgba(16,185,129,.13);margin:.15rem .1rem}

    /* Hotel image preview */
    .hotel-preview{width:100%;height:140px;object-fit:cover;border-radius:10px;margin-top:.5rem}

    /* Section divider */
    .s-div{height:1px;background:rgba(255,255,255,.06);margin:1.2rem 0}

    /* Empty */
    .empty{text-align:center;padding:3.5rem 2rem;background:#1a1d27;border-radius:14px;border:2px dashed rgba(255,255,255,.07)}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <!-- Page header -->
    <div class="pg-header">
      <div style="display:flex;align-items:flex-start;gap:1rem">
        <?php if ($tour['image']): ?>
        <img src="<?= e($tour['image']) ?>" alt="" style="width:66px;height:52px;object-fit:cover;border-radius:9px;flex-shrink:0;border:1px solid rgba(255,255,255,.07)">
        <?php endif; ?>
        <div>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.3rem">
            <span class="chip chip-em"><?= e($tour['tour_type']) ?></span>
            <span class="chip chip-gray"><i class="fas fa-map-marker-alt" style="font-size:.5rem"></i><?= e($tour['destination']) ?></span>
            <span class="chip chip-gray"><i class="fas fa-clock" style="font-size:.5rem"></i><?= e($tour['duration']) ?></span>
          </div>
          <h1 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:#fff;line-height:1.25"><?= e($tour['name']) ?></h1>
          <p style="font-size:.78rem;color:rgba(255,255,255,.3);margin-top:.2rem"><?= count($days) ?> day<?= count($days)!==1?'s':'' ?> in itinerary</p>
        </div>
      </div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="<?= SITE_URL ?>/tour-detail.php?slug=<?= e($tour['slug']) ?>" target="_blank" class="ab ab-ol ab-sm"><i class="fas fa-eye" style="font-size:.6rem"></i> Preview</a>
        <a href="tours.php" class="ab ab-ol ab-sm"><i class="fas fa-arrow-left" style="font-size:.6rem"></i> Tours</a>
        <a href="tour-itinerary.php?tour_id=<?= $tourId ?>&edit=new" class="ab ab-em ab-sm"><i class="fas fa-plus" style="font-size:.6rem"></i> Add Day</a>
      </div>
    </div>

    <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.22);color:#34d399;padding:.8rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;font-size:.875rem;display:flex;align-items:center;gap:.65rem">
      <i class="fas fa-check-circle"></i><?= e($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.18);color:#f87171;padding:.8rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;font-size:.875rem">
      <?php foreach($errors as $err): ?><div>· <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ ADD / EDIT FORM ═══ -->
    <?php if (isset($_GET['edit'])): ?>
    <div class="card highlight" style="padding:1.75rem;margin-bottom:1.5rem">
      <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:#fff;margin-bottom:1.5rem;display:flex;align-items:center;gap:.7rem">
        <span style="width:30px;height:30px;border-radius:50%;background:rgba(16,185,129,.13);color:#34d399;display:flex;align-items:center;justify-content:center;font-size:.75rem">
          <i class="fas <?= $editing ? 'fa-pen' : 'fa-plus' ?>"></i>
        </span>
        <?= $editing ? 'Edit Day '.$editing['day_number'] : 'Add New Day' ?>
      </h2>

      <form method="POST" enctype="multipart/form-data" id="day-form">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="save_day">
        <input type="hidden" name="day_id" value="<?= $editing ? $editing['id'] : 0 ?>">
        <input type="hidden" name="existing_hotel_image" value="<?= e($editing['hotel_image'] ?? '') ?>">

        <!-- Row 1: Day number + Title -->
        <div style="display:grid;grid-template-columns:110px 1fr;gap:1rem;margin-bottom:1rem">
          <div>
            <label class="f-label">Day # <span>*</span></label>
            <input type="number" class="f-input" name="day_number" min="1" max="99" required value="<?= $editing ? $editing['day_number'] : $nextDay ?>">
          </div>
          <div>
            <label class="f-label">Day Title <span>*</span></label>
            <input type="text" class="f-input" name="title" required placeholder="e.g. Arrival in Arusha & Transfer to Serengeti" value="<?= e($editing['title'] ?? '') ?>">
          </div>
        </div>

        <!-- Row 2: Departure → Arrival -->
        <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:.75rem;align-items:end;margin-bottom:1rem">
          <div>
            <label class="f-label">Departure Location</label>
            <input type="text" class="f-input" name="departure_location" placeholder="e.g. Arusha Airport" value="<?= e($editing['departure_location'] ?? '') ?>">
          </div>
          <div style="padding-bottom:.75rem;color:rgba(255,255,255,.2);font-size:1.2rem;text-align:center">→</div>
          <div>
            <label class="f-label">Arrival Location</label>
            <input type="text" class="f-input" name="arrival_location" placeholder="e.g. Serengeti Central" value="<?= e($editing['arrival_location'] ?? '') ?>">
          </div>
        </div>

        <!-- Row 3: Distance + Travel Time -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
          <div>
            <label class="f-label">Distance</label>
            <input type="text" class="f-input" name="distance" placeholder="e.g. 325km" value="<?= e($editing['distance'] ?? '') ?>">
          </div>
          <div>
            <label class="f-label">Travel Time</label>
            <input type="text" class="f-input" name="travel_time" placeholder="e.g. 4hrs 30mins" value="<?= e($editing['travel_time'] ?? '') ?>">
          </div>
        </div>

        <div class="s-div"></div>

        <!-- Row 4: Accommodation + Hotel URL -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
          <div>
            <label class="f-label">Hotel / Accommodation Name</label>
            <input type="text" class="f-input" name="accommodation" placeholder="e.g. Serengeti Sopa Lodge" value="<?= e($editing['accommodation'] ?? '') ?>">
          </div>
          <div>
            <label class="f-label">Hotel Website URL</label>
            <input type="url" class="f-input" name="hotel_url" placeholder="https://www.hotelname.com" value="<?= e($editing['hotel_url'] ?? '') ?>">
            <div class="f-hint">Visitors can click to view hotel website</div>
          </div>
        </div>

        <!-- Row 5: Hotel Image -->
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:1rem;margin-bottom:1rem">
          <label class="f-label" style="margin-bottom:.75rem">Hotel / Accommodation Image</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div>
              <label class="f-label" style="font-size:.58rem">Upload from computer</label>
              <input type="file" class="f-input" name="hotel_image_file" accept="image/jpeg,image/png,image/webp" id="hotel-file">
              <div class="f-hint">JPG/PNG/WebP · Max 8MB</div>
            </div>
            <div>
              <label class="f-label" style="font-size:.58rem">Or paste image URL</label>
              <input type="url" class="f-input" name="hotel_image_url" id="hotel-url-input"
                     placeholder="https://..." value="<?= e($editing['hotel_image'] ?? '') ?>">
            </div>
          </div>
          <?php if (!empty($editing['hotel_image'])): ?>
          <div style="margin-top:.75rem">
            <div style="font-size:.7rem;color:rgba(255,255,255,.3);margin-bottom:.4rem">Current image:</div>
            <img src="<?= e($editing['hotel_image']) ?>" alt="" class="hotel-preview" id="hotel-preview">
          </div>
          <?php else: ?>
          <img src="" alt="" class="hotel-preview" id="hotel-preview" style="display:none;margin-top:.75rem">
          <?php endif; ?>
        </div>

        <div class="s-div"></div>

        <!-- Description -->
        <div style="margin-bottom:1rem">
          <label class="f-label">Day Description <span>*</span></label>
          <textarea class="f-textarea" name="description" rows="4"
                    placeholder="Describe what happens this day — activities, landscapes, wildlife sightings, cultural moments..."><?= e($editing['description'] ?? '') ?></textarea>
        </div>

        <!-- Meals + Highlights -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
          <div>
            <label class="f-label">Meals Included</label>
            <input type="text" class="f-input" name="meals" placeholder="Breakfast, Lunch, Dinner" value="<?= e($editing['meals'] ?? '') ?>">
            <div class="f-hint">Comma-separated</div>
          </div>
          <div>
            <label class="f-label">Activities / Highlights <small style="text-transform:none;letter-spacing:0;font-size:.68rem;color:rgba(255,255,255,.2)">(one per line)</small></label>
            <textarea class="f-textarea" name="highlights" rows="3"
                      placeholder="Morning game drive&#10;Big Five sighting&#10;Sundowner at camp"><?= e(str_replace('|', "\n", $editing['highlights'] ?? '')) ?></textarea>
          </div>
        </div>

        <!-- Notes -->
        <div style="margin-bottom:1.5rem">
          <label class="f-label">Important Notes / Warnings <small style="text-transform:none;letter-spacing:0;font-size:.68rem;color:rgba(255,255,255,.2)">(shown as amber alert)</small></label>
          <textarea class="f-textarea" name="notes" rows="2"
                    placeholder="e.g. Lunch (Breakfast & dinner not included). Non-alcoholic drinks."><?= e($editing['notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
          <button type="submit" class="ab ab-em"><i class="fas fa-save" style="font-size:.6rem"></i> <?= $editing ? 'Update Day' : 'Save Day' ?></button>
          <a href="tour-itinerary.php?tour_id=<?= $tourId ?>" class="ab ab-ol"><i class="fas fa-times" style="font-size:.6rem"></i> Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- ═══ ITINERARY CARDS ═══ -->
    <?php if (empty($days)): ?>
    <div class="empty">
      <div style="font-size:2.8rem;margin-bottom:.75rem">📅</div>
      <h3 style="font-family:'Playfair Display',serif;font-size:1.15rem;color:#fff;margin-bottom:.4rem">No itinerary yet</h3>
      <p style="color:rgba(255,255,255,.3);font-size:.85rem;margin-bottom:1.25rem">Add day-by-day details to help travellers understand what to expect.</p>
      <a href="tour-itinerary.php?tour_id=<?= $tourId ?>&edit=new" class="ab ab-em"><i class="fas fa-plus" style="font-size:.6rem"></i> Add First Day</a>
    </div>
    <?php else: ?>
    <div style="position:relative">
      <?php foreach ($days as $idx => $day):
        [$clr,$clrBg] = dc((int)$day['day_number']);
        $hls   = array_filter(explode('|', $day['highlights'] ?? ''));
        $isLast= $idx === count($days) - 1;
      ?>
      <div class="day-card" style="position:relative">
        <?php if (!$isLast): ?>
        <div class="timeline-dot" style="background:linear-gradient(to bottom,<?= $clr ?>30,transparent)"></div>
        <?php endif; ?>

        <div class="day-header">
          <!-- Day circle -->
          <div class="day-num" style="background:<?= $clrBg ?>;color:<?= $clr ?>;border:1.5px solid <?= $clr ?>35">
            <?= str_pad($day['day_number'],2,'0',STR_PAD_LEFT) ?>
          </div>

          <!-- Content -->
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
              <div>
                <!-- Route line -->
                <?php if ($day['departure_location'] || $day['arrival_location']): ?>
                <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem;flex-wrap:wrap">
                  <?php if ($day['departure_location']): ?>
                  <span class="chip chip-gray"><i class="fas fa-plane-departure" style="font-size:.5rem"></i><?= e($day['departure_location']) ?></span>
                  <?php endif; ?>
                  <?php if ($day['distance'] || $day['travel_time']): ?>
                  <div style="display:flex;align-items:center;gap:.3rem">
                    <div style="width:20px;height:1px;background:rgba(255,255,255,.15)"></div>
                    <?php if ($day['distance']): ?><span class="chip chip-gray" style="font-size:.58rem"><i class="fas fa-road" style="font-size:.48rem"></i><?= e($day['distance']) ?></span><?php endif; ?>
                    <?php if ($day['travel_time']): ?><span class="chip chip-gray" style="font-size:.58rem"><i class="fas fa-clock" style="font-size:.48rem"></i><?= e($day['travel_time']) ?></span><?php endif; ?>
                    <div style="width:20px;height:1px;background:rgba(255,255,255,.15)"></div>
                  </div>
                  <?php endif; ?>
                  <?php if ($day['arrival_location']): ?>
                  <span class="chip chip-em"><i class="fas fa-map-marker-alt" style="font-size:.5rem"></i><?= e($day['arrival_location']) ?></span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
                <h3 style="font-family:'Playfair Display',serif;font-size:1rem;color:#fff;line-height:1.3;margin-bottom:.3rem"><?= e($day['title']) ?></h3>
                <!-- Meals + accommodation chips -->
                <div style="display:flex;flex-wrap:wrap;gap:.35rem">
                  <?php if ($day['meals']): foreach(array_filter(array_map('trim',explode(',',$day['meals']))) as $m): ?>
                  <span class="chip chip-amber"><i class="fas fa-utensils" style="font-size:.48rem"></i><?= e($m) ?></span>
                  <?php endforeach; endif; ?>
                  <?php if ($day['accommodation']): ?>
                  <span class="chip chip-blue">
                    <i class="fas fa-bed" style="font-size:.48rem"></i>
                    <?php if ($day['hotel_url']): ?>
                    <a href="<?= e($day['hotel_url']) ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none"><?= e($day['accommodation']) ?> <i class="fas fa-external-link-alt" style="font-size:.45rem;opacity:.6"></i></a>
                    <?php else: ?>
                    <?= e($day['accommodation']) ?>
                    <?php endif; ?>
                  </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Actions -->
              <div style="display:flex;gap:.35rem;align-items:center;flex-shrink:0">
                <?php if ($idx > 0): ?>
                <form method="POST" style="display:inline"><input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="move_day"><input type="hidden" name="day_id" value="<?= $day['id'] ?>"><input type="hidden" name="direction" value="up"><button class="ab ab-xs" title="Move up" type="submit"><i class="fas fa-chevron-up" style="font-size:.6rem"></i></button></form>
                <?php endif; ?>
                <?php if ($idx < count($days)-1): ?>
                <form method="POST" style="display:inline"><input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="move_day"><input type="hidden" name="day_id" value="<?= $day['id'] ?>"><input type="hidden" name="direction" value="down"><button class="ab ab-xs" title="Move down" type="submit"><i class="fas fa-chevron-down" style="font-size:.6rem"></i></button></form>
                <?php endif; ?>
                <a href="tour-itinerary.php?tour_id=<?= $tourId ?>&edit=<?= $day['id'] ?>" class="ab ab-ol ab-sm"><i class="fas fa-pen" style="font-size:.58rem"></i> Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete Day <?= $day['day_number'] ?>?')">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete_day">
                  <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                  <button type="submit" class="ab ab-del ab-sm"><i class="fas fa-trash" style="font-size:.58rem"></i></button>
                </form>
              </div>
            </div>

            <!-- Hotel image thumbnail + description row -->
            <?php if ($day['hotel_image'] || $day['description']): ?>
            <div style="display:grid;grid-template-columns:<?= $day['hotel_image'] ? '160px 1fr' : '1fr' ?>;gap:1rem;margin-top:.85rem;align-items:start">
              <?php if ($day['hotel_image']): ?>
              <img src="<?= e($day['hotel_image']) ?>" alt="<?= e($day['accommodation']) ?>"
                   style="width:160px;height:100px;object-fit:cover;border-radius:9px;border:1px solid rgba(255,255,255,.07)">
              <?php endif; ?>
              <?php if ($day['description']): ?>
              <p style="color:rgba(255,255,255,.45);font-size:.85rem;line-height:1.7;margin:0"><?= e(mb_substr($day['description'],0,200)).(mb_strlen($day['description'])>200?'…':'') ?></p>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Highlights pills -->
            <?php if (!empty($hls)): ?>
            <div style="margin-top:.6rem;display:flex;flex-wrap:wrap;align-items:center;gap:.15rem">
              <span style="font-size:.6rem;font-family:'Montserrat',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.2);margin-right:.25rem">Activities:</span>
              <?php foreach($hls as $hl): ?><span class="hl-pill"><?= e(trim($hl)) ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if ($day['notes']): ?>
            <div style="margin-top:.65rem;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.18);border-radius:8px;padding:.55rem .9rem;display:flex;align-items:flex-start;gap:.5rem">
              <i class="fas fa-exclamation-triangle" style="color:#fbbf24;font-size:.7rem;margin-top:.15rem;flex-shrink:0"></i>
              <span style="font-size:.78rem;color:rgba(245,158,11,.85)"><?= e($day['notes']) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Add next day -->
    <div style="margin-top:1rem;padding:1.1rem;background:#1a1d27;border-radius:12px;border:1.5px dashed rgba(16,185,129,.18);text-align:center">
      <a href="tour-itinerary.php?tour_id=<?= $tourId ?>&edit=new" class="ab ab-em" style="display:inline-flex"><i class="fas fa-plus" style="font-size:.6rem"></i> Add Day <?= $nextDay ?></a>
      <p style="color:rgba(255,255,255,.2);font-size:.72rem;margin-top:.5rem"><?= count($days) ?> day<?= count($days)!==1?'s':'' ?> · Next will be Day <?= $nextDay ?></p>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
/* Image preview */
const hotelFile = document.getElementById('hotel-file');
const hotelUrlIn = document.getElementById('hotel-url-input');
const hotelPrev = document.getElementById('hotel-preview');
function showPreview(src) {
  if (!hotelPrev) return;
  if (src) { hotelPrev.src = src; hotelPrev.style.display = 'block'; }
  else { hotelPrev.style.display = 'none'; }
}
if (hotelFile) {
  hotelFile.addEventListener('change', () => {
    if (hotelFile.files[0]) {
      showPreview(URL.createObjectURL(hotelFile.files[0]));
      if (hotelUrlIn) hotelUrlIn.value = '';
    }
  });
}
if (hotelUrlIn) {
  let t;
  hotelUrlIn.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => { showPreview(hotelUrlIn.value.trim()); }, 600);
  });
}
</script>
</body>
</html>
