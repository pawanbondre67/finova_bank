<?php
/**
 * View all transactions with filters.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$type = $_GET['type'] ?? 'all';
$user_search = trim($_GET['user'] ?? '');

$sql = 'SELECT t.*, a.account_no, u.full_name FROM transactions t
    JOIN accounts a ON t.account_id = a.id JOIN users u ON a.user_id = u.id WHERE 1=1';
if ($type !== 'all') $sql .= " AND t.transaction_type = '" . $conn->real_escape_string($type) . "'";
if ($date_from) $sql .= " AND DATE(t.transaction_date) >= '" . $conn->real_escape_string($date_from) . "'";
if ($date_to) $sql .= " AND DATE(t.transaction_date) <= '" . $conn->real_escape_string($date_to) . "'";
if ($user_search) $sql .= " AND (u.full_name LIKE '%" . $conn->real_escape_string($user_search) . "%' OR u.email LIKE '%" . $conn->real_escape_string($user_search) . "%')";
$sql .= ' ORDER BY t.transaction_date DESC LIMIT 100';
$rows = $conn->query($sql);

$page_title = 'Transactions';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <form method="GET" class="filter-bar">
        <div class="form-group"><label>Type</label>
            <select name="type"><option value="all">All</option>
                <option value="credit" <?= $type==='credit'?'selected':'' ?>>Credit</option>
                <option value="debit" <?= $type==='debit'?'selected':'' ?>>Debit</option>
            </select>
        </div>
        <div class="form-group"><label>User</label><input type="text" name="user" value="<?= e($user_search) ?>" placeholder="Name or email"></div>
        <div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= e($date_from) ?>"></div>
        <div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= e($date_to) ?>"></div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>User</th><th>Account</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
            <tbody>
            <?php while ($t = $rows->fetch_assoc()): ?>
                <tr>
                    <td><?= e(date('d M Y H:i', strtotime($t['transaction_date']))) ?></td>
                    <td><?= e($t['full_name']) ?></td>
                    <td><?= e($t['account_no']) ?></td>
                    <td><span class="badge <?= badgeClass($t['transaction_type']) ?>"><?= e($t['transaction_type']) ?></span></td>
                    <td><?= formatINR($t['amount']) ?></td>
                    <td><?= e($t['description']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
