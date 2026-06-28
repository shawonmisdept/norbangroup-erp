const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);

    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    try {
        return await navigator.serviceWorker.register('/sw.js', { scope: '/' });
    } catch {
        return null;
    }
}

async function getVapidPublicKey() {
    const meta = document.querySelector('meta[name="vapid-public-key"]');

    if (meta?.content) {
        return meta.content;
    }

    const response = await fetch('/employee/push/vapid-public-key', {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error('Unable to load push configuration');
    }

    const data = await response.json();

    return data.publicKey;
}

async function subscribeToPush(registration) {
    const publicKey = await getVapidPublicKey();

    if (! publicKey) {
        throw new Error('Push notifications are not configured');
    }

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
    });

    const payload = subscription.toJSON();

    const response = await fetch('/employee/push/subscribe', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            endpoint: payload.endpoint,
            keys: payload.keys,
            contentEncoding: 'aesgcm',
        }),
    });

    if (! response.ok) {
        throw new Error('Failed to save push subscription');
    }

    localStorage.setItem('emp-push-enabled', '1');

    return subscription;
}

async function unsubscribeFromPush(registration) {
    const subscription = await registration.pushManager.getSubscription();

    if (! subscription) {
        localStorage.removeItem('emp-push-enabled');

        return;
    }

    await fetch('/employee/push/unsubscribe', {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ endpoint: subscription.endpoint }),
    });

    await subscription.unsubscribe();
    localStorage.removeItem('emp-push-enabled');
}

export async function enableEmployeePush() {
    if (!('Notification' in window) || !('PushManager' in window)) {
        throw new Error('Push notifications are not supported on this device');
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        throw new Error('Notification permission denied');
    }

    const registration = await registerServiceWorker();

    if (! registration) {
        throw new Error('Service worker registration failed');
    }

    await subscribeToPush(registration);
}

export async function disableEmployeePush() {
    const registration = await navigator.serviceWorker.getRegistration('/sw.js');

    if (registration) {
        await unsubscribeFromPush(registration);
    }
}

export function isStandalonePwa() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

export function canInstallPwa() {
    return 'BeforeInstallPromptEvent' in window || isStandalonePwa();
}

export async function initEmployeePwa() {
    const registration = await registerServiceWorker();

    if (! registration) {
        return;
    }

    if (Notification.permission === 'granted' && localStorage.getItem('emp-push-enabled') === '1') {
        const existing = await registration.pushManager.getSubscription();

        if (! existing) {
            try {
                await subscribeToPush(registration);
            } catch {
                localStorage.removeItem('emp-push-enabled');
            }
        }
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__empPwaInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('emp-pwa-install-available'));
    });
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('employeePwaBanner', () => ({
        visible: false,
        canInstall: false,
        canPush: false,
        message: '',
        dismissed: localStorage.getItem('emp-pwa-banner-dismissed') === '1',

        init() {
            if (this.dismissed) {
                return;
            }

            this.canPush = 'Notification' in window && 'PushManager' in window
                && Notification.permission !== 'granted'
                && !! document.querySelector('meta[name="vapid-public-key"]')?.content;

            this.canInstall = ! isStandalonePwa();
            this.visible = this.canInstall || this.canPush;

            window.addEventListener('emp-pwa-install-available', () => {
                this.canInstall = true;
                this.visible = true;
            });
        },

        async installApp() {
            const prompt = window.__empPwaInstallPrompt;

            if (! prompt) {
                this.message = 'Use your browser menu: Add to Home Screen / Install app.';

                return;
            }

            prompt.prompt();
            await prompt.userChoice;
            window.__empPwaInstallPrompt = null;
            this.canInstall = false;
            this.updateVisibility();
        },

        async enablePush() {
            try {
                await enableEmployeePush();
                this.canPush = false;
                this.message = 'Notifications enabled.';
                this.updateVisibility();
            } catch (error) {
                this.message = error?.message ?? 'Could not enable notifications.';
            }
        },

        dismiss() {
            localStorage.setItem('emp-pwa-banner-dismissed', '1');
            this.visible = false;
        },

        updateVisibility() {
            this.visible = (this.canInstall || this.canPush) && ! this.dismissed;
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('emp-app')) {
        initEmployeePwa();
    }
});
