<?php
/**
 * Loan detail and repayment schedule.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$loan_id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT la.*, lt.type_name FROM loan_applications la
    JOIN loan_types lt ON la.loan_type_id = lt.id
    WHERE la.id = ? AND la.user_id = ?');
$stmt->bind_param('ii', $loan_id, $_SESSION['user_id']);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    setFlash('error', 'Loan not found.');
    header('Location: ' . url('user/my_loans.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf() && $loan['status'] === 'active' && isset($_POST['pay_emi'])) {
    $rep = $conn->prepare("SELECT * FROM loan_repayments WHERE loan_id = ? AND status = 'pending' ORDER BY installment_no ASC LIMIT 1");
    $rep->bind_param('i', $loan_id);
    $rep->execute();
    $installment = $rep->get_result()->fetch_assoc();
    if ($installment) {
        $account = getUserAccount($conn, (int) $_SESSION['user_id']);
        if ($account['balance'] >= $installment['emi_amount']) {
            $new_bal = $account['balance'] - $installment['emi_amount'];
            $upd = $conn->prepare('UPDATE accounts SET balance = ? WHERE id = ?');
            $upd->bind_param('di', $new_bal, $account['id']);
            $upd->execute();
            recordTransaction($conn, $account['id'], 'debit', $installment['emi_amount'], 'EMI payment - ' . $loan['loan_ref'], $new_bal, 'Loan EMI');
            $pay = $conn->prepare("UPDATE loan_repayments SET status='paid', paid_amount=?, paid_date=CURDATE() WHERE id=?");
            $pay->bind_param('di', $installment['emi_amount'], $installment['id']);
            $pay->execute();
            setFlash('success', 'EMI paid for installment #' . $installment['installment_no']);
        } else {
            setFlash('error', 'Insufficient balance for EMI.');
        }
    }
    header('Location: ' . url('user/loan_detail.php?id=' . $loan_id));
    exit;
}

$schedule = $conn->prepare('SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY installment_no');
$schedule->bind_param('i', $loan_id);
$schedule->execute();
$rows = $schedule->get_result();

$page_title = 'Loan Details';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2><?= e($loan['loan_ref']) ?> — <?= e($loan['type_name']) ?></h2>
    <table>
        <tr><th>Amount</th><td><?= formatINR($loan['amount_requested']) ?></td></tr>
        <tr><th>Rate</th><td><?= e($loan['interest_rate']) ?>%</td></tr>
        <tr><th>EMI</th><td><?= formatINR($loan['emi_amount']) ?></td></tr>
        <tr><th>Total Payable</th><td><?= formatINR($loan['total_payable']) ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?= badgeClass($loan['status']) ?>"><?= e($loan['status']) ?></span></td></tr>
    </table>
    <?php if ($loan['status'] === 'active'): ?>
    <form method="POST" style="margin-top:16px;"><?= csrfField() ?>
        <button type="submit" name="pay_emi" value="1" class="btn btn-primary" onclick="return confirm('Pay next EMI?');">Pay EMI</button>
    </form>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Repayment Schedule</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Due Date</th><th>EMI</th><th>Principal</th><th>Interest</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($r = $rows->fetch_assoc()):
                $rowClass = $r['status'] === 'paid' ? 'style="background:#d1fae5"' : ($r['status'] === 'overdue' ? 'style="background:#fee2e2"' : '');
            ?>
                <tr <?= $rowClass ?>>
                    <td><?= (int) $r['installment_no'] ?></td>
                    <td><?= e($r['due_date']) ?></td>
                    <td><?= formatINR($r['emi_amount']) ?></td>
                    <td><?= formatINR($r['principal_part']) ?></td>
                    <td><?= formatINR($r['interest_part']) ?></td>
                    <td><?= formatINR($r['outstanding_bal']) ?></td>
                    <td><span class="badge <?= badgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
