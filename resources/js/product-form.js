const typeSelect = document.getElementById('benefit_type');
if (typeSelect) {
    const pvFields = document.querySelectorAll('.product-pv-fields');
    const percentFields = document.querySelectorAll('.product-percent-fields');
    const pvTablet = document.getElementById('pv_per_tablet');
    const boxPv = document.getElementById('box_pv');
    const commission = document.getElementById('commission_percent');

    const syncTypeFields = () => {
        const isPercent = typeSelect.value === 'percent';
        pvFields.forEach((el) => el.classList.toggle('d-none', isPercent));
        percentFields.forEach((el) => el.classList.toggle('d-none', !isPercent));
        if (pvTablet) {
            pvTablet.required = !isPercent;
            if (isPercent) {
                pvTablet.value = pvTablet.value || '0';
            }
        }
        if (boxPv) {
            boxPv.required = !isPercent;
            if (isPercent) {
                boxPv.value = boxPv.value || '0';
            }
        }
        if (commission) {
            commission.required = isPercent;
            if (!isPercent) {
                commission.value = commission.value || '0';
            }
        }
    };

    typeSelect.addEventListener('change', syncTypeFields);
    syncTypeFields();
}
