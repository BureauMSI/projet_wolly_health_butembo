import { enhanceSelect } from './searchable-select';

const cashForm = document.getElementById('cash-movement-form');
if (cashForm) {
    const direction = document.getElementById('cash-direction');
    const category = document.getElementById('cash-category');

    const filterCategories = () => {
        if (!direction || !category) {
            return;
        }
        const selected = direction.value;
        const currentValue = category.value;
        [...category.options].forEach((option) => {
            const allows = option.dataset.allows;
            if (!allows) {
                return;
            }
            option.disabled = !(allows === 'both' || allows === selected);
            option.hidden = option.disabled;
        });
        const current = category.selectedOptions[0];
        if (current && (current.hidden || current.disabled)) {
            const first = [...category.options].find((option) => !option.hidden && !option.disabled && option.value);
            if (first) {
                category.value = first.value;
            }
        } else if (currentValue) {
            category.value = currentValue;
        }
        enhanceSelect(category);
    };

    direction?.addEventListener('change', filterCategories);
    filterCategories();
}
