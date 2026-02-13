<script>
(function () {
    if (window.__zgnLineCalcInitialized) {
        return;
    }

    window.__zgnLineCalcInitialized = true;

    function parseNumber(value) {
        if (value === null || value === undefined) {
            return 0;
        }

        var normalized = String(value).replace(/,/g, '');
        var parsed = parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoney(value) {
        var fixed = (Math.round(value * 100) / 100).toFixed(2);

        return 'PKR ' + fixed;
    }

    function getInputValue(container, field) {
        var fieldEl = container.querySelector('[data-line-field="' + field + '"]');
        if (!fieldEl) {
            return 0;
        }

        var input = fieldEl.tagName === 'INPUT' ? fieldEl : fieldEl.querySelector('input');
        if (!input) {
            return 0;
        }

        return parseNumber(input.value);
    }

    function setInputValue(container, field, value) {
        var fieldEl = container.querySelector('[data-line-field="' + field + '"]');
        if (!fieldEl) {
            return;
        }

        var input = fieldEl.tagName === 'INPUT' ? fieldEl : fieldEl.querySelector('input');
        if (!input) {
            return;
        }

        input.value = value.toFixed(2);
    }

    function getDiscountMode() {
        var modeInput = document.querySelector('input[name="data.discount_mode"]');
        if (!modeInput) {
            return 'percent';
        }

        return modeInput.value || 'percent';
    }

    function recalc() {
        var items = document.querySelectorAll('.fi-fo-repeater-item');
        var discountMode = getDiscountMode();

        var subtotal = 0;
        var totalDiscount = 0;
        var totalTax = 0;

        items.forEach(function (item) {
            var unitPrice = getInputValue(item, 'unit_price');
            var qty = getInputValue(item, 'quantity');
            var discountRate = getInputValue(item, 'discount');
            var taxRate = getInputValue(item, 'tax');
            var discountAmountInput = getInputValue(item, 'discount_amount');
            var taxAmountInput = getInputValue(item, 'tax_amount');

            qty = qty > 0 ? qty : 0;

            var lineSubtotal = unitPrice * qty;
            var discountAmount = 0;

            if (discountMode === 'amount') {
                discountAmount = Math.min(discountAmountInput, lineSubtotal);
                discountRate = lineSubtotal > 0 ? (discountAmount / lineSubtotal) * 100 : 0;
            } else {
                discountAmount = lineSubtotal * (discountRate / 100);
            }

            var taxableLine = Math.max(0, lineSubtotal - discountAmount);
            var taxAmount = 0;

            if (discountMode === 'amount') {
                taxAmount = Math.min(taxAmountInput, lineSubtotal);
                taxRate = taxableLine > 0 ? (taxAmount / taxableLine) * 100 : 0;
            } else {
                taxAmount = taxableLine * (taxRate / 100);
            }

            var lineTotal = taxableLine + taxAmount;

            setInputValue(item, 'line_total', lineTotal);

            subtotal += lineSubtotal;
            totalDiscount += discountAmount;
            totalTax += taxAmount;
        });

        var total = subtotal - totalDiscount + totalTax;

        var subtotalEl = document.querySelector('[data-summary="subtotal"]');
        var discountEl = document.querySelector('[data-summary="discount"]');
        var taxEl = document.querySelector('[data-summary="tax"]');
        var totalEl = document.querySelector('[data-summary="total"]');

        if (subtotalEl) {
            subtotalEl.textContent = formatMoney(subtotal);
        }
        if (discountEl) {
            discountEl.textContent = formatMoney(totalDiscount);
        }
        if (taxEl) {
            taxEl.textContent = formatMoney(totalTax);
        }
        if (totalEl) {
            totalEl.textContent = formatMoney(total);
        }
    }

    document.addEventListener('input', function (event) {
        if (event.target.closest('[data-line-field]')) {
            recalc();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.closest('[data-line-field]')) {
            recalc();
        }
    });

    document.addEventListener('DOMContentLoaded', recalc);
    document.addEventListener('livewire:navigated', recalc);
})();
</script>
