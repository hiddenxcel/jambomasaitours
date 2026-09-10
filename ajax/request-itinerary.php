<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once '../includes/mailer.php';
require_once '../includes/itinerary_pdf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

/* Bot check — timestamp set by JS when the modal opens. A real visitor takes
   at least ~2 seconds to read the form and type; bots that submit instantly
   (or skip JS and never set it) get silently "succeeded" without a real send.
   (A hidden field-name honeypot was tried first but browser autofill kept
   filling it for genuine visitors, since autocomplete="off" is unreliable.) */
$openedAt = (int)($_POST['im_ts'] ?? 0);
if (!$openedAt || (microtime(true) * 1000 - $openedAt) < 1500) {
    echo json_encode(['success' => true, 'message' => 'Itinerary sent! Check your email shortly.']);
    exit;
}

$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS itinerary_shares (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    token        VARCHAR(64) NOT NULL UNIQUE,
    tour_id      INT NOT NULL,
    name         VARCHAR(150) DEFAULT '',
    email        VARCHAR(190) DEFAULT '',
    whatsapp     VARCHAR(40)  DEFAULT '',
    travelers    INT DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    price_per_person  DECIMAL(10,2) DEFAULT 0,
    total_price       DECIMAL(10,2) DEFAULT 0,
    pdf_filename VARCHAR(64)  DEFAULT '',
    view_count   INT NOT NULL DEFAULT 0,
    last_viewed_at TIMESTAMP NULL,
    expires_at   TIMESTAMP NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tour (tour_id)
)");
foreach ([
    "ALTER TABLE itinerary_shares ADD COLUMN IF NOT EXISTS travelers INT DEFAULT 0",
    "ALTER TABLE itinerary_shares ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2) DEFAULT 0",
    "ALTER TABLE itinerary_shares ADD COLUMN IF NOT EXISTS price_per_person DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE itinerary_shares ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE itinerary_shares ADD COLUMN IF NOT EXISTS travel_date DATE DEFAULT NULL",
] as $sql) { try { $db->exec($sql); } catch (\Throwable $e) {} }

/* Simple rate limit: max 5 itinerary requests per IP per hour */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$db->exec("CREATE TABLE IF NOT EXISTS itinerary_share_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_time (ip, created_at)
)");
$rl = $db->prepare("SELECT COUNT(*) FROM itinerary_share_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)");
$rl->execute([$ip]);
if ((int)$rl->fetchColumn() >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later or contact us on WhatsApp.']);
    exit;
}

$tourId    = sanitizeInt($_POST['tour_id'] ?? 0, 1);
$name      = sanitizeInput($_POST['name']     ?? '');
$email     = sanitizeEmail($_POST['email']    ?? '');
$whatsapp  = sanitizeInput($_POST['whatsapp'] ?? '');
$travelers = sanitizeInt($_POST['travelers'] ?? 1, 1, 50);
$travelDateRaw = trim($_POST['travel_date'] ?? '');
$travelDate = ($travelDateRaw && validateDate($travelDateRaw)) ? $travelDateRaw : null;

$errors = [];
if (!$tourId)           $errors[] = 'Invalid tour.';
if (empty($name))       $errors[] = 'Full name is required.';
if (!$email)            $errors[] = 'A valid email address is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$tStmt = $db->prepare("SELECT * FROM tours WHERE id = ? LIMIT 1");
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();
if (!$tour) {
    echo json_encode(['success' => false, 'message' => 'Tour not found.']);
    exit;
}

$iStmt = $db->prepare("SELECT * FROM tour_itinerary WHERE tour_id = ? ORDER BY day_number ASC");
$iStmt->execute([$tourId]);
$days = $iStmt->fetchAll();

if (empty($days)) {
    echo json_encode(['success' => false, 'message' => 'This tour does not have a detailed itinerary yet. Please contact us on WhatsApp.']);
    exit;
}

try {
    $db->prepare("INSERT INTO itinerary_share_attempts (ip) VALUES (?)")->execute([$ip]);

    $pStmt = $db->prepare("SELECT * FROM tour_photos WHERE tour_id = ? ORDER BY sort_order ASC, id ASC");
    $pStmt->execute([$tourId]);
    $galleryPhotos = $pStmt->fetchAll();

    $imageLibrary = [];
    try {
        $imageLibrary = $db->query("SELECT id, image, caption, destination FROM tour_image_library WHERE caption != ''")->fetchAll();
    } catch (\Throwable $e) { /* table may not exist yet */ }

    $discountTiers = [];
    try {
        $tierStmt = $db->prepare("SELECT * FROM tour_discount_tiers WHERE tour_id = ? ORDER BY min_people ASC");
        $tierStmt->execute([$tourId]);
        $discountTiers = $tierStmt->fetchAll();
    } catch (\Throwable $e) { /* table may not exist yet */ }

    $addons = [];
    try {
        $addonStmt = $db->prepare("SELECT * FROM tour_addons WHERE tour_id = ? ORDER BY sort_order ASC, id ASC");
        $addonStmt->execute([$tourId]);
        $addons = $addonStmt->fetchAll();
    } catch (\Throwable $e) { /* table may not exist yet */ }

    $quote = computeGroupQuote($db, $tourId, (float)$tour['price'], $travelers);

    $pdfFilename = generateItineraryPdf($tour, $days, $galleryPhotos, ['name' => $name, 'travel_date' => $travelDate], $quote, $imageLibrary, $discountTiers, $addons);

    $token = bin2hex(random_bytes(16));
    $db->prepare("INSERT INTO itinerary_shares (token, tour_id, name, email, whatsapp, travelers, discount_percent, price_per_person, total_price, pdf_filename, travel_date, expires_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() + INTERVAL 30 DAY)")
       ->execute([$token, $tourId, $name, $email, $whatsapp, $travelers, $quote['discount_percent'], $quote['discounted_price'], $quote['total'], $pdfFilename, $travelDate]);

    $shareUrl = SITE_URL . '/i/' . $token;

    /* Email the lead a download link (not the PDF attachment) */
    $fname = e(explode(' ', trim($name))[0] ?: 'there');
    $tourNameE = e($tour['name']);
    $waLink = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . urlencode('Hi! I just downloaded the itinerary for ' . $tour['name'] . ' and would like to know more.');

    $content = <<<HTML
    <h2 style="color:#7d4817;margin:0 0 8px">Your Safari Itinerary is Ready! 🦁</h2>
    <p style="color:#555;margin:0 0 20px">Hi {$fname}, here's your detailed day-by-day itinerary for <strong>{$tourNameE}</strong>.</p>
    <div style="text-align:center;margin:24px 0">
      <a href="{$shareUrl}" class="btn">📋 View &amp; Download Itinerary</a>
    </div>
    <p style="color:#888;font-size:12px;text-align:center">This link stays active for 30 days — save it, no need to request it again.</p>
    <div style="text-align:center;margin:20px 0">
      <a href="{$waLink}" class="btn" style="background:#25D366">💬 Chat With Us on WhatsApp</a>
    </div>
    HTML;

    sendMail($email, $name, "Your Itinerary: {$tour['name']} — Jambo Masai Tours", emailWrap($content));

    /* Notify admin of the lead */
    $adminEmail = getSetting('admin_notify_email', SITE_EMAIL);
    if ($adminEmail) {
        $adminContent = "<h2 style=\"color:#7d4817\">📋 Itinerary Download Lead</h2>"
            . "<div class=\"info-row\"><span class=\"info-label\">Tour</span><span class=\"info-value\">{$tourNameE}</span></div>"
            . "<div class=\"info-row\"><span class=\"info-label\">Name</span><span class=\"info-value\">" . e($name) . "</span></div>"
            . "<div class=\"info-row\"><span class=\"info-label\">Email</span><span class=\"info-value\">" . e($email) . "</span></div>"
            . "<div class=\"info-row\"><span class=\"info-label\">WhatsApp</span><span class=\"info-value\">" . e($whatsapp ?: '—') . "</span></div>";
        sendMail($adminEmail, SITE_NAME . ' Admin', "📋 Itinerary Lead: {$tour['name']} — {$name}", emailWrap($adminContent));
    }

    unset($_SESSION[CSRF_TOKEN_NAME]);
    echo json_encode(['success' => true, 'message' => 'Itinerary sent! Check your email for the download link.', 'share_url' => $shareUrl]);
} catch (\Throwable $e) {
    error_log('request-itinerary error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again or contact us on WhatsApp.']);
}
