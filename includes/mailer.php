<?php
/**
 * Jambo Masai Tours — Email Mailer Helper
 * Uses PHPMailer with SMTP settings from site_settings table.
 * Falls back to PHP mail() if SMTP not configured.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

$_mailerVendor = __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
if (file_exists($_mailerVendor)) {
    require_once $_mailerVendor;
    require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
}

/**
 * Send an email using SMTP (PHPMailer) or PHP mail() fallback.
 *
 * @param string $toEmail  Recipient email
 * @param string $toName   Recipient name
 * @param string $subject  Email subject
 * @param string $body     HTML body
 * @param string $altBody  Plain-text fallback
 * @return bool
 */
function sendMail(string $toEmail, string $toName, string $subject, string $body, string $altBody = ''): bool {
    $host      = getSetting('smtp_host');
    $port      = (int)(getSetting('smtp_port', '587') ?: 587);
    $user      = getSetting('smtp_user');
    $pass      = getSetting('smtp_pass');
    $fromEmail = getSetting('smtp_from_email', SITE_EMAIL);
    $fromName  = getSetting('smtp_from_name',  SITE_NAME);

    /* Use PHPMailer if available and SMTP configured */
    if (class_exists(PHPMailer::class) && !empty($host) && !empty($user)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($fromEmail, $fromName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);

            return $mail->send();
        } catch (\Throwable $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    /* Fallback: PHP mail() */
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($toEmail, $subject, $body, $headers);
}

/* ─── Email Templates ─────────────────────────────────────────── */

/**
 * Wrap content in branded HTML email shell.
 */
function emailWrap(string $content, string $preheader = ''): string {
    $siteName = SITE_NAME;
    $siteUrl  = SITE_URL;
    $phone    = SITE_PHONE;
    $email    = SITE_EMAIL;
    $year     = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$siteName}</title>
<style>
  body{margin:0;padding:0;background:#f4f4f4;font-family:'Segoe UI',Arial,sans-serif;color:#333}
  .wrap{max-width:600px;margin:0 auto;background:#fff}
  .header{background:#0a1a0f;padding:28px 32px;text-align:center}
  .header h1{margin:0;color:#10b981;font-size:22px;letter-spacing:1px}
  .header p{margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:2px;text-transform:uppercase}
  .body{padding:32px}
  .footer{background:#0a1a0f;padding:20px 32px;text-align:center}
  .footer p{margin:4px 0;color:rgba(255,255,255,.45);font-size:11px}
  .footer a{color:#10b981;text-decoration:none}
  .divider{border:none;border-top:1px solid #e8e8e8;margin:24px 0}
  .btn{display:inline-block;background:#10b981;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;margin:8px 0}
  .info-row{display:flex;padding:10px 0;border-bottom:1px solid #f0f0f0}
  .info-label{color:#888;font-size:13px;width:140px;flex-shrink:0}
  .info-value{color:#333;font-size:13px;font-weight:600}
  .badge{display:inline-block;background:#e8faf4;color:#059669;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
  @media(max-width:600px){.body{padding:20px}.info-row{flex-direction:column}.info-label{margin-bottom:2px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>{$siteName}</h1>
    <p>Tanzania Safari Experts</p>
  </div>
  <div class="body">
    {$content}
  </div>
  <div class="footer">
    <p>{$siteName} · Arusha, Tanzania</p>
    <p><a href="tel:{$phone}">{$phone}</a> · <a href="mailto:{$email}">{$email}</a></p>
    <p><a href="{$siteUrl}">{$siteUrl}</a></p>
    <p style="margin-top:12px;font-size:10px;color:rgba(255,255,255,.25)">&copy; {$year} {$siteName}. All rights reserved.</p>
  </div>
</div>
</body>
</html>
HTML;
}

/**
 * Email to ADMIN when a new booking arrives.
 */
function emailBookingAdmin(array $data): bool {
    $adminEmail = getSetting('admin_notify_email', SITE_EMAIL);
    if (empty($adminEmail)) return false;

    $tourName   = e($data['tour_name']  ?? 'N/A');
    $name       = e($data['first_name'] ?? '') . ' ' . e($data['last_name'] ?? '');
    $email      = e($data['email']      ?? '');
    $phone      = e($data['phone']      ?? 'N/A');
    $country    = e($data['country']    ?? 'N/A');
    $date       = e($data['travel_date']     ?? 'N/A');
    $travelers  = e($data['travelers']       ?? '1');
    $requests   = e($data['special_requests']?? '—');
    $adminUrl   = SITE_URL . '/admin/bookings.php';
    $ts         = date('d M Y H:i');

    $content = <<<HTML
<h2 style="color:#059669;margin:0 0 4px">🦁 New Booking Request</h2>
<p style="color:#888;margin:0 0 24px;font-size:13px">Received: {$ts}</p>

<div style="background:#f8fffe;border-left:4px solid #10b981;padding:16px;border-radius:0 8px 8px 0;margin-bottom:24px">
  <strong style="font-size:15px">{$tourName}</strong>
</div>

<div>
  <div class="info-row"><span class="info-label">Client</span><span class="info-value">{$name}</span></div>
  <div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:{$email}">{$email}</a></span></div>
  <div class="info-row"><span class="info-label">Phone / WhatsApp</span><span class="info-value">{$phone}</span></div>
  <div class="info-row"><span class="info-label">Country</span><span class="info-value">{$country}</span></div>
  <div class="info-row"><span class="info-label">Travel Date</span><span class="info-value">{$date}</span></div>
  <div class="info-row"><span class="info-label">Travelers</span><span class="info-value">{$travelers}</span></div>
  <div class="info-row" style="border:none"><span class="info-label">Special Requests</span><span class="info-value">{$requests}</span></div>
</div>

<hr class="divider">
<p style="text-align:center">
  <a href="{$adminUrl}" class="btn">View in Admin Panel →</a>
</p>
<p style="text-align:center;color:#aaa;font-size:12px">Reply to {$email} or WhatsApp {$phone} to confirm this booking.</p>
HTML;

    $subject = "🦁 New Booking: {$tourName} — {$name}";
    return sendMail($adminEmail, SITE_NAME . ' Admin', $subject, emailWrap($content));
}

/**
 * Email to CUSTOMER confirming their booking was received.
 */
function emailBookingCustomer(array $data): bool {
    $email    = $data['email'] ?? '';
    if (empty($email)) return false;

    $name     = e($data['first_name'] ?? 'Traveller') . ' ' . e($data['last_name'] ?? '');
    $fname    = e($data['first_name'] ?? 'Traveller');
    $tourName = e($data['tour_name']  ?? 'Safari Tour');
    $date     = e($data['travel_date']     ?? 'TBD');
    $travelers= e($data['travelers']       ?? '1');
    $toursUrl = SITE_URL . '/tours.php';
    $waLink   = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . urlencode('Hi! I\'d like to confirm my booking for ' . ($data['tour_name'] ?? 'a safari tour') . '.');

    $content = <<<HTML
<h2 style="color:#059669;margin:0 0 8px">Booking Request Received! 🎉</h2>
<p style="color:#555;margin:0 0 24px">Hi {$fname}, thank you for choosing <strong>Jambo Masai Tours</strong>. We've received your booking request and our team will contact you within <strong style="color:#059669">24 hours</strong> to confirm your itinerary.</p>

<div style="background:#f8fffe;border:1px solid #d1fae5;border-radius:10px;padding:20px;margin-bottom:24px">
  <h3 style="margin:0 0 12px;color:#333;font-size:15px">📋 Your Booking Summary</h3>
  <div class="info-row"><span class="info-label">Safari Tour</span><span class="info-value">{$tourName}</span></div>
  <div class="info-row"><span class="info-label">Travel Date</span><span class="info-value">{$date}</span></div>
  <div class="info-row" style="border:none"><span class="info-label">Travelers</span><span class="info-value">{$travelers}</span></div>
</div>

<p style="color:#555;font-size:14px">While you wait, feel free to explore more tours or chat with us on WhatsApp for immediate assistance.</p>

<div style="text-align:center;margin:24px 0">
  <a href="{$waLink}" class="btn" style="background:#25D366;margin-right:8px">💬 WhatsApp Us</a>
  <a href="{$toursUrl}" class="btn">🦁 Explore Tours</a>
</div>

<hr class="divider">
<p style="color:#888;font-size:12px;text-align:center">This is an automated confirmation. No payment is required at this stage. Our specialists will be in touch shortly.</p>
HTML;

    $subject = "Booking Confirmed: {$tourName} — Jambo Masai Tours";
    return sendMail($email, trim($name), $subject, emailWrap($content));
}

/**
 * Email to ADMIN when a contact/enquiry form is submitted.
 */
function emailContactAdmin(array $data): bool {
    $adminEmail = getSetting('admin_notify_email', SITE_EMAIL);
    if (empty($adminEmail)) return false;

    $name    = e($data['name']    ?? 'Unknown');
    $email   = e($data['email']   ?? '');
    $phone   = e($data['phone']   ?? '—');
    $subject = e($data['subject'] ?? 'General Enquiry');
    $message = nl2br(e($data['message'] ?? ''));
    $ts      = date('d M Y H:i');
    $adminUrl= SITE_URL . '/admin/contacts.php';

    $content = <<<HTML
<h2 style="color:#059669;margin:0 0 4px">📩 New Contact Message</h2>
<p style="color:#888;margin:0 0 24px;font-size:13px">Received: {$ts}</p>

<div style="background:#f8fffe;border-left:4px solid #10b981;padding:12px 16px;border-radius:0 8px 8px 0;margin-bottom:24px">
  <strong style="font-size:14px">Subject: {$subject}</strong>
</div>

<div>
  <div class="info-row"><span class="info-label">Name</span><span class="info-value">{$name}</span></div>
  <div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:{$email}">{$email}</a></span></div>
  <div class="info-row" style="border:none"><span class="info-label">Phone</span><span class="info-value">{$phone}</span></div>
</div>

<hr class="divider">
<div style="background:#f9f9f9;padding:16px;border-radius:8px;font-size:14px;line-height:1.7;color:#444">
  {$message}
</div>
<hr class="divider">

<p style="text-align:center">
  <a href="{$adminUrl}" class="btn">View in Admin →</a>
</p>
HTML;

    $emailSubject = "📩 New Enquiry: {$subject} — {$name}";
    return sendMail($adminEmail, SITE_NAME . ' Admin', $emailSubject, emailWrap($content));
}

/**
 * Email to ADMIN for modal booking enquiry.
 */
function emailModalBookingAdmin(array $data): bool {
    $adminEmail = getSetting('admin_notify_email', SITE_EMAIL);
    if (empty($adminEmail)) return false;

    $name        = e($data['name']        ?? 'Unknown');
    $email       = e($data['email']       ?? '');
    $phone       = e($data['phone']       ?? '—');
    $destination = e($data['destination'] ?? '—');
    $travelDate  = e($data['travel_date'] ?? '—');
    $message     = nl2br(e($data['message'] ?? '—'));
    $ts          = date('d M Y H:i');
    $adminUrl    = SITE_URL . '/admin/contacts.php';

    $content = <<<HTML
<h2 style="color:#059669;margin:0 0 4px">🦁 New Safari Booking Enquiry</h2>
<p style="color:#888;margin:0 0 24px;font-size:13px">Via website booking modal · {$ts}</p>

<div>
  <div class="info-row"><span class="info-label">Name</span><span class="info-value">{$name}</span></div>
  <div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:{$email}">{$email}</a></span></div>
  <div class="info-row"><span class="info-label">Phone</span><span class="info-value">{$phone}</span></div>
  <div class="info-row"><span class="info-label">Destination</span><span class="info-value">{$destination}</span></div>
  <div class="info-row"><span class="info-label">Travel Date</span><span class="info-value">{$travelDate}</span></div>
  <div class="info-row" style="border:none"><span class="info-label">Message</span><span class="info-value">{$message}</span></div>
</div>
<hr class="divider">
<p style="text-align:center">
  <a href="{$adminUrl}" class="btn">View in Admin →</a>
</p>
HTML;

    $subject = "🦁 New Booking Enquiry: {$destination} — {$name}";
    return sendMail($adminEmail, SITE_NAME . ' Admin', $subject, emailWrap($content));
}
