<?php
/**
 * List user's fixed deposits.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = $conn->prepare('SELECT * FROM fixed_deposits WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$fds = $stmt->get_result();

$page_title = 'My Fixed Deposits';
$panel = 'user';
include __DIR__ . '/../includes/panel_header.php';
?>
<div class="card">
    <p><a href="<?= url('user/fixed_deposit.php') ?>" class="btn btn-primary btn-sm">+ New FD</a></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ref</th><th>Principal</th><th>Rate</th><th>Tenure</th><th>Maturity Amt</th><th>Start</th><th>Maturity Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($f = $fds->fetch_assoc()):
                $display_status = $f['status'];
                if (strtotime($f['maturity_date']) < time() && $f['status'] === 'active') {
                    $display_status = 'matured';
                }
            ?>
                <tr>
                    <td><?= e($f['fd_ref']) ?></td>
                    <td><?= formatINR($f['principal']) ?></td>
                    <td><?= e($f['interest_rate']) ?>%</td>
                    <td><?= (int) $f['tenure_months'] ?> mo</td>
                    <td><?= formatINR($f['maturity_amount']) ?></td>
                    <td><?= e($f['start_date']) ?></td>
                    <td><?= e($f['maturity_date']) ?></td>
                    <td><span class="badge <?= badgeClass($display_status) ?>"><?= e($display_status) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel_footer.php'; ?>
