<?php
/**
 * Admin - approve/reject loan applications.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';
requireAdmin();

$status_filter = $_GET['status'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $loan_id = (int) ($_POST['loan_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    $q = $conn->prepare('SELECT la.*, u.email FROM loan_applications la JOIN users u ON la.user_id = u.id WHERE la.id = ?');
    $q->bind_param('i', $loan_id);
    $q->execute();
    $loan = $q->get_result()->fetch_assoc();

    if ($loan && $action === 'approve' && $loan['status'] === 'pending') {
        $conn->begin_transaction();
        try {
            $upd = $conn->prepare("UPDATE loan_applications SET status='approved', approved_at=NOW(), admin_remarks=? WHERE id=?");
            $upd->bind_param('si', $remarks, $loan_id);
            $upd->execute();

            generateRepaymentSchedule($loan_id, $loan['amount_requested'], $loan['interest_rate'], $loan['tenure_months'], $conn);

            $acc = $conn->prepare('SELECT balance FROM accounts WHERE id = ?');
            $acc->bind_param('i', $loan['account_id']);
            $acc->execute();
            $bal = $acc->get_result()->fetch_assoc()['balance'];
            $new_bal = $bal + $loan['amount_requested'];
            $upd_acc = $conn->prepare('UPDATE accounts SET balance = ? WHERE id = ?');
            $upd_acc->bind_param('di', $new_bal, $loan['account_id']);
            $upd_acc->execute();
            recordTransaction($conn, $loan['account_id'], 'credit', $loan['amount_requested'], 'Loan disbursement - ' . $loan['loan_ref'], $new_bal, 'Loan approved');

            $active = $conn->prepare("UPDATE loan_applications SET status='active' WHERE id=?");
            $active->bind_param('i', $loan_id);
            $active->execute();

            $conn->commit();
            @sendMail($loan['email'], 'Loan Approved - Finova Bank', '<p>Your loan ' . e($loan['loan_ref']) . ' has been approved. ' . e($remarks) . '</p>');
            setFlash('success', 'Loan approved and disbursed.');
        } catch (Exception $e) {
            $conn->rollback();
            setFlash('error', 'Approval failed.');
        }
    } elseif ($loan && $action === 'reject' && $loan['status'] === 'pending') {
        $upd = $conn->prepare("UPDATE loan_applications SET status='rejected', admin_remarks=? WHERE id=?");
        $upd->bind_param('si', $remarks, $loan_id);
        $upd->execute();
        @sendMail($loan['email'], 'Loan Rejected - Finova Bank', '<p>Your loan was rejected. Remarks: ' . e($remarks) . '</p>');
        setFlash('success', 'Loan rejected.');
    }
    header('Location: ' . url('admin/loan_applications.php'));
    exit;
}

$sql = 'SELECT la.*, u.full_name, lt.type_name FROM loan_applications la
    JOIN users u ON la.user_id = u.id JOIN loan_types lt ON la.loan_type_id = lt.id';
if ($status_filter !== 'all') {
    $sql .= " WHERE la.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= ' ORDER BY la.applied_at DESC';
$loans = $conn->query($sql);

$page_title = 'Loan Applications';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <form method="GET" class="filter-bar">
        <div class="form-group"><label>Status</label>
            <select name="status" onchange="this.form.submit()">
                <option value="all">All</option>
                <?php foreach (['pending','approved','rejected','active','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ref</th><th>User</th><th>Type</th><th>Amount</th><th>EMI</th><th>Tenure</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($l = $loans->fetch_assoc()): ?>
                <tr>
                    <td><?= e($l['loan_ref']) ?></td>
                    <td><?= e($l['full_name']) ?></td>
                    <td><?= e($l['type_name']) ?></td>
                    <td><?= formatINR($l['amount_requested']) ?></td>
                    <td><?= formatINR($l['emi_amount']) ?></td>
                    <td><?= (int) $l['tenure_months'] ?></td>
                    <td><span class="badge <?= badgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
                    <td><?= e(date('d M Y', strtotime($l['applied_at']))) ?></td>
                    <td class="actions">
                        <a href="<?= url('admin/loan_detail.php?id=' . (int) $l['id']) ?>" class="btn btn-sm btn-secondary">View</a>
                        <?php if ($l['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;"><?= csrfField() ?>
                            <input type="hidden" name="loan_id" value="<?= (int) $l['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="text" name="remarks" placeholder="Remarks" required style="width:100px;">
                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Approve loan?');">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;"><?= csrfField() ?>
                            <input type="hidden" name="loan_id" value="<?= (int) $l['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="remarks" value="Does not meet criteria">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject loan?');">Reject</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
