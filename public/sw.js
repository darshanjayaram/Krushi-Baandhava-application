// Krushi Baandhava PWA Service Worker (v7 - Scheme Protection & Push Notifications Active)
const CACHE_NAME = 'krushi-baandhava-v7';
const STATIC_ASSETS = [
    './offline',
    './manifest.json',
    './icons/icon-192.png',
    './icons/icon-512.png',
    './icons/icon-192.svg',
    './icons/icon-512.svg'
];

// Precache App Shell and Offline Page
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return Promise.allSettled(
                STATIC_ASSETS.map((url) => cache.add(url).catch(() => {}))
            );
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

// Allow client pages to trigger immediate skip waiting on user refresh
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Intercept fetch requests
self.addEventListener('fetch', (event) => {
    // 1. Strictly ignore unsupported browser schemes (e.g. chrome-extension://, moz-extension://, file://, data:)
    if (!event.request || !event.request.url || (!event.request.url.startsWith('http://') && !event.request.url.startsWith('https://'))) {
        return;
    }

    // 2. Only handle GET requests and exclude admin routes
    if (event.request.method !== 'GET' || event.request.url.includes('/admin')) {
        return;
    }

    let url;
    try {
        url = new URL(event.request.url);
    } catch (e) {
        return;
    }

    // Strategy 1: Stale-while-revalidate for Vite build chunks & static icons
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.match(event.request).then((cachedResponse) => {
                    const fetchPromise = fetch(event.request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200 && event.request.url.startsWith('http')) {
                            cache.put(event.request, networkResponse.clone()).catch(() => {});
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
                if (response && response.status === 200 && event.request.url.startsWith('http')) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone).catch(() => {});
                    }).catch(() => {});
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
                        const offlineUrl = new URL('./offline', self.registration.scope).href;
                        return caches.match(offlineUrl).then((offlineFallback) => {
                            return offlineFallback || caches.match('./offline') || caches.match('/offline') || caches.match('/');
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
        icon: data.icon || '/icons/icon-192.svg',
        badge: data.badge || data.icon || '/icons/icon-192.svg',
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
