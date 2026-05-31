<?php
/**
 * List user's loan applications.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = $conn->prepare('SELECT la.*, lt.type_name FROM loan_applications la
    JOIN loan_types lt ON la.loan_type_id = lt.id WHERE la.user_id = ? ORDER BY la.applied_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$loans = $stmt->get_result();

$page_title = 'My Loans';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <p><a href="<?= url('user/apply_loan.php') ?>" class="btn btn-primary btn-sm">+ Apply for Loan</a></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ref</th><th>Type</th><th>Amount</th><th>EMI</th><th>Tenure</th><th>Status</th><th>Applied</th><th>Action</th></tr></thead>
            <tbody>
            <?php while ($l = $loans->fetch_assoc()): ?>
                <tr>
                    <td><?= e($l['loan_ref']) ?></td>
                    <td><?= e($l['type_name']) ?></td>
                    <td><?= formatINR($l['amount_requested']) ?></td>
                    <td><?= formatINR($l['emi_amount']) ?></td>
                    <td><?= (int) $l['tenure_months'] ?> mo</td>
                    <td><span class="badge <?= badgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
                    <td><?= e(date('d M Y', strtotime($l['applied_at']))) ?></td>
                    <td><a href="<?= url('user/loan_detail.php?id=' . (int) $l['id']) ?>" class="btn btn-sm btn-secondary">View Details</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
