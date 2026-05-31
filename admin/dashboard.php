<?php
/**
 * Admin dashboard with stats, charts, recent transactions.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$stats = [];
$stats['users'] = $conn->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'];
$stats['transactions'] = $conn->query('SELECT COUNT(*) c FROM transactions')->fetch_assoc()['c'];
$stats['loans'] = $conn->query("SELECT COUNT(*) c FROM loan_applications WHERE status IN ('approved','active')")->fetch_assoc()['c'];
$stats['deposits'] = $conn->query("SELECT COALESCE(SUM(principal),0) c FROM fixed_deposits WHERE status='active'")->fetch_assoc()['c'];

$monthly = $conn->query("SELECT DATE_FORMAT(transaction_date, '%b %Y') as month,
    COUNT(*) as total, SUM(amount) as volume
    FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m'), month ORDER BY MIN(transaction_date) ASC");
$monthly_data = [];
while ($r = $monthly->fetch_assoc()) $monthly_data[] = $r;

$loan_status = $conn->query('SELECT status, COUNT(*) as count FROM loan_applications GROUP BY status');
$loan_data = [];
while ($r = $loan_status->fetch_assoc()) $loan_data[] = $r;

$acc_types = $conn->query('SELECT account_type, COUNT(*) as count FROM accounts GROUP BY account_type');
$acc_data = [];
while ($r = $acc_types->fetch_assoc()) $acc_data[] = $r;

$recent = $conn->query('SELECT t.*, a.account_no, u.full_name FROM transactions t
    JOIN accounts a ON t.account_id = a.id JOIN users u ON a.user_id = u.id
    ORDER BY t.transaction_date DESC LIMIT 10');

$page_title = 'Admin Dashboard';
$panel = 'admin';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="stats-grid">
    <div class="stat-card"><h3>Total Users</h3><div class="value"><?= (int) $stats['users'] ?></div></div>
    <div class="stat-card"><h3>Transactions</h3><div class="value"><?= (int) $stats['transactions'] ?></div></div>
    <div class="stat-card"><h3>Active Loans</h3><div class="value"><?= (int) $stats['loans'] ?></div></div>
    <div class="stat-card"><h3>FD Deposits</h3><div class="value"><?= formatINR($stats['deposits']) ?></div></div>
</div>
<div class="charts-grid">
    <div class="chart-card"><h3>Monthly Transaction Volume</h3><canvas id="chartMonthly"></canvas></div>
    <div class="chart-card"><h3>Loan Status</h3><canvas id="chartLoans"></canvas></div>
    <div class="chart-card"><h3>Account Types</h3><canvas id="chartAccounts"></canvas></div>
</div>
<div class="card">
    <h2>Recent Transactions</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>User</th><th>Account</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
            <tbody>
            <?php while ($t = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($t['transaction_date']))) ?></td>
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
<?php
$extra_scripts = '<script>
const monthlyData = ' . json_encode($monthly_data) . ';
const loanData = ' . json_encode($loan_data) . ';
const accData = ' . json_encode($acc_data) . ';
new Chart(document.getElementById("chartMonthly"), {
    type: "bar",
    data: {
        labels: monthlyData.map(d => d.month),
        datasets: [{ label: "Volume (₹)", data: monthlyData.map(d => parseFloat(d.volume)), backgroundColor: "#1a56db" }]
    }
});
new Chart(document.getElementById("chartLoans"), {
    type: "pie",
    data: { labels: loanData.map(d => d.status), datasets: [{ data: loanData.map(d => d.count), backgroundColor: ["#fbbf24","#3b82f6","#059669","#dc2626","#94a3b8"] }] }
});
new Chart(document.getElementById("chartAccounts"), {
    type: "doughnut",
    data: { labels: accData.map(d => d.account_type), datasets: [{ data: accData.map(d => d.count), backgroundColor: ["#1a56db","#0ea5e9","#6366f1"] }] }
});
</script>';
include __DIR__ . '/../includes/panel_footer.php';
