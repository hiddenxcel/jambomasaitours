<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';

$db = getDB();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
  if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('Invalid token.'); }
  $id = sanitizeInt($_POST['contact_id'] ?? 0, 1, PHP_INT_MAX);
  if ($id) { $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ?")->execute([$id]); }
  redirect(url('admin/contacts.php'));
}

$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">
    <?php
    $unread = 0; foreach ($contacts as $c0) { if ($c0['status'] === 'unread') $unread++; }
    $view = ($_GET['view'] ?? '') === 'unread' ? 'unread' : 'all';
    $shown = $view === 'unread' ? array_values(array_filter($contacts, fn($c0) => $c0['status'] === 'unread')) : $contacts;
    adm_pagehead('Messages', 'Enquiries sent through the website contact forms. ' . $unread . ' unread, ' . count($contacts) . ' in total.', [
      'icon' => 'fa-envelope',
      'help' => [
        'key' => 'contacts', 'title' => 'How messages work',
        'steps' => [
          '<strong>New enquiries are marked Unread</strong> and also arrive in your inbox if email notifications are on (<a href="settings.php#email">Settings</a>).',
          '<strong>Click "Read more"</strong> to open the full message in a pop-up.',
          '<strong>Reply</strong> with the Reply button. It opens your email program with the customer\'s address and subject already filled in. If they left a phone number, use the WhatsApp button.',
          '<strong>Press "Mark read"</strong> once you have dealt with it, so only new messages stay highlighted.',
        ],
        'tips' => [
          'Fast replies win bookings. Aim to answer within 24 hours, ideally within a few hours.',
          'Messages are kept here even after they are marked read.',
          'Marking a message read does not email the customer.',
          'Some messages are advertising from other companies (website design, SEO, backlinks, "free listing"). Ignore them and press Mark read. Never click their links.',
        ],
      ],
    ]);
    ?>

    <div style="display:flex;gap:.5rem;margin-bottom:1.1rem;flex-wrap:wrap">
      <a href="contacts.php" class="btn btn--sm <?= $view === 'all' ? 'btn--primary' : 'btn--outline' ?>">All <span style="opacity:.7;font-weight:500"><?= count($contacts) ?></span></a>
      <a href="contacts.php?view=unread" class="btn btn--sm <?= $view === 'unread' ? 'btn--primary' : 'btn--outline' ?>">Unread <span style="opacity:.7;font-weight:500"><?= $unread ?></span></a>
    </div>

    <div class="adm-card">
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead><tr><th>#</th><th>From</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php if (!$shown): ?>
            <tr><td colspan="7"><div class="adm-empty"><i class="fas fa-inbox"></i><strong><?= $view === 'unread' ? 'No unread messages' : 'No messages yet' ?></strong>Enquiries from the website will appear here.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($shown as $c):
              $wa = preg_replace('/\D+/', '', (string)($c['phone'] ?? '')); ?>
            <tr style="<?= $c['status'] === 'unread' ? 'background:rgba(193,122,58,.06)' : '' ?>">
              <td style="font-size:.74rem"><?= e($c['id']) ?></td>
              <td>
                <strong style="color:#fff"><?= e($c['name']) ?></strong><br>
                <a href="mailto:<?= e($c['email']) ?>" style="font-size:.78rem"><?= e($c['email']) ?></a>
                <?php if (!empty($c['phone'])): ?><br><small style="color:var(--text-dim)"><?= e($c['phone']) ?></small><?php endif; ?>
              </td>
              <td style="color:var(--text)"><?= e($c['subject']) ?></td>
              <td style="max-width:260px;white-space:normal;font-size:.85rem">
                <?= e(truncate($c['message'], 100)) ?>
                <?php if (mb_strlen($c['message']) > 100): ?>
                  <button type="button" class="msg-more"
                    data-name="<?= e($c['name']) ?>"
                    data-email="<?= e($c['email']) ?>"
                    data-subject="<?= e($c['subject']) ?>"
                    data-date="<?= e(formatDate($c['created_at'])) ?>"
                    data-message="<?= e($c['message']) ?>">Read more</button>
                <?php endif; ?>
              </td>
              <td><span class="badge badge--<?= $c['status'] === 'unread' ? 'pending' : 'confirmed' ?>"><?= e($c['status']) ?></span></td>
              <td style="font-size:.8rem"><?= e(formatDate($c['created_at'])) ?></td>
              <td style="min-width:150px">
                <?php if ($c['status'] === 'unread'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="contact_id" value="<?= e($c['id']) ?>">
                  <input type="hidden" name="mark_read"  value="1">
                  <button type="submit" class="btn btn--sm btn--outline">Mark read</button>
                </form>
                <?php endif; ?>
                <a href="mailto:<?= e($c['email']) ?>?subject=Re: <?= urlencode($c['subject']) ?>" class="btn btn--sm btn--primary"><i class="fas fa-reply"></i> Reply</a>
                <?php if ($wa): ?><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn--sm btn--outline" title="Open WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- ===== Message viewer modal ===== -->
<div id="msgModal" class="msg-modal" aria-hidden="true">
  <div class="msg-modal__backdrop" data-close></div>
  <div class="msg-modal__box" role="dialog" aria-modal="true" aria-labelledby="msgModalSubject">
    <button type="button" class="msg-modal__x" data-close aria-label="Close">&times;</button>
    <h2 id="msgModalSubject" class="msg-modal__subject"></h2>
    <div class="msg-modal__meta">
      <span><i class="fa-regular fa-user"></i> <b id="msgModalName"></b></span>
      <span><i class="fa-regular fa-envelope"></i> <a id="msgModalEmail" href="#"></a></span>
      <span><i class="fa-regular fa-calendar"></i> <span id="msgModalDate"></span></span>
    </div>
    <div id="msgModalBody" class="msg-modal__body"></div>
    <div class="msg-modal__foot">
      <a id="msgModalReply" href="#" class="btn btn--sm btn--primary"><i class="fa-solid fa-reply"></i> Reply</a>
      <button type="button" class="btn btn--sm btn--outline" data-close>Close</button>
    </div>
  </div>
</div>

<style>
/* Modal inaendana na dark theme ya admin panel (rangi kamili, si variables) */
.msg-more{background:none;border:none;color:#e0a46a;font-size:.8rem;
  font-weight:600;cursor:pointer;padding:0;margin-left:2px;text-decoration:underline}
.msg-modal{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:20px}
.msg-modal.is-open{display:flex}
.msg-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(2px)}
.msg-modal__box{position:relative;background:#1a1d27;color:#e5e7eb;
  width:100%;max-width:560px;max-height:85vh;overflow-y:auto;border-radius:14px;padding:26px 26px 22px;
  border:1px solid rgba(255,255,255,.08);
  box-shadow:0 20px 60px rgba(0,0,0,.6);animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}
.msg-modal__x{position:absolute;top:10px;right:14px;background:none;border:none;font-size:1.7rem;
  line-height:1;cursor:pointer;color:rgba(255,255,255,.55)}
.msg-modal__x:hover{color:#fff}
.msg-modal__subject{font-size:1.25rem;font-weight:700;margin:0 30px 14px 0;color:#fff}
.msg-modal__meta{display:flex;flex-wrap:wrap;gap:16px;font-size:.85rem;color:rgba(255,255,255,.7);
  padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,.1)}
.msg-modal__meta i{color:#e0a46a;margin-right:2px}
.msg-modal__meta b{color:#fff;font-weight:600}
.msg-modal__meta a{color:#e0a46a}
.msg-modal__body{font-size:.95rem;line-height:1.65;white-space:pre-wrap;word-break:break-word;color:#e5e7eb}
.msg-modal__foot{display:flex;gap:10px;justify-content:flex-end;margin-top:22px}
</style>

<script>
(function(){
  var modal   = document.getElementById('msgModal');
  var elSubj  = document.getElementById('msgModalSubject');
  var elName  = document.getElementById('msgModalName');
  var elEmail = document.getElementById('msgModalEmail');
  var elDate  = document.getElementById('msgModalDate');
  var elBody  = document.getElementById('msgModalBody');
  var elReply = document.getElementById('msgModalReply');

  function open(btn){
    var d = btn.dataset;
    elSubj.textContent  = d.subject || '(No subject)';
    elName.textContent  = d.name;
    elEmail.textContent = d.email;
    elEmail.href        = 'mailto:' + d.email;
    elDate.textContent  = d.date;
    elBody.textContent  = d.message;
    elReply.href        = 'mailto:' + d.email + '?subject=Re: ' + encodeURIComponent(d.subject || '');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }
  function close(){
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.msg-more').forEach(function(b){
    b.addEventListener('click', function(){ open(b); });
  });
  modal.querySelectorAll('[data-close]').forEach(function(el){
    el.addEventListener('click', close);
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') close();
  });
})();
</script>
</body>
</html>
