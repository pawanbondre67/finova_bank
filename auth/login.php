<?php
/**
 * User login with 2FA OTP redirect.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('user/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $error = 'Invalid security token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ($user['auth_provider'] === 'google') {
            $error = "This account uses Google login. Please click 'Continue with Google'.";
        } elseif (!password_verify($password, $user['password'] ?? '')) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] === 'frozen') {
            $error = 'Your account has been frozen. Please contact support at support@finovabank.com';
        } else {
            $acc = $conn->prepare('SELECT status FROM accounts WHERE user_id = ? LIMIT 1');
            $acc->bind_param('i', $user['id']);
            $acc->execute();
            $account = $acc->get_result()->fetch_assoc();
            if ($account && $account['status'] === 'frozen') {
                $error = 'Your account has been frozen. Please contact support at support@finovabank.com';
            } else {
                $otp = (string) random_int(100000, 999999);
                $ins = $conn->prepare('INSERT INTO otp_tokens (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
                $ins->bind_param('is', $user['id'], $otp);
                $ins->execute();

                $_SESSION['pre_auth_user_id'] = $user['id'];
                $_SESSION['otp_attempts'] = 0;

                require_once __DIR__ . '/../mail/mailer.php';
                $body = '<p>Your OTP for login is: <strong>' . e($otp) . '</strong></p><p>Valid for 10 minutes. Do not share this with anyone.</p>';
                @sendMail($user['email'], 'Your Finova Bank Login OTP', $body);

                header('Location: ' . url('auth/verify_otp.php'));
                exit;
            }
        }
    }
}

$page_title = 'Login';
include __DIR__ . '/../includes/header.php';
$flash = getFlash();
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Customer Login</h2>
        <?php if ($flash): ?><div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p style="text-align:center;margin-top:12px;"><a href="<?= url('auth/forgot_password.php') ?>">Forgot password?</a></p>
        <div class="divider-or">— or —</div>
        <a href="<?= url('auth/google_login.php') ?>" class="google-btn">
            <img src="https://developers.google.com/identity/images/g-logo.png" width="18" height="18" alt="Google">
            Continue with Google
        </a>
        <p style="text-align:center;margin-top:16px;">New user? <a href="<?= url('auth/register.php') ?>">Register</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
