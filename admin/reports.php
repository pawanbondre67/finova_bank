<?php
/**
 * Admin reports with Chart.js visualizations.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$monthly = $conn->query("SELECT DATE_FORMAT(transaction_date, '%b %Y') as month,
    COUNT(*) as total, SUM(amount) as volume
    FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m'), month ORDER BY MIN(transaction_date) ASC");
$monthly_data = [];
while ($r = $monthly->fetch_assoc()) $monthly_data[] = $r;

$by_type = $conn->query("SELECT transaction_type, COUNT(*) as cnt, SUM(amount) as vol FROM transactions GROUP BY transaction_type");
$type_data = [];
while ($r = $by_type->fetch_assoc()) $type_data[] = $r;

$users_month = $conn->query("SELECT DATE_FORMAT(date_joined, '%b %Y') as month, COUNT(*) as cnt FROM users
    WHERE date_joined >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY MIN(date_joined)");

$page_title = 'Reports';
$panel = 'admin';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="charts-grid">
    <div class="chart-card"><h3>12-Month Transaction Volume</h3><canvas id="reportMonthly"></canvas></div>
    <div class="chart-card"><h3>Transactions by Type</h3><canvas id="reportType"></canvas></div>
</div>
<?php
$extra_scripts = '<script>
const monthlyData = ' . json_encode($monthly_data) . ';
const typeData = ' . json_encode($type_data) . ';
new Chart(document.getElementById("reportMonthly"), {
    type: "line",
    data: { labels: monthlyData.map(d=>d.month), datasets: [{ label: "Volume", data: monthlyData.map(d=>parseFloat(d.volume)), borderColor: "#1a56db", fill: false }] }
});
new Chart(document.getElementById("reportType"), {
    type: "bar",
    data: { labels: typeData.map(d=>d.transaction_type), datasets: [{ label: "Count", data: typeData.map(d=>d.cnt), backgroundColor: ["#059669","#dc2626","#2563eb"] }] }
});
</script>';
include __DIR__ . '/../includes/panel_footer.php';
