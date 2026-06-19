/* ═══════════════════════════════════════════════════
   JAMBO MASAI TOURS — main.js
   ═══════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ─── 1. LOADING SCREEN ─────────────────────────── */
  const loadingScreen = document.getElementById('loading-screen');
  if (loadingScreen) {
    const hide = () => loadingScreen.classList.add('is-hidden');
    if (document.readyState === 'complete') {
      setTimeout(hide, 800);
    } else {
      window.addEventListener('load', () => setTimeout(hide, 800));
    }
  }

  /* ─── 2. HERO IMAGE KEN BURNS ───────────────────── */
  const hero = document.querySelector('.hero');
  if (hero) {
    hero.classList.add('is-loaded');
  }

  /* ─── 3. NAVBAR SCROLL BEHAVIOUR ───────────────── */
  const navbar = document.getElementById('main-nav');
  if (navbar) {
    const updateNav = () => navbar.classList.toggle('navbar--solid', window.scrollY > 60);
    window.addEventListener('scroll', updateNav, { passive: true });
    updateNav(); // run on load so state is correct immediately
  }

  /* ─── 4. MOBILE MENU ────────────────────────────── */
  const toggle   = document.querySelector('.navbar__toggle');
  const menu     = document.getElementById('nav-menu');
  const backdrop = document.getElementById('nav-backdrop');

  function openMenu() {
    menu.classList.add('is-open');
    toggle.classList.add('is-active');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    if (backdrop) { backdrop.style.display = 'block'; requestAnimationFrame(() => backdrop.classList.add('is-visible')); }
  }
  function closeMenu() {
    menu.classList.remove('is-open');
    toggle.classList.remove('is-active');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (backdrop) { backdrop.classList.remove('is-visible'); setTimeout(() => { backdrop.style.display = ''; }, 300); }
  }

  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      menu.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    // Close on any link click inside drawer
    menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

    // Close on backdrop click
    if (backdrop) backdrop.addEventListener('click', closeMenu);

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && menu.classList.contains('is-open')) { closeMenu(); toggle.focus(); }
    });

    // Auto-close when resizing to desktop
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 992 && menu.classList.contains('is-open')) closeMenu();
    }, { passive: true });
  }

  /* ─── 5. SCROLL PROGRESS BAR ────────────────────── */
  const progress = document.querySelector('.scroll-progress');
  if (progress) {
    const update = () => {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      progress.style.width = (window.scrollY / max * 100).toFixed(2) + '%';
    };
    window.addEventListener('scroll', update, { passive: true });
  }

  /* ─── 6. BACK TO TOP ────────────────────────────── */
  const btt = document.getElementById('back-to-top');
  if (btt) {
    const check = () => btt.classList.toggle('is-visible', window.scrollY > 400);
    window.addEventListener('scroll', check, { passive: true });
    btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ─── 7. WHATSAPP FLOAT ─────────────────────────── */
  const waFloat = document.getElementById('whatsapp-float');
  if (waFloat) {
    setTimeout(() => waFloat.classList.add('is-visible'), 3000);
  }

  /* ─── 8. LAZY IMAGE LOADING ─────────────────────── */
  if ('IntersectionObserver' in window) {
    const imgObserver = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
        }
        if (img.dataset.srcset) {
          img.srcset = img.dataset.srcset;
          img.removeAttribute('data-srcset');
        }
        img.classList.add('img-loaded');
        obs.unobserve(img);
      });
    }, { rootMargin: '250px' });

    document.querySelectorAll('img[data-src]').forEach(img => imgObserver.observe(img));
  } else {
    // Fallback: load all
    document.querySelectorAll('img[data-src]').forEach(img => {
      img.src = img.dataset.src;
    });
  }

  /* ─── 9. STATS COUNTER ──────────────────────────── */
  const counters = document.querySelectorAll('[data-counter]');
  if (counters.length) {
    const easeOutCubic = t => 1 - Math.pow(1 - t, 3);

    function animateCounter(el) {
      const target   = parseInt(el.dataset.counter, 10);
      const duration = 2200;
      const suffix   = el.dataset.suffix || '';
      const start    = performance.now();

      const step = (now) => {
        const p = Math.min((now - start) / duration, 1);
        el.textContent = Math.floor(easeOutCubic(p) * target).toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    }

    const counterObs = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        animateCounter(entry.target);
        obs.unobserve(entry.target);
      });
    }, { threshold: .3 });

    counters.forEach(c => counterObs.observe(c));
  }

  /* ─── 10. TESTIMONIALS SLIDER ───────────────────── */
  const slider = document.querySelector('.testimonials-slider');
  if (slider) {
    const track  = slider.querySelector('.testimonials-track');
    const cards  = slider.querySelectorAll('.testimonial-card');
    const prev   = slider.querySelector('.slider-btn--prev');
    const next   = slider.querySelector('.slider-btn--next');
    const dotsWrap = slider.querySelector('.slider-dots');

    let current  = 0;
    let autoplay;
    const visible = () => window.innerWidth >= 768 ? 2 : 1;

    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      const count = Math.ceil(cards.length / visible());
      for (let i = 0; i < count; i++) {
        const d = document.createElement('button');
        d.className = 'slider-dot' + (i === 0 ? ' is-active' : '');
        d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        d.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(d);
      }
    }

    function goTo(idx) {
      const count = Math.ceil(cards.length / visible());
      current = ((idx % count) + count) % count;
      const offset = current * (100 / visible());
      track.style.transform = `translateX(-${offset}%)`;
      dotsWrap && dotsWrap.querySelectorAll('.slider-dot').forEach((d, i) => {
        d.classList.toggle('is-active', i === current);
      });
    }

    function startAuto() {
      autoplay = setInterval(() => goTo(current + 1), 5000);
    }
    function stopAuto() { clearInterval(autoplay); }

    buildDots();
    startAuto();

    prev && prev.addEventListener('click', () => { stopAuto(); goTo(current - 1); startAuto(); });
    next && next.addEventListener('click', () => { stopAuto(); goTo(current + 1); startAuto(); });
    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);

    // Touch swipe
    let touchX = 0;
    slider.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', e => {
      const diff = touchX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        stopAuto();
        goTo(current + (diff > 0 ? 1 : -1));
        startAuto();
      }
    });

    window.addEventListener('resize', buildDots);
  }

  /* ─── 11. GALLERY LIGHTBOX ──────────────────────── */
  const lightbox = document.getElementById('lightbox');
  const lbImg    = lightbox ? lightbox.querySelector('.lightbox__image') : null;
  const lbClose  = lightbox ? lightbox.querySelector('.lightbox__close') : null;
  const galleryItems = document.querySelectorAll('.gallery-item[data-img]');

  if (lightbox && lbImg) {
    galleryItems.forEach(item => {
      item.addEventListener('click', () => {
        lbImg.src = item.dataset.img;
        lbImg.alt = item.dataset.alt || '';
        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      });
    });

    const closeLb = () => {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    lbClose && lbClose.addEventListener('click', closeLb);
    lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLb(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLb(); });
  }

  /* ─── 12. GALLERY FILTER ────────────────────────── */
  const filterBtns = document.querySelectorAll('.gallery-filter__btn');
  const galleryItemsAll = document.querySelectorAll('.gallery-item');

  if (filterBtns.length) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const cat = btn.dataset.filter;
        galleryItemsAll.forEach(item => {
          const match = cat === 'all' || item.dataset.category === cat;
          item.style.display = match ? '' : 'none';
          if (match) item.style.animation = 'scaleIn .3s ease both';
        });
      });
    });
  }

  /* ─── 13. BUTTON RIPPLE EFFECT ──────────────────── */
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const r = document.createElement('span');
      r.className = 'ripple';
      const rect = btn.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
      this.appendChild(r);
      setTimeout(() => r.remove(), 600);
    });
  });

  /* ─── 14. SAFARI SEARCH FORM ────────────────────── */
  const searchForm = document.getElementById('safari-search');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const params = new URLSearchParams(new FormData(this));
      window.location.href = 'tours.php?' + params.toString();
    });
  }

  /* ─── 15. SMOOTH ANCHOR SCROLL ──────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      const offset = 80;
      window.scrollTo({
        top: target.getBoundingClientRect().top + window.scrollY - offset,
        behavior: 'smooth',
      });
    });
  });

  /* ─── 16. AOS INIT ──────────────────────────────── */
  if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 800, once: true, offset: 80, easing: 'ease-out-cubic' });
  }

  /* ─── 17. HERO SLIDER ───────────────────────────── */
  (function heroSlider() {
    const slides      = document.querySelectorAll('.hero__slide');
    const prevBtn     = document.getElementById('hero-prev');
    const nextBtn     = document.getElementById('hero-next');
    const currentNum  = document.getElementById('hero-current-num');
    const progressEl  = document.getElementById('hero-counter-progress');
    if (slides.length < 2) return;

    let current = 0;
    let timer   = null;
    const DELAY = 7000;
    const total = slides.length;

    function updateCounter(idx) {
      if (currentNum) currentNum.textContent = String(idx + 1).padStart(2, '0');
      if (progressEl) progressEl.style.height = ((idx + 1) / total * 100) + '%';
    }

    function goTo(idx) {
      const prev = current;
      current = (idx + total) % total;
      slides[prev].classList.remove('hero__slide--active');
      slides[current].classList.add('hero__slide--active');
      updateCounter(current);
    }

    function start() {
      clearInterval(timer);
      timer = setInterval(() => goTo(current + 1), DELAY);
    }

    function pause() { clearInterval(timer); }

    if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); start(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); start(); });

    /* touch swipe */
    let touchStartX = 0;
    const hero = document.getElementById('hero');
    if (hero) {
      hero.addEventListener('mouseenter', pause);
      hero.addEventListener('mouseleave', start);
      hero.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
      hero.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); start(); }
      }, { passive: true });
    }

    updateCounter(0);
    start();
  })();

})();
