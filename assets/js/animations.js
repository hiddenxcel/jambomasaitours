/* ═══════════════════════════════════════════════════
   JAMBO MASAI TOURS — animations.js
   ═══════════════════════════════════════════════════ */
(function () {
  'use strict';

  const isMobile = window.matchMedia('(pointer: coarse)').matches;
  const reduced  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduced) return;

  /* ─── 1. HERO PARALLAX ──────────────────────────── */
  const heroMedia = document.querySelector('.hero__media');
  const heroContent = document.querySelector('.hero__content');

  if (heroMedia && heroContent && !isMobile) {
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (ticking) return;
      requestAnimationFrame(() => {
        const y = window.scrollY;
        if (y < window.innerHeight) {
          heroMedia.style.transform   = `translateY(${y * .35}px)`;
          heroContent.style.transform = `translateY(${y * .15}px)`;
          heroContent.style.opacity   = Math.max(0, 1 - y / 600);
        }
        ticking = false;
      });
      ticking = true;
    }, { passive: true });
  }

  /* ─── 2. 3D CARD TILT ───────────────────────────── */
  if (!isMobile) {
    document.querySelectorAll('.tour-card').forEach(card => {
      card.addEventListener('mousemove', function (e) {
        const rect   = this.getBoundingClientRect();
        const x      = (e.clientX - rect.left) / rect.width  - .5;
        const y      = (e.clientY - rect.top)  / rect.height - .5;
        this.style.transform = `perspective(1000px) rotateY(${x * 8}deg) rotateX(${-y * 8}deg) translateZ(8px) translateY(-8px)`;
        this.style.boxShadow = `${-x * 20}px ${y * 20}px 40px rgba(26,26,26,.2)`;
      });
      card.addEventListener('mouseleave', function () {
        this.style.transform = '';
        this.style.boxShadow = '';
        this.style.transition = 'transform .5s cubic-bezier(.4,0,.2,1), box-shadow .5s ease';
        setTimeout(() => { this.style.transition = ''; }, 500);
      });
    });
  }

  /* ─── 3. DESTINATION CARD CURSOR FOLLOW ─────────── */
  if (!isMobile) {
    document.querySelectorAll('.destination-card').forEach(card => {
      const arrow = card.querySelector('.destination-card__arrow');
      if (!arrow) return;
      card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        arrow.style.top  = y + 'px';
        arrow.style.left = x + 'px';
        arrow.style.transform = 'translate(-50%, -50%) scale(1)';
      });
      card.addEventListener('mouseleave', () => {
        arrow.style.transform = 'translate(-50%, -50%) scale(0)';
      });
    });
  }

  /* ─── 4. SECTION TITLE UNDERLINE DRAW ───────────── */
  const titleObs = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('section-title--draw');
      obs.unobserve(entry.target);
    });
  }, { threshold: .6 });

  document.querySelectorAll('.section-title').forEach(t => titleObs.observe(t));

  /* ─── 5. FEATURE CARD STAGGER ───────────────────── */
  const featureGrid = document.querySelector('.features-grid');
  if (featureGrid) {
    const cards = featureGrid.querySelectorAll('.feature-card');
    const staggerObs = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const cards2 = entry.target.querySelectorAll('.feature-card');
        cards2.forEach((c, i) => {
          c.style.transitionDelay = `${i * 80}ms`;
        });
        obs.unobserve(entry.target);
      });
    }, { threshold: .1 });
    staggerObs.observe(featureGrid);
  }

  /* ─── 6. STAT CARD NUMBER SHIMMER ───────────────── */
  document.querySelectorAll('.stat-card__number').forEach(el => {
    const parent = el.closest('.stat-card');
    if (!parent) return;
    const obs = new IntersectionObserver(([entry], o) => {
      if (!entry.isIntersecting) return;
      el.style.animation = 'float 3s ease-in-out infinite';
      o.unobserve(parent);
    }, { threshold: .5 });
    obs.observe(parent);
  });

  /* ─── 7. SCROLL-TRIGGERED TEXT SPLIT ────────────── */
  // Stagger words in .hero__heading on load
  const heroH = document.querySelector('.hero__heading');
  if (heroH) {
    const lines = heroH.querySelectorAll('em, :not(em)');
    // already animated via CSS keyframes — no-op here
  }

  /* ─── 8. ABOUT IMAGE REVEAL ─────────────────────── */
  const aboutMedia = document.querySelector('.about-media');
  if (aboutMedia) {
    const obs = new IntersectionObserver(([entry], o) => {
      if (!entry.isIntersecting) return;
      aboutMedia.style.animation = 'slideInLeft .9s cubic-bezier(.16,1,.3,1) both';
      o.unobserve(aboutMedia);
    }, { threshold: .2 });
    obs.observe(aboutMedia);
  }

})();
