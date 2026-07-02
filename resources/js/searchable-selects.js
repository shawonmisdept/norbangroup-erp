import TomSelect from 'tom-select';

const ENHANCE_SELECTOR = 'select.erp-input, select.emp-input';
let scrollCloseBound = false;

function isVisible(el) {
    if (! el?.isConnected) {
        return false;
    }

    return el.getClientRects().length > 0;
}

function shouldEnhanceSelect(el) {
    if (el.tomselect || el.multiple || el.disabled) {
        return false;
    }

    if (el.dataset.searchable === 'false') {
        return false;
    }

    if (el.dataset.dynamicOptions === 'true') {
        return false;
    }

    if (el.dataset.searchable === 'true') {
        return true;
    }

    return el.options.length > 5;
}

function syncNativeToTomSelect(el) {
    if (! el?.tomselect) {
        return;
    }

    const val = el.value ?? '';

    if (String(el.tomselect.getValue() ?? '') !== String(val)) {
        el.tomselect.setValue(val, true);
    }
}

function closeOtherTomSelects(exceptEl) {
    document.querySelectorAll(ENHANCE_SELECTOR).forEach((select) => {
        if (select === exceptEl || ! select.tomselect?.isOpen) {
            return;
        }

        select.tomselect.close();
    });
}

function closeAllTomSelects() {
    document.querySelectorAll(ENHANCE_SELECTOR).forEach((select) => {
        select.tomselect?.close();
    });
}

function bindScrollClose() {
    if (scrollCloseBound) {
        return;
    }

    scrollCloseBound = true;
    window.addEventListener('scroll', closeAllTomSelects, { capture: true, passive: true });
}

function syncDropdownWidth(el, dropdown) {
    const wrapper = el.closest('.ts-wrapper') || el.parentElement;
    const control = wrapper?.querySelector('.ts-control');

    if (! control || ! dropdown) {
        return;
    }

    const { width } = control.getBoundingClientRect();

    dropdown.style.width = `${width}px`;
    dropdown.style.minWidth = `${width}px`;
}

export function enhanceSelect(el) {
    if (! shouldEnhanceSelect(el)) {
        return null;
    }

    if (el.tomselect) {
        return el.tomselect;
    }

    try {
        const ts = new TomSelect(el, {
            create: false,
            allowEmptyOption: true,
            maxOptions: 500,
            maxItems: 1,
            openOnFocus: true,
            closeAfterSelect: true,
            placeholder: el.dataset.placeholder || null,
            plugins: ['dropdown_input'],
            onChange(value) {
                el.value = value ?? '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            },
            onDropdownOpen(dropdown) {
                closeOtherTomSelects(el);
                syncDropdownWidth(el, dropdown);
            },
        });

        syncNativeToTomSelect(el);
        el.dataset.tsEnhanced = '1';
        bindScrollClose();

        return ts;
    } catch (error) {
        console.warn('Searchable select init failed:', el.name || el.id || el, error);

        return null;
    }
}

export function initSearchableSelects(root = document, { visibleOnly = true } = {}) {
    root.querySelectorAll(ENHANCE_SELECTOR).forEach((el) => {
        if (visibleOnly && ! isVisible(el)) {
            return;
        }

        enhanceSelect(el);
    });
}

export function destroySearchableSelect(el) {
    if (el?.tomselect) {
        el.tomselect.destroy();
        delete el.dataset.tsEnhanced;
    }
}

export function refreshSearchableSelect(el) {
    if (! el) {
        return;
    }

    destroySearchableSelect(el);
    enhanceSelect(el);
}

export function syncTomSelects(root = document) {
    root.querySelectorAll(ENHANCE_SELECTOR).forEach((el) => syncNativeToTomSelect(el));
}

window.initSearchableSelects = initSearchableSelects;
window.refreshSearchableSelect = refreshSearchableSelect;
window.syncTomSelects = syncTomSelects;
window.enhanceSelect = enhanceSelect;
window.destroySearchableSelect = destroySearchableSelect;

function bootSearchableSelects() {
    initSearchableSelects(document, { visibleOnly: true });
}

document.addEventListener('alpine:initialized', () => {
    requestAnimationFrame(bootSearchableSelects);
});

document.addEventListener('DOMContentLoaded', () => {
    if (window.Alpine) {
        return;
    }

    bootSearchableSelects();
});
