/**
 * Live EMI calculator for apply_loan.php
 */
function calculateEMI(principal, annualRate, tenureMonths) {
    const r = annualRate / 12 / 100;
    if (r === 0) return principal / tenureMonths;
    return principal * r * Math.pow(1 + r, tenureMonths) / (Math.pow(1 + r, tenureMonths) - 1);
}

function buildAmortizationTable(principal, annualRate, tenureMonths) {
    const r = annualRate / 12 / 100;
    const emi = calculateEMI(principal, annualRate, tenureMonths);
    let balance = principal;
    const rows = [];
    for (let i = 1; i <= tenureMonths; i++) {
        const interest = balance * r;
        const principalPart = emi - interest;
        balance -= principalPart;
        rows.push({
            month: i,
            emi: emi.toFixed(2),
            principal: principalPart.toFixed(2),
            interest: interest.toFixed(2),
            balance: Math.max(0, balance).toFixed(2)
        });
    }
    return rows;
}

let debounceTimer;
function updateEMIDisplay() {
    const amountEl = document.getElementById('loan-amount');
    const rateEl = document.getElementById('interest-rate');
    const tenureEl = document.getElementById('tenure');
    if (!amountEl || !rateEl || !tenureEl) return;

    const p = parseFloat(amountEl.value);
    const r = parseFloat(rateEl.value);
    const n = parseInt(tenureEl.value, 10);
    if (!p || !r || !n) return;

    const emi = calculateEMI(p, r, n);
    const total = emi * n;
    const interest = total - p;

    const emiDisplay = document.getElementById('emi-display');
    const totalDisplay = document.getElementById('total-display');
    const interestDisplay = document.getElementById('interest-display');
    if (emiDisplay) emiDisplay.textContent = '₹' + emi.toFixed(2);
    if (totalDisplay) totalDisplay.textContent = '₹' + total.toFixed(2);
    if (interestDisplay) interestDisplay.textContent = '₹' + interest.toFixed(2);

    renderAmortizationTable(buildAmortizationTable(p, r, n));
}

function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amort-table-body');
    if (!tbody) return;
    tbody.innerHTML = rows.slice(0, 12).map(row =>
        `<tr><td>${row.month}</td><td>₹${row.principal}</td><td>₹${row.interest}</td><td>₹${row.emi}</td><td>₹${row.balance}</td></tr>`
    ).join('');
    if (rows.length > 12) {
        tbody.innerHTML += `<tr><td colspan="5"><em>Showing first 12 of ${rows.length} months</em></td></tr>`;
    }
}

function debouncedUpdate() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updateEMIDisplay, 300);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#loan-amount, #tenure, #interest-rate').forEach(el => {
        el.addEventListener('input', debouncedUpdate);
    });
    const loanType = document.getElementById('loan_type_id');
    if (loanType) {
        loanType.addEventListener('change', function () {
            const opt = loanType.options[loanType.selectedIndex];
            const rate = opt.getAttribute('data-rate');
            const min = opt.getAttribute('data-min');
            const max = opt.getAttribute('data-max');
            if (rate) document.getElementById('interest-rate').value = rate;
            if (min) document.getElementById('loan-amount').min = min;
            if (max) document.getElementById('loan-amount').max = max;
            debouncedUpdate();
        });
    }
    updateEMIDisplay();
});
