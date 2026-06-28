export function registerConfirmDialog(Alpine) {
    Alpine.data('confirmDialog', () => ({
        open: false,
        title: 'Are you sure?',
        message: '',
        confirmText: 'Yes, continue',
        cancelText: 'Cancel',
        variant: 'danger',
        _resolve: null,

        init() {
            window.__confirmDialog = this;
        },

        show(options = {}) {
            return new Promise((resolve) => {
                this._resolve = resolve;
                this.title = options.title ?? 'Are you sure?';
                this.message = options.message ?? '';
                this.confirmText = options.confirmText ?? 'Yes, continue';
                this.cancelText = options.cancelText ?? 'Cancel';
                this.variant = options.variant ?? 'danger';
                this.open = true;
            });
        },

        confirm() {
            this.open = false;
            this._resolve?.(true);
            this._resolve = null;
        },

        cancel() {
            this.open = false;
            this._resolve?.(false);
            this._resolve = null;
        },

        onBackdropClick() {
            this.cancel();
        },
    }));
}

export function initConfirmHandlers() {
    document.addEventListener('keydown', (event) => {
        const dialog = window.__confirmDialog;

        if (! dialog?.open) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            dialog.cancel();
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            dialog.confirm();
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (! form || form.dataset.confirmSubmitting === '1') {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const dialog = window.__confirmDialog;

        if (! dialog) {
            form.submit();
            return;
        }

        const ok = await dialog.show({
            title: form.dataset.confirmTitle || 'Are you sure?',
            message: form.dataset.confirm || '',
            variant: form.dataset.confirmVariant || 'danger',
            confirmText: form.dataset.confirmOk || 'Yes, continue',
            cancelText: form.dataset.confirmCancel || 'Cancel',
        });

        if (ok) {
            form.dataset.confirmSubmitting = '1';
            form.submit();
        }
    }, true);

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-confirm-click]');

        if (! trigger || trigger.dataset.confirmSubmitting === '1') {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const dialog = window.__confirmDialog;

        if (! dialog) {
            return;
        }

        const ok = await dialog.show({
            title: trigger.dataset.confirmTitle || 'Are you sure?',
            message: trigger.dataset.confirmClick || trigger.dataset.confirm || '',
            variant: trigger.dataset.confirmVariant || 'danger',
            confirmText: trigger.dataset.confirmOk || 'Yes, continue',
            cancelText: trigger.dataset.confirmCancel || 'Cancel',
        });

        if (! ok) {
            return;
        }

        const formId = trigger.dataset.confirmForm;
        const href = trigger.dataset.confirmHref;

        if (formId) {
            const form = document.getElementById(formId);

            if (form) {
                form.dataset.confirmSubmitting = '1';
                form.requestSubmit();
            }

            return;
        }

        if (href) {
            window.location.href = href;
        }
    }, true);
}
