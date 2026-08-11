const CACHE_NAME = 'elricobajon-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/manifest.json',
    '/favicon.ico',
    '/img/Logo.png',
    '/img/icon-192.png',
    '/img/icon-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Network-First para HTML/API (para ver siempre saldos y pedidos en vivo) y Cache-First para estáticos
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Recursos estáticos (imágenes, fuentes, css, js) -> Cache First con fallback a Red
    if (url.pathname.match(/\.(png|jpg|jpeg|svg|ico|css|js|woff2?)$/i)) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                return cachedResponse || fetch(event.request).then((networkResponse) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                });
            })
        );
        return;
    }

    // Navegación (HTML/Vistas del CRM) -> Network First con fallback a Caché
    if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(event.request).then((networkResponse) => {
                return caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, networkResponse.clone());
                    return networkResponse;
                });
            }).catch(() => {
                return caches.match(event.request);
            })
        );
        return;
    }

    // Demás solicitudes: Network normal
    event.respondWith(fetch(event.request));
});

// ---------------------------------------------------------
// WEB PUSH NOTIFICATIONS
// ---------------------------------------------------------
self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    let data = { title: 'Nueva Notificación', body: 'Tienes un nuevo mensaje.', icon: '/img/icon-192.png', url: '/' };
    
    if (e.data) {
        try {
            const parsed = e.data.json();
            data.title = parsed.title || data.title;
            data.body = parsed.body || data.body;
            data.icon = parsed.icon || data.icon;
            data.url = parsed.data && parsed.data.url ? parsed.data.url : data.url;
        } catch (err) {
            data.body = e.data.text();
        }
    }

    var options = {
        body: data.body,
        icon: data.icon,
        vibrate: [100, 50, 100],
        data: {
            url: data.url
        }
    };

    e.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function (e) {
    var notification = e.notification;
    var action = e.action;
    var url = notification.data.url || '/';

    notification.close();

    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // Revisa si ya hay una pestaña abierta con esa URL o el mismo origen
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no está abierta, abre una nueva
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
