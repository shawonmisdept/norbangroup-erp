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

function pickDatasetValue(elements, key) {
    for (const el of elements) {
        if (! el?.dataset) {
            continue;
        }

        const value = el.dataset[key];

        if (value) {
            return value;
        }
    }

    return null;
}

function resolveConfirmOptions(form, submitter = null) {
    const sources = [form, submitter].filter(Boolean);

    return {
        message: pickDatasetValue(sources, 'confirm'),
        title: pickDatasetValue(sources, 'confirmTitle'),
        variant: pickDatasetValue(sources, 'confirmVariant'),
        confirmText: pickDatasetValue(sources, 'confirmOk'),
        cancelText: pickDatasetValue(sources, 'confirmCancel'),
    };
}

function injectSubmitterField(form, submitter) {
    if (! submitter?.name) {
        return;
    }

    form.querySelectorAll('[data-confirm-submitter]').forEach((el) => el.remove());

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = submitter.name;
    hidden.value = submitter.value;
    hidden.dataset.confirmSubmitter = '1';
    form.appendChild(hidden);
}

function resubmitConfirmedForm(form, submitter = null) {
    form.dataset.confirmSubmitting = '1';

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(submitter || undefined);

        return;
    }

    injectSubmitterField(form, submitter);
    form.submit();
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
        const form = event.target.closest('form');

        if (! form || form.dataset.confirmSubmitting === '1') {
            return;
        }

        const options = resolveConfirmOptions(form, event.submitter);

        if (! options.message) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const dialog = window.__confirmDialog;
        const submitter = event.submitter;

        if (! dialog) {
            resubmitConfirmedForm(form, submitter);
            return;
        }

        const ok = await dialog.show({
            title: options.title || 'Are you sure?',
            message: options.message,
            variant: options.variant || 'danger',
            confirmText: options.confirmText || 'Yes, continue',
            cancelText: options.cancelText || 'Cancel',
        });

        if (ok) {
            resubmitConfirmedForm(form, submitter);
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
