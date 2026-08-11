<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Auto-detect HTTPS so the cookie is "secure" on production but still works on localhost/.local.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com https://embed.tawk.to https://*.tawk.to; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://embed.tawk.to https://*.tawk.to; img-src 'self' data: blob: https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://*.tawk.to; frame-src https://www.google.com https://*.tawk.to; connect-src 'self' https://*.tawk.to wss://*.tawk.to; object-src 'none'; base-uri 'self'");
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function asset(string $path): string {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

function url(string $path = ''): string {
    return SITE_URL . '/' . ltrim($path, '/');
}

function formatPrice(float $price): string {
    return '$' . number_format($price, 0, '.', ',');
}

function formatDate(string $date): string {
    return date('F j, Y', strtotime($date));
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function truncate(string $text, int $length = 120): string {
    if (strlen($text) <= $length) return $text;
    return rtrim(substr($text, 0, $length)) . '...';
}

function starRating(float $rating): string {
    $html = '<span class="stars" aria-label="Rating: ' . $rating . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<span class="star star--full">&#9733;</span>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<span class="star star--half">&#9733;</span>';
        } else {
            $html .= '<span class="star star--empty">&#9733;</span>';
        }
    }
    $html .= '</span>';
    return $html;
}

function getFlashMessage(): array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return [];
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Ondoa alama za checkbox zilizoandikwa kwa mkono ndani ya matini
 * (☐ ☑ ☒ ❏ ▢ □ na "[ ]" / "[x]") mwanzoni mwa kila kipengele cha orodha.
 *
 * Alama hizi huingia pale makala inapoandikwa/inapobandikwa kutoka nje. Ni
 * herufi za kawaida, si checkbox — CSS haiwezi kuzificha. Zikiachwa,
 * `.checklist` hupata alama mbili: ile ya CSS na hii ya matini. Kuziondoa
 * hapa kunahakikisha kila post — ya zamani na mpya — inatoka safi bila
 * kuhariri content iliyopo kwenye database.
 */
function stripChecklistGlyphs(string $html): string {
    return preg_replace(
        '/(<(?:li|label|p)\b[^>]*>)\s*(?:&nbsp;|\s)*(?:[\x{2610}-\x{2612}\x{274F}\x{25A2}\x{25A1}\x{2751}\x{2752}]|\[\s*[xX\x{2713}]?\s*\])\s*/u',
        '$1',
        $html
    ) ?? $html;
}

/**
 * Render blog post content safely.
 * - If content has HTML block elements → output as-is (admin entered HTML via toolbar).
 * - If content is plain text → auto-convert double newlines to <p> and single to <br>.
 */
function blogContent(string $text): string {
    if (empty($text)) return '';
    $text = trim($text);
    // Already has block-level HTML — render as-is
    if (preg_match('/<(p|h[1-6]|ul|ol|li|blockquote|div|table|hr)\b/i', $text)) {
        return stripChecklistGlyphs($text);
    }
    // Plain text — build paragraphs from double newlines
    $paragraphs = preg_split('/\n{2,}/', $text);
    $out = '';
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') continue;
        $out .= '<p>' . inlineSafeHtml($para) . '</p>' . "\n";
    }
    return $out ?: '<p>' . inlineSafeHtml($text) . '</p>';
}

/**
 * Escape a text fragment for output, but restore a whitelist of simple inline
 * formatting tags (as inserted by the admin editor toolbar: bold, italic,
 * underline, strikethrough, code, links, images) so they still render —
 * everything else stays escaped as literal text.
 */
function inlineSafeHtml(string $text): string {
    $html = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false));
    $html = preg_replace('#&lt;(/?(?:strong|em|b|i|u|s|code))&gt;#i', '<$1>', $html);
    $html = preg_replace('#&lt;a href=&quot;(.*?)&quot;&gt;#i', '<a href="$1">', $html);
    $html = preg_replace('#&lt;/a&gt;#i', '</a>', $html);
    $html = preg_replace_callback('#&lt;img\s+(.*?)&gt;#i', function($m) {
        return '<img ' . str_replace('&quot;', '"', $m[1]) . '>';
    }, $html);
    return $html;
}

function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $db = getDB();
            $rows = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (\Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}
