<?php
/**
 * Shared helper functions for Finova Bank.
 */
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function calculateEMI($principal, $annual_rate, $tenure_months)
{
    $r = $annual_rate / 12 / 100;
    if ($r == 0) {
        return $principal / $tenure_months;
    }
    return $principal * $r * pow(1 + $r, $tenure_months) / (pow(1 + $r, $tenure_months) - 1);
}

function generateRef($prefix)
{
    return $prefix . date('ymd') . strtoupper(substr(uniqid(), -4));
}

function generateAccountNo()
{
    return 'FNV' . random_int(1000000, 9999999);
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatINR($amount)
{
    return '₹' . number_format((float) $amount, 2);
}

function e($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    $path = ltrim($path, '/');
    return APP_BASE . ($path ? '/' . $path : '');
}

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

function requireAdmin()
{
    if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function validateCsrf()
{
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        setFlash('error', 'Invalid security token. Please try again.');
        return false;
    }
    return true;
}

function getUserAccount($conn, $user_id)
{
    $stmt = $conn->prepare('SELECT * FROM accounts WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function generateRepaymentSchedule($loan_id, $principal, $annual_rate, $tenure, $conn)
{
    $r = $annual_rate / 12 / 100;
    $emi = calculateEMI($principal, $annual_rate, $tenure);
    $balance = $principal;
    $due_date = date('Y-m-d', strtotime('+1 month'));

    for ($i = 1; $i <= $tenure; $i++) {
        $interest = $balance * $r;
        $principal_part = $emi - $interest;
        $balance -= $principal_part;
        if ($balance < 0) {
            $balance = 0;
        }

        $stmt = $conn->prepare('INSERT INTO loan_repayments
            (loan_id, installment_no, due_date, emi_amount, principal_part, interest_part, outstanding_bal)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisdddd', $loan_id, $i, $due_date, $emi, $principal_part, $interest, $balance);
        $stmt->execute();
        $due_date = date('Y-m-d', strtotime($due_date . ' +1 month'));
    }
}

function fdInterestRate($tenure_months)
{
    $rates = [3 => 5.5, 6 => 6.0, 12 => 6.75, 24 => 7.0, 36 => 7.25];
    return $rates[$tenure_months] ?? 6.0;
}

function fdMaturityAmount($principal, $rate, $tenure_months)
{
    return $principal * (1 + ($rate / 100) * ($tenure_months / 12));
}

function recordTransaction($conn, $account_id, $type, $amount, $description, $balance_after, $remarks = '')
{
    $stmt = $conn->prepare('INSERT INTO transactions (account_id, transaction_type, amount, description) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isds', $account_id, $type, $amount, $description);
    $stmt->execute();
    $txn_id = $conn->insert_id;

    $hist = $conn->prepare('INSERT INTO transaction_history (transaction_id, account_id, amount, balance_after, remarks) VALUES (?, ?, ?, ?, ?)');
    $hist->bind_param('iidds', $txn_id, $account_id, $amount, $balance_after, $remarks);
    $hist->execute();

    return $txn_id;
}

function badgeClass($status)
{
    $map = [
        'active' => 'badge-success',
        'success' => 'badge-success',
        'credit' => 'badge-success',
        'approved' => 'badge-info',
        'pending' => 'badge-warning',
        'frozen' => 'badge-danger',
        'rejected' => 'badge-danger',
        'debit' => 'badge-danger',
        'failed' => 'badge-danger',
        'closed' => 'badge-secondary',
        'paid' => 'badge-success',
        'overdue' => 'badge-danger',
        'matured' => 'badge-success',
    ];
    return $map[strtolower($status)] ?? 'badge-secondary';
}
