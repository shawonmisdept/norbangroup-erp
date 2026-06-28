const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const SW_URL = '/employee/sw.js';
const SW_SCOPE = '/employee/';

window.__empPwaInstallPrompt = window.__empPwaInstallPrompt ?? null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.__empPwaInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('emp-pwa-install-available'));
});

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);

    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}

function detectContentEncoding(subscription) {
    if (subscription?.contentEncoding) {
        return subscription.contentEncoding;
    }

    return 'aes128gcm';
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    try {
        return await navigator.serviceWorker.register(SW_URL, { scope: SW_SCOPE });
    } catch (error) {
        console.warn('Service worker registration failed', error);

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
            contentEncoding: detectContentEncoding(subscription),
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
        throw new Error('Notification permission denied. Allow notifications in browser site settings.');
    }

    const registration = await registerServiceWorker();

    if (! registration) {
        throw new Error('Service worker registration failed');
    }

    await subscribeToPush(registration);
}

export async function disableEmployeePush() {
    const registration = await navigator.serviceWorker.getRegistration(SW_URL);

    if (registration) {
        await unsubscribeFromPush(registration);
    }
}

export function isStandalonePwa() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

export function hasInstallPrompt() {
    return !! window.__empPwaInstallPrompt;
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

            this.syncState();

            window.addEventListener('emp-pwa-install-available', () => {
                this.syncState();
            });
        },

        syncState() {
            this.canPush = 'Notification' in window && 'PushManager' in window
                && Notification.permission !== 'granted'
                && !! document.querySelector('meta[name="vapid-public-key"]')?.content;

            this.canInstall = hasInstallPrompt() && ! isStandalonePwa();
            this.visible = this.canInstall || this.canPush;
        },

        async installApp() {
            const prompt = window.__empPwaInstallPrompt;

            if (! prompt) {
                this.message = 'Install not ready. Use Chrome menu (⋮) → Install Employee Portal.';

                return;
            }

            await prompt.prompt();
            const { outcome } = await prompt.userChoice;

            window.__empPwaInstallPrompt = null;
            this.canInstall = false;

            if (outcome === 'accepted') {
                this.message = 'App installed.';
            }

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

function shouldInitEmployeePwa() {
    return window.location.pathname.startsWith('/employee');
}

document.addEventListener('DOMContentLoaded', () => {
    if (shouldInitEmployeePwa()) {
        initEmployeePwa();
    }
});
