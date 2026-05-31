<?php
/**
 * Forgot password - sends reset link via email.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $email = trim($_POST['email'] ?? '');
    $stmt = $conn->prepare('SELECT id, auth_provider FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['auth_provider'] === 'google') {
        $error = 'Google accounts cannot reset password here. Use Continue with Google.';
    } elseif ($user) {
        $token = bin2hex(random_bytes(32));
        $ins = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
        $ins->bind_param('ss', $email, $token);
        $ins->execute();

        $link = 'http://' . $_SERVER['HTTP_HOST'] . url('auth/reset_password.php') . '?token=' . urlencode($token);
        $body = '<p>Click to reset your password:</p><p><a href="' . e($link) . '">' . e($link) . '</a></p><p>Valid for 1 hour.</p>';
        @sendMail($email, 'Finova Bank Password Reset', $body);
    }
    $message = 'If that email exists, a reset link has been sent.';
}

$page_title = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Forgot Password</h2>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST"><?= csrfField() ?>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
        </form>
        <p style="text-align:center;margin-top:16px;"><a href="<?= url('auth/login.php') ?>">Back to login</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
