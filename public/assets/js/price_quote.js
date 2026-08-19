(function () {
    const itemsBody = document.getElementById('items-body');
    const addRowBtn = document.getElementById('add-row-btn');
    const form = document.getElementById('quote-form');
    const formError = document.getElementById('form-error');
    const calcModeSelect = document.getElementById('calc-mode');
    const docRateInput = document.getElementById('doc-rate');
    const rateCurrencySelect = document.getElementById('rate-currency');

    // Keeps full precision (up to 8 decimal places) while clearing the
    // binary floating-point noise JS arithmetic produces.
    function round8(n) {
        return Math.round((n + Number.EPSILON) * 1e8) / 1e8;
    }

    function getCalcMode() {
        return calcModeSelect.value;
    }

    // G = quantity_liters, A = structure_price are typed per row; D = the
    // exchange rate (Rate) is a single document-level value (one rate
    // applies to every line, not per-row). Which of B (discount) / E (price)
    // is the other typed input — and which is derived from it — depends on
    // the chosen calc mode:
    //
    // Mode 1 — staff knows the discount (structure price known first, then
    // discount, then staff looks up Rate):
    //   C = A - B         (price after discount, Kip)
    //   E = C / D          (price in the chosen currency, rounded to 4dp —
    //                       the one place we do round, since staff only need
    //                       a usable quote)
    //   F = C * G          (line total, Kip)
    //
    // Mode 2 — staff already has the price (looked up first, along with
    // structure price and Rate) and works backwards to the discount:
    //   C = E * D          (price after discount, Kip)
    //   B = A - C          (discount, Kip)
    //   F = C * G          (line total, Kip)
    function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.f-qty').value) || 0;
        const structurePrice = parseFloat(tr.querySelector('.f-structure').value) || 0;
        const fx = parseFloat(docRateInput.value) || 0;

        // Kip has no fractional unit, so discount/after-discount/total are
        // always rounded to whole Kip — only the price keeps decimals
        // (rounded to 4dp, the precision staff actually look up/quote).
        let afterDiscount;
        if (getCalcMode() === '2') {
            const usdPrice = parseFloat(tr.querySelector('.f-usd').value) || 0;
            afterDiscount = Math.round(usdPrice * fx);
            tr.querySelector('.f-after-discount').value = afterDiscount;
            tr.querySelector('.f-discount').value = Math.round(structurePrice - afterDiscount);
        } else {
            const discount = parseFloat(tr.querySelector('.f-discount').value) || 0;
            afterDiscount = Math.round(structurePrice - discount);
            tr.querySelector('.f-after-discount').value = afterDiscount;
            const usdPrice = fx > 0 ? Math.round((afterDiscount / fx) * 1e4) / 1e4 : 0;
            tr.querySelector('.f-usd').value = usdPrice;
        }

        tr.querySelector('.f-total').value = Math.round(afterDiscount * qty);
    }

    // The field staff type into for the "other" side of the discount/price
    // pair flips with the mode; the derived one gets a readonly look so it's
    // clear which field to fill in for the chosen method.
    function applyCalcModeToRow(tr) {
        const isMode2 = getCalcMode() === '2';
        tr.querySelector('.f-discount').readOnly = isMode2;
        tr.querySelector('.f-usd').readOnly = !isMode2;
    }

    function recalcAllRows() {
        itemsBody.querySelectorAll('tr').forEach(tr => recalcRow(tr));
        recalcGrandTotal();
    }

    function applyCalcModeToAllRows() {
        itemsBody.querySelectorAll('tr').forEach(tr => applyCalcModeToRow(tr));
        recalcAllRows();
    }

    // The Rate's currency (USD/THB/CNY) doesn't change any math — it only
    // relabels the per-row price field and the grand-total box so staff see
    // which currency the number they're reading is in.
    function updateCurrencyLabels() {
        const currency = rateCurrencySelect.value;
        itemsBody.querySelectorAll('.currency-label').forEach(el => {
            el.textContent = currency;
        });
        const grandTotalLabel = document.getElementById('grand-total-currency-label');
        if (grandTotalLabel) {
            grandTotalLabel.textContent = currency;
        }
    }

    // Line totals come out in Kip; the grand total is separately converted to
    // the chosen currency by summing each line's own price × quantity (same
    // result as total_amount / exchange_rate, without a division per line).
    function recalcGrandTotal() {
        const totalUsd = round8(
            Array.from(itemsBody.querySelectorAll('tr')).reduce((sum, tr) => {
                const qty = parseFloat(tr.querySelector('.f-qty').value) || 0;
                const usdPrice = parseFloat(tr.querySelector('.f-usd').value) || 0;
                return sum + qty * usdPrice;
            }, 0)
        );
        document.getElementById('total-usd-input').value = totalUsd;
    }

    function makeRow(item) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="number" class="f-qty" placeholder="0" step="any"></td>
            <td><input type="number" class="f-structure" placeholder="0" step="any"></td>
            <td><input type="number" class="f-discount" placeholder="0" step="any"></td>
            <td><input type="number" class="f-after-discount" placeholder="0" step="any" readonly></td>
            <td>
                <div class="field-inline">
                    <span class="currency-label"></span>
                    <input type="number" class="f-usd" placeholder="0" step="any">
                </div>
            </td>
            <td><input type="number" class="f-total" placeholder="0" step="any" readonly></td>
            <td><button type="button" class="remove-row">×</button></td>
        `;
        itemsBody.appendChild(tr);

        tr.querySelector('.remove-row').addEventListener('click', () => {
            tr.remove();
            recalcGrandTotal();
        });

        ['.f-qty', '.f-structure', '.f-discount', '.f-usd'].forEach(sel => {
            tr.querySelector(sel).addEventListener('input', () => {
                recalcRow(tr);
                recalcGrandTotal();
            });
        });

        applyCalcModeToRow(tr);
        tr.querySelector('.currency-label').textContent = rateCurrencySelect.value;

        if (item) {
            // Values come from NUMERIC(18,8) columns as full-precision strings
            // (e.g. "320000.00000000"); parseFloat before assigning so the
            // input shows the trimmed number instead of that raw string.
            tr.querySelector('.f-qty').value = parseFloat(item.quantity_liters) || 0;
            tr.querySelector('.f-structure').value = parseFloat(item.structure_price) || 0;
            tr.querySelector('.f-discount').value = parseFloat(item.discount) || 0;
            tr.querySelector('.f-after-discount').value = parseFloat(item.price_after_discount) || 0;
            tr.querySelector('.f-usd').value = parseFloat(item.usd_price) || 0;
            tr.querySelector('.f-total').value = parseFloat(item.total_amount) || 0;
        }
    }

    addRowBtn.addEventListener('click', () => makeRow());

    calcModeSelect.addEventListener('change', applyCalcModeToAllRows);
    docRateInput.addEventListener('input', recalcAllRows);
    rateCurrencySelect.addEventListener('change', updateCurrencyLabels);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.textContent = '';

        const rate = parseFloat(docRateInput.value) || 0;
        const items = [];
        itemsBody.querySelectorAll('tr').forEach(tr => {
            items.push({
                quantity_liters: parseFloat(tr.querySelector('.f-qty').value) || 0,
                structure_price: parseFloat(tr.querySelector('.f-structure').value) || 0,
                discount: parseFloat(tr.querySelector('.f-discount').value) || 0,
                price_after_discount: parseFloat(tr.querySelector('.f-after-discount').value) || 0,
                exchange_rate: rate,
                usd_price: parseFloat(tr.querySelector('.f-usd').value) || 0,
                total_amount: parseFloat(tr.querySelector('.f-total').value) || 0,
            });
        });

        if (items.length === 0) {
            formError.textContent = 'ກະລຸນາເພີ່ມຢ່າງໜ້ອຍໜຶ່ງລາຍການ';
            return;
        }

        const payload = {
            doc_no: form.doc_no.value,
            doc_date: form.doc_date.value,
            total_usd: parseFloat(document.getElementById('total-usd-input').value) || 0,
            remark: form.remark.value,
            rate_currency: rateCurrencySelect.value,
            header_lang: document.getElementById('header-lang').value,
            items: items,
        };

        const isEdit = !!(window.EDIT_QUOTE && window.EDIT_QUOTE.id);
        const url = isEdit ? `api/price_quotes.php?id=${window.EDIT_QUOTE.id}` : 'api/price_quotes.php';

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
            window.location.href = 'price_quote_view.php?id=' + data.id;
        } catch (err) {
            formError.textContent = 'ເກີດຂໍ້ຜິດພາດ: ' + err.message;
        }
    });

    if (window.EDIT_QUOTE && window.EDIT_QUOTE.items.length) {
        window.EDIT_QUOTE.items.forEach(makeRow);
    } else {
        makeRow();
    }
    updateCurrencyLabels();
})();
