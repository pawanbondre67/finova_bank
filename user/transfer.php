<?php
/**
 * Fund transfer between Finova accounts.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$sender = getUserAccount($conn, $user_id);
$error = '';
$success_ref = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $recipient_no = trim($_POST['recipient_account'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? 'Fund transfer');

    if ($amount <= 0) {
        $error = 'Enter a valid amount.';
    } elseif ($recipient_no === $sender['account_no']) {
        $error = 'Cannot transfer to your own account.';
    } elseif ($sender['status'] !== 'active') {
        $error = 'Your account is not active.';
    } elseif ($amount > $sender['balance']) {
        $error = 'Insufficient balance.';
    } else {
        $r = $conn->prepare('SELECT a.*, u.email, u.full_name FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.account_no = ? AND a.status = "active" LIMIT 1');
        $r->bind_param('s', $recipient_no);
        $r->execute();
        $recipient = $r->get_result()->fetch_assoc();

        if (!$recipient) {
            $error = 'Recipient account not found or inactive.';
        } else {
            $conn->begin_transaction();
            try {
                $new_sender_bal = $sender['balance'] - $amount;
                $new_recv_bal = $recipient['balance'] + $amount;

                $upd1 = $conn->prepare('UPDATE accounts SET balance = ? WHERE id = ?');
                $upd1->bind_param('di', $new_sender_bal, $sender['id']);
                $upd1->execute();

                $upd2 = $conn->prepare('UPDATE accounts SET balance = ? WHERE id = ?');
                $upd2->bind_param('di', $new_recv_bal, $recipient['id']);
                $upd2->execute();

                $desc_out = $description . ' to ' . $recipient_no;
                $desc_in = $description . ' from ' . $sender['account_no'];

                recordTransaction($conn, $sender['id'], 'debit', $amount, $desc_out, $new_sender_bal, 'Transfer out');
                recordTransaction($conn, $recipient['id'], 'credit', $amount, $desc_in, $new_recv_bal, 'Transfer in');

                $conn->commit();
                $success_ref = generateRef('TXN');

                $su = $conn->prepare('SELECT email FROM users WHERE id = ?');
                $su->bind_param('i', $user_id);
                $su->execute();
                $sender_email = $su->get_result()->fetch_assoc()['email'];

                $body = '<p>Transfer of ' . formatINR($amount) . ' completed. Ref: ' . e($success_ref) . '</p>';
                @sendMail($sender_email, 'Transfer Sent - Finova Bank', $body);
                @sendMail($recipient['email'], 'Funds Received - Finova Bank', '<p>You received ' . formatINR($amount) . ' from ' . e($sender['account_no']) . '.</p>');

                if ($new_sender_bal < 1000) {
                    @sendMail($sender_email, 'Low Balance Alert - Finova Bank', '<p>Your balance is below ₹1000: ' . formatINR($new_sender_bal) . '</p>');
                }

                setFlash('success', 'Transfer successful! Reference: ' . $success_ref);
                header('Location: ' . url('user/transfer.php'));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Transfer failed. Please try again.';
            }
        }
    }
}

$page_title = 'Fund Transfer';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <h2>Transfer Funds</h2>
    <p>Available: <strong><?= formatINR($sender['balance']) ?></strong> (<?= e($sender['account_no']) ?>)</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" onsubmit="return confirm('Confirm this transfer?');">
        <?= csrfField() ?>
        <div class="form-group"><label>Recipient Account (FNV...)</label><input type="text" name="recipient_account" required pattern="FNV[0-9]{7}" placeholder="FNV9876543"></div>
        <div class="form-group"><label>Amount (₹)</label><input type="number" name="amount" step="0.01" min="1" required></div>
        <div class="form-group"><label>Description</label><input type="text" name="description" value="Fund transfer"></div>
        <button type="submit" class="btn btn-primary">Transfer</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
