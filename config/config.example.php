<?php
// NAKALA YA MFANO — Nakili faili hii kuwa config.php kisha jaza taarifa zako.
// Copy this file to config.php and fill in your own values.

define('DB_HOST',    'localhost');
define('DB_NAME',    'jambo_masai_tours');
define('DB_USER',    'root');
define('DB_PASS',    '');               // weka password yako hapa kwenye server halisi
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'http://localhost/jambomasaitours.com');
define('SITE_NAME',  'Jambo Masai Tours');
define('SITE_EMAIL', 'info@jambomasaitours.com');
define('SITE_PHONE', '+255 659 667 271');
define('WHATSAPP_NUMBER', '255659667271');

define('SESSION_NAME',      'jmt_session');
define('CSRF_TOKEN_NAME',   '_csrf_token');
define('UPLOADS_PATH',      __DIR__ . '/../uploads/');
define('UPLOADS_URL',       SITE_URL . '/uploads/');

// Hero & placeholder images (Unsplash)
define('IMG_HERO',         'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1920&q=85&auto=format&fit=crop');
define('IMG_SERENGETI',    'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=800&q=80&auto=format&fit=crop');
define('IMG_NGORONGORO',   'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=800&q=80&auto=format&fit=crop');
define('IMG_KILIMANJARO',  'https://images.unsplash.com/photo-1521150932951-303a95503ed3?w=800&q=80&auto=format&fit=crop');
define('IMG_ZANZIBAR',     'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=800&q=80&auto=format&fit=crop');
define('IMG_MAASAI',       SITE_URL . '/uploads/about-small.jpeg');
define('IMG_TARANGIRE',    'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=800&q=80&auto=format&fit=crop');
define('IMG_ABOUT',        SITE_URL . '/uploads/about-main.jpg');
