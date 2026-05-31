<?php
/**
 * Two-factor OTP verification after login.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['pre_auth_user_id'])) {
    header('Location: ' . url('auth/login.php'));
    exit;
}

$user_id = (int) $_SESSION['pre_auth_user_id'];
$_SESSION['otp_attempts'] = $_SESSION['otp_attempts'] ?? 0;

if (isset($_SESSION['otp_locked_until']) && time() < $_SESSION['otp_locked_until']) {
    $error = 'Too many failed attempts. Try again after 15 minutes.';
} else {
    unset($_SESSION['otp_locked_until']);
    $error = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!validateCsrf()) {
        $error = 'Invalid security token.';
    } else {
        $otp_entered = trim($_POST['otp'] ?? '');
        $stmt = $conn->prepare('SELECT * FROM otp_tokens WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $token = $stmt->get_result()->fetch_assoc();

        if ($token && $token['otp_code'] === $otp_entered) {
            $upd = $conn->prepare('UPDATE otp_tokens SET used = 1 WHERE id = ?');
            $upd->bind_param('i', $token['id']);
            $upd->execute();

            $u = $conn->prepare('SELECT full_name, email FROM users WHERE id = ?');
            $u->bind_param('i', $user_id);
            $u->execute();
            $user = $u->get_result()->fetch_assoc();

            unset($_SESSION['pre_auth_user_id'], $_SESSION['otp_attempts']);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = 'user';

            setFlash('success', 'Login successful!');
            header('Location: ' . url('user/dashboard.php'));
            exit;
        }

        $_SESSION['otp_attempts']++;
        if ($_SESSION['otp_attempts'] >= 3) {
            $_SESSION['otp_locked_until'] = time() + 900;
            $error = 'Too many failed attempts. Locked for 15 minutes.';
        } else {
            $error = 'Invalid or expired OTP. Attempts remaining: ' . (3 - $_SESSION['otp_attempts']);
        }
    }
}

$page_title = 'Verify OTP';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Enter OTP</h2>
        <p>A 6-digit code was sent to your registered email. Valid for 10 minutes.</p>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group"><label>OTP Code</label><input type="text" name="otp" pattern="[0-9]{6}" maxlength="6" required autofocus></div>
            <button type="submit" class="btn btn-primary btn-block" <?= !empty($_SESSION['otp_locked_until']) && time() < $_SESSION['otp_locked_until'] ? 'disabled' : '' ?>>Verify</button>
        </form>
        <p style="text-align:center;margin-top:16px;"><a href="<?= url('auth/login.php') ?>">Back to login</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
