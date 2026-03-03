// Zewalo PWA Service Worker
const CACHE_VERSION = 'zewalo-v1.5.1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const OFFLINE_URL = '/offline.html';

// Static assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/favicon.ico',
    '/css/app.css',
    '/js/app.js'
];

// API routes that should not be cached
const API_ROUTES = [
    '/livewire',
    '/api/',
    '/admin',
    '/login',
    '/logout',
    '/register',
    '/checkout',
    '/cart'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS.map(url => {
                    return new Request(url, { cache: 'reload' });
                })).catch(err => {
                    console.log('[SW] Some static assets failed to cache:', err);
                });
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            return cacheName.startsWith('zewalo-') && 
                                   cacheName !== STATIC_CACHE && 
                                   cacheName !== DYNAMIC_CACHE;
                        })
                        .map((cacheName) => {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - network first for API, cache first for static
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip API routes, admin panel, and auth routes
    if (API_ROUTES.some(route => url.pathname.includes(route))) {
        return;
    }

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Network first strategy for HTML pages
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Clone and cache the response
                    const responseClone = response.clone();
                    caches.open(DYNAMIC_CACHE)
                        .then((cache) => cache.put(request, responseClone));
                    return response;
                })
                .catch(() => {
                    return caches.match(request)
                        .then((cachedResponse) => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            return caches.match(OFFLINE_URL);
                        });
                })
        );
        return;
    }

    // Cache first strategy for static assets
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/)) {
        event.respondWith(
            caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        // Return cached and update in background
                        fetch(request)
                            .then((response) => {
                                caches.open(STATIC_CACHE)
                                    .then((cache) => cache.put(request, response));
                            })
                            .catch(() => {});
                        return cachedResponse;
                    }
                    
                    return fetch(request)
                        .then((response) => {
                            const responseClone = response.clone();
                            caches.open(STATIC_CACHE)
                                .then((cache) => cache.put(request, responseClone));
                            return response;
                        });
                })
        );
        return;
    }

    // Network first for everything else
    event.respondWith(
        fetch(request)
            .then((response) => {
                const responseClone = response.clone();
                caches.open(DYNAMIC_CACHE)
                    .then((cache) => cache.put(request, responseClone));
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// Background sync for offline form submissions (future enhancement)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-rentals') {
        console.log('[SW] Syncing rentals...');
        // Future: implement offline form submission sync
    }
});

// Push notifications (future enhancement)
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-72x72.png',
            vibrate: [100, 50, 100],
            data: {
                url: data.url || '/'
            }
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const urlToOpen = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(urlToOpen);
            })
    );
});
