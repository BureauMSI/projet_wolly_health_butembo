import TomSelect from 'tom-select';

const ENTITY_NAME = /(^|\[)(member_id|client_id|sponsor_id|placement_parent_id|referrer_member_id|source_client_id|branch_id|product_id|registration_branch_id|operation_type_id)(]|$)/;

const PLACEHOLDER = () => document.body?.dataset?.selectSearch || '';
const NO_RESULTS = () => document.body?.dataset?.selectNoResults || '—';

/**
 * @param {HTMLSelectElement} select
 * @returns {boolean}
 */
export function shouldEnhanceSelect(select) {
    if (!(select instanceof HTMLSelectElement)) {
        return false;
    }
    if (select.dataset.noSearch === '1' || select.dataset.noSearch === 'true') {
        return false;
    }
    if (select.disabled || select.multiple) {
        return false;
    }
    if (select.dataset.searchable === '1' || select.dataset.searchable === 'true') {
        return true;
    }
    if (select.classList.contains('js-searchable') || select.classList.contains('product-select')) {
        return true;
    }
    const name = select.getAttribute('name') || '';
    if (ENTITY_NAME.test(name)) {
        return true;
    }

    return select.options.length >= 8;
}

/**
 * @param {HTMLSelectElement} select
 * @returns {TomSelect|null}
 */
export function enhanceSelect(select) {
    if (!shouldEnhanceSelect(select)) {
        return null;
    }

    if (select.tomselect) {
        select.tomselect.destroy();
    }

    const emptyLabel = select.querySelector('option[value=""]')?.textContent?.trim() || '';
    const placeholder = select.dataset.searchPlaceholder || PLACEHOLDER() || emptyLabel;

    return new TomSelect(select, {
        allowEmptyOption: true,
        create: false,
        maxOptions: null,
        hideSelected: false,
        closeAfterSelect: true,
        placeholder,
        plugins: ['clear_button'],
        render: {
            no_results: () => `<div class="no-results">${select.dataset.noResults || NO_RESULTS()}</div>`,
        },
    });
}

/**
 * @param {ParentNode} [root]
 */
export function enhanceSelects(root = document) {
    root.querySelectorAll('select.form-select, select.product-select').forEach((select) => {
        enhanceSelect(select);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    enhanceSelects();
});
