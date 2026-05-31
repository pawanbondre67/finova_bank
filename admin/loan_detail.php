<?php
/**
 * Admin view of loan repayment schedule.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$loan_id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT la.*, u.full_name, lt.type_name FROM loan_applications la
    JOIN users u ON la.user_id = u.id JOIN loan_types lt ON la.loan_type_id = lt.id WHERE la.id = ?');
$stmt->bind_param('i', $loan_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    setFlash('error', 'Loan not found.');
    header('Location: ' . url('admin/loan_applications.php'));
    exit;
}

$schedule = $conn->prepare('SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY installment_no');
$schedule->bind_param('i', $loan_id);
$schedule->execute();
$rows = $schedule->get_result();

$page_title = 'Loan Detail';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2><?= e($loan['loan_ref']) ?> — <?= e($loan['full_name']) ?></h2>
    <p><?= e($loan['type_name']) ?> | <?= formatINR($loan['amount_requested']) ?> | Status: <span class="badge <?= badgeClass($loan['status']) ?>"><?= e($loan['status']) ?></span></p>
    <?php if ($loan['admin_remarks']): ?><p>Remarks: <?= e($loan['admin_remarks']) ?></p><?php endif; ?>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Due</th><th>EMI</th><th>Principal</th><th>Interest</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($r = $rows->fetch_assoc()): ?>
                <tr>
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
    <p style="margin-top:12px;"><a href="<?= url('admin/loan_applications.php') ?>">← Back</a></p>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
