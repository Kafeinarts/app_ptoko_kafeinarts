const CACHE = 'kafeinarts-v1.6.0';
const ASSETS = [
  '/',
  '/offline.html',
  '/manifest.webmanifest',
  '/assets/css/app.css?v=1.5.0',
  '/assets/js/app.js?v=1.6.0',
  '/assets/vendor/adminlte.min.css',
  '/assets/vendor/adminlte.min.js',
  '/assets/vendor/bootstrap.bundle.min.js',
  '/assets/vendor/bootstrap-icons/bootstrap-icons.min.css',
  '/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
  '/assets/vendor/chart.umd.min.js',
  '/assets/vendor/sweetalert2.all.min.js',
  '/assets/vendor/sweetalert2.min.css',
  '/assets/vendor/fonts/source-sans-3-400.woff2',
  '/assets/vendor/fonts/source-sans-3-600.woff2',
  '/assets/vendor/fonts/source-sans-3-700.woff2',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/assets/icons/favicon.svg',
  '/assets/icons/logo-mark.svg',
  '/assets/icons/logo-light.svg',
  '/assets/img/product-placeholder.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/offline.html')))
    );
    return;
  }

  if (url.pathname.startsWith('/storage/') || url.pathname.startsWith('/assets/')) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        const copy = response.clone();
        caches.open(CACHE).then((cache) => cache.put(request, copy));
        return response;
      }).catch(() => cached))
    );
  }
});
