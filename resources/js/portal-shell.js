/**
 * Detect Capacitor / unified APK shell (hide PWA install banners, tune UX).
 */
export function isNativeShell() {
    if (window.Capacitor?.isNativePlatform?.()) {
        return true;
    }

    if (document.querySelector('meta[name="portal-shell"]')?.content === 'app') {
        return true;
    }

    try {
        return sessionStorage.getItem('portal-shell') === '1';
    } catch {
        return false;
    }
}

export function markPortalShell() {
    try {
        sessionStorage.setItem('portal-shell', '1');
    } catch {
        // ignore private browsing
    }
}
