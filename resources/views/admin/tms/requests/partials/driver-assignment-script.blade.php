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

        const toggleType = () => {
            const isCompany = typeSelect.value === 'company';
            companyField?.classList.toggle('hidden', !isCompany);
            rentalField?.classList.toggle('hidden', isCompany);
            if (companySelect) companySelect.required = isCompany;
            if (rentalSelect) rentalSelect.required = !isCompany;
        };

        const applyDefaultVehicle = (select) => {
            const defaultVehicleId = select?.selectedOptions[0]?.dataset.vehicle;
            if (defaultVehicleId && vehicleSelect) {
                vehicleSelect.value = defaultVehicleId;
            }
        };

        typeSelect.addEventListener('change', toggleType);
        companySelect?.addEventListener('change', () => applyDefaultVehicle(companySelect));
        rentalSelect?.addEventListener('change', () => applyDefaultVehicle(rentalSelect));

        toggleType();
    });
});
</script>
