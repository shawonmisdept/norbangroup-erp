self.addEventListener('fetch', () => {
    // Required for Chrome PWA installability; network pass-through.
});

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};

    try {
        payload = event.data.json();
    } catch {
        payload = { body: event.data.text() };
    }

    const title = payload.title || 'Employee Portal';
    const targetUrl = payload.data?.url || payload.url || '/employee/dashboard';

    event.waitUntil(self.registration.showNotification(title, {
        body: payload.body || '',
        icon: payload.icon || '/pwa/icon-192.png',
        badge: payload.badge || '/pwa/icon-192.png',
        data: { url: targetUrl },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/employee/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes('/employee') && 'focus' in client) {
                    client.navigate(url);

                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        }),
    );
});

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
