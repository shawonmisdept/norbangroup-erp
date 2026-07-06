const RING_RADIUS = 54;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

function formatElapsed(totalMinutes) {
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;

    return `${hours}h ${mins}m`;
}

export function registerEmployeeCheckIn(Alpine) {
    Alpine.data('checkInWidget', (config = {}) => ({
        checkInIso: config.checkInIso ?? null,
        checkOutIso: config.checkOutIso ?? null,
        shiftMinutes: Number(config.shiftMinutes ?? 480),
        shiftLabel: config.shiftLabel ?? '8-hour shift',
        status: config.status ?? 'idle',
        nextAction: config.nextAction ?? 'in',
        checkInLabel: config.checkInLabel ?? '',
        checkOutLabel: config.checkOutLabel ?? '',
        workMinutes: Number(config.workMinutes ?? 0),
        elapsedLabel: '0h 0m',
        ringOffset: RING_CIRCUMFERENCE,
        progress: 0,
        timer: null,

        init() {
            this.tick();

            if (this.status === 'active') {
                this.timer = setInterval(() => this.tick(), 30000);
            }
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        },

        tick() {
            if (! this.checkInIso) {
                this.elapsedLabel = '0h 0m';
                this.progress = 0;
                this.ringOffset = RING_CIRCUMFERENCE;

                return;
            }

            const start = new Date(this.checkInIso);
            const end = this.checkOutIso ? new Date(this.checkOutIso) : new Date();
            const elapsedMinutes = this.status === 'done' && this.workMinutes > 0
                ? this.workMinutes
                : Math.max(0, Math.floor((end.getTime() - start.getTime()) / 60000));

            this.elapsedLabel = formatElapsed(elapsedMinutes);
            this.progress = this.shiftMinutes > 0
                ? Math.min(elapsedMinutes / this.shiftMinutes, 1)
                : 0;
            this.ringOffset = RING_CIRCUMFERENCE * (1 - this.progress);
        },

        statusText() {
            if (this.status === 'done' && this.checkInLabel && this.checkOutLabel) {
                return `Checked out at ${this.checkOutLabel}`;
            }

            if (this.status === 'active' && this.checkInLabel) {
                return `Checked in at ${this.checkInLabel}`;
            }

            if (this.status === 'idle') {
                return 'Not checked in yet today';
            }

            return '';
        },
    }));

    Alpine.data('checkInPage', (config = {}) => ({
        punchType: config.punchType ?? 'in',
        gateToken: config.gateToken ?? '',
        storeUrl: config.storeUrl ?? '',
        hasPhoto: false,
        hasGps: false,
        submitting: false,
        stream: null,
        gpsStatus: 'Waiting…',
        gpsCoords: '—',

        init() {
            this.$nextTick(() => {
                this.fetchGps();
            });
        },

        destroy() {
            this.stopCamera();
        },

        canSubmit() {
            if (this.submitting || ! this.hasGps) {
                return false;
            }

            return this.punchType !== 'in' || this.hasPhoto;
        },

        updateSubmit() {
            return this.canSubmit();
        },

        setGps(lat, lng) {
            this.$refs.latInput.value = lat;
            this.$refs.lngInput.value = lng;
            this.gpsCoords = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            this.gpsStatus = 'Ready';
            this.hasGps = true;
        },

        fetchGps() {
            this.gpsStatus = 'Locating…';

            if (! navigator.geolocation) {
                this.gpsStatus = 'GPS not supported';

                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => this.setGps(pos.coords.latitude, pos.coords.longitude),
                () => {
                    this.gpsStatus = 'GPS denied';
                    this.hasGps = false;
                },
                { enableHighAccuracy: true, timeout: 15000 },
            );
        },

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                });
                this.$refs.video.srcObject = this.stream;
                this.$refs.placeholder?.classList.add('hidden');
                this.$refs.startCamera?.classList.add('hidden');
                this.$refs.capturePhoto?.classList.remove('hidden');
            } catch {
                alert('Camera access denied. Please allow camera permission.');
            }
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.snapshot;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.$refs.photoInput.value = canvas.toDataURL('image/jpeg', 0.85);
            this.hasPhoto = true;
            this.$refs.capturePhoto.textContent = 'Selfie captured ✓';
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
        },

        scrollToGps() {
            this.$refs.gpsPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        scrollToVerify() {
            if (this.punchType === 'out') {
                this.scrollToGps();

                return;
            }

            this.$refs.verifyPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        async submitPunch() {
            if (! this.canSubmit()) {
                this.scrollToVerify();

                return;
            }

            if (this.punchType === 'out') {
                const dialog = window.__confirmDialog;

                if (dialog) {
                    const ok = await dialog.show({
                        title: 'Confirm check-out?',
                        message: 'You are about to check out. Make sure you have finished work for today and are still within the allowed factory area.',
                        confirmText: 'Yes, check out',
                        cancelText: 'Cancel',
                        variant: 'danger',
                    });

                    if (! ok) {
                        return;
                    }
                }

                this.$refs.photoInput.value = '';
            }

            this.submitting = true;
            this.stopCamera();
            this.$refs.form.submit();
        },
    }));
}
