<?php
/**
 * Reset password using token from email.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$valid = false;
$email = '';

if ($token) {
    $stmt = $conn->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $valid = true;
        $email = $row['email'];
    } else {
        $error = 'Invalid or expired reset link.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $valid = true;
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
        $valid = true;
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $upd->bind_param('ss', $hash, $email);
        $upd->execute();
        $mark = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
        $mark->bind_param('s', $token);
        $mark->execute();
        setFlash('success', 'Password updated. Please login.');
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

$page_title = 'Reset Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Reset Password</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($valid && !$error): ?>
        <form method="POST"><?= csrfField() ?>
            <div class="form-group"><label>New Password</label><input type="password" name="password" minlength="8" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" minlength="8" required></div>
            <button type="submit" class="btn btn-primary btn-block">Update Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
