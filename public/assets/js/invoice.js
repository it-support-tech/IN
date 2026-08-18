(function () {
    const customerSelect = document.getElementById('customer_id');
    const itemsBody = document.getElementById('items-body');
    const addRowBtn = document.getElementById('add-row-btn');
    const form = document.getElementById('invoice-form');
    const formError = document.getElementById('form-error');

    function fillCustomerFields() {
        const opt = customerSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            document.getElementById('company_name').value = '';
            document.getElementById('village_district').value = '';
            document.getElementById('province').value = '';
            document.getElementById('tax_id').value = '';
            return;
        }
        document.getElementById('company_name').value = opt.dataset.company || '';
        document.getElementById('village_district').value =
            [opt.dataset.village, opt.dataset.district].filter(Boolean).join(' ');
        document.getElementById('province').value = opt.dataset.province || '';
        document.getElementById('tax_id').value = opt.dataset.taxId || '';
    }

    const ITEM_OPTIONS = [
        'DBO-N-0001:ກາຊວນ',
        'DBO-T-0001:ກາຊວນ',
        'G91-N-0002:ແອດຊັງ',
        'G91-T-0002:ແອດຊັງ',
        'G95-T-0003:ແອດຊັງພິເສດ',
        'G95-N-0003:ແອດຊັງພິເສດ',
    ];
    const WAREHOUSE_OPTIONS = [
        'Savannkhet - Fuel',
        'Khammouane - Fuel',
        'Vientiane - Fuel',
        'Luangnamtha - Fuel',
        'Sayabouly - Fuel',
        'Bolikhamxay - Fuel',
        
    ];
    const PRODUCT_CODE_OPTIONS = [
        'F-DB0 : Fuel - Diesel - Tax',
        'F-DB0 : Fuel - Diesel - Non Tax',
        'F-G95 : Fuel - Gasoline 95 - Tax',
        'F-G95 : Fuel - Gasoline 95 - Non-Tax',
        'F-G91 : Fuel - Gasoline 91 - Tax',
        'F-G91 : Fuel - Gasoline 91 - Non-Tax',
    ];

    const vatModeSelect = document.getElementById('vat-mode');
    const subtotalInput = document.getElementById('subtotal-input');
    const vatInput = document.getElementById('vat-input');
    const totalInput = document.getElementById('total-input');
    const dueInput = document.getElementById('due-input');
    const VAT_RATE = 0.10;

    // Keeps full precision (up to 8 decimal places) while clearing the
    // binary floating-point noise JS arithmetic produces (e.g. 0.1 + 0.2),
    // rather than truncating to 2 decimals like a display-rounding would.
    function round8(n) {
        return Math.round((n + Number.EPSILON) * 1e8) / 1e8;
    }

    function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.f-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.f-price').value) || 0;
        const discount = parseFloat(tr.querySelector('.f-discount').value) || 0;
        // discount is per unit (e.g. per liter), not a flat deduction off the line
        tr.querySelector('.f-line-total').value = round8(qty * (price - discount));
    }

    function recalcTotals() {
        const linesSum = round8(
            Array.from(itemsBody.querySelectorAll('.f-line-total'))
                .reduce((sum, el) => sum + (parseFloat(el.value) || 0), 0)
        );

        let subtotal, vat, total;
        if (vatModeSelect.value === 'inclusive') {
            // The summed line totals are treated as VAT-inclusive; back the
            // VAT amount out of them instead of adding it on top.
            total = linesSum;
            subtotal = round8(total / (1 + VAT_RATE));
            vat = round8(total - subtotal);
        } else {
            subtotal = linesSum;
            vat = round8(subtotal * VAT_RATE);
            total = round8(subtotal + vat);
        }

        subtotalInput.value = subtotal;
        vatInput.value = vat;
        totalInput.value = total;
        // Defaults the amount due to the full total (typical full-payment
        // case); staff can still overwrite it by hand for partial payments.
        dueInput.value = total;
    }

    function optionsHtml(values) {
        return values.map(v => `<option value="${v}">${v}</option>`).join('');
    }

    function ensureOption(select, value) {
        if (!value) return;
        const exists = Array.from(select.options).some(o => o.value === value);
        if (!exists) {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            select.insertBefore(opt, select.firstChild);
        }
        select.value = value;
    }

    function parseDescription(text) {
        const lines = (text || '').split('\n');
        const result = { item: lines[0] || '', so: '', inNo: '', warehouse: '', productCode: '' };
        lines.slice(1).forEach(line => {
            if (line.startsWith('SO-')) {
                result.so = line.slice(3);
            } else if (line.startsWith('IN-')) {
                result.inNo = line.slice(3);
            } else if (line.startsWith('ສາງ: ')) {
                const rest = line.slice('ສາງ: '.length);
                const parts = rest.split(', ລະຫັດສິນຄ້າ: ');
                result.warehouse = parts[0] || '';
                result.productCode = parts[1] || '';
            }
        });
        return result;
    }

    function makeRow(item) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="desc-cell">
                <select class="f-item">${optionsHtml(ITEM_OPTIONS)}</select>
                <div class="prefix-row"><span class="prefix">SO-</span><input type="text" class="f-so" placeholder="0126080075"></div>
                <div class="prefix-row"><span class="prefix">IN-</span><input type="text" class="f-in" placeholder="126080075"></div>
                <div class="prefix-row"><span class="prefix">ທະບຽນ:</span><input type="text" class="f-plate" placeholder="ບກ 0000"></div>
                <div class="prefix-row wrap">
                    <span class="prefix">ສາງ:</span>
                    <select class="f-warehouse">${optionsHtml(WAREHOUSE_OPTIONS)}</select>.
                    <span class="prefix">ລະຫັດສິນຄ້າ:</span>
                    <select class="f-productcode">${optionsHtml(PRODUCT_CODE_OPTIONS)}</select>
                </div>
            </td>
            <td><input type="text" class="f-unit" value="Litter"></td>
            <td><input type="number" class="f-qty" placeholder="0" step="any"></td>
            <td><input type="number" class="f-price" placeholder="0" step="any"></td>
            <td><input type="number" class="f-discount" placeholder="0" step="any"></td>
            <td><input type="number" class="f-line-total" placeholder="0" step="any"></td>
            <td><button type="button" class="remove-row">×</button></td>
        `;
        itemsBody.appendChild(tr);

        tr.querySelector('.remove-row').addEventListener('click', () => {
            tr.remove();
            recalcTotals();
        });

        ['.f-qty', '.f-price', '.f-discount'].forEach(sel => {
            tr.querySelector(sel).addEventListener('input', () => {
                recalcRow(tr);
                recalcTotals();
            });
        });

        if (item) {
            const parsed = parseDescription(item.description);
            ensureOption(tr.querySelector('.f-item'), parsed.item);
            tr.querySelector('.f-so').value = parsed.so;
            tr.querySelector('.f-in').value = parsed.inNo;
            tr.querySelector('.f-plate').value = item.vehicle_plate || '';
            ensureOption(tr.querySelector('.f-warehouse'), parsed.warehouse);
            ensureOption(tr.querySelector('.f-productcode'), parsed.productCode);
            tr.querySelector('.f-unit').value = item.unit || '';
            tr.querySelector('.f-qty').value = item.quantity;
            tr.querySelector('.f-price').value = item.unit_price;
            tr.querySelector('.f-discount').value = item.discount;
            tr.querySelector('.f-line-total').value = item.line_total;
        }
    }

    function buildDescription(tr) {
        const item = tr.querySelector('.f-item').value;
        const so = tr.querySelector('.f-so').value.trim();
        const inNo = tr.querySelector('.f-in').value.trim();
        const warehouse = tr.querySelector('.f-warehouse').value;
        const productCode = tr.querySelector('.f-productcode').value;

        const lines = [item];
        if (so) lines.push('SO-' + so);
        if (inNo) lines.push('IN-' + inNo);
        lines.push('ສາງ: ' + warehouse + ', ລະຫັດສິນຄ້າ: ' + productCode);
        return lines.join('\n');
    }

    customerSelect.addEventListener('change', fillCustomerFields);
    addRowBtn.addEventListener('click', () => makeRow());
    vatModeSelect.addEventListener('change', recalcTotals);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.textContent = '';

        const items = [];
        itemsBody.querySelectorAll('tr').forEach(tr => {
            items.push({
                description: buildDescription(tr),
                vehicle_plate: tr.querySelector('.f-plate').value.trim(),
                unit: tr.querySelector('.f-unit').value,
                quantity: parseFloat(tr.querySelector('.f-qty').value) || 0,
                unit_price: parseFloat(tr.querySelector('.f-price').value) || 0,
                discount: parseFloat(tr.querySelector('.f-discount').value) || 0,
                line_total: parseFloat(tr.querySelector('.f-line-total').value) || 0,
            });
        });

        if (items.length === 0) {
            formError.textContent = 'ກະລຸນາເພີ່ມຢ່າງໜ້ອຍໜຶ່ງລາຍການ';
            return;
        }
        if (!customerSelect.value) {
            formError.textContent = 'ກະລຸນາເລືອກລູກຄ້າ';
            return;
        }

        const payload = {
            customer_id: customerSelect.value,
            bank_account_id: form.bank_account_id.value,
            currency: form.currency.value,
            invoice_no: form.invoice_no.value,
            po_number: form.po_number.value,
            invoice_date: form.invoice_date.value,
            due_date: form.due_date.value,
            subtotal: parseFloat(document.getElementById('subtotal-input').value) || 0,
            vat_amount: parseFloat(document.getElementById('vat-input').value) || 0,
            total: parseFloat(document.getElementById('total-input').value) || 0,
            amount_due: parseFloat(document.getElementById('due-input').value) || 0,
            items: items,
        };

        const isEdit = !!(window.EDIT_INVOICE && window.EDIT_INVOICE.id);
        const url = isEdit ? `api/invoices.php?id=${window.EDIT_INVOICE.id}` : 'api/invoices.php';

        try {
            const res = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) {
                formError.textContent = data.error || 'ເກີດຂໍ້ຜິດພາດ';
                return;
            }
            window.location.href = 'invoice_view.php?id=' + data.id;
        } catch (err) {
            formError.textContent = 'ເກີດຂໍ້ຜິດພາດ: ' + err.message;
        }
    });

    if (window.EDIT_INVOICE && window.EDIT_INVOICE.items.length) {
        window.EDIT_INVOICE.items.forEach(makeRow);
        fillCustomerFields();
    } else {
        makeRow();
    }
})();
