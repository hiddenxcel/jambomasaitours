<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

/* Mwongozo wa kazi za kila siku: [id, ikoni, kichwa, maelezo mafupi, hatua[], kiungo cha ukurasa] */
$guides = [
  ['tour', 'fa-compass', 'Add or edit a safari tour', 'Create a tour page customers can read and book.', [
      'Open <a href="tours.php">Safari Tours</a> and press <strong>Add new tour</strong> (or the pencil icon to edit).',
      'Fill in the name, destination, type, <strong>price in USD</strong>, duration, description and cover image. Press <strong>Save</strong>.',
      'Open the <strong>Itinerary</strong> tab and add one entry for each day.',
      'Open the <strong>Photos</strong> tab and add 4 or more photos.',
      'Optional: <strong>Group Pricing</strong> for discounts and <strong>Add-ons</strong> for extras.',
      'Press <strong>Preview</strong> to check the live page.',
    ], 'tours.php?edit=0', 'Add a tour'],
  ['blog', 'fa-pen-nib', 'Publish a blog post', 'Articles bring new customers from Google.', [
      'Open <a href="blog.php">Blog Posts</a> and press <strong>New post</strong>.',
      'Write the title, the short excerpt and the article. Use H2 headings and bullet lists from the toolbar.',
      'Fill in <strong>SEO Title</strong>, <strong>Meta Description</strong> and <strong>Keywords</strong>. Aim for the green ticks in the Post checklist.',
      'Add a featured image, choose a category and set the status to <strong>Published</strong>.',
      'Press the button, then <strong>Preview</strong> to read the live post.',
    ], 'blog.php?edit=0', 'Write a post'],
  ['booking', 'fa-calendar-check', 'Handle a booking request', 'Answer customers quickly and keep records tidy.', [
      'A request arrives in <a href="bookings.php">Bookings</a> as <strong>Pending</strong>, and you get an email if notifications are on.',
      'Contact the customer by email or WhatsApp from the buttons in the row.',
      'After agreeing the details, change the status to <strong>Confirmed</strong> and press Save. The customer is <strong>not</strong> emailed automatically.',
      'After the trip, mark it <strong>Completed</strong>. Mark it <strong>Cancelled</strong> if it will not go ahead.',
    ], 'bookings.php', 'Open bookings'],
  ['message', 'fa-envelope', 'Reply to a message', 'Enquiries from the contact forms.', [
      'Open <a href="contacts.php">Messages</a>. Unread messages are highlighted.',
      'Press <strong>Read more</strong> for the full text, then <strong>Reply</strong> to open your email.',
      'Press <strong>Mark read</strong> when you are done.',
    ], 'contacts.php', 'Open messages'],
  ['review', 'fa-star', 'Add a guest review', 'Show real feedback on the website.', [
      'Open <a href="testimonials.php">Guest Reviews</a> and press <strong>New review</strong>.',
      'Copy the review text exactly, add the name, country, rating and where it came from.',
      'Keep <strong>Approved</strong> on to show it on the website.',
    ], 'testimonials.php?edit=0', 'Add a review'],
  ['photos', 'fa-images', 'Upload photos', 'Gallery photos, tour photos and the AI image library.', [
      '<a href="gallery.php">Gallery</a>: photos shown on the Gallery page, grouped by category.',
      'Tour photos: inside each tour (Photos tab).',
      '<a href="image-library.php">Image Library</a>: photos with captions that the itinerary PDF uses. Always write a caption.',
      'Use sharp landscape photos, JPG, PNG or WebP, up to 8 MB.',
    ], 'gallery.php', 'Open gallery'],
  ['home', 'fa-house', 'Change the homepage banner', 'The first thing visitors see.', [
      '<a href="hero-slides.php">Hero Slides</a>: headline, subtitle and buttons.',
      '<a href="hero-settings.php">Hero Video</a>: the background video and fallback picture.',
      '<a href="page-intros.php">Page Intros</a>: banner pictures and titles on other pages.',
    ], 'hero-slides.php', 'Open hero slides'],
  ['settings', 'fa-gear', 'Update the logo, email or contacts', 'Site-wide options.', [
      'Open <a href="settings.php">Settings</a> and jump to the section with the menu at the top.',
      'Change what you need, then press <strong>Save all settings</strong> once at the bottom.',
      'After changing email details, use <strong>Send test email</strong> to check it works.',
    ], 'settings.php', 'Open settings'],
];

$faq = [
  ['Do my changes go live straight away?', 'Yes. Everything you save in the admin is shown on the live website immediately. Use Preview before you publish important changes.'],
  ['Why is the tour price in US dollars?', 'USD is the real, stored price. The website can show visitors other currencies, but those are only conversions for viewing. Always enter the USD price, numbers only.'],
  ['How do I make a post or tour appear higher on Google?', 'Write clear titles that include the main words people search for, keep the meta description around 150 characters, add real photos, link to your other pages, and publish regularly. The Post checklist in the blog editor guides you.'],
  ['I deleted something by mistake. Can I get it back?', 'Deleting is permanent. Ask your developer to restore it from a backup as soon as possible. When in doubt, hide an item (switch it off or save it as a draft) instead of deleting.'],
  ['The page looks old after I changed something.', 'Press Ctrl and F5 together to refresh without the saved copy. If it still looks old, wait a few minutes and try again.'],
  ['Who can I ask for help?', 'Contact your web developer. Tell them the page name, what you tried and any red message you saw.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help &amp; Guide | Jambo Masai Tours Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <style>
    .hg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;margin-bottom:1.5rem}
    .hg-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.2rem 1.25rem;display:flex;flex-direction:column;scroll-margin-top:80px}
    .hg-card h2{font-size:1rem;font-weight:650;color:#fff;margin:0 0 .25rem;display:flex;align-items:center;gap:.6rem}
    .hg-card h2 i{width:30px;height:30px;border-radius:9px;background:rgba(193,122,58,.16);color:var(--primary-light);display:inline-flex;align-items:center;justify-content:center;font-size:.8rem}
    .hg-card p.sub{color:var(--text-dim);font-size:.82rem;margin:0 0 .9rem}
    .hg-card .adm-steps{margin-bottom:1rem}
    .hg-card .btn{margin-top:auto;align-self:flex-start}
    details.hg-faq{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:.85rem 1.1rem;margin-bottom:.6rem}
    details.hg-faq summary{cursor:pointer;color:#fff;font-weight:600;font-size:.9rem}
    details.hg-faq p{margin:.7rem 0 0;color:var(--text-muted);font-size:.86rem;line-height:1.6}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">
    <?php adm_pagehead('Help & Guide', 'Step-by-step instructions for the jobs you do most often.', ['icon' => 'fa-circle-question']); ?>

    <?php adm_tip('<strong>Tip:</strong> every page also has a "How this page works" box at the top. Press <em>Hide</em> once you know it; it stays hidden on that page until you press <em>Show</em>.', 'brand'); ?>

    <div class="hg-grid">
      <?php foreach ($guides as $g): ?>
      <section class="hg-card" id="<?= e($g[0]) ?>">
        <h2><i class="fas <?= e($g[1]) ?>"></i><?= e($g[2]) ?></h2>
        <p class="sub"><?= e($g[3]) ?></p>
        <ol class="adm-steps"><?php foreach ($g[4] as $step): ?><li><?= $step ?></li><?php endforeach; ?></ol>
        <a class="btn-adm btn-adm-outline btn-adm-sm" href="<?= e($g[5]) ?>"><?= e($g[6]) ?> <i class="fas fa-arrow-right"></i></a>
      </section>
      <?php endforeach; ?>
    </div>

    <h2 style="font-size:1.15rem;font-weight:650;color:#fff;margin:0 0 .8rem">Common questions</h2>
    <?php foreach ($faq as $f): ?>
    <details class="hg-faq"><summary><?= e($f[0]) ?></summary><p><?= e($f[1]) ?></p></details>
    <?php endforeach; ?>
  </main>
</div>
</body>
</html>
