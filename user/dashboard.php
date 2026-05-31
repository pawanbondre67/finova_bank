<?php
/**
 * User dashboard - balance, mini statement, quick actions.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$account = getUserAccount($conn, $user_id);

$stmt = $conn->prepare('SELECT t.*, th.balance_after
    FROM transactions t
    JOIN transaction_history th ON t.id = th.transaction_id
    WHERE t.account_id = ?
    ORDER BY t.transaction_date DESC
    LIMIT 5');
$stmt->bind_param('i', $account['id']);
$stmt->execute();
$mini = $stmt->get_result();

$page_title = 'Dashboard';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="balance-card">
    <p>Welcome, <?= e($_SESSION['user_name']) ?></p>
    <p>Account: <?= e($account['account_no'] ?? 'N/A') ?></p>
    <p class="amount"><?= formatINR($account['balance'] ?? 0) ?></p>
    <span>Available Balance</span>
</div>
<div class="quick-actions">
    <a href="<?= url('user/transfer.php') ?>" class="btn btn-primary">Transfer</a>
    <a href="<?= url('user/balance.php') ?>" class="btn btn-secondary">Check Balance</a>
    <a href="<?= url('user/apply_loan.php') ?>" class="btn btn-secondary">Apply for Loan</a>
    <a href="<?= url('user/statement_pdf.php') ?>" class="btn btn-secondary">Download Statement</a>
</div>
<div class="card">
    <h2>Mini Statement (Last 5)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Balance</th></tr></thead>
            <tbody>
            <?php if ($mini->num_rows === 0): ?>
                <tr><td colspan="5">No transactions yet.</td></tr>
            <?php else: while ($row = $mini->fetch_assoc()): ?>
                <tr>
                    <td><?= e(date('d M Y H:i', strtotime($row['transaction_date']))) ?></td>
                    <td><span class="badge <?= badgeClass($row['transaction_type']) ?>"><?= e($row['transaction_type']) ?></span></td>
                    <td><?= formatINR($row['amount']) ?></td>
                    <td><?= e($row['description']) ?></td>
                    <td><?= formatINR($row['balance_after']) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <p style="margin-top:12px;"><a href="<?= url('user/history.php') ?>">View full history →</a></p>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
