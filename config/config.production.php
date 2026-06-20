<?php
/* =====================================================================
   CONFIG YA PRODUCTION — Jambo Masai Tours (jambomasaitours.com)
   ---------------------------------------------------------------------
   JINSI YA KUTUMIA kwenye server (cPanel):
     1. Pakia faili hii kwenye  config/  ya server.
     2. Ibadilishe jina kuwa    config.php   (futa ya zamani kwanza kama ipo).
     3. Jaza sehemu 3 za DB hapa chini (DB_NAME, DB_USER, DB_PASS) kutoka
        cPanel > MySQL Databases.
     4. (Hiari) Weka DEEPSEEK_API_KEY kama unatumia chatbot.
   FAILI HII HAIENDI GitHub (config/config.php ime-ignore). Kila server yake.
   ===================================================================== */

/* ─── 1. DATABASE (JAZA HIZI 3 KUTOKA cPanel) ─── */
define('DB_HOST',    'localhost');                 // kawaida 'localhost' kwenye cPanel
define('DB_NAME',    'jambomasait_XXXXX');         // <-- BADILI: jina kamili la DB ya cPanel
define('DB_USER',    'jambomasait_XXXXX');         // <-- BADILI: user wa DB ya cPanel
define('DB_PASS',    'WEKA_PASSWORD_HAPA');        // <-- BADILI: nenosiri la huyo user
define('DB_CHARSET', 'utf8mb4');

/* ─── 2. SITE — production ─── */
// SITE_URL inajitambua yenyewe (https + domain halisi). Haina haja ya kubadili
// kati ya www / non-www / http / https — .htaccess inashughulikia canonical.
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['SERVER_PORT'] ?? '') === '443')
         || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$__host   = $_SERVER['HTTP_HOST'] ?? 'jambomasaitours.com';
define('SITE_URL', $__scheme . '://' . $__host);

define('SITE_NAME',  'Jambo Masai Tours');
define('SITE_EMAIL', 'info@jambomasaitours.com');
define('SITE_PHONE', '+255 659 667 271');
define('WHATSAPP_NUMBER', '255659667271');

define('SESSION_NAME',      'jmt_session');
define('CSRF_TOKEN_NAME',   '_csrf_token');

/* ─── 3. AI Chatbot (DeepSeek) ───
   Weka API key yako na AI_BOT_ENABLED=true ili kuiwasha. Kama key tupu au
   AI_BOT_ENABLED=false, bot inajibu "offline / tumia WhatsApp" badala ya kupiga API. */
define('AI_BOT_ENABLED',    true);
define('DEEPSEEK_API_KEY',  '');   // <-- WEKA DeepSeek API key yako hapa
define('DEEPSEEK_API_URL',  'https://api.deepseek.com/chat/completions');
define('DEEPSEEK_MODEL',    'deepseek-chat');

define('UPLOADS_PATH',      __DIR__ . '/../uploads/');
define('UPLOADS_URL',       SITE_URL . '/uploads/');

/* ─── 4. Timezone (Tanzania) ─── */
date_default_timezone_set('Africa/Dar_es_Salaam');

/* ─── 5. Hero & placeholder images (Unsplash) ─── */
define('IMG_HERO',         'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1920&q=85&auto=format&fit=crop');
define('IMG_SERENGETI',    'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=800&q=80&auto=format&fit=crop');
define('IMG_NGORONGORO',   'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=800&q=80&auto=format&fit=crop');
define('IMG_KILIMANJARO',  'https://images.unsplash.com/photo-1521150932951-303a95503ed3?w=800&q=80&auto=format&fit=crop');
define('IMG_ZANZIBAR',     'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=800&q=80&auto=format&fit=crop');
define('IMG_MAASAI',       SITE_URL . '/uploads/about-small.jpeg');
define('IMG_TARANGIRE',    'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=800&q=80&auto=format&fit=crop');
define('IMG_ABOUT',        SITE_URL . '/uploads/about-main.jpg');
