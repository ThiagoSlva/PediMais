// Service Worker para PWA - Entregas
const CACHE_NAME = 'entregas-pwa-v1';

self.addEventListener('install', function(event) {
    console.log('[Entregas SW] Service Worker instalado');
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    console.log('[Entregas SW] Service Worker ativado');
    return self.clients.claim();
});

// Manter service worker ativo
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
