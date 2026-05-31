<?php
/**
 * Manage users - view, freeze, delete.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf() && isset($_POST['delete_user'])) {
    $uid = (int) $_POST['user_id'];
    $del = $conn->prepare('DELETE FROM users WHERE id = ?');
    $del->bind_param('i', $uid);
    $del->execute();
    setFlash('success', 'User deleted.');
    header('Location: ' . url('admin/users.php'));
    exit;
}

$users = $conn->query('SELECT u.*, a.account_no, a.status as acc_status, a.id as account_id FROM users u
    LEFT JOIN accounts a ON u.id = a.user_id ORDER BY u.id DESC');

$page_title = 'Manage Users';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Account</th><th>Status</th><th class="actions">Actions</th></tr></thead>
            <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['phone'] ?? '—') ?></td>
                    <td><?= e($u['account_no'] ?? '—') ?></td>
                    <td><span class="badge <?= badgeClass($u['status']) ?>"><?= e($u['status']) ?></span></td>
                    <td class="actions">
                        <?php if ($u['account_id']): ?>
                        <form method="POST" action="<?= url('admin/freeze_account.php') ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="account_id" value="<?= (int) $u['account_id'] ?>">
                            <input type="hidden" name="redirect" value="admin/users.php">
                            <input type="hidden" name="action" value="<?= $u['acc_status'] === 'frozen' ? 'unfreeze' : 'freeze' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Change account status?');"><?= $u['acc_status'] === 'frozen' ? 'Unfreeze' : 'Freeze' ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="delete_user" value="1">
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
