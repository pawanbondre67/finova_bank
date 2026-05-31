<?php
/**
 * User registration - creates user and savings account.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Security validation failed.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $address = trim($_POST['address'] ?? '');

        if ($full_name === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Phone must be 10 digits.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $chk = $conn->prepare('SELECT id FROM users WHERE email = ?');
            $chk->bind_param('s', $email);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $errors[] = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, password, address, auth_provider) VALUES (?, ?, ?, ?, ?, "local")');
                $stmt->bind_param('sssss', $full_name, $email, $phone, $hash, $address);
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $account_no = generateAccountNo();
                    $acc = $conn->prepare('INSERT INTO accounts (user_id, account_no, account_type, balance, open_date, status) VALUES (?, ?, "savings", 0.00, CURDATE(), "active")');
                    $acc->bind_param('is', $user_id, $account_no);
                    $acc->execute();
                    setFlash('success', 'Registration successful! Your account number is ' . $account_no . '. Please login.');
                    header('Location: ' . url('auth/login.php'));
                    exit;
                }
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
include __DIR__ . '/../includes/header.php';
$flash = getFlash();
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Create Account</h2>
        <?php if ($flash): ?><div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Phone (10 digits)</label><input type="text" name="phone" pattern="[0-9]{10}" required value="<?= e($_POST['phone'] ?? '') ?>"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" minlength="8" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" minlength="8" required></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= e($_POST['address'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <div class="divider-or">— or —</div>
        <a href="<?= url('auth/google_login.php') ?>" class="google-btn">
            <img src="https://developers.google.com/identity/images/g-logo.png" width="18" height="18" alt="Google">
            Continue with Google
        </a>
        <p style="font-size:12px;color:#888;text-align:center;margin-top:8px;">Signing up with Google skips manual registration and auto-creates your account.</p>
        <p style="text-align:center;margin-top:16px;">Already have an account? <a href="<?= url('auth/login.php') ?>">Login</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
