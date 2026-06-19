/* ═══════════════════════════════════════════════════
   JAMBO MASAI TOURS — booking.js
   Loaded ONLY on booking.php
   ═══════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ─── 1. STEP MANAGEMENT ────────────────────────── */
  const steps   = document.querySelectorAll('.booking-step');
  const panels  = document.querySelectorAll('.booking-panel');
  let current   = 0;

  function showStep(idx) {
    panels.forEach((p, i) => {
      p.classList.toggle('is-active', i === idx);
    });
    steps.forEach((s, i) => {
      s.classList.toggle('is-active', i === idx);
      s.classList.toggle('is-done',   i < idx);
    });
    current = idx;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    updateSummary();
  }

  document.querySelectorAll('[data-step-next]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (validatePanel(current)) showStep(current + 1);
    });
  });

  document.querySelectorAll('[data-step-prev]').forEach(btn => {
    btn.addEventListener('click', () => showStep(current - 1));
  });

  /* ─── 2. PANEL VALIDATION ───────────────────────── */
  function validatePanel(idx) {
    const panel  = panels[idx];
    if (!panel) return true;
    const fields = panel.querySelectorAll('[required]');
    let valid    = true;

    fields.forEach(f => {
      f.classList.remove('is-error');
      const err = f.parentElement.querySelector('.form-error');
      if (err) err.remove();

      const empty = f.value.trim() === '';
      const badEmail = f.type === 'email' && f.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value);

      if (empty || badEmail) {
        f.classList.add('is-error');
        const msg = document.createElement('span');
        msg.className = 'form-error';
        msg.textContent = badEmail ? 'Please enter a valid email.' : 'This field is required.';
        f.parentElement.appendChild(msg);
        valid = false;
      }
    });

    return valid;
  }

  /* ─── 3. DATE CONSTRAINT ────────────────────────── */
  const dateInput = document.getElementById('travel_date');
  if (dateInput) {
    const min = new Date();
    min.setDate(min.getDate() + 14);
    dateInput.min = min.toISOString().split('T')[0];
  }

  /* ─── 4. PRICE CALCULATOR ───────────────────────── */
  const tourSelect  = document.getElementById('tour_id');
  const travelersEl = document.getElementById('travelers');
  const priceEl     = document.getElementById('calc-price');
  const totalEl     = document.getElementById('calc-total');

  function updatePrice() {
    if (!tourSelect || !travelersEl || !priceEl || !totalEl) return;
    const opt = tourSelect.selectedOptions[0];
    const pp  = parseFloat(opt ? opt.dataset.price || 0 : 0);
    const n   = parseInt(travelersEl.value, 10) || 1;
    priceEl.textContent = pp ? '$' + pp.toLocaleString() : '—';
    totalEl.textContent = pp ? '$' + (pp * n).toLocaleString() : '—';
  }

  tourSelect  && tourSelect.addEventListener('change', updatePrice);
  travelersEl && travelersEl.addEventListener('input',  updatePrice);
  updatePrice();

  /* ─── 5. PRE-SELECT TOUR FROM URL PARAM ─────────── */
  if (tourSelect) {
    const slug = new URLSearchParams(window.location.search).get('tour');
    if (slug) {
      Array.from(tourSelect.options).forEach(opt => {
        if (opt.dataset.slug === slug) opt.selected = true;
      });
      updatePrice();
    }
  }

  /* ─── 6. SUMMARY PREVIEW ────────────────────────── */
  function updateSummary() {
    const fields = {
      's-tour'       : document.getElementById('tour_id'),
      's-date'       : document.getElementById('travel_date'),
      's-travelers'  : document.getElementById('travelers'),
      's-name'       : document.getElementById('first_name'),
      's-email'      : document.getElementById('email'),
      's-country'    : document.getElementById('country'),
    };
    Object.entries(fields).forEach(([id, el]) => {
      const out = document.getElementById(id);
      if (!out || !el) return;
      if (el.tagName === 'SELECT') {
        out.textContent = el.selectedOptions[0]?.text || '—';
      } else {
        out.textContent = el.value || '—';
      }
    });

    const totalOut = document.getElementById('s-total');
    if (totalOut && totalEl) totalOut.textContent = totalEl.textContent;
  }

  /* ─── 7. FORM FINAL SUBMIT ──────────────────────── */
  const form = document.getElementById('booking-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!validatePanel(current)) {
        e.preventDefault();
      }
    });
  }

})();
