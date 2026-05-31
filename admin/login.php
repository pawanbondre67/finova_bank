<?php
/**
 * Admin login.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT * FROM admins WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['admin_name'];
        $_SESSION['role'] = 'admin';
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
    $error = 'Invalid admin credentials.';
}

$page_title = 'Admin Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="card">
        <h2>Admin Login</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST"><?= csrfField() ?>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="admin@finovabank.com" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p style="text-align:center;margin-top:16px;"><a href="<?= url('index.php') ?>">← Back to site</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
