/* Liquid-glass navigation — dock magnification + lens + pill inayopumua.
   Inatumia #p-nav / #p-nav-links ya includes/public_navbar.php. Hakuna maktaba za nje. */
(function () {
  'use strict';
  var nav = document.getElementById('p-nav');
  var ul = document.getElementById('p-nav-links');
  if (!nav || !ul) return;
  var inner = nav.querySelector('.pnav-inner');
  if (!inner) return;

  var fine = window.matchMedia('(hover:hover) and (pointer:fine)').matches;
  var reduce = window.matchMedia('(prefers-reduced-motion:reduce)').matches;

  var items = [].slice.call(ul.children).filter(function (li) { return li.tagName === 'LI' && li.firstElementChild && li.firstElementChild.tagName === 'A'; })
    .map(function (li) { return { li: li, a: li.firstElementChild, x: 0, w: 0, s: 1, tx: 0, ts: 1 }; });
  if (!items.length) return;

  var lens = document.createElement('span');
  lens.className = 'pnav-lens';
  lens.setAttribute('aria-hidden', 'true');
  ul.insertBefore(lens, ul.firstChild);
  nav.classList.add('pnav-liquid');

  var AMP = 0.17;          /* ukuaji wa juu wa kiungo kilicho chini ya mshale */
  var RADIUS = 125;        /* umbali (px) ambao jirani zinaathirika */
  var PAD = 3;             /* lens inazidi kiungo kidogo kila upande */

  var activeItem = null;
  items.forEach(function (it) { if (it.li.classList.contains('pnav-active')) activeItem = it; });

  /* ── vipimo (havibadiliki na transform) ── */
  function measure() {
    items.forEach(function (it) {
      it.x = it.li.offsetLeft + it.a.offsetLeft;
      it.w = it.a.offsetWidth;
    });
  }

  /* ── hali ── */
  var px = -9999, over = false, frozen = false, focusIt = null, pressed = null;
  var L = { x: 0, w: 0, vx: 0, vw: 0, tx: 0, tw: 0, init: false };
  var raf = 0;

  function nearest() {
    var best = null, bd = 1e9;
    items.forEach(function (it) {
      var d = Math.abs(px - (it.x + it.w / 2));
      if (d < bd) { bd = d; best = it; }
    });
    return best;
  }

  function targets() {
    var hot = over && px > -1000;
    var near = focusIt || (hot ? nearest() : null);
    var target = near || activeItem;
    items.forEach(function (it) {
      var s = 1;
      if (hot && !reduce) {
        var d = Math.abs(px - (it.x + it.w / 2));
        var t = Math.max(0, 1 - d / RADIUS);
        s = 1 + AMP * t * t * (3 - 2 * t);
      } else if (focusIt === it && !reduce) { s = 1 + AMP * 0.6; }
      if (pressed === it) s *= 0.93;
      it.ts = s;
    });
    /* offsets: jirani zinasogea ili zisigongane na kikundi kibaki katikati */
    var extra = 0;
    items.forEach(function (it) { extra += (it.s - 1) * it.w; });
    var run = 0;
    items.forEach(function (it) {
      it.tx = run + (it.s - 1) * it.w / 2 - extra / 2;
      run += (it.s - 1) * it.w;
    });
    if (target) {
      /* lens inalingana na kiungo kilichokua: kituo chake + ukubwa wake */
      L.tx = target.x + target.tx - (target.w * target.s - target.w) / 2 - PAD / 2;
      L.tw = target.w * target.s + PAD;
    }
    lens.classList.toggle('on', !!target);
    lens.classList.toggle('rest', !near);
    items.forEach(function (it) { it.li.classList.toggle('pnav-near', it === near); });
    return target;
  }

  function frame() {
    raf = 0;
    var target = targets();
    var moving = false;

    /* kiungo: lerp laini (hakuna CSS transition kwenye transform) */
    items.forEach(function (it) {
      var ds = it.ts - it.s;
      it.s += ds * 0.24;
      if (Math.abs(ds) > 0.0006) moving = true; else it.s = it.ts;
      it.a.style.transform = (Math.abs(it.s - 1) < 0.0005 && Math.abs(it.tx) < 0.05) ? '' : 'translate3d(' + it.tx.toFixed(2) + 'px,0,0) scale(' + it.s.toFixed(4) + ')';
    });

    /* lens: spring yenye overshoot kidogo ("kimiminika") */
    if (target) {
      if (!L.init || reduce) { L.x = L.tx; L.w = L.tw; L.vx = L.vw = 0; L.init = true; }
      var k = 0.2, c = 0.74;
      L.vx = (L.vx + (L.tx - L.x) * k) * c; L.x += L.vx;
      L.vw = (L.vw + (L.tw - L.w) * k) * c; L.w += L.vw;
      if (Math.abs(L.vx) > 0.04 || Math.abs(L.vw) > 0.04 || Math.abs(L.tx - L.x) > 0.3 || Math.abs(L.tw - L.w) > 0.3) moving = true;
      else { L.x = L.tx; L.w = L.tw; }
      /* squash & stretch ndogo wakati wa kusogea */
      var stretch = 1 + Math.min(Math.abs(L.vx) / 90, 0.12);
      lens.style.transform = 'translate3d(' + L.x.toFixed(2) + 'px,0,0) scaleY(' + (2 - stretch).toFixed(3) + ')';
      lens.style.width = Math.max(L.w, 0).toFixed(2) + 'px';
    }
    if (moving || over) raf = requestAnimationFrame(frame);
  }
  function kick() { if (!raf) raf = requestAnimationFrame(frame); }

  /* ── matukio ya mshale ── */
  if (fine) {
    inner.addEventListener('pointerenter', function () { nav.classList.add('pnav-hot'); });
    inner.addEventListener('pointerleave', function () {
      nav.classList.remove('pnav-hot');
      over = false; frozen = false; px = -9999; pressed = null; kick();
    });
    inner.addEventListener('pointermove', function (e) {
      var ir = inner.getBoundingClientRect();
      nav.style.setProperty('--mx', (e.clientX - ir.left).toFixed(0) + 'px');
      /* mshale ndani ya dropdown ya mega: gandisha hali ya sasa */
      if (e.target.closest && e.target.closest('.pnav-mega-drop')) { frozen = true; return; }
      frozen = false;
      var ur = ul.getBoundingClientRect();
      var inLinks = e.clientX >= ur.left - 8 && e.clientX <= ur.right + 8 && e.clientY >= ur.top && e.clientY <= ur.bottom;
      over = inLinks;
      px = e.clientX - ur.left;
      kick();
    });
    ul.addEventListener('pointerdown', function (e) {
      var li = e.target.closest && e.target.closest('li');
      items.forEach(function (it) { if (it.li === li) pressed = it; });
      kick();
    });
    window.addEventListener('pointerup', function () { if (pressed) { pressed = null; kick(); } });
  }

  /* kibodi: lens inafuata fokasi */
  ul.addEventListener('focusin', function (e) {
    var li = e.target.closest && e.target.closest('li');
    items.forEach(function (it) { if (it.li === li) focusIt = it; });
    kick();
  });
  ul.addEventListener('focusout', function () { focusIt = null; kick(); });

  /* ── vipimo vinapobadilika (resize, pill kuwa solid, fonts) ── */
  function remeasure() { measure(); L.init = false; kick(); }
  var rt = 0;
  window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(remeasure, 80); }, { passive: true });
  /* Mpangilio ukibadilika kwa sababu yoyote (pill kuwa solid, jina la nembo kuonekana, fonts, resize)
     → pima upya. ResizeObserver inashika yote, pamoja na wakati wa transition ya pill. */
  if ('ResizeObserver' in window) {
    var pend = 0;
    var ro = new ResizeObserver(function () {
      if (pend) return;
      pend = requestAnimationFrame(function () { pend = 0; remeasure(); });
    });
    ro.observe(ul);
    items.forEach(function (it) { ro.observe(it.a); });
  }
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(remeasure);
  window.addEventListener('load', remeasure);
  remeasure();
  nav._liquid = { items: items, measure: remeasure }; /* kwa ukaguzi (DevTools) */
})();
