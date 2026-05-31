<?php
/**
 * Apply for fixed deposit.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$account = getUserAccount($conn, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $principal = (float) ($_POST['principal'] ?? 0);
    $tenure = (int) ($_POST['tenure_months'] ?? 0);

    if ($principal < 1000) {
        setFlash('error', 'Minimum FD amount is ₹1000.');
    } elseif ($principal > $account['balance']) {
        setFlash('error', 'Insufficient account balance.');
    } elseif (!in_array($tenure, [3, 6, 12, 24, 36], true)) {
        setFlash('error', 'Invalid tenure selected.');
    } else {
        $rate = fdInterestRate($tenure);
        $maturity = fdMaturityAmount($principal, $rate, $tenure);
        $fd_ref = 'FD' . date('ymd') . random_int(100, 999);
        $start = date('Y-m-d');
        $maturity_date = date('Y-m-d', strtotime("+{$tenure} months"));

        $conn->begin_transaction();
        try {
            $new_bal = $account['balance'] - $principal;
            $upd = $conn->prepare('UPDATE accounts SET balance = ? WHERE id = ?');
            $upd->bind_param('di', $new_bal, $account['id']);
            $upd->execute();
            recordTransaction($conn, $account['id'], 'debit', $principal, 'Fixed deposit - ' . $fd_ref, $new_bal, 'FD booking');

            $ins = $conn->prepare('INSERT INTO fixed_deposits (fd_ref, user_id, account_id, principal, interest_rate, tenure_months, maturity_amount, start_date, maturity_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->bind_param('siiddidss', $fd_ref, $user_id, $account['id'], $principal, $rate, $tenure, $maturity, $start, $maturity_date);
            $ins->execute();

            $conn->commit();

            $u = $conn->prepare('SELECT email FROM users WHERE id = ?');
            $u->bind_param('i', $user_id);
            $u->execute();
            $email = $u->get_result()->fetch_assoc()['email'];
            @sendMail($email, 'FD Created - Finova Bank', '<p>FD ' . e($fd_ref) . ' created. Maturity: ' . formatINR($maturity) . '</p>');

            setFlash('success', 'Fixed deposit created! Ref: ' . $fd_ref);
            header('Location: ' . url('user/my_fds.php'));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            setFlash('error', 'FD creation failed.');
        }
    }
}

$page_title = 'Fixed Deposit';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2>Apply for Fixed Deposit</h2>
    <p>Available balance: <strong><?= formatINR($account['balance']) ?></strong></p>
    <form method="POST" id="fd-form"><?= csrfField() ?>
        <div class="form-group"><label>Principal (₹, min 1000)</label><input type="number" name="principal" id="fd-principal" min="1000" step="100" required></div>
        <div class="form-group"><label>Tenure</label>
            <select name="tenure_months" id="fd-tenure" required>
                <option value="3">3 months (5.5%)</option>
                <option value="6">6 months (6.0%)</option>
                <option value="12" selected>12 months (6.75%)</option>
                <option value="24">24 months (7.0%)</option>
                <option value="36">36 months (7.25%)</option>
            </select>
        </div>
        <div class="emi-preview" id="fd-preview">Maturity amount: —</div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Book this FD?');">Submit FD</button>
    </form>
</div>
<script>
const rates = {3:5.5,6:6.0,12:6.75,24:7.0,36:7.25};
function updateFdPreview() {
    const p = parseFloat(document.getElementById('fd-principal').value) || 0;
    const t = parseInt(document.getElementById('fd-tenure').value);
    const r = rates[t];
    const maturity = p * (1 + (r/100) * (t/12));
    document.getElementById('fd-preview').textContent = 'Maturity amount: ₹' + maturity.toFixed(2) + ' at ' + r + '%';
}
document.getElementById('fd-principal').addEventListener('input', updateFdPreview);
document.getElementById('fd-tenure').addEventListener('change', updateFdPreview);
</script>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
