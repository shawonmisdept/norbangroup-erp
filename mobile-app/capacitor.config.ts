import type { CapacitorConfig } from '@capacitor/cli';

/**
 * Production: loads live Laravel portal (web changes sync automatically).
 * Local dev: point server.url to your HTTPS tunnel or production.
 */
const config: CapacitorConfig = {
    appId: 'com.norbangroup.portal',
    appName: 'Norban Portal',
    webDir: 'dist',
    server: {
        url: 'https://portal.norbangroup.com/app',
        cleartext: false,
        androidScheme: 'https',
    },
    android: {
        allowMixedContent: false,
    },
    plugins: {
        SplashScreen: {
            launchAutoHide: true,
            androidSplashResourceName: 'splash',
            backgroundColor: '#0f2744',
        },
        StatusBar: {
            style: 'DARK',
            backgroundColor: '#0f2744',
        },
    },
};

export default config;
