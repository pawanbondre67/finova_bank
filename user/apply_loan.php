<?php
/**
 * Loan application with live EMI calculator.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$account = getUserAccount($conn, $user_id);
$types = $conn->query('SELECT * FROM loan_types WHERE is_active = 1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $loan_type_id = (int) ($_POST['loan_type_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $tenure = (int) ($_POST['tenure'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    $lt = $conn->prepare('SELECT * FROM loan_types WHERE id = ?');
    $lt->bind_param('i', $loan_type_id);
    $lt->execute();
    $loan_type = $lt->get_result()->fetch_assoc();

    if (!$loan_type) {
        setFlash('error', 'Invalid loan type.');
    } elseif ($amount < $loan_type['min_amount'] || $amount > $loan_type['max_amount']) {
        setFlash('error', 'Amount out of allowed range.');
    } elseif ($tenure < $loan_type['min_tenure'] || $tenure > $loan_type['max_tenure']) {
        setFlash('error', 'Tenure out of allowed range.');
    } else {
        $rate = (float) $loan_type['interest_rate'];
        $emi = calculateEMI($amount, $rate, $tenure);
        $total = $emi * $tenure;
        $interest = $total - $amount;
        $loan_ref = 'LN' . date('ymd') . random_int(1000, 9999);

        $ins = $conn->prepare('INSERT INTO loan_applications
            (loan_ref, user_id, account_id, loan_type_id, amount_requested, tenure_months, interest_rate, emi_amount, total_payable, total_interest, purpose, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")');
        $ins->bind_param('siiiiddddds', $loan_ref, $user_id, $account['id'], $loan_type_id, $amount, $tenure, $rate, $emi, $total, $interest, $purpose);
        $ins->execute();

        $u = $conn->prepare('SELECT email FROM users WHERE id = ?');
        $u->bind_param('i', $user_id);
        $u->execute();
        $email = $u->get_result()->fetch_assoc()['email'];
        @sendMail($email, 'Loan Application Submitted', '<p>Your loan application ' . e($loan_ref) . ' has been submitted.</p>');

        setFlash('success', 'Loan application submitted! Ref: ' . $loan_ref);
        header('Location: ' . url('user/my_loans.php'));
        exit;
    }
}

$page_title = 'Apply for Loan';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2>EMI Calculator</h2>
    <div class="form-row">
        <div class="form-group">
            <label>Loan Amount (₹)</label>
            <div class="slider-row">
                <input type="range" id="loan-amount-range" min="10000" max="500000" step="1000" value="100000" oninput="document.getElementById('loan-amount').value=this.value; debouncedUpdate();">
                <input type="number" id="loan-amount" name="amount_display" value="100000" min="10000" max="500000">
            </div>
        </div>
        <div class="form-group">
            <label>Tenure (months)</label>
            <div class="slider-row">
                <input type="range" id="tenure-range" min="6" max="60" value="24" oninput="document.getElementById('tenure').value=this.value; debouncedUpdate();">
                <input type="number" id="tenure" value="24" min="6" max="240">
            </div>
        </div>
    </div>
    <div class="form-group"><label>Interest Rate (% p.a.)</label><input type="number" id="interest-rate" step="0.01" value="10.5"></div>
    <div class="emi-preview">
        <p>Monthly EMI: <span id="emi-display" class="emi-value">—</span></p>
        <p>Total Payable: <span id="total-display">—</span></p>
        <p>Total Interest: <span id="interest-display">—</span></p>
    </div>
    <div class="table-wrap">
        <table><thead><tr><th>Month</th><th>Principal</th><th>Interest</th><th>EMI</th><th>Balance</th></tr></thead>
        <tbody id="amort-table-body"></tbody></table>
    </div>
</div>
<div class="card">
    <h2>Loan Application</h2>
    <form method="POST"><?= csrfField() ?>
        <div class="form-group"><label>Loan Type</label>
            <select name="loan_type_id" id="loan_type_id" required>
                <option value="">Select type</option>
                <?php $types->data_seek(0); while ($t = $types->fetch_assoc()): ?>
                <option value="<?= (int) $t['id'] ?>" data-rate="<?= e($t['interest_rate']) ?>" data-min="<?= e($t['min_amount']) ?>" data-max="<?= e($t['max_amount']) ?>"><?= e($t['type_name']) ?> (<?= e($t['interest_rate']) ?>%)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Amount (₹)</label><input type="number" name="amount" step="0.01" required></div>
        <div class="form-group"><label>Tenure (months)</label><input type="number" name="tenure" required></div>
        <div class="form-group"><label>Purpose</label><textarea name="purpose" rows="3" required></textarea></div>
        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
