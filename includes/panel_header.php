<?php
/**
 * Top bar for user/admin panel pages.
 */
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Finova Bank</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="panel-body">
<div class="panel-layout">
<?php include __DIR__ . '/navbar.php'; ?>
<div class="panel-content">
    <header class="panel-topbar">
        <h1><?= e($page_title) ?></h1>
    </header>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
    <div class="panel-main">
