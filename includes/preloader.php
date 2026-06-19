<?php /* Site-wide page-load preloader — include once, immediately after <body> */ ?>
<div id="page-preloader" role="status" aria-label="Loading">
  <div class="loading">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>
<noscript><style>#page-preloader{display:none!important}</style></noscript>
<style>
#page-preloader{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;background:#0a0a0a;transition:opacity .5s ease,visibility .5s ease}
#page-preloader.is-hidden{opacity:0;visibility:hidden;pointer-events:none}
#page-preloader .loading{--speed-of-animation:0.9s;display:flex;justify-content:center;align-items:center;width:100px;height:100px;gap:6px}
#page-preloader .loading span{width:4px;height:50px;background:#10b981;animation:plBarScale var(--speed-of-animation) ease-in-out infinite}
#page-preloader .loading span:nth-child(2){background:#34d399;animation-delay:-0.8s}
#page-preloader .loading span:nth-child(3){background:#6ee7b7;animation-delay:-0.7s}
#page-preloader .loading span:nth-child(4){background:#059669;animation-delay:-0.6s}
#page-preloader .loading span:nth-child(5){background:#047857;animation-delay:-0.5s}
@keyframes plBarScale{0%,40%,100%{transform:scaleY(.05)}20%{transform:scaleY(1)}}
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
    navigator.serviceWorker.register('<?= SITE_URL ?>/sw.js');
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
