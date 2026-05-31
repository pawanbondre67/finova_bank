<?php
/**
 * Transaction history with filters and pagination.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$account = getUserAccount($conn, (int) $_SESSION['user_id']);
$type_filter = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = 't.account_id = ?';
$params = [$account['id']];
$types = 'i';

if ($type_filter !== 'all' && in_array($type_filter, ['credit', 'debit', 'transfer'])) {
    $where .= ' AND t.transaction_type = ?';
    $params[] = $type_filter;
    $types .= 's';
}
if ($date_from) {
    $where .= ' AND DATE(t.transaction_date) >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to) {
    $where .= ' AND DATE(t.transaction_date) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

$count_sql = "SELECT COUNT(*) as c FROM transactions t WHERE $where";
$cs = $conn->prepare($count_sql);
$cs->bind_param($types, ...$params);
$cs->execute();
$total = (int) $cs->get_result()->fetch_assoc()['c'];
$total_pages = max(1, (int) ceil($total / $per_page));

$sql = "SELECT t.*, th.balance_after FROM transactions t
    LEFT JOIN transaction_history th ON t.id = th.transaction_id
    WHERE $where ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

$page_title = 'Transaction History';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <form method="GET" class="filter-bar">
        <div class="form-group"><label>Type</label>
            <select name="type">
                <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="credit" <?= $type_filter === 'credit' ? 'selected' : '' ?>>Credit</option>
                <option value="debit" <?= $type_filter === 'debit' ? 'selected' : '' ?>>Debit</option>
                <option value="transfer" <?= $type_filter === 'transfer' ? 'selected' : '' ?>>Transfer</option>
            </select>
        </div>
        <div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= e($date_from) ?>"></div>
        <div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= e($date_to) ?>"></div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Balance After</th></tr></thead>
            <tbody>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($row['transaction_date']))) ?></td>
                    <td><span class="badge <?= badgeClass($row['transaction_type']) ?>"><?= e($row['transaction_type']) ?></span></td>
                    <td><?= formatINR($row['amount']) ?></td>
                    <td><?= e($row['description']) ?></td>
                    <td><?= formatINR($row['balance_after'] ?? 0) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&type=<?= e($type_filter) ?>&date_from=<?= e($date_from) ?>&date_to=<?= e($date_to) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
