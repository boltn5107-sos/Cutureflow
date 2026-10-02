/*
 |--------------------------------------------------------------------------
 | Service worker — Couture+
 |--------------------------------------------------------------------------
 | Stratégies
 |  - navigation (HTML)  : network-first, repli sur le cache hors-ligne
 |  - assets statiques   : cache-first (/build/, /images/icons/, .css, .js, .woff2…)
 |
 | Garde-fous
 |  - les requêtes non-GET et les appels vers une autre origine ne sont jamais interceptés
 |  - /admin, /connexion, /deconnexion passent toujours par le réseau (jamais de copie hors-ligne)
 |  - aucune réponse 401 / 403 / 419 / 5xx n'est stockée ni servie depuis le cache
 */

const CACHE_NAME = 'coutureplus-v3';
const OFFLINE_FALLBACK = '/';

const PRECACHE_URLS = [
    OFFLINE_FALLBACK,
    '/manifest.json',
    '/favicon.svg',
    '/favicon.ico',
    '/images/icons/favicon.svg',
    '/build/manifest.json',
];

const NETWORK_ONLY_PREFIXES = [
    '/admin',
    '/connexion',
    '/deconnexion',
];

const STATIC_EXTENSIONS = /\.(?:css|js|mjs|map|woff2?|ttf|otf|eot|svg|png|jpe?g|gif|webp|avif|ico|json|webmanifest)$/i;

const STATIC_PREFIXES = ['/build/', '/images/icons/'];

self.addEventListener('install', (event) => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) =>
            // allSettled : un fichier absent ne doit jamais faire échouer l'installation.
            Promise.allSettled(
                PRECACHE_URLS.map((url) =>
                    fetch(new Request(url, { cache: 'reload', credentials: 'same-origin' }))
                        .then((response) => {
                            if (! isStorable(response)) {
                                return undefined;
                            }

                            return cache.put(url, response);
                        })
                        .catch(() => undefined)
                )
            )
        )
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cles) => Promise.all(
                cles.filter((cle) => cle !== CACHE_NAME).map((cle) => caches.delete(cle))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    let url;
    try {
        url = new URL(request.url);
    } catch (e) {
        return;
    }

    if (url.origin !== self.location.origin) {
        return;
    }

    if (isNetworkOnly(url.pathname)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
    }
});

/* ------------------------------------------------------ notifications push */

/*
 * Une notification reçue alors que l'application est fermée (ou simplement
 * en arrière-plan) devient une notification système du navigateur. Le payload
 * suit la même forme que la notification « database » : titre, message,
 * url et icône. Le clic rouvre l'application sur la page concernée.
 */
self.addEventListener('push', (event) => {
    let donnees = {};

    try {
        donnees = event.data ? event.data.json() : {};
    } catch (e) {
        donnees = {};
    }

    const options = {
        body: donnees.message || '',
        icon: donnees.icon || '/images/icons/icon-192.png',
        badge: '/images/icons/icon-192.png',
        data: { url: donnees.url || '/' },
    };

    event.waitUntil(
        self.registration.showNotification(donnees.title || 'Couture+', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    const url = event.notification.data?.url || '/';

    event.notification.close();

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }

            return self.clients.openWindow(url);
        })
    );
});

/* ---------------------------------------------------------------- strategies */

async function networkFirst(request) {
    const cache = await caches.open(CACHE_NAME);

    try {
        const response = await fetch(request);

        if (isStorable(response)) {
            cache.put(request, response.clone()).catch(() => undefined);
        }

        return response;
    } catch (error) {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        const fallback = await cache.match(OFFLINE_FALLBACK);

        if (fallback) {
            return fallback;
        }

        return offlineResponse();
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);

        if (isStorable(response)) {
            cache.put(request, response.clone()).catch(() => undefined);
        }

        return response;
    } catch (error) {
        if (cached) {
            return cached;
        }

        return Response.error();
    }
}

/* ------------------------------------------------------------------- helpers */

function isNetworkOnly(pathname) {
    return NETWORK_ONLY_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(prefix + '/'));
}

function isStaticAsset(pathname) {
    if (STATIC_PREFIXES.some((prefix) => pathname.startsWith(prefix))) {
        return true;
    }

    return STATIC_EXTENSIONS.test(pathname);
}

/** Ne jamais conserver une réponse d'authentification, d'erreur serveur ou explicitement non cachable. */
function isStorable(response) {
    if (! response || response.type === 'opaque' || response.type === 'opaqueredirect') {
        return false;
    }

    if (! response.ok) {
        return false;
    }

    if (response.status === 401 || response.status === 403 || response.status === 419) {
        return false;
    }

    if (response.status >= 500) {
        return false;
    }

    if ((response.headers.get('Cache-Control') || '').toLowerCase().includes('no-store')) {
        return false;
    }

    return true;
}

function offlineResponse() {
    return new Response(
        '<!doctype html><html lang="fr"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width, initial-scale=1">' +
        '<title>Hors connexion — Couture+</title></head>' +
        '<body style="font-family:system-ui,sans-serif;background:#fdfbf7;color:#382a11;padding:2.5rem;text-align:center">' +
        '<h1 style="font-size:1.25rem;margin:0 0 .5rem">Vous êtes hors connexion</h1>' +
        '<p style="font-size:.9rem;color:#6e5424;margin:0">Reconnectez-vous puis rechargez cette page.</p>' +
        '</body></html>',
        {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        }
    );
}
