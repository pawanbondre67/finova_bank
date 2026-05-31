<?php
/**
 * Balance inquiry.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$account = getUserAccount($conn, (int) $_SESSION['user_id']);
$last = $conn->query('SELECT MAX(history_date) as updated FROM transaction_history WHERE account_id = ' . (int) $account['id']);
$updated = $last->fetch_assoc()['updated'] ?? $account['open_date'];

$page_title = 'Balance Inquiry';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="balance-card">
    <p>Current Balance</p>
    <p class="amount"><?= formatINR($account['balance']) ?></p>
</div>
<div class="card">
    <table>
        <tr><th>Account Number</th><td><?= e($account['account_no']) ?></td></tr>
        <tr><th>Account Type</th><td><?= e(ucfirst($account['account_type'])) ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?= badgeClass($account['status']) ?>"><?= e($account['status']) ?></span></td></tr>
        <tr><th>Last Updated</th><td><?= e($updated ? date('d M Y H:i:s', strtotime($updated)) : 'N/A') ?></td></tr>
    </table>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
