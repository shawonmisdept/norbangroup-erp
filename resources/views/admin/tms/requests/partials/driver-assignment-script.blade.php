<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        const typeSelect = form.querySelector('.driver-type-select');
        if (!typeSelect) return;

        const companyField = form.querySelector('.company-driver-field');
        const rentalField = form.querySelector('.rental-driver-field');
        const companySelect = form.querySelector('.company-driver-select');
        const rentalSelect = form.querySelector('.rental-driver-select');
        const vehicleSelect = form.querySelector('.assign-vehicle-select');
        const warningBox = form.querySelector('#vehicle-paper-warning');

        const toggleType = () => {
            const isCompany = typeSelect.value === 'company';
            companyField?.classList.toggle('hidden', !isCompany);
            rentalField?.classList.toggle('hidden', isCompany);
            if (companySelect) companySelect.required = isCompany;
            if (rentalSelect) rentalSelect.required = !isCompany;
        };

        const showPaperWarnings = () => {
            if (!warningBox || !vehicleSelect) return;

            let warnings = [];
            const selected = vehicleSelect.selectedOptions[0];

            if (selected?.dataset.warnings) {
                try {
                    warnings = JSON.parse(selected.dataset.warnings) || [];
                } catch (e) {
                    warnings = [];
                }
            }

            if (!warnings.length && companySelect?.selectedOptions[0]?.dataset.vehicle) {
                const defaultId = companySelect.selectedOptions[0].dataset.vehicle;
                const defaultOpt = vehicleSelect.querySelector(`option[value="${defaultId}"]`);
                if (defaultOpt?.dataset.warnings) {
                    try {
                        warnings = JSON.parse(defaultOpt.dataset.warnings) || [];
                    } catch (e) {
                        warnings = [];
                    }
                }
            }

            if (!warnings.length && rentalSelect?.selectedOptions[0]?.dataset.vehicle) {
                const defaultId = rentalSelect.selectedOptions[0].dataset.vehicle;
                const defaultOpt = vehicleSelect.querySelector(`option[value="${defaultId}"]`);
                if (defaultOpt?.dataset.warnings) {
                    try {
                        warnings = JSON.parse(defaultOpt.dataset.warnings) || [];
                    } catch (e) {
                        warnings = [];
                    }
                }
            }

            if (warnings.length) {
                warningBox.innerHTML = '<strong>⚠ Paper warning:</strong><ul class="mt-1 list-disc list-inside">'
                    + warnings.map(w => `<li>${w}</li>`).join('')
                    + '</ul><p class="mt-1 text-amber-700">Trip can still be approved.</p>';
                warningBox.classList.remove('hidden');
            } else {
                warningBox.innerHTML = '';
                warningBox.classList.add('hidden');
            }
        };

        const applyDefaultVehicle = (select) => {
            const defaultVehicleId = select?.selectedOptions[0]?.dataset.vehicle;
            if (defaultVehicleId && vehicleSelect) {
                vehicleSelect.value = defaultVehicleId;
            }
            showPaperWarnings();
        };

        typeSelect.addEventListener('change', toggleType);
        companySelect?.addEventListener('change', () => applyDefaultVehicle(companySelect));
        rentalSelect?.addEventListener('change', () => applyDefaultVehicle(rentalSelect));
        vehicleSelect?.addEventListener('change', showPaperWarnings);

        toggleType();
        showPaperWarnings();
    });
});
</script>
