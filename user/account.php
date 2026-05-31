<?php
/**
 * View account details.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$account = getUserAccount($conn, (int) $_SESSION['user_id']);
$u = $conn->prepare('SELECT * FROM users WHERE id = ?');
$u->bind_param('i', $_SESSION['user_id']);
$u->execute();
$user = $u->get_result()->fetch_assoc();

$page_title = 'My Account';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2>Account Details</h2>
    <table>
        <tr><th>Account Number</th><td><?= e($account['account_no']) ?></td></tr>
        <tr><th>Account Type</th><td><?= e(ucfirst($account['account_type'])) ?></td></tr>
        <tr><th>Balance</th><td><?= formatINR($account['balance']) ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?= badgeClass($account['status']) ?>"><?= e($account['status']) ?></span></td></tr>
        <tr><th>Opened</th><td><?= e($account['open_date']) ?></td></tr>
        <tr><th>Account Holder</th><td><?= e($user['full_name']) ?></td></tr>
        <tr><th>Email</th><td><?= e($user['email']) ?></td></tr>
        <tr><th>Phone</th><td><?= e($user['phone'] ?? '—') ?></td></tr>
    </table>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
