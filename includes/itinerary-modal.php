<?php
/* Itinerary Download Modal — include once per tour-detail page */
$_imCsrf = generateCsrfToken();
?>
<div id="im-overlay"
     style="position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;padding:1rem;
            background:rgba(0,0,0,.7);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
            opacity:0;transition:opacity .3s cubic-bezier(.4,0,.2,1)"
     aria-modal="true" role="dialog" aria-label="Download Itinerary"
     onclick="imClose(event)">

  <div id="im-card"
       style="width:100%;max-width:440px;max-height:90vh;overflow-y:auto;border-radius:20px;
              background:#111;border:1px solid rgba(255,255,255,.1);
              box-shadow:0 32px 80px rgba(0,0,0,.7),0 0 0 1px rgba(201,168,76,.08);
              transform:translateY(24px) scale(.97);transition:transform .35s cubic-bezier(.16,1,.3,1),opacity .35s;
              opacity:0"
       onclick="event.stopPropagation()">

    <div style="padding:1.6rem 1.6rem 0;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
      <div>
        <h2 style="font-family:'Nanum Myeongjo',Georgia,serif;font-size:1.3rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:.25rem">
          Download <span style="background:linear-gradient(135deg,#c17a3a,#a05e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Itinerary</span>
        </h2>
        <p style="font-size:.8rem;color:rgba(255,255,255,.45);font-family:'Inter',sans-serif">We'll email you a link to view &amp; download it.</p>
      </div>
      <button onclick="imClose()" aria-label="Close"
              style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
                     color:rgba(255,255,255,.6);font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;
                     transition:all .2s;flex-shrink:0;line-height:1">×</button>
    </div>

    <div style="height:1px;background:rgba(255,255,255,.07);margin:1rem 1.6rem 0"></div>

    <div id="im-form-wrap" style="padding:1.25rem 1.6rem 1.6rem">
      <form id="im-form" novalidate>
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" id="im-csrf" value="<?= e($_imCsrf) ?>">
        <input type="hidden" name="tour_id" id="im-tour-id" value="">
        <input type="hidden" name="im_ts" id="im-ts" value="">

        <div style="margin-bottom:1rem">
          <label class="im-label" for="im-name">Full Name <span style="color:#f87171">*</span></label>
          <input type="text" id="im-name" name="name" class="im-input" required placeholder="John Smith" autocomplete="name">
        </div>

        <div style="margin-bottom:1rem">
          <label class="im-label" for="im-email">Email <span style="color:#f87171">*</span></label>
          <input type="email" id="im-email" name="email" class="im-input" required placeholder="you@email.com" autocomplete="email">
        </div>

        <div style="margin-bottom:1rem">
          <label class="im-label" for="im-whatsapp">WhatsApp Number</label>
          <input type="tel" id="im-whatsapp" name="whatsapp" class="im-input" placeholder="+255 700 000 000" autocomplete="tel">
        </div>

        <div style="margin-bottom:1rem">
          <label class="im-label" for="im-travelers">Number of Travellers</label>
          <input type="number" id="im-travelers" name="travelers" class="im-input" value="2" min="1" max="50">
          <div style="font-size:.68rem;color:rgba(255,255,255,.25);margin-top:.35rem">Group discounts are applied automatically based on group size.</div>
        </div>

        <div style="margin-bottom:1.4rem">
          <label class="im-label" for="im-travel-date">Preferred Travel Date <span class="f-hint" style="text-transform:none;letter-spacing:0;color:rgba(255,255,255,.25)">(optional)</span></label>
          <input type="date" id="im-travel-date" name="travel_date" class="im-input" min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>

        <div id="im-error" style="display:none;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#f87171;font-family:'Inter',sans-serif"></div>

        <button type="submit" id="im-submit"
                style="width:100%;background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;
                       font-family:'Montserrat',sans-serif;font-weight:700;font-size:.85rem;letter-spacing:.06em;
                       text-transform:uppercase;padding:.95rem 1.5rem;border-radius:12px;border:none;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;gap:.6rem;
                       transition:all .3s;box-shadow:0 4px 20px rgba(160,94,34,.3)">
          <i class="fas fa-file-pdf" style="font-size:.75rem"></i> Send Me the Itinerary
        </button>

        <p style="text-align:center;font-size:.7rem;color:rgba(255,255,255,.2);margin-top:.85rem;font-family:'Montserrat',sans-serif">
          <i class="fas fa-shield-alt" style="color:#a05e22;margin-right:.3rem;font-size:.65rem"></i>
          No spam — just your itinerary link.
        </p>
      </form>
    </div>

    <div id="im-success" style="display:none;padding:2.5rem 1.6rem;text-align:center">
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(160,94,34,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
        <i class="fas fa-check" style="font-size:1.5rem;color:#a05e22"></i>
      </div>
      <h3 style="font-family:'Nanum Myeongjo',Georgia,serif;font-size:1.2rem;color:#fff;margin-bottom:.5rem">Itinerary Sent!</h3>
      <p id="im-success-msg" style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:1rem">Check your email for the download link.</p>
      <a id="im-view-link" href="#" target="_blank" rel="noopener"
         style="display:inline-flex;align-items:center;gap:.5rem;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.78rem;
                padding:.8rem 1.6rem;border-radius:10px;text-decoration:none;
                color:#a05e22;background:rgba(160,94,34,.1);border:1px solid rgba(160,94,34,.2);transition:all .25s;margin-bottom:.6rem">
        <i class="fas fa-arrow-up-right-from-square"></i> View Itinerary Now
      </a>
      <div style="margin-top:.85rem">
        <button onclick="imClose()" style="font-family:'Montserrat',sans-serif;font-size:.72rem;color:rgba(255,255,255,.3);background:none;border:none;cursor:pointer">Close</button>
      </div>
    </div>

  </div>
</div>

<style>
.im-label{display:block;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.38);margin-bottom:.4rem}
.im-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:10px;padding:.72rem 1rem;font-family:'Inter',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s,background .2s;-webkit-appearance:none;box-sizing:border-box}
.im-input:focus{border-color:rgba(160,94,34,.5);background:rgba(160,94,34,.04)}
.im-input::placeholder{color:rgba(255,255,255,.22)}
#im-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(160,94,34,.4)}
#im-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
</style>

<script>
(function(){
  const overlay = document.getElementById('im-overlay');
  const card    = document.getElementById('im-card');
  const form    = document.getElementById('im-form');
  const errDiv  = document.getElementById('im-error');
  const submit  = document.getElementById('im-submit');

  window.openItineraryModal = function(tourId) {
    document.getElementById('im-tour-id').value = tourId;
    document.getElementById('im-ts').value = Date.now();
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
      overlay.style.opacity = '1';
      card.style.opacity    = '1';
      card.style.transform  = 'translateY(0) scale(1)';
    });
    setTimeout(() => document.getElementById('im-name')?.focus(), 350);
  };

  window.imClose = function(e) {
    if (e && e.target !== overlay) return;
    overlay.style.opacity = '0';
    card.style.opacity    = '0';
    card.style.transform  = 'translateY(24px) scale(.97)';
    setTimeout(() => {
      overlay.style.display = 'none';
      document.body.style.overflow = '';
      document.getElementById('im-form-wrap').style.display = '';
      document.getElementById('im-success').style.display   = 'none';
      form.reset();
      errDiv.style.display = 'none';
      submit.disabled = false;
      submit.innerHTML = '<i class="fas fa-file-pdf" style="font-size:.75rem"></i> Send Me the Itinerary';
    }, 320);
  };

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && overlay.style.display === 'flex') window.imClose();
  });

  form && form.addEventListener('submit', async function(e) {
    e.preventDefault();
    errDiv.style.display = 'none';
    submit.disabled = true;
    submit.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.75rem"></i> Sending…';

    try {
      const fd  = new FormData(form);
      const url = (window.SITE_URL || '') + '/ajax/request-itinerary.php';
      const res = await fetch(url, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        document.getElementById('im-form-wrap').style.display = 'none';
        document.getElementById('im-success').style.display   = 'block';
        document.getElementById('im-success-msg').textContent = data.message || 'Check your email for the download link.';
        const viewLink = document.getElementById('im-view-link');
        if (data.share_url) { viewLink.href = data.share_url; viewLink.style.display = 'inline-flex'; }
        else { viewLink.style.display = 'none'; }
      } else {
        errDiv.textContent   = data.message || 'Something went wrong. Please try again.';
        errDiv.style.display = 'block';
        submit.disabled      = false;
        submit.innerHTML     = '<i class="fas fa-file-pdf" style="font-size:.75rem"></i> Send Me the Itinerary';
      }
    } catch(err) {
      errDiv.textContent   = 'Network error. Please check your connection and try again.';
      errDiv.style.display = 'block';
      submit.disabled      = false;
      submit.innerHTML     = '<i class="fas fa-file-pdf" style="font-size:.75rem"></i> Send Me the Itinerary';
    }
  });
})();
</script>
