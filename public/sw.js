// Krushi Baandhava PWA Service Worker (v2)
const CACHE_NAME = 'krushi-baandhava-v2';
const STATIC_ASSETS = [
    '/',
    '/offline',
    '/manifest.json',
    '/icons/icon-192.svg',
    '/icons/icon-512.svg'
];

// Precache App Shell and Offline Page
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('Pre-cache partial failure:', err);
            });
        })
    );
    self.skipWaiting();
});

// Clean up stale caches from previous versions
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Intercept fetch requests
self.addEventListener('fetch', (event) => {
    // Only handle GET requests and exclude admin routes
    if (event.request.method !== 'GET' || event.request.url.includes('/admin')) {
        return;
    }

    const url = new URL(event.request.url);

    // Strategy 1: Stale-while-revalidate for Vite build chunks & static icons
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.match(event.request).then((cachedResponse) => {
                    const fetchPromise = fetch(event.request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(() => cachedResponse);

                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // Strategy 2: Network-first with offline cache fallback for farmer HTML views
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // For document navigation requests when completely offline, show dedicated offline screen
                    if (event.request.mode === 'navigate' || (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'))) {
                        return caches.match('/offline').then((offlineFallback) => {
                            return offlineFallback || caches.match('/');
                        });
                    }
                    return null;
                });
            })
    );
});

// Push Notifications Listener
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'Krushi Baandhava', body: event.data ? event.data.text() : 'Market price updates available.' };
    }

    const title = data.title || 'Krushi Baandhava - ಕೃಷಿ ಬಾಂಧವ';
    const options = {
        body: data.body || 'New agricultural mandi rates and forecasts are available.',
        icon: '/icons/icon-192.svg',
        badge: '/icons/icon-192.svg',
        data: { url: data.url || '/' }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Notification Click Handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
