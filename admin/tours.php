<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db     = getDB();
$errors = [];

/* -- Add included/not_included columns if missing -- */
foreach (["ALTER TABLE tours ADD COLUMN IF NOT EXISTS included TEXT DEFAULT ''",
          "ALTER TABLE tours ADD COLUMN IF NOT EXISTS not_included TEXT DEFAULT ''"]
         as $sql) { try { $db->exec($sql); } catch (\Throwable $e) {} }

/* -- Ensure itinerary + photos tables exist -- */
$db->exec("CREATE TABLE IF NOT EXISTS tour_itinerary (id INT AUTO_INCREMENT PRIMARY KEY,tour_id INT NOT NULL,day_number INT NOT NULL,title VARCHAR(255) NOT NULL,description TEXT,departure_location VARCHAR(200) DEFAULT '',arrival_location VARCHAR(200) DEFAULT '',distance VARCHAR(50) DEFAULT '',travel_time VARCHAR(50) DEFAULT '',accommodation VARCHAR(200) DEFAULT '',hotel_url VARCHAR(500) DEFAULT '',hotel_image VARCHAR(500) DEFAULT '',meals VARCHAR(200) DEFAULT '',highlights TEXT DEFAULT '',notes TEXT DEFAULT '',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY unique_day(tour_id,day_number))");
$db->exec("CREATE TABLE IF NOT EXISTS tour_photos (id INT AUTO_INCREMENT PRIMARY KEY,tour_id INT NOT NULL,image VARCHAR(500) NOT NULL,caption VARCHAR(255) DEFAULT '',sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

/* -- slugify helper ----------------------- */
function makeSlug(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

/* -- POST handlers ------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    /* -- Save (add or edit) -- */
    if ($action === 'save') {
        $id          = sanitizeInt($_POST['id'] ?? 0, 0);
        $name        = sanitizeInput($_POST['name']        ?? '');
        $slug        = makeSlug(sanitizeInput($_POST['slug'] ?? '') ?: $name);
        $destination = sanitizeInput($_POST['destination'] ?? '');
        $tourType    = sanitizeInput($_POST['tour_type']   ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $rawHL       = sanitizeInput($_POST['highlights']   ?? '');
        $highlights  = implode('|', array_filter(array_map('trim', explode("\n", $rawHL))));
        $rawInc      = sanitizeInput($_POST['included']    ?? '');
        $included    = implode('|', array_filter(array_map('trim', explode("\n", $rawInc))));
        $rawNot      = sanitizeInput($_POST['not_included'] ?? '');
        $notIncluded = implode('|', array_filter(array_map('trim', explode("\n", $rawNot))));
        $price       = (float)($_POST['price'] ?? 0);
        $duration    = sanitizeInput($_POST['duration']    ?? '');
        $maxTrav     = sanitizeInt($_POST['max_travelers'] ?? 12, 1, 50);
        $rating      = min(5.0, max(0.0, (float)($_POST['rating'] ?? 4.8)));
        $featured    = isset($_POST['featured']) ? 1 : 0;
        $existingImg = sanitizeInput($_POST['existing_image'] ?? '');
        $urlImg      = sanitizeInput($_POST['image_url'] ?? '');

        if (empty($name))        $errors[] = 'Tour name is required.';
        if (empty($destination)) $errors[] = 'Destination is required.';
        if (empty($tourType))    $errors[] = 'Tour type is required.';
        if (empty($description)) $errors[] = 'Description is required.';
        if ($price <= 0)         $errors[] = 'Price must be greater than 0.';
        if (empty($duration))    $errors[] = 'Duration is required.';

        // Handle image: uploaded file takes priority, then URL, then existing
        $imageResult = handleImageUpload('image_file', $urlImg ?: $existingImg);
        if (isset($imageResult['error'])) { $errors[] = $imageResult['error']; }
        $imageUrl = $imageResult['url'] ?? $existingImg;
        if (empty($imageUrl)) $errors[] = 'Tour image is required.';

        // Ensure unique slug
        if (empty($errors)) {
            $slugCheck = $db->prepare("SELECT id FROM tours WHERE slug = ? AND id != ? LIMIT 1");
            $slugCheck->execute([$slug, $id]);
            if ($slugCheck->fetch()) {
                $slug .= '-' . time();
            }
        }

        if (empty($errors)) {
            if ($id) {
                $db->prepare("UPDATE tours SET name=:nm,slug=:sl,destination=:dst,tour_type=:tt,description=:desc,highlights=:hl,included=:inc,not_included=:ni,price=:pr,duration=:dur,max_travelers=:mt,rating=:rt,image=:img,featured=:ft WHERE id=:id")
                   ->execute([':nm'=>$name,':sl'=>$slug,':dst'=>$destination,':tt'=>$tourType,':desc'=>$description,':hl'=>$highlights,':inc'=>$included,':ni'=>$notIncluded,':pr'=>$price,':dur'=>$duration,':mt'=>$maxTrav,':rt'=>$rating,':img'=>$imageUrl,':ft'=>$featured,':id'=>$id]);
                redirect(SITE_URL . '/admin/tours.php?edit='.$id.'&tab=details&msg='.urlencode('Tour updated successfully.'));
            } else {
                $db->prepare("INSERT INTO tours (name,slug,destination,tour_type,description,highlights,included,not_included,price,duration,max_travelers,rating,image,featured) VALUES (:nm,:sl,:dst,:tt,:desc,:hl,:inc,:ni,:pr,:dur,:mt,:rt,:img,:ft)")
                   ->execute([':nm'=>$name,':sl'=>$slug,':dst'=>$destination,':tt'=>$tourType,':desc'=>$description,':hl'=>$highlights,':inc'=>$included,':ni'=>$notIncluded,':pr'=>$price,':dur'=>$duration,':mt'=>$maxTrav,':rt'=>$rating,':img'=>$imageUrl,':ft'=>$featured]);
                $newId = $db->lastInsertId();
                redirect(SITE_URL . '/admin/tours.php?edit='.$newId.'&tab=itinerary&msg='.urlencode('Tour added! Now add the day-by-day itinerary below.'));
            }
        }
    }

    /* -- Delete -- */
    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM tours WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/tours.php?msg=Tour+deleted.');
    }

    /* -- Toggle featured -- */
    if ($action === 'toggle_featured') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("UPDATE tours SET featured = 1 - featured WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/tours.php');
    }

    /* -- ITINERARY: save day -- */
    if ($action === 'save_day') {
        $tourId  = sanitizeInt($_POST['tour_id']  ?? 0, 1);
        $dayId   = sanitizeInt($_POST['day_id']   ?? 0, 0);
        $dayNum  = sanitizeInt($_POST['day_number']?? 0, 1, 99);
        $title   = sanitizeInput($_POST['title']               ?? '');
        $desc    = sanitizeInput($_POST['description']         ?? '');
        $depLoc  = sanitizeInput($_POST['departure_location']  ?? '');
        $arrLoc  = sanitizeInput($_POST['arrival_location']    ?? '');
        $dist    = sanitizeInput($_POST['distance']            ?? '');
        $ttime   = sanitizeInput($_POST['travel_time']         ?? '');
        $accom   = sanitizeInput($_POST['accommodation']       ?? '');
        $hotelUrl= filter_var(trim($_POST['hotel_url'] ?? ''), FILTER_SANITIZE_URL);
        $meals   = sanitizeInput($_POST['meals']               ?? '');
        $rawHL   = sanitizeInput($_POST['highlights']          ?? '');
        $notes   = sanitizeInput($_POST['notes']               ?? '');
        $highlights = implode('|', array_filter(array_map('trim', explode("\n", $rawHL))));
        $existHotel = sanitizeInput($_POST['existing_hotel_image'] ?? '');
        $hotelImgUrl= sanitizeInput($_POST['hotel_image_url']      ?? '');
        $imgR    = handleImageUpload('hotel_image_file', $hotelImgUrl ?: $existHotel);
        $hotelImg= $imgR['url'] ?? $existHotel;
        if (!empty($title) && $dayNum >= 1) {
            $fields = [$dayNum,$title,$desc,$depLoc,$arrLoc,$dist,$ttime,$accom,$hotelUrl,$hotelImg,$meals,$highlights,$notes];
            if ($dayId) {
                $db->prepare("UPDATE tour_itinerary SET day_number=?,title=?,description=?,departure_location=?,arrival_location=?,distance=?,travel_time=?,accommodation=?,hotel_url=?,hotel_image=?,meals=?,highlights=?,notes=? WHERE id=? AND tour_id=?")->execute([...$fields,$dayId,$tourId]);
            } else {
                $ex=$db->prepare("SELECT id FROM tour_itinerary WHERE tour_id=? AND day_number=?");$ex->execute([$tourId,$dayNum]);
                if($ex->fetch()) $db->prepare("UPDATE tour_itinerary SET day_number=day_number+1 WHERE tour_id=? AND day_number>=?")->execute([$tourId,$dayNum]);
                $db->prepare("INSERT INTO tour_itinerary (tour_id,day_number,title,description,departure_location,arrival_location,distance,travel_time,accommodation,hotel_url,hotel_image,meals,highlights,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$tourId,...$fields]);
            }
        }
        redirect(SITE_URL.'/admin/tours.php?edit='.$tourId.'&tab=itinerary&msg='.urlencode($dayId?'Day updated.':'Day added.'));
    }

    /* -- ITINERARY: delete day -- */
    if ($action === 'delete_day') {
        $tourId = sanitizeInt($_POST['tour_id'] ?? 0, 1);
        $dayId  = sanitizeInt($_POST['day_id']  ?? 0, 1);
        if ($dayId) $db->prepare("DELETE FROM tour_itinerary WHERE id=? AND tour_id=?")->execute([$dayId,$tourId]);
        redirect(SITE_URL.'/admin/tours.php?edit='.$tourId.'&tab=itinerary&msg=Day+deleted.');
    }

    /* -- ITINERARY: move day -- */
    if ($action === 'move_day') {
        $tourId = sanitizeInt($_POST['tour_id'] ?? 0, 1);
        $dayId  = sanitizeInt($_POST['day_id']  ?? 0, 1);
        $dir    = $_POST['direction'] ?? '';
        $cur    = $db->prepare("SELECT day_number FROM tour_itinerary WHERE id=? AND tour_id=?");$cur->execute([$dayId,$tourId]);$row=$cur->fetch();
        if ($row) {
            $curN=(int)$row['day_number'];$newN=$dir==='up'?$curN-1:$curN+1;
            if ($newN>=1){$n=$db->prepare("SELECT id FROM tour_itinerary WHERE tour_id=? AND day_number=?");$n->execute([$tourId,$newN]);$nr=$n->fetch();
                if($nr)$db->prepare("UPDATE tour_itinerary SET day_number=? WHERE id=?")->execute([$curN,$nr['id']]);
                $db->prepare("UPDATE tour_itinerary SET day_number=? WHERE id=?")->execute([$newN,$dayId]);}
        }
        redirect(SITE_URL.'/admin/tours.php?edit='.$tourId.'&tab=itinerary');
    }

    /* -- PHOTOS: upload -- */
    if ($action === 'upload_photos') {
        $tourId  = sanitizeInt($_POST['tour_id'] ?? 0, 1);
        $urls    = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? '')));
        $uploaded= 0;
        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['name'] as $i => $fname) {
                if (!$fname) continue;
                $_FILES['_sp'] = ['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
                $r = handleImageUpload('_sp','');
                if (isset($r['url'])) { $s=$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM tour_photos WHERE tour_id=$tourId")->fetchColumn(); $db->prepare("INSERT INTO tour_photos (tour_id,image,caption,sort_order) VALUES (?,?,?,?)")->execute([$tourId,$r['url'],'', $s]); $uploaded++; }
            }
        }
        foreach ($urls as $url) { if (!filter_var($url,FILTER_VALIDATE_URL)) continue; $s=$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM tour_photos WHERE tour_id=$tourId")->fetchColumn(); $db->prepare("INSERT INTO tour_photos (tour_id,image,caption,sort_order) VALUES (?,?,?,?)")->execute([$tourId,$url,'',$s]); $uploaded++; }
        redirect(SITE_URL.'/admin/tours.php?edit='.$tourId.'&tab=photos&msg='.urlencode($uploaded.' photo(s) added.'));
    }

    /* -- PHOTOS: delete -- */
    if ($action === 'delete_photo') {
        $tourId  = sanitizeInt($_POST['tour_id']  ?? 0, 1);
        $photoId = sanitizeInt($_POST['photo_id'] ?? 0, 1);
        if ($photoId) $db->prepare("DELETE FROM tour_photos WHERE id=? AND tour_id=?")->execute([$photoId,$tourId]);
        redirect(SITE_URL.'/admin/tours.php?edit='.$tourId.'&tab=photos&msg=Photo+deleted.');
    }
}

$msg      = sanitizeInput($_GET['msg'] ?? '');
$editId   = sanitizeInt($_GET['edit'] ?? 0, 0);
$activeTab= sanitizeInput($_GET['tab'] ?? 'details');
$editing  = null;
$editDayId= sanitizeInt($_GET['edit_day'] ?? 0, 0);
$editingDay = null;
$itineraryDays = [];
$tourPhotos    = [];

if ($editId) {
    $s = $db->prepare("SELECT * FROM tours WHERE id=?"); $s->execute([$editId]);
    $editing = $s->fetch();
    if ($editing) {
        /* Load itinerary */
        $dStmt = $db->prepare("SELECT * FROM tour_itinerary WHERE tour_id=? ORDER BY day_number ASC");
        $dStmt->execute([$editId]); $itineraryDays = $dStmt->fetchAll();
        /* Load day being edited */
        if ($editDayId) { $ed=$db->prepare("SELECT * FROM tour_itinerary WHERE id=? AND tour_id=?");$ed->execute([$editDayId,$editId]);$editingDay=$ed->fetch(); }
        /* Load photos */
        $pStmt = $db->prepare("SELECT * FROM tour_photos WHERE tour_id=? ORDER BY sort_order ASC,id ASC");
        $pStmt->execute([$editId]); $tourPhotos = $pStmt->fetchAll();
    }
}
$filterType = sanitizeInput($_GET['type'] ?? '');
if ($filterType !== '') {
    $ts = $db->prepare("SELECT * FROM tours WHERE tour_type = ? ORDER BY featured DESC, created_at DESC");
    $ts->execute([$filterType]);
    $tours = $ts->fetchAll();
} else {
    $tours = $db->query("SELECT * FROM tours ORDER BY featured DESC, created_at DESC")->fetchAll();
}
$csrfToken = generateCsrfToken();
$nextDay   = count($itineraryDays) + 1;

$destinations = ['Serengeti','Ngorongoro','Kilimanjaro','Zanzibar','Tarangire','Lake Manyara','Maasai Heartland','Selous'];
$tourTypes    = [
    'Wildlife Safari','Great Migration Safari','Trekking','Beach Holiday',
    'Cultural Tour','Honeymoon Safari','Family Safari','Balloon Safari',
    'Walking Safari','Bird Watching Safari','Photography Safari',
    'Adventure Tour','Camping Safari','Luxury Safari','Budget Safari',
    'Group Safari','Day Trip',
];

/* Day colour helper */
function dayClr(int $n):array{$c=[['#10b981','rgba(16,185,129,.15)'],['#f59e0b','rgba(245,158,11,.15)'],['#3b82f6','rgba(59,130,246,.15)'],['#8b5cf6','rgba(139,92,246,.15)'],['#f97316','rgba(249,115,22,.15)']];return $c[($n-1)%count($c)];}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tours | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <!-- Page Header -->
    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;font-weight:700">
          <?= isset($_GET['edit']) ? ($editing ? 'Edit: '.e($editing['name']) : 'Add New Tour') : 'Safari Tours' ?>
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.1rem">
          <?= isset($_GET['edit']) ? ($editing ? 'Manage tour details, itinerary and photos in one place' : 'Fill in tour details below') : count($tours).' tours in database' ?>
        </p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if (isset($_GET['edit']) && $editing): ?>
        <a href="<?= url('tour-detail.php?slug='.e($editing['slug'])) ?>" target="_blank" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-eye" style="font-size:.6rem"></i> Preview
        </a>
        <a href="tours.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> All Tours
        </a>
        <?php else: ?>
        <a href="tours.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-plus" style="font-size:.6rem"></i> Add New Tour
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Message -->
    <?php if ($msg): ?>
    <div class="alert <?= str_contains($msg,'error')||str_contains($msg,'Error') ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:1.25rem">
      <i class="fas <?= str_contains($msg,'error') ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i> <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
    <!-- ----------------------------------------
         TABBED TOUR EDITOR
    ---------------------------------------- -->
    <div class="adm-card" style="margin-bottom:1.5rem">

      <!-- Tab bar -->
      <div style="display:flex;border-bottom:1px solid rgba(255,255,255,.08);padding:0 1.25rem;overflow-x:auto;gap:.25rem">
        <?php
        $tabs = [
          'details'   => ['fa-compass',   'Tour Details',  true],
          'itinerary' => ['fa-route',     'Itinerary ('.count($itineraryDays).' days)', (bool)$editing],
          'photos'    => ['fa-images',    'Photos ('.count($tourPhotos).')', (bool)$editing],
        ];
        foreach ($tabs as $tabKey => [$tabIcon,$tabLabel,$tabEnabled]): ?>
        <?php if ($tabEnabled): ?>
        <a href="tours.php?edit=<?= $editId ?>&tab=<?= $tabKey ?>"
           style="display:inline-flex;align-items:center;gap:.45rem;padding:.85rem .9rem;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab===$tabKey?'#10b981':'transparent' ?>;color:<?= $activeTab===$tabKey?'#10b981':'rgba(255,255,255,.45)' ?>;white-space:nowrap;transition:all .2s;margin-bottom:-1px">
          <i class="fas <?= $tabIcon ?>" style="font-size:.68rem"></i><?= $tabLabel ?>
        </a>
        <?php else: ?>
        <span style="display:inline-flex;align-items:center;gap:.45rem;padding:.85rem .9rem;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;color:rgba(255,255,255,.2);white-space:nowrap;cursor:not-allowed">
          <i class="fas <?= $tabIcon ?>" style="font-size:.68rem"></i><?= $tabLabel ?>
          <span style="font-size:.55rem;background:rgba(255,255,255,.06);padding:.1rem .4rem;border-radius:4px">Save first</span>
        </span>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Error alert -->
      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin:1rem 1.25rem 0">
        <i class="fas fa-exclamation-circle"></i>
        <ul style="list-style:none;margin:0">
          <?php foreach ($errors as $e_): ?><li>� <?= e($e_) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="adm-card-body">

        <!-- -- TAB: TOUR DETAILS -- -->
        <?php if ($activeTab === 'details'): ?>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">
          <input type="hidden" name="existing_image" value="<?= e($editing['image'] ?? '') ?>">

          <div class="f-grid-2">
            <div class="f-group">
              <label class="f-label">Tour Name <span>*</span></label>
              <input type="text" class="f-input" name="name" id="tour-name" required
                     value="<?= e($editing['name'] ?? '') ?>" placeholder="Serengeti Great Migration Safari">
            </div>
            <div class="f-group">
              <label class="f-label">Slug (URL)</label>
              <input type="text" class="f-input" name="slug" id="tour-slug"
                     value="<?= e($editing['slug'] ?? '') ?>" placeholder="auto-generated from name">
            </div>
            <div class="f-group">
              <label class="f-label">Destination <span>*</span></label>
              <select class="f-select" name="destination">
                <option value="">� Select �</option>
                <?php foreach ($destinations as $d): ?>
                <option value="<?= $d ?>" <?= ($editing['destination'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="f-group">
              <label class="f-label">Tour Type <span>*</span></label>
              <select class="f-select" name="tour_type">
                <option value="">� Select �</option>
                <?php foreach ($tourTypes as $tt): ?>
                <option value="<?= $tt ?>" <?= ($editing['tour_type'] ?? '') === $tt ? 'selected' : '' ?>><?= $tt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="f-group">
              <label class="f-label">Price (USD) <span>*</span></label>
              <input type="number" class="f-input" name="price" min="1" step="0.01"
                     value="<?= e($editing['price'] ?? '') ?>" placeholder="2500.00">
            </div>
            <div class="f-group">
              <label class="f-label">Duration <span>*</span></label>
              <input type="text" class="f-input" name="duration"
                     value="<?= e($editing['duration'] ?? '') ?>" placeholder="7 Days / 6 Nights">
            </div>
            <div class="f-group">
              <label class="f-label">Max Travelers</label>
              <input type="number" class="f-input" name="max_travelers" min="1" max="50"
                     value="<?= e($editing['max_travelers'] ?? 12) ?>">
            </div>
            <div class="f-group">
              <label class="f-label">Rating (0�5)</label>
              <input type="number" class="f-input" name="rating" min="0" max="5" step="0.1"
                     value="<?= e($editing['rating'] ?? '4.8') ?>">
            </div>
          </div>

          <div class="f-group">
            <label class="f-label">Description <span>*</span></label>
            <textarea class="f-textarea" name="description" rows="4"
                      placeholder="Full tour description..."><?= e($editing['description'] ?? '') ?></textarea>
          </div>

          <div class="f-group">
            <label class="f-label">Highlights <span class="f-hint" style="text-transform:none;letter-spacing:0;font-size:.7rem">(one per line)</span></label>
            <textarea class="f-textarea" name="highlights" rows="4"
                      placeholder="Witness the Great Migration&#10;Big Five game drives&#10;Expert local guides"><?= e(str_replace('|', "\n", $editing['highlights'] ?? '')) ?></textarea>
          </div>

          <!-- What's Included / Not Included -->
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:var(--radius-lg);padding:1.1rem;margin-bottom:1.1rem">
            <h4 style="font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.4);margin-bottom:.9rem;display:flex;align-items:center;gap:.5rem">
              <i class="fas fa-list-check" style="color:#10b981;font-size:.72rem"></i>
              What's Included / Not Included
            </h4>
            <div class="f-grid-2">
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label" style="display:flex;align-items:center;gap:.4rem">
                  <i class="fas fa-check-circle" style="color:#34d399;font-size:.7rem"></i>
                  What's Included <span class="f-hint" style="text-transform:none;letter-spacing:0">(one per line)</span>
                </label>
                <textarea class="f-textarea" name="included" rows="7"
                          placeholder="Park entrance fees&#10;Expert safari guide&#10;All accommodation&#10;All meals (full board)&#10;4WD game vehicle&#10;Airport transfers&#10;Flying doctors insurance"><?= e(str_replace('|', "\n", $editing['included'] ?? "Park entrance fees\nExpert safari guide\nAll accommodation\nAll meals (full board)\n4WD game vehicle\nAirport transfers\nFlying doctors insurance")) ?></textarea>
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label" style="display:flex;align-items:center;gap:.4rem">
                  <i class="fas fa-times-circle" style="color:#f87171;font-size:.7rem"></i>
                  Not Included <span class="f-hint" style="text-transform:none;letter-spacing:0">(one per line)</span>
                </label>
                <textarea class="f-textarea" name="not_included" rows="7"
                          placeholder="International flights&#10;Visa fees&#10;Travel insurance&#10;Personal expenses&#10;Gratuities&#10;Alcoholic beverages"><?= e(str_replace('|', "\n", $editing['not_included'] ?? "International flights\nVisa fees\nTravel insurance\nPersonal expenses\nGratuities\nAlcoholic beverages")) ?></textarea>
              </div>
            </div>
            <div class="f-hint" style="margin-top:.5rem"><i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>These appear on the tour detail page under "What's Included"</div>
          </div>

          <!-- Image section -->
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:var(--radius);padding:1.1rem;margin-bottom:1.1rem">
            <label class="f-label" style="margin-bottom:.75rem;display:block">Tour Cover Image</label>
            <div class="f-grid-2">
              <div>
                <label class="f-label" style="font-size:.58rem">Upload file</label>
                <input type="file" class="f-input" name="image_file" accept="image/jpeg,image/png,image/webp" id="img-file-input">
                <div class="f-hint">JPG/PNG/WebP � Max 8MB</div>
              </div>
              <div>
                <label class="f-label" style="font-size:.58rem">Or paste image URL</label>
                <input type="url" class="f-input" name="image_url" id="img-url-input"
                       value="<?= e($editing['image'] ?? '') ?>" placeholder="https://...">
              </div>
            </div>
            <?php if (!empty($editing['image'])): ?>
            <div style="margin-top:.75rem;display:flex;align-items:center;gap:.75rem">
              <img src="<?= e($editing['image']) ?>" alt="" class="img-small" style="width:90px;height:64px">
              <span style="font-size:.72rem;color:rgba(255,255,255,.3)">Current cover image</span>
            </div>
            <?php endif; ?>
          </div>

          <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.25rem">
            <label class="toggle">
              <input type="checkbox" name="featured" value="1" <?= ($editing['featured'] ?? 0) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
            <label style="font-family:'Montserrat',sans-serif;font-size:.78rem;color:rgba(255,255,255,.65);cursor:pointer">
              Featured tour <span style="font-size:.68rem;color:rgba(255,255,255,.3)">(shows on homepage)</span>
            </label>
          </div>

          <div style="display:flex;gap:.65rem;flex-wrap:wrap">
            <button type="submit" class="btn btn--primary btn--lg" style="display:inline-flex;align-items:center;gap:.4rem">
              <i class="fas fa-save" style="font-size:.72rem"></i>
              <?= $editing ? 'Save Changes' : 'Add Tour & Continue to Itinerary ?' ?>
            </button>
            <a href="tours.php" class="btn btn--outline btn--lg">Cancel</a>
          </div>
        </form>


        <!-- -- TAB: ITINERARY -- -->
        <?php elseif ($activeTab === 'itinerary' && $editing): ?>

        <!-- Add / Edit Day Form -->
        <?php $showDayForm = isset($_GET['add_day']) || $editingDay; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:.65rem">
          <div>
            <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1rem;color:#fff;font-weight:700">
              Day-by-Day Itinerary � <?= e($editing['name']) ?>
            </h3>
            <p style="font-size:.72rem;color:rgba(255,255,255,.35);font-family:'Montserrat',sans-serif;margin-top:.15rem"><?= count($itineraryDays) ?> day<?= count($itineraryDays)!==1?'s':'' ?> � Next: Day <?= $nextDay ?></p>
          </div>
          <a href="tours.php?edit=<?= $editId ?>&tab=itinerary&add_day=1"
             class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
            <i class="fas fa-plus" style="font-size:.6rem"></i> Add Day <?= $nextDay ?>
          </a>
        </div>

        <?php if ($showDayForm): ?>
        <div style="background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.2);border-radius:var(--radius-lg);padding:1.25rem;margin-bottom:1.25rem">
          <h4 style="font-family:'Nanum Myeongjo',serif;font-size:.95rem;color:#fff;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
            <i class="fas <?= $editingDay ? 'fa-pen' : 'fa-plus' ?>" style="color:#10b981;font-size:.78rem"></i>
            <?= $editingDay ? 'Edit Day '.$editingDay['day_number'] : 'Add Day '.$nextDay ?>
          </h4>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save_day">
            <input type="hidden" name="tour_id" value="<?= $editId ?>">
            <input type="hidden" name="day_id" value="<?= $editingDay ? $editingDay['id'] : 0 ?>">
            <input type="hidden" name="existing_hotel_image" value="<?= e($editingDay['hotel_image'] ?? '') ?>">

            <div class="f-grid-2">
              <div class="f-group">
                <label class="f-label">Day Number <span>*</span></label>
                <input type="number" class="f-input" name="day_number" min="1" max="99" required
                       value="<?= $editingDay ? $editingDay['day_number'] : $nextDay ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Day Title <span>*</span></label>
                <input type="text" class="f-input" name="title" required
                       placeholder="e.g. Arrival in Arusha & Serengeti Transfer"
                       value="<?= e($editingDay['title'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Departure Location</label>
                <input type="text" class="f-input" name="departure_location"
                       placeholder="e.g. Kilimanjaro Airport" value="<?= e($editingDay['departure_location'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Arrival Location</label>
                <input type="text" class="f-input" name="arrival_location"
                       placeholder="e.g. Serengeti National Park" value="<?= e($editingDay['arrival_location'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Distance</label>
                <input type="text" class="f-input" name="distance" placeholder="e.g. 325km" value="<?= e($editingDay['distance'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Travel Time</label>
                <input type="text" class="f-input" name="travel_time" placeholder="e.g. 5hrs 30mins" value="<?= e($editingDay['travel_time'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Hotel / Accommodation</label>
                <input type="text" class="f-input" name="accommodation" placeholder="e.g. Serengeti Sopa Lodge" value="<?= e($editingDay['accommodation'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Hotel Website URL</label>
                <input type="url" class="f-input" name="hotel_url" placeholder="https://..." value="<?= e($editingDay['hotel_url'] ?? '') ?>">
              </div>
            </div>
            <!-- Hotel image -->
            <div class="f-grid-2" style="margin-bottom:1rem">
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Hotel Image � Upload</label>
                <input type="file" class="f-input" name="hotel_image_file" accept="image/jpeg,image/png,image/webp">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Hotel Image � URL</label>
                <input type="url" class="f-input" name="hotel_image_url" placeholder="https://..." value="<?= e($editingDay['hotel_image'] ?? '') ?>">
              </div>
            </div>
            <?php if (!empty($editingDay['hotel_image'])): ?>
            <div style="margin-bottom:1rem">
              <img src="<?= e($editingDay['hotel_image']) ?>" alt="" class="img-small" style="width:120px;height:80px">
            </div>
            <?php endif; ?>
            <div class="f-group">
              <label class="f-label">Description</label>
              <textarea class="f-textarea" name="description" rows="3"
                        placeholder="Describe activities, sights, experiences..."><?= e($editingDay['description'] ?? '') ?></textarea>
            </div>
            <div class="f-grid-2">
              <div class="f-group">
                <label class="f-label">Meals Included <span class="f-hint" style="text-transform:none;letter-spacing:0">(comma separated)</span></label>
                <input type="text" class="f-input" name="meals" placeholder="Breakfast, Lunch, Dinner" value="<?= e($editingDay['meals'] ?? '') ?>">
              </div>
              <div class="f-group">
                <label class="f-label">Activities <span class="f-hint" style="text-transform:none;letter-spacing:0">(one per line)</span></label>
                <textarea class="f-textarea" name="highlights" rows="3"
                          placeholder="Morning game drive&#10;Sundowner at camp"><?= e(str_replace('|',"\n",$editingDay['highlights']??'')) ?></textarea>
              </div>
            </div>
            <div class="f-group">
              <label class="f-label">Important Notes</label>
              <textarea class="f-textarea" name="notes" rows="2"
                        placeholder="e.g. Bring sunscreen. Non-alcoholic drinks only."><?= e($editingDay['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:.65rem">
              <button type="submit" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
                <i class="fas fa-save" style="font-size:.6rem"></i> <?= $editingDay ? 'Update Day' : 'Save Day' ?>
              </button>
              <a href="tours.php?edit=<?= $editId ?>&tab=itinerary" class="btn btn--outline btn--sm">Cancel</a>
            </div>
          </form>
        </div>
        <?php endif; ?>

        <!-- Days list -->
        <?php if (empty($itineraryDays) && !$showDayForm): ?>
        <div style="text-align:center;padding:2.5rem;background:rgba(255,255,255,.02);border:2px dashed rgba(255,255,255,.08);border-radius:var(--radius-lg)">
          <i class="fas fa-route" style="font-size:2rem;color:rgba(255,255,255,.15);margin-bottom:.65rem;display:block"></i>
          <p style="color:rgba(255,255,255,.3);font-size:.85rem;margin-bottom:1rem">No itinerary yet. Add the first day to get started.</p>
          <a href="tours.php?edit=<?= $editId ?>&tab=itinerary&add_day=1" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
            <i class="fas fa-plus" style="font-size:.6rem"></i> Add Day 1
          </a>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <?php foreach ($itineraryDays as $idx => $day):
            [$clr,$clrBg] = dayClr((int)$day['day_number']);
            $hls = array_filter(explode('|',$day['highlights']??''));
            $isLast = $idx === count($itineraryDays)-1;
          ?>
          <div style="background:#1f2333;border:1px solid rgba(255,255,255,.08);border-radius:var(--radius-lg);padding:1rem 1.1rem;position:relative;transition:border-color .2s" onmouseover="this.style.borderColor='rgba(16,185,129,.2)'" onmouseout="this.style.borderColor='rgba(255,255,255,.08)'">
            <div style="display:flex;align-items:flex-start;gap:.85rem">
              <!-- Day badge -->
              <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;flex-shrink:0;background:<?= $clrBg ?>;color:<?= $clr ?>;border:1.5px solid <?= $clr ?>35">
                <?= str_pad($day['day_number'],2,'0',STR_PAD_LEFT) ?>
              </div>
              <div style="flex:1;min-width:0">
                <!-- Route line -->
                <?php if ($day['departure_location']||$day['arrival_location']): ?>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;margin-bottom:.4rem">
                  <?php if($day['departure_location']): ?><span style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:rgba(255,255,255,.4);background:rgba(255,255,255,.06);padding:.18rem .55rem;border-radius:4px"><?= e($day['departure_location']) ?></span><span style="color:rgba(255,255,255,.2);font-size:.7rem">?</span><?php endif; ?>
                  <?php if($day['distance']): ?><span style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.3)"><?= e($day['distance']) ?></span><?php endif; ?>
                  <?php if($day['travel_time']): ?><span style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.3)">� <?= e($day['travel_time']) ?></span><?php endif; ?>
                  <?php if($day['arrival_location']): ?><span style="font-size:.7rem;color:rgba(255,255,255,.2)">?</span><span style="font-family:'Montserrat',sans-serif;font-size:.62rem;color:<?= $clr ?>;background:<?= $clrBg ?>;padding:.18rem .55rem;border-radius:4px"><?= e($day['arrival_location']) ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <h4 style="font-family:'Nanum Myeongjo',serif;color:#fff;font-size:.95rem;font-weight:700;margin-bottom:.3rem"><?= e($day['title']) ?></h4>
                <!-- Meals + accommodation chips -->
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.4rem">
                  <?php if($day['meals']): foreach(array_filter(array_map('trim',explode(',',$day['meals']))) as $m): ?>
                  <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:600;padding:.15rem .5rem;border-radius:999px;background:rgba(245,158,11,.1);color:#fbbf24"><?= e($m) ?></span>
                  <?php endforeach; endif; ?>
                  <?php if($day['accommodation']): ?>
                  <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:600;padding:.15rem .5rem;border-radius:999px;background:rgba(96,165,250,.1);color:#93c5fd"><i class="fas fa-bed" style="font-size:.48rem;margin-right:.2rem"></i><?= e($day['accommodation']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if($day['description']): ?>
                <p style="font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.6;margin-bottom:.35rem"><?= e(mb_substr($day['description'],0,120)).(mb_strlen($day['description'])>120?'�':'') ?></p>
                <?php endif; ?>
                <?php if(!empty($hls)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:.2rem">
                  <?php foreach(array_slice($hls,0,4) as $hl): ?>
                  <span style="font-family:'Montserrat',sans-serif;font-size:.58rem;padding:.12rem .5rem;border-radius:999px;background:rgba(16,185,129,.08);color:#34d399"><?= e(trim($hl)) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
              <!-- Actions -->
              <div style="display:flex;gap:.3rem;flex-shrink:0">
                <?php if ($idx > 0): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="move_day">
                  <input type="hidden" name="tour_id" value="<?= $editId ?>">
                  <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                  <input type="hidden" name="direction" value="up">
                  <button type="submit" class="btn btn--sm" style="padding:.28rem .5rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);font-size:.6rem" title="Move up"><i class="fas fa-chevron-up"></i></button>
                </form>
                <?php endif; ?>
                <?php if (!$isLast): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="move_day">
                  <input type="hidden" name="tour_id" value="<?= $editId ?>">
                  <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                  <input type="hidden" name="direction" value="down">
                  <button type="submit" class="btn btn--sm" style="padding:.28rem .5rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);font-size:.6rem" title="Move down"><i class="fas fa-chevron-down"></i></button>
                </form>
                <?php endif; ?>
                <a href="tours.php?edit=<?= $editId ?>&tab=itinerary&edit_day=<?= $day['id'] ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.3rem .6rem">Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete Day <?= $day['day_number'] ?>?')">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete_day">
                  <input type="hidden" name="tour_id" value="<?= $editId ?>">
                  <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm" style="font-size:.62rem;padding:.3rem .6rem"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Add next day footer -->
        <div style="margin-top:1rem;padding:.85rem;background:rgba(255,255,255,.02);border:1.5px dashed rgba(16,185,129,.18);border-radius:var(--radius-lg);text-align:center">
          <a href="tours.php?edit=<?= $editId ?>&tab=itinerary&add_day=1" style="display:inline-flex;align-items:center;gap:.4rem;font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;color:#10b981;text-decoration:none">
            <i class="fas fa-plus" style="font-size:.6rem"></i> Add Day <?= $nextDay ?>
          </a>
        </div>
        <?php endif; ?>


        <!-- -- TAB: PHOTOS -- -->
        <?php elseif ($activeTab === 'photos' && $editing): ?>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:.65rem">
          <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1rem;color:#fff;font-weight:700">
            Tour Photos � <?= e($editing['name']) ?>
          </h3>
          <span style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.35)"><?= count($tourPhotos) ?> photo<?= count($tourPhotos)!==1?'s':'' ?> uploaded</span>
        </div>

        <!-- Upload form -->
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:var(--radius-lg);padding:1.1rem;margin-bottom:1.1rem">
          <h4 style="font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.4);margin-bottom:.85rem">Add Photos</h4>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="upload_photos">
            <input type="hidden" name="tour_id" value="<?= $editId ?>">
            <div class="f-grid-2">
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Upload files (multiple allowed)</label>
                <input type="file" class="f-input" name="photos[]" multiple accept="image/jpeg,image/png,image/webp">
                <div class="f-hint">JPG/PNG/WebP � Max 8MB each</div>
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Or paste image URLs <span class="f-hint" style="text-transform:none;letter-spacing:0">(one per line)</span></label>
                <textarea class="f-textarea" name="image_urls" rows="3" placeholder="https://example.com/photo1.jpg&#10;https://example.com/photo2.jpg" style="min-height:72px"></textarea>
              </div>
            </div>
            <button type="submit" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem;margin-top:.75rem">
              <i class="fas fa-upload" style="font-size:.6rem"></i> Upload Photos
            </button>
          </form>
        </div>

        <!-- Photos grid -->
        <?php if (empty($tourPhotos)): ?>
        <div style="text-align:center;padding:2rem;background:rgba(255,255,255,.02);border:2px dashed rgba(255,255,255,.07);border-radius:var(--radius-lg)">
          <i class="fas fa-images" style="font-size:2rem;color:rgba(255,255,255,.12);margin-bottom:.5rem;display:block"></i>
          <p style="color:rgba(255,255,255,.3);font-size:.82rem">No photos yet. Upload some above.</p>
        </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.65rem">
          <?php foreach ($tourPhotos as $i => $p): ?>
          <div style="background:#1f2333;border:1px solid rgba(255,255,255,.08);border-radius:var(--radius);overflow:hidden">
            <img src="<?= e($p['image']) ?>" alt="" style="width:100%;height:110px;object-fit:cover;display:block">
            <div style="padding:.5rem .65rem">
              <div style="font-size:.62rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif;margin-bottom:.3rem">
                Photo #<?= $i+1 ?><?= $i===0?' � <span style="color:#10b981">Cover</span>':'' ?>
              </div>
              <form method="POST" style="display:inline">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_photo">
                <input type="hidden" name="tour_id" value="<?= $editId ?>">
                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn--danger btn--sm" style="font-size:.6rem;padding:.25rem .55rem;width:100%" onclick="return confirm('Delete this photo?')">
                  <i class="fas fa-trash" style="font-size:.55rem"></i> Delete
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:.75rem;font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.25)">
          <i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>
          First photo is used as the hero cover. Photos appear in the mosaic gallery on the tour detail page.
        </div>
        <?php endif; ?>

        <?php endif; ?>

      </div><!-- /adm-card-body -->
    </div><!-- /adm-card -->
    <?php endif; ?>

    <!-- -- TOURS TABLE -- -->
    <?php if (!isset($_GET['edit'])): ?>
    <div class="adm-card">
      <div class="adm-card-header">
        <h2 class="adm-card-title">
          <?= $filterType !== '' ? e($filterType) : 'All Tours' ?>
          <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.3);font-weight:400">(<?= count($tours) ?>)</span>
        </h2>
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
          <?php if ($filterType === 'Great Migration Safari'): ?>
          <a href="tours.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.62rem">
            <i class="fas fa-times" style="font-size:.6rem"></i> Clear filter
          </a>
          <?php else: ?>
          <a href="tours.php?type=Great+Migration+Safari" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.62rem;border-color:rgba(160,94,34,.4);color:#c17a3a">
            <i class="fas fa-horse" style="font-size:.6rem"></i> Migration Packages
          </a>
          <?php endif; ?>
          <a href="tours.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
            <i class="fas fa-plus" style="font-size:.6rem"></i> Add New Tour
          </a>
        </div>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead><tr><th>Tour</th><th>Destination</th><th>Type</th><th>Duration</th><th>Price</th><th>Featured</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($tours as $t): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.65rem">
                  <?php if($t['image']): ?>
                  <img src="<?= e($t['image']) ?>" alt="" class="img-small">
                  <?php endif; ?>
                  <div>
                    <div style="font-weight:600;color:#fff;font-size:.82rem"><?= e($t['name']) ?></div>
                    <div style="font-size:.68rem;color:rgba(255,255,255,.28)"><?= e($t['slug']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-size:.8rem"><?= e($t['destination']) ?></td>
              <td><span class="badge" style="background:rgba(16,185,129,.1);color:#34d399"><?= e($t['tour_type']) ?></span></td>
              <td style="font-size:.8rem"><?= e($t['duration']) ?></td>
              <td style="font-weight:600;color:#10b981"><?= formatPrice($t['price']) ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle_featured">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn--sm" style="font-size:.62rem;padding:.28rem .65rem;background:<?= $t['featured']?'rgba(251,191,36,.15)':'rgba(255,255,255,.06)' ?>;color:<?= $t['featured']?'#fbbf24':'rgba(255,255,255,.4)' ?>;border:1px solid <?= $t['featured']?'rgba(251,191,36,.25)':'rgba(255,255,255,.1)' ?>">
                    <?= $t['featured'] ? '? Featured' : '? Feature' ?>
                  </button>
                </form>
              </td>
              <td style="white-space:nowrap">
                <a href="tours.php?edit=<?= $t['id'] ?>&tab=details" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="Edit tour details">
                  <i class="fas fa-pen"></i>
                </a>
                <a href="tours.php?edit=<?= $t['id'] ?>&tab=itinerary" class="btn btn--sm" style="font-size:.62rem;padding:.28rem .6rem;background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.2)" title="Manage itinerary">
                  <i class="fas fa-route"></i>
                </a>
                <a href="tours.php?edit=<?= $t['id'] ?>&tab=photos" class="btn btn--sm" style="font-size:.62rem;padding:.28rem .6rem;background:rgba(96,165,250,.1);color:#60a5fa;border:1px solid rgba(96,165,250,.2)" title="Manage photos">
                  <i class="fas fa-images"></i>
                </a>
                <a href="<?= url('tour-detail.php?slug='.e($t['slug'])) ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" target="_blank" title="Preview">
                  <i class="fas fa-eye"></i>
                </a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete tour permanently?')">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm" style="font-size:.62rem;padding:.28rem .6rem">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tours)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:rgba(255,255,255,.3)">No tours yet. Click "Add New Tour" above.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
/* Auto-generate slug from name */
const nameInput = document.getElementById('tour-name');
const slugInput = document.getElementById('tour-slug');
if (nameInput && slugInput && !slugInput.value) {
  nameInput.addEventListener('input', () => {
    slugInput.value = nameInput.value.toLowerCase().trim()
      .replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
  });
}
/* Clear URL field when file is chosen */
const fileInput = document.getElementById('img-file-input');
const urlInput  = document.getElementById('img-url-input');
if (fileInput && urlInput) {
  fileInput.addEventListener('change', () => { if (fileInput.files.length) urlInput.value = ''; });
}
</script>
</body>
</html>
