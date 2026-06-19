<?php
/* Booking Modal — include once per page, anywhere in <body> */
$_bmCsrf  = generateCsrfToken();
$_bmPhone = SITE_PHONE;
$_bmWA    = WHATSAPP_NUMBER;
try {
    $_bmDests = getDB()->query("SELECT DISTINCT destination FROM tours ORDER BY destination")->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $_bmDests = []; }
if (empty($_bmDests)) {
    $_bmDests = ['Serengeti','Ngorongoro','Kilimanjaro','Zanzibar','Tarangire','Maasai Heartland','Mt. Meru'];
}
?>

<!-- ════════════════════════════════════════
     BOOKING MODAL — global, opens on any page
════════════════════════════════════════ -->
<div id="bm-overlay"
     style="position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;padding:1rem;
            background:rgba(0,0,0,.7);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
            opacity:0;transition:opacity .3s cubic-bezier(.4,0,.2,1)"
     aria-modal="true" role="dialog" aria-label="Book a Safari"
     onclick="bmClose(event)">

  <div id="bm-card"
       style="width:100%;max-width:480px;max-height:90vh;overflow-y:auto;border-radius:20px;
              background:#111;border:1px solid rgba(255,255,255,.1);
              box-shadow:0 32px 80px rgba(0,0,0,.7),0 0 0 1px rgba(201,168,76,.08);
              transform:translateY(24px) scale(.97);transition:transform .35s cubic-bezier(.16,1,.3,1),opacity .35s;
              opacity:0;scrollbar-width:thin;scrollbar-color:#10b981 #111"
       onclick="event.stopPropagation()">

    <!-- Header -->
    <div style="padding:1.6rem 1.6rem 0;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
      <div>
        <h2 style="font-family:'Playfair Display',Georgia,serif;font-size:1.45rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:.25rem">
          Book Your <span style="background:linear-gradient(135deg,#34d399,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Safari</span>
        </h2>
        <p style="font-size:.8rem;color:rgba(255,255,255,.45);font-family:'Inter',sans-serif">We'll get back within 24 hours.</p>
      </div>
      <button id="bm-close" onclick="bmClose()" aria-label="Close"
              style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
                     color:rgba(255,255,255,.6);font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;
                     transition:all .2s;flex-shrink:0;line-height:1">
        ×
      </button>
    </div>

    <!-- Divider -->
    <div style="height:1px;background:rgba(255,255,255,.07);margin:1rem 1.6rem 0"></div>

    <!-- Form state -->
    <div id="bm-form-wrap" style="padding:1.25rem 1.6rem 1.6rem">
      <form id="bm-form" novalidate>
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" id="bm-csrf" value="<?= e($_bmCsrf) ?>">

        <!-- Name -->
        <div style="margin-bottom:1rem">
          <label class="bm-label" for="bm-name">Full Name <span style="color:#f87171">*</span></label>
          <input type="text" id="bm-name" name="name" class="bm-input" required
                 placeholder="John Smith" autocomplete="name">
        </div>

        <!-- Email + Phone row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem">
          <div>
            <label class="bm-label" for="bm-email">Email <span style="color:#f87171">*</span></label>
            <input type="email" id="bm-email" name="email" class="bm-input" required
                   placeholder="you@email.com" autocomplete="email">
          </div>
          <div>
            <label class="bm-label" for="bm-phone">Phone / WhatsApp</label>
            <input type="tel" id="bm-phone" name="phone" class="bm-input"
                   placeholder="+255 700 000 000" autocomplete="tel">
          </div>
        </div>

        <!-- Destination -->
        <div style="margin-bottom:1rem">
          <label class="bm-label" for="bm-dest">Preferred Destination</label>
          <div style="position:relative">
            <select id="bm-dest" name="destination" class="bm-input" style="cursor:pointer">
              <option value="">Choose destination</option>
              <?php foreach ($_bmDests as $d): ?>
              <option value="<?= e($d) ?>"><?= e($d) ?></option>
              <?php endforeach; ?>
              <option value="Multiple Destinations">Multiple Destinations</option>
              <option value="Undecided - Need Advice">Undecided – Need Advice</option>
            </select>
            <i class="fas fa-chevron-down" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);font-size:.65rem;pointer-events:none"></i>
          </div>
        </div>

        <!-- Date + Travelers row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem">
          <div>
            <label class="bm-label" for="bm-date">Travel Date</label>
            <input type="date" id="bm-date" name="travel_date" class="bm-input"
                   min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
          </div>
          <div>
            <label class="bm-label" for="bm-travelers">Travelers</label>
            <input type="number" id="bm-travelers" name="travelers" class="bm-input"
                   value="2" min="1" max="50" autocomplete="off">
          </div>
        </div>

        <!-- Message -->
        <div style="margin-bottom:1.4rem">
          <label class="bm-label" for="bm-message">Message</label>
          <textarea id="bm-message" name="message" class="bm-input"
                    style="resize:vertical;min-height:90px;line-height:1.6"
                    placeholder="Tell us about your dream trip — type of safari, accommodation preferences, special occasions…"></textarea>
        </div>

        <!-- Error message -->
        <div id="bm-error"
             style="display:none;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);
                    border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;
                    font-size:.82rem;color:#f87171;font-family:'Inter',sans-serif">
        </div>

        <!-- Submit -->
        <button type="submit" id="bm-submit"
                style="width:100%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;
                       font-family:'Montserrat',sans-serif;font-weight:700;font-size:.85rem;letter-spacing:.06em;
                       text-transform:uppercase;padding:.95rem 1.5rem;border-radius:12px;border:none;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;gap:.6rem;
                       transition:all .3s;box-shadow:0 4px 20px rgba(16,185,129,.3)">
          <i class="fas fa-compass text-xs" style="font-size:.75rem"></i>
          Request Booking
        </button>

        <!-- Trust note -->
        <p style="text-align:center;font-size:.7rem;color:rgba(255,255,255,.2);margin-top:.85rem;font-family:'Montserrat',sans-serif">
          <i class="fas fa-shield-alt" style="color:#10b981;margin-right:.3rem;font-size:.65rem"></i>
          No payment required · Free cancellation · TATO Licensed
        </p>
      </form>
    </div>

    <!-- Success state (hidden initially) -->
    <div id="bm-success" style="display:none;padding:2.5rem 1.6rem;text-align:center">
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(16,185,129,.12);
                  display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
        <i class="fas fa-check" style="font-size:1.5rem;color:#10b981"></i>
      </div>
      <h3 style="font-family:'Playfair Display',Georgia,serif;font-size:1.25rem;color:#fff;margin-bottom:.5rem">
        Booking Request Sent!
      </h3>
      <p style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:1.5rem">
        Thank you! Our safari specialists will contact you within <strong style="color:rgba(255,255,255,.75)">24 hours</strong> to plan your perfect adventure.
      </p>
      <a href="https://wa.me/<?= e($_bmWA) ?>"
         target="_blank" rel="noopener"
         style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;
                padding:.8rem 1.6rem;border-radius:10px;text-decoration:none;
                color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2);transition:all .25s">
        <i class="fab fa-whatsapp" style="font-size:1.1rem"></i> Chat on WhatsApp
      </a>
      <div style="margin-top:.85rem">
        <button onclick="bmClose()"
                style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.3);background:none;border:none;cursor:pointer">
          Close
        </button>
      </div>
    </div>

  </div><!-- /card -->
</div><!-- /overlay -->

<style>
.bm-label{display:block;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.38);margin-bottom:.4rem}
.bm-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.72rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s,background .2s;-webkit-appearance:none;box-sizing:border-box}
.bm-input:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.04)}
.bm-input::placeholder{color:rgba(255,255,255,.22)}
.bm-input option{background:#1a1a1a;color:#e5e7eb}
#bm-card::-webkit-scrollbar{width:3px}
#bm-card::-webkit-scrollbar-thumb{background:#10b981;border-radius:2px}
#bm-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(16,185,129,.4)}
#bm-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
#bm-close:hover{background:rgba(255,255,255,.14);color:#fff}
</style>

<script>
(function(){
  const overlay = document.getElementById('bm-overlay');
  const card    = document.getElementById('bm-card');
  const form    = document.getElementById('bm-form');
  const errDiv  = document.getElementById('bm-error');
  const submit  = document.getElementById('bm-submit');

  /* Open */
  window.openBookingModal = function(destSlug) {
    if (destSlug) {
      const sel = document.getElementById('bm-dest');
      if (sel) {
        const opt = Array.from(sel.options).find(o => o.value.toLowerCase().includes(destSlug.toLowerCase()));
        if (opt) sel.value = opt.value;
      }
    }
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
      overlay.style.opacity = '1';
      card.style.opacity    = '1';
      card.style.transform  = 'translateY(0) scale(1)';
    });
    setTimeout(() => document.getElementById('bm-name')?.focus(), 350);
  };

  /* Close */
  window.bmClose = function(e) {
    if (e && e.target !== overlay) return;
    overlay.style.opacity = '0';
    card.style.opacity    = '0';
    card.style.transform  = 'translateY(24px) scale(.97)';
    setTimeout(() => {
      overlay.style.display = 'none';
      document.body.style.overflow = '';
      /* Reset form state */
      document.getElementById('bm-form-wrap').style.display = '';
      document.getElementById('bm-success').style.display   = 'none';
      form.reset();
      errDiv.style.display = 'none';
    }, 320);
  };

  /* Escape key */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && overlay.style.display === 'flex') {
      overlay.style.opacity = '0';
      card.style.opacity    = '0';
      card.style.transform  = 'translateY(24px) scale(.97)';
      setTimeout(() => {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('bm-form-wrap').style.display = '';
        document.getElementById('bm-success').style.display   = 'none';
        form.reset();
        errDiv.style.display = 'none';
      }, 320);
    }
  });

  /* Submit */
  form && form.addEventListener('submit', async function(e) {
    e.preventDefault();
    errDiv.style.display = 'none';
    submit.disabled = true;
    submit.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.75rem"></i> Sending…';

    try {
      const fd  = new FormData(form);
      const url = (window.SITE_URL || '') + '/ajax/book.php';
      const res = await fetch(url, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        document.getElementById('bm-form-wrap').style.display = 'none';
        document.getElementById('bm-success').style.display   = 'block';
        /* refresh CSRF for next use */
        const newToken = document.createElement('input');
        newToken.type  = 'hidden';
        newToken.name  = '<?= CSRF_TOKEN_NAME ?>';
        newToken.id    = 'bm-csrf';
        form.appendChild(newToken);
      } else {
        errDiv.textContent   = data.message || 'Something went wrong. Please try again.';
        errDiv.style.display = 'block';
        submit.disabled      = false;
        submit.innerHTML     = '<i class="fas fa-compass" style="font-size:.75rem"></i> Request Booking';
      }
    } catch(err) {
      errDiv.textContent   = 'Network error. Please check your connection and try again.';
      errDiv.style.display = 'block';
      submit.disabled      = false;
      submit.innerHTML     = '<i class="fas fa-compass" style="font-size:.75rem"></i> Request Booking';
    }
  });
})();
</script>
<?php
/* Set SITE_URL as JS variable so AJAX works from any subdirectory */
echo '<script>window.SITE_URL = "' . SITE_URL . '";</script>';
?>
