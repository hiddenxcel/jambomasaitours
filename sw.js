/* Jambo Masai Tours — service worker (installable PWA + light offline support) */
const CACHE_NAME = 'jmt-cache-v3';
const STATIC_RE = /\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf)$/i;

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  /* Pages: ALWAYS network (never serve a stale cached page) — only fall back to
     cache when truly offline. This avoids stale links/markup during development. */
  if (req.mode === 'navigate' || url.pathname.endsWith('.php') || url.pathname.endsWith('/')
      || !STATIC_RE.test(url.pathname)) {
    event.respondWith(
      fetch(req).catch(() => caches.match(req))
    );
    return;
  }

  /* Static assets (CSS/JS/images/fonts, incl. CDN): cache-first for speed + offline */
  if (STATIC_RE.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return res;
        });
      })
    );
  }
});
