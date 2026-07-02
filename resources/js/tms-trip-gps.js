function fillGpsFields(form) {
    const lat = form.querySelector('input[name="latitude"]');
    const lng = form.querySelector('input[name="longitude"]');
    const accuracy = form.querySelector('input[name="accuracy_m"]');
    const status = form.querySelector('[data-role="gps-status"]');

    if (! lat || ! lng || !('geolocation' in navigator)) {
        return Promise.resolve(false);
    }

    return new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                lat.value = String(position.coords.latitude);
                lng.value = String(position.coords.longitude);

                if (accuracy) {
                    accuracy.value = position.coords.accuracy
                        ? String(position.coords.accuracy)
                        : '';
                }

                if (status) {
                    status.textContent = 'Location captured for trip log.';
                    status.classList.remove('hidden');
                }

                resolve(true);
            },
            () => {
                if (status) {
                    status.textContent = 'Location unavailable — trip will continue without GPS.';
                    status.classList.remove('hidden');
                }

                resolve(false);
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 },
        );
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-tms-trip-gps]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.gpsReady === '1') {
                return;
            }

            event.preventDefault();

            fillGpsFields(form).finally(() => {
                form.dataset.gpsReady = '1';
                form.requestSubmit();
            });
        });
    });
});
