<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* CSRF */
if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

/* Collect & sanitize */
$name        = sanitizeInput($_POST['name']        ?? '');
$email       = sanitizeEmail($_POST['email']       ?? '');
$phone       = sanitizeInput($_POST['phone']       ?? '');
$destination = sanitizeInput($_POST['destination'] ?? '');
$travelDate  = sanitizeInput($_POST['travel_date'] ?? '');
$travelers   = sanitizeInt($_POST['travelers']     ?? 2, 1, 50);
$message     = sanitizeInput($_POST['message']     ?? '');

/* Validate */
$errors = [];
if (empty($name))    $errors[] = 'Full name is required.';
if (!$email)         $errors[] = 'A valid email address is required.';
if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

/* Build a full message for the contacts table */
$fullMessage = "BOOKING INQUIRY via Website Modal\n\n"
    . "Destination: " . ($destination ?: 'Not specified') . "\n"
    . "Travel Date: " . ($travelDate ?: 'Not specified') . "\n"
    . "Travelers: $travelers\n"
    . "Phone: " . ($phone ?: 'Not provided') . "\n\n"
    . "Message:\n" . ($message ?: 'No additional message.');

try {
    $db = getDB();
    $db->prepare("
        INSERT INTO contacts (name, email, phone, subject, message, status, created_at)
        VALUES (:name, :email, :phone, :subject, :message, 'unread', NOW())
    ")->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':phone'   => $phone,
        ':subject' => 'Safari Booking Inquiry' . ($destination ? ': ' . $destination : ''),
        ':message' => $fullMessage,
    ]);
    unset($_SESSION[CSRF_TOKEN_NAME]);

    /* Send admin notification */
    if (getSetting('notify_on_booking', '1') === '1') {
        emailModalBookingAdmin([
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'destination' => $destination,
            'travel_date' => $travelDate ?? '',
            'message'     => $message,
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Your booking request has been received! We\'ll contact you within 24 hours.']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again or contact us on WhatsApp.']);
}
