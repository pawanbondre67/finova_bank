<?php
/**
 * Account monitoring - list and freeze/unfreeze.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$accounts = $conn->query('SELECT a.*, u.full_name, u.email FROM accounts a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC');

$page_title = 'Accounts';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Account No</th><th>Owner</th><th>Type</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($a = $accounts->fetch_assoc()): ?>
                <tr>
                    <td><?= e($a['account_no']) ?></td>
                    <td><?= e($a['full_name']) ?></td>
                    <td><?= e($a['account_type']) ?></td>
                    <td><?= formatINR($a['balance']) ?></td>
                    <td><span class="badge <?= badgeClass($a['status']) ?>"><?= e($a['status']) ?></span></td>
                    <td class="actions">
                        <form method="POST" action="<?= url('admin/freeze_account.php') ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
                            <input type="hidden" name="redirect" value="admin/accounts.php">
                            <input type="hidden" name="action" value="<?= $a['status'] === 'frozen' ? 'unfreeze' : 'freeze' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Change status?');"><?= $a['status'] === 'frozen' ? 'Unfreeze' : 'Freeze' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
