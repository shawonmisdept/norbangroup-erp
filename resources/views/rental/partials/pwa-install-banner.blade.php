<div
    x-data="rentalPwaBanner()"
    x-show="visible"
    x-cloak
    class="emp-pwa-banner"
    role="region"
    aria-label="Install app and notifications"
>
    <div class="emp-pwa-banner-inner">
        <div class="min-w-0 flex-1">
            <p class="emp-pwa-banner-title">Install Driver App</p>
            <p class="emp-pwa-banner-text">Add to home screen and get trip alerts even when the browser is closed.</p>
        </div>
        <div class="emp-pwa-banner-actions">
            <button type="button" class="emp-pwa-btn emp-pwa-btn-primary" @click="installApp()" x-show="canInstall">
                Install
            </button>
            <button type="button" class="emp-pwa-btn emp-pwa-btn-secondary" @click="enablePush()" x-show="canPush">
                Enable alerts
            </button>
            <button type="button" class="emp-pwa-btn emp-pwa-btn-ghost" @click="dismiss()" aria-label="Dismiss">
                &times;
            </button>
        </div>
    </div>
    <p x-show="message" x-text="message" class="emp-pwa-banner-message"></p>
</div>
