<div x-data="confirmDialog()" x-init="init()" x-cloak>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="confirm-dialog-backdrop"
         @click.self="onBackdropClick()"
         @keydown.escape.window="open && cancel()">
        <div class="confirm-dialog-card"
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             role="dialog"
             aria-modal="true"
             :aria-label="title">
            <div class="confirm-dialog-icon"
                 :class="{
                    'confirm-dialog-icon-danger': variant === 'danger',
                    'confirm-dialog-icon-warning': variant === 'warning',
                    'confirm-dialog-icon-primary': variant === 'primary',
                 }">
                <svg x-show="variant === 'danger'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <svg x-show="variant === 'warning'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                </svg>
                <svg x-show="variant === 'primary'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h3 class="confirm-dialog-title" x-text="title"></h3>
            <p class="confirm-dialog-message" x-text="message"></p>

            <div class="confirm-dialog-actions">
                <button type="button" class="confirm-dialog-btn-cancel" @click="cancel()" x-text="cancelText"></button>
                <button type="button"
                        class="confirm-dialog-btn-confirm"
                        :class="{
                            'confirm-dialog-btn-danger': variant === 'danger',
                            'confirm-dialog-btn-warning': variant === 'warning',
                            'confirm-dialog-btn-primary': variant === 'primary',
                        }"
                        @click="confirm()"
                        x-text="confirmText"></button>
            </div>
        </div>
    </div>
</div>
