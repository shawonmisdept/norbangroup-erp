import Alpine from 'alpinejs';

Alpine.data('referenceFiles', () => ({
    techpack: [{ id: 1, fileName: null, previewUrl: null, isPdf: false }],
    artwork: [{ id: 2, fileName: null, previewUrl: null, isPdf: false }],
    nextId: 3,

    addTechpack() {
        this.techpack.push({ id: this.nextId++, fileName: null, previewUrl: null, isPdf: false });
    },

    addArtwork() {
        this.artwork.push({ id: this.nextId++, fileName: null, previewUrl: null, isPdf: false });
    },

    onSelect(slot, event) {
        const file = event.target.files[0];

        if (slot.previewUrl) {
            URL.revokeObjectURL(slot.previewUrl);
        }

        if (! file) {
            slot.fileName = null;
            slot.previewUrl = null;
            slot.isPdf = false;
            return;
        }

        slot.fileName = file.name;
        slot.isPdf = file.type === 'application/pdf';
        slot.previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    },

    canAddMore(slots) {
        return slots.some((slot) => slot.fileName);
    },
}));

Alpine.data('rolePermissions', (selected = []) => ({
    selected: [...selected],

    isChecked(key) {
        return this.selected.includes(key);
    },

    toggle(key, checked) {
        if (checked) {
            if (! this.selected.includes(key)) {
                this.selected.push(key);
            }
        } else {
            this.selected = this.selected.filter((item) => item !== key);
        }
    },

    setGroup(keys, checked) {
        keys.forEach((key) => this.toggle(key, checked));
        this.$nextTick(() => {
            keys.forEach((key) => {
                const input = this.$root.querySelector('[data-permission="' + key + '"]');

                if (input) {
                    input.checked = checked;
                }
            });
        });
    },

    groupKeys(groupName) {
        return Array.from(this.$root.querySelectorAll('[data-group="' + groupName + '"]'))
            .map((el) => el.dataset.permission);
    },
}));

Alpine.data('toastHub', () => ({
    toasts: [],
    nextId: 1,

    init() {
        const success = document.getElementById('flash-success');
        const error = document.getElementById('flash-error');

        if (success?.textContent) {
            this.push(success.textContent, 'success');
        }

        if (error?.textContent) {
            this.push(error.textContent, 'error');
        }
    },

    push(message, type = 'success', duration = 4500) {
        const id = this.nextId++;
        const toast = { id, message, type, visible: true };

        this.toasts.push(toast);

        if (duration > 0) {
            setTimeout(() => this.dismiss(id), duration);
        }
    },

    dismiss(id) {
        const toast = this.toasts.find((item) => item.id === id);

        if (toast) {
            toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter((item) => item.id !== id);
            }, 200);
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();
