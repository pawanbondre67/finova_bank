<?php
/**
 * Admin view of all fixed deposits.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$fds = $conn->query('SELECT fd.*, u.full_name FROM fixed_deposits fd JOIN users u ON fd.user_id = u.id ORDER BY fd.created_at DESC');

$page_title = 'Fixed Deposits';
$panel = 'admin';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ref</th><th>User</th><th>Principal</th><th>Rate</th><th>Tenure</th><th>Maturity</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($f = $fds->fetch_assoc()): ?>
                <tr>
                    <td><?= e($f['fd_ref']) ?></td>
                    <td><?= e($f['full_name']) ?></td>
                    <td><?= formatINR($f['principal']) ?></td>
                    <td><?= e($f['interest_rate']) ?>%</td>
                    <td><?= (int) $f['tenure_months'] ?> mo</td>
                    <td><?= formatINR($f['maturity_amount']) ?></td>
                    <td><span class="badge <?= badgeClass($f['status']) ?>"><?= e($f['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
