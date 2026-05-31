<?php
/**
 * Download PDF bank statement (FPDF).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$account = getUserAccount($conn, $user_id);
$u = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$u->bind_param('i', $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        setFlash('error', 'FPDF not installed. Run: composer require setasign/fpdf');
        header('Location: ' . url('user/statement_pdf.php'));
        exit;
    }
    require_once $autoload;

    $stmt = $conn->prepare('SELECT t.*, th.balance_after FROM transactions t
        LEFT JOIN transaction_history th ON t.id = th.transaction_id
        WHERE t.account_id = ? AND DATE(t.transaction_date) BETWEEN ? AND ?
        ORDER BY t.transaction_date ASC');
    $stmt->bind_param('iss', $account['id'], $date_from, $date_to);
    $stmt->execute();
    $txns = $stmt->get_result();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'FINOVA BANK', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Account Statement', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated: ' . date('d M Y H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Account No: ' . $account['account_no'], 0, 1);
    $pdf->Cell(0, 6, 'Holder: ' . $user['full_name'], 0, 1);
    $pdf->Cell(0, 6, 'Type: ' . ucfirst($account['account_type']), 0, 1);
    $pdf->Cell(0, 6, 'Period: ' . $date_from . ' to ' . $date_to, 0, 1);
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(35, 7, 'Date', 1);
    $pdf->Cell(55, 7, 'Description', 1);
    $pdf->Cell(30, 7, 'Debit', 1);
    $pdf->Cell(30, 7, 'Credit', 1);
    $pdf->Cell(30, 7, 'Balance', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    while ($row = $txns->fetch_assoc()) {
        $debit = in_array($row['transaction_type'], ['debit']) ? number_format($row['amount'], 2) : '';
        $credit = $row['transaction_type'] === 'credit' ? number_format($row['amount'], 2) : ($row['transaction_type'] === 'transfer' ? '' : '');
        if ($row['transaction_type'] === 'debit') {
            $debit = number_format($row['amount'], 2);
            $credit = '';
        } else {
            $credit = number_format($row['amount'], 2);
            $debit = '';
        }
        $pdf->Cell(35, 6, date('d/m/Y', strtotime($row['transaction_date'])), 1);
        $pdf->Cell(55, 6, substr($row['description'] ?? '', 0, 28), 1);
        $pdf->Cell(30, 6, $debit, 1);
        $pdf->Cell(30, 6, $credit, 1);
        $pdf->Cell(30, 6, number_format($row['balance_after'] ?? 0, 2), 1);
        $pdf->Ln();
    }
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'This is a system-generated statement.', 0, 1, 'C');
    $pdf->Output('D', 'statement_' . $account['account_no'] . '.pdf');
    exit;
}

$page_title = 'PDF Statement';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2>Download Bank Statement</h2>
    <form method="POST"><?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Date From</label><input type="date" name="date_from" required></div>
            <div class="form-group"><label>Date To</label><input type="date" name="date_to" required></div>
        </div>
        <button type="submit" class="btn btn-primary">Generate PDF</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
