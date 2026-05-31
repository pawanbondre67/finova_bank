<?php
/**
 * Freeze or unfreeze account (POST handler).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . url('admin/accounts.php'));
    exit;
}

$account_id = (int) ($_POST['account_id'] ?? 0);
$action = $_POST['action'] ?? '';
$new_status = $action === 'freeze' ? 'frozen' : 'active';

if ($account_id && in_array($new_status, ['frozen', 'active'])) {
    $stmt = $conn->prepare('UPDATE accounts SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $new_status, $account_id);
    $stmt->execute();
    setFlash('success', 'Account ' . ($new_status === 'frozen' ? 'frozen' : 'activated') . ' successfully.');
}

$redirect = $_POST['redirect'] ?? 'admin/accounts.php';
header('Location: ' . url($redirect));
exit;
