<?php /* Site-wide page-load preloader — include once, immediately after <body> */ ?>
<div id="page-preloader" role="status" aria-label="Loading">
  <div class="loader-ring">
    <div class="loader-ring-inner"></div>
  </div>

</div>
<noscript><style>#page-preloader{display:none!important}</style></noscript>
<style>
#page-preloader{position:fixed;inset:0;z-index:100000;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.5rem;background:#0f0f0f;transition:opacity .45s ease,visibility .45s ease}
#page-preloader.is-hidden{opacity:0;visibility:hidden;pointer-events:none}
.loader-ring{width:44px;height:44px;border-radius:50%;border:3px solid rgba(160,94,34,.12);border-top-color:#a05e22;animation:loaderSpin .8s cubic-bezier(.4,0,.2,1) infinite}
@keyframes loaderSpin{to{transform:rotate(360deg)}}
.loader-brand{font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.25)}
</style>
<script>
(function(){
  var pl = document.getElementById('page-preloader');
  if (!pl) return;
  var shownAt = Date.now();
  var MIN_MS = 350;   /* avoid flashing on very fast loads */
  var MAX_MS = 6000;  /* safety fallback for slow/hanging assets */
  var done = false;
  function hide(){
    if (done) return;
    done = true;
    var wait = Math.max(0, MIN_MS - (Date.now() - shownAt));
    setTimeout(function(){
      pl.classList.add('is-hidden');
      setTimeout(function(){ pl.remove(); }, 600);
    }, wait);
  }
  if (document.readyState === 'complete') hide();
  else window.addEventListener('load', hide);
  setTimeout(hide, MAX_MS);
})();

/* Register service worker for installable PWA + offline support */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function(){
    navigator.serviceWorker.register('<?= SITE_URL ?>/sw.js').then(function(reg){
      reg.update();                       // always check for a newer SW
    });
    /* When a new SW takes control, reload once so stale pages are replaced */
    var _reloaded = false;
    navigator.serviceWorker.addEventListener('controllerchange', function(){
      if (_reloaded) return; _reloaded = true; window.location.reload();
    });
  });
}
</script>
<?php
$_tawkto = trim(getSetting('tawkto_widget_id'));
if ($_tawkto !== ''):
    if (stripos($_tawkto, '<script') !== false):
        /* Admin pasted the full Tawk.to embed snippet — output as-is */
        echo $_tawkto;
    else: ?>
<!-- Tawk.to live chat -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s2=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/<?= e($_tawkto) ?>';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s2.parentNode.insertBefore(s1,s2);
})();
</script>
<?php endif; endif; ?>
