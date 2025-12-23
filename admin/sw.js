// Service Worker para PWA - Admin
const CACHE_NAME = 'admin-pwa-v1';

self.addEventListener('install', function(event) {
    console.log('[Admin SW] Service Worker instalado');
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    console.log('[Admin SW] Service Worker ativado');
    return self.clients.claim();
});

// Manter service worker ativo
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
