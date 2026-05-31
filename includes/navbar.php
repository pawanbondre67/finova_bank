<?php
/**
 * Sidebar navigation for user or admin panel.
 * Set $panel = 'user' or 'admin' before including.
 */
$panel = $panel ?? 'user';
$current = basename($_SERVER['PHP_SELF']);

$user_links = [
    'dashboard.php' => ['Dashboard', 'user/dashboard.php'],
    'account.php' => ['My Account', 'user/account.php'],
    'balance.php' => ['Balance', 'user/balance.php'],
    'transfer.php' => ['Transfer', 'user/transfer.php'],
    'history.php' => ['History', 'user/history.php'],
    'apply_loan.php' => ['Apply Loan', 'user/apply_loan.php'],
    'my_loans.php' => ['My Loans', 'user/my_loans.php'],
    'fixed_deposit.php' => ['Fixed Deposit', 'user/fixed_deposit.php'],
    'my_fds.php' => ['My FDs', 'user/my_fds.php'],
    'statement_pdf.php' => ['Statement PDF', 'user/statement_pdf.php'],
    'profile.php' => ['Profile', 'user/profile.php'],
];

$admin_links = [
    'dashboard.php' => ['Dashboard', 'admin/dashboard.php'],
    'users.php' => ['Users', 'admin/users.php'],
    'accounts.php' => ['Accounts', 'admin/accounts.php'],
    'transactions.php' => ['Transactions', 'admin/transactions.php'],
    'loan_applications.php' => ['Loans', 'admin/loan_applications.php'],
    'fixed_deposits.php' => ['Fixed Deposits', 'admin/fixed_deposits.php'],
    'reports.php' => ['Reports', 'admin/reports.php'],
];

$links = $panel === 'admin' ? $admin_links : $user_links;
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="<?= url($panel === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php') ?>">Finova Bank</a>
        <span class="sidebar-role"><?= $panel === 'admin' ? 'Admin' : 'Customer' ?></span>
    </div>
    <?php if ($panel === 'user' && !empty($_SESSION['user_name'])): ?>
    <div class="sidebar-user">
        <?php if (!empty($_SESSION['profile_pic'])): ?>
            <img src="<?= e($_SESSION['profile_pic']) ?>" alt="Profile" class="avatar">
        <?php else: ?>
            <span class="avatar-placeholder"><?= e(strtoupper(substr($_SESSION['user_name'], 0, 1))) ?></span>
        <?php endif; ?>
        <span><?= e($_SESSION['user_name']) ?></span>
    </div>
    <?php endif; ?>
    <nav class="sidebar-nav">
        <?php foreach ($links as $file => $item): ?>
            <a href="<?= url($item[1]) ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($item[0]) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= url($panel === 'admin' ? 'auth/logout.php?type=admin' : 'auth/logout.php') ?>" class="logout-link">Logout</a>
    </div>
</aside>
