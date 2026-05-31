<?php
/**
 * Profile management and password change.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$is_google_user = ($user['auth_provider'] === 'google');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['action'] ?? 'profile';
    if ($action === 'profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($is_google_user && $phone && !preg_match('/^[0-9]{10}$/', $phone)) {
            setFlash('error', 'Phone must be 10 digits.');
        } else {
            $upd = $conn->prepare('UPDATE users SET full_name=?, phone=?, address=? WHERE id=?');
            $upd->bind_param('sssi', $full_name, $phone, $address, $user_id);
            $upd->execute();
            $_SESSION['user_name'] = $full_name;
            setFlash('success', 'Profile updated.');
        }
    } elseif ($action === 'password' && !$is_google_user) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8 || $new !== $confirm) {
            setFlash('error', 'New password must be 8+ chars and match confirmation.');
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $upd = $conn->prepare('UPDATE users SET password=? WHERE id=?');
            $upd->bind_param('si', $hash, $user_id);
            $upd->execute();
            setFlash('success', 'Password changed successfully.');
        }
    }
    header('Location: ' . url('user/profile.php'));
    exit;
}

$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$page_title = 'Profile';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<?php if ($is_google_user && empty($user['phone'])): ?>
<div class="alert alert-warning">Please add your phone number to complete your profile.</div>
<?php endif; ?>
<div class="card">
    <h2>Update Profile</h2>
    <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="profile">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required value="<?= e($user['full_name']) ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" pattern="[0-9]{10}" value="<?= e($user['phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= e($user['address'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Save Profile</button>
    </form>
</div>
<?php if (!$is_google_user): ?>
<div class="card">
    <h2>Change Password</h2>
    <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="password">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="8" required></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" minlength="8" required></div>
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>
<?php else: ?>
<div class="alert alert-info">You signed in with Google. Password change is not available for Google accounts.</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
