import { enhanceSelect } from './searchable-select';

const form = document.getElementById('sale-form');
if (form) {
    const source = document.getElementById('products-data');
    const products = source ? JSON.parse(source.textContent || '[]') : [];
    const body = document.getElementById('sale-lines');
    const addBtn = document.getElementById('add-sale-line');
    const buyerType = document.getElementById('buyer_type');
    const benefitMode = document.getElementById('benefit_mode');
    const memberWrap = document.getElementById('member-wrap');
    const clientWrap = document.getElementById('client-wrap');
    const benefitWrap = document.getElementById('benefit-wrap');

    const initialSource = document.getElementById('sale-initial-lines');
    const initialLines = initialSource ? JSON.parse(initialSource.textContent || '[]') : [];

    const packingLabel = (value) => {
        const option = document.querySelector(`#packing-template [value="${value}"]`);
        return option ? option.textContent : value;
    };

    const currentMode = () => (benefitMode?.value === 'percent' ? 'percent' : 'pv');

    const filteredProducts = () => products.filter((product) => {
        const type = product.benefit_type === 'percent' ? 'percent' : 'pv';
        return type === currentMode();
    });

    const priceForProduct = (product, packing) => {
        const isMember = buyerType?.value === 'member';
        if (packing === 'box') {
            return isMember
                ? (product.member_box_price_usd ?? product.box_price_usd)
                : product.box_price_usd;
        }

        return isMember
            ? (product.member_unit_price_usd ?? product.unit_price_usd)
            : product.unit_price_usd;
    };

    const productLabel = (product) => {
        const price = priceForProduct(product, 'tablet');
        const mode = currentMode();
        if (mode === 'percent') {
            return `${product.name} · ${price} USD · ${Number(product.commission_percent).toFixed(2)} %`;
        }

        return `${product.name} · ${price} USD · ${Number(product.pv_per_tablet).toFixed(2)} PV`;
    };

    const selectedPrice = (option, packing) => {
        if (!option) {
            return '';
        }
        const isMember = buyerType?.value === 'member';
        if (packing === 'box') {
            return isMember ? (option.dataset.memberBox || option.dataset.box) : option.dataset.box;
        }

        return isMember ? (option.dataset.memberUnit || option.dataset.unit) : option.dataset.unit;
    };

    const selectedMeta = (option, packing) => {
        const price = selectedPrice(option, packing);
        if (!price || !option?.value) {
            return '';
        }
        if (currentMode() === 'percent') {
            const percent = option.dataset.commission || '0';
            return `${price} USD · ${Number(percent).toFixed(2)} %`;
        }
        const pv = packing === 'box' ? (option.dataset.pvBox || '0') : (option.dataset.pvUnit || '0');
        return `${price} USD · ${Number(pv).toFixed(2)} PV`;
    };

    const refreshLineMeta = () => {
        body?.querySelectorAll('tr').forEach((row) => {
            const select = row.querySelector('.product-select');
            const packing = row.querySelector('.packing-select')?.value;
            const meta = row.querySelector('.line-meta');
            const option = select?.selectedOptions?.[0];
            if (!meta) {
                return;
            }
            meta.textContent = selectedMeta(option, packing);
        });
    };

    const rebuildProductOptions = (select, preferredId = null) => {
        if (!select) {
            return;
        }
        if (select.tomselect) {
            select.tomselect.destroy();
        }
        const selected = preferredId ?? select.value;
        const options = filteredProducts().map((product) => (
            `<option value="${product.id}" data-unit="${product.unit_price_usd}" data-member-unit="${product.member_unit_price_usd ?? product.unit_price_usd}" data-box="${product.box_price_usd}" data-member-box="${product.member_box_price_usd ?? product.box_price_usd}" data-pv-unit="${product.pv_per_tablet}" data-pv-box="${product.box_pv}" data-commission="${product.commission_percent ?? 0}">${productLabel(product)}</option>`
        )).join('');
        select.innerHTML = `<option value="">${form.dataset.selectLabel || ''}</option>${options}`;
        if (selected && [...select.options].some((option) => option.value === String(selected))) {
            select.value = String(selected);
        } else {
            select.value = '';
        }
        enhanceSelect(select);
    };

    const refreshAllProductSelects = () => {
        body?.querySelectorAll('.product-select').forEach((select) => {
            rebuildProductOptions(select, select.value);
        });
        refreshLineMeta();
    };

    const addLine = (preset = null) => {
        if (!body) {
            return;
        }
        const index = body.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-select form-select-sm product-select" name="items[${index}][product_id]" required data-searchable="1">
                    <option value="">${form.dataset.selectLabel || ''}</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm packing-select" name="items[${index}][packing]" required data-no-search="1">
                    <option value="tablet">${packingLabel('tablet')}</option>
                    <option value="box">${packingLabel('box')}</option>
                </select>
            </td>
            <td>
                <input class="form-control form-control-sm qty-input" type="number" min="1" value="1" name="items[${index}][quantity]" required>
            </td>
            <td class="line-meta small text-muted"></td>
            <td>
                <button class="btn btn-sm btn-outline-danger remove-line" type="button">${form.dataset.removeLabel || '×'}</button>
            </td>
        `;
        body.appendChild(row);
        const productSelect = row.querySelector('.product-select');
        rebuildProductOptions(productSelect, preset?.product_id ?? null);
        if (preset) {
            const packingSelect = row.querySelector('.packing-select');
            const qtyInput = row.querySelector('.qty-input');
            if (packingSelect && preset.packing) {
                packingSelect.value = preset.packing;
            }
            if (qtyInput && preset.quantity) {
                qtyInput.value = String(preset.quantity);
            }
        }
    };

    const toggleBuyer = () => {
        const isMember = buyerType?.value === 'member';
        memberWrap?.classList.toggle('d-none', !isMember);
        clientWrap?.classList.toggle('d-none', isMember);
        benefitWrap?.classList.remove('d-none');
        memberWrap?.querySelector('select')?.toggleAttribute('required', isMember);
        clientWrap?.querySelector('select')?.toggleAttribute('required', !isMember);
    };

    addBtn?.addEventListener('click', () => {
        addLine();
        refreshLineMeta();
    });
    buyerType?.addEventListener('change', () => {
        toggleBuyer();
        refreshAllProductSelects();
    });
    benefitMode?.addEventListener('change', () => {
        refreshAllProductSelects();
    });
    body?.addEventListener('change', (event) => {
        if (event.target.closest('.product-select, .packing-select')) {
            refreshLineMeta();
        }
    });
    body?.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-line');
        if (button) {
            const row = button.closest('tr');
            row?.querySelectorAll('select').forEach((select) => {
                if (select.tomselect) {
                    select.tomselect.destroy();
                }
            });
            row?.remove();
            refreshLineMeta();
        }
    });

    toggleBuyer();
    if (body && body.querySelectorAll('tr').length === 0) {
        if (initialLines.length) {
            initialLines.forEach((line) => addLine(line));
        } else {
            addLine();
        }
    }
    refreshAllProductSelects();
}
