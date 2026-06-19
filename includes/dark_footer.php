<?php /* Dark-theme standalone footer — include at end of page body */ ?>

<!-- FOOTER -->
<footer class="border-t border-white/[.07] py-14 px-4 lg:px-0 mt-8">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
      <!-- Brand -->
      <div>
        <img src="<?= SITE_URL ?>/uploads/logo-husika.png" alt="<?= e($siteName) ?>"
             style="height:48px;width:auto;max-width:160px;object-fit:contain;display:block;margin-bottom:1rem">
        <p class="text-white/40 text-sm leading-relaxed mb-4">Tanzania's premier safari operator crafting extraordinary wildlife adventures since 2009.</p>
        <div class="flex gap-2.5">
          <?php foreach ([
            ['fab fa-facebook-f',getSetting('social_facebook','#')],
            ['fab fa-instagram',getSetting('social_instagram','#')],
            ['fab fa-twitter',getSetting('social_twitter','#')],
            ['fab fa-youtube',getSetting('social_youtube','#')],
            ['fab fa-tiktok',getSetting('social_tiktok','#')],
          ] as $s): ?>
          <a href="<?= e($s[1]) ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full flex items-center justify-center text-white/40 hover:text-emerald-400 transition-all text-sm" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
            <i class="<?= $s[0] ?>"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Quick links -->
      <div>
        <h4 class="font-nav font-700 text-white text-sm uppercase tracking-widest mb-4">Quick Links</h4>
        <ul class="space-y-2.5">
          <?php
          $_fNav = $_navItems ?? $_pNavItems ?? [];
          foreach ($_fNav as $_fKey => $_fItem):
            $_fLabel = $_fItem['mob'] ?? $_fItem['desk'] ?? $_fItem['label'] ?? '';
          ?>
          <li><a href="<?= e($_fItem['url']) ?>" class="text-white/45 hover:text-emerald-400 text-sm transition-colors"><?= e($_fLabel) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <!-- Tours -->
      <div>
        <h4 class="font-nav font-700 text-white text-sm uppercase tracking-widest mb-4">Safari Types</h4>
        <ul class="space-y-2.5">
          <?php foreach (['Wildlife Safari','Cultural Tour','Trekking','Beach Holiday','Honeymoon Safari','Family Safari'] as $t): ?>
          <li><a href="<?= url('tours.php?tour_type='.urlencode($t)) ?>" class="text-white/45 hover:text-emerald-400 text-sm transition-colors"><?= $t ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <!-- Contact -->
      <div>
        <h4 class="font-nav font-700 text-white text-sm uppercase tracking-widest mb-4">Contact Us</h4>
        <ul class="space-y-3">
          <li class="flex items-start gap-3 text-white/45 text-sm"><i class="fas fa-map-marker-alt text-emerald-400 mt-0.5 text-xs"></i><a href="https://maps.app.goo.gl/cqcERfdGpABg9xo49" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);text-decoration:none" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='rgba(255,255,255,.45)'">Arusha, Tanzania 12105</a></li>
          <li class="flex items-start gap-3 text-sm"><a href="tel:<?= e(SITE_PHONE) ?>" class="text-white/45 hover:text-emerald-400 transition-colors flex items-start gap-3"><i class="fas fa-phone text-emerald-400 mt-0.5 text-xs"></i><?= e(SITE_PHONE) ?></a></li>
          <li class="flex items-start gap-3 text-sm"><a href="mailto:<?= e(SITE_EMAIL) ?>" class="text-white/45 hover:text-emerald-400 transition-colors flex items-start gap-3"><i class="fas fa-envelope text-emerald-400 mt-0.5 text-xs"></i><?= e(SITE_EMAIL) ?></a></li>
        </ul>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 mt-4 font-nav font-semibold text-xs px-4 py-2 rounded-lg transition-all hover:scale-105"
           style="color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.2)">
          <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
        </a>
      </div>
    </div>
    <div class="border-t border-white/[.07] pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-white/25 text-xs font-nav">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved. &middot; Built by <span class="text-emerald-400/70">hiddenxcel</span></p>
      <div class="flex items-center gap-1.5">
        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
        <p class="text-white/25 text-xs font-nav">Secure booking · SSL encrypted · TATO Licensed</p>
      </div>
    </div>
  </div>
</footer>

<!-- WhatsApp float -->
<a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" id="wa-float" class="wa-float" target="_blank" rel="noopener" aria-label="WhatsApp">
  <i class="fab fa-whatsapp text-2xl relative z-10"></i>
</a>

<!-- Back to top -->
<button id="back-top" aria-label="Back to top"><i class="fas fa-chevron-up text-sm"></i></button>

<!-- Shared JS (navbar handled by public_navbar.php) -->
<script>
(function(){
  /* Stubs for any remaining old navbar references */
  if (!window.openNavSearch)  window.openNavSearch  = function(){ if(window.pnavOpenSearch)  pnavOpenSearch();  };
  if (!window.closeNavSearch) window.closeNavSearch = function(){ if(window.pnavCloseSearch) pnavCloseSearch(); };
  if (!window.doNavSearch)    window.doNavSearch    = function(){
    var inp = document.getElementById('pnav-s-input') || document.getElementById('nav-search-input');
    var q   = inp ? inp.value.trim() : '';
    if (q) window.location = (window.SITE_URL||'') + '/tours.php?search=' + encodeURIComponent(q);
  };

  /* Back to top */
  const btt = document.getElementById('back-top');
  window.addEventListener('scroll', () => btt && btt.classList.toggle('visible', scrollY > 400), { passive: true });
  btt && btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* WhatsApp float */
  const wa = document.getElementById('wa-float');
  if (wa) setTimeout(() => wa.classList.add('visible'), 2500);

  /* Reveal on scroll */
  const revObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revObs.unobserve(e.target); } });
  }, { threshold: 0.07 });
  document.querySelectorAll('.reveal').forEach(el => revObs.observe(el));

})();
</script>

<?= $pageScript ?? '' ?>

<!-- Lenis Smooth Scroll -->
<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
<script>
(function(){
  /* Skip on admin pages */
  if (window.location.pathname.includes('/admin/')) return;

  const lenis = new Lenis({
    duration: 1.3,
    easing: function(t){ return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
    smooth: true,
    mouseMultiplier: 0.8,
    smoothTouch: false,
    touchMultiplier: 2,
  });

  function raf(time){ lenis.raf(time); requestAnimationFrame(raf); }
  requestAnimationFrame(raf);

  /* Expose globally so anchor links work smoothly */
  window._lenis = lenis;

  /* Smooth anchor scroll */
  document.querySelectorAll('a[href^="#"]').forEach(function(a){
    a.addEventListener('click', function(e){
      var id = this.getAttribute('href');
      if (id === '#') return;
      var el = document.querySelector(id);
      if (el){ e.preventDefault(); lenis.scrollTo(el, { offset: -80, duration: 1.4 }); }
    });
  });
})();
</script>

</body>
</html>
