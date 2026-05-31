<?php
/**
 * Public / landing page header.
 */
if (!isset($page_title)) {
    $page_title = 'Finova Bank';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Finova Bank</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="public-body">
<header class="public-header">
    <div class="container header-inner">
        <a href="<?= url('index.php') ?>" class="logo">
            <span class="logo-mark" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="28" height="28" rx="8" fill="currentColor"/>
                    <path d="M8 18V10h2.2v3.2h7.6V10H20v8h-2.2v-3.4H10.2V18H8z" fill="#fff"/>
                </svg>
            </span>
            <span class="logo-text">Finova <em>Bank</em></span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded')==='true'?'false':'true'); document.querySelector('.public-nav').classList.toggle('open');">
            <span></span><span></span><span></span>
        </button>
        <nav class="public-nav">
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('index.php') ?>#features">Features</a>
            <a href="<?= url('auth/login.php') ?>">Login</a>
            <a href="<?= url('admin/login.php') ?>" class="nav-muted">Admin</a>
            <a href="<?= url('auth/register.php') ?>" class="btn btn-primary btn-sm">Open Account</a>
        </nav>
    </div>
</header>
<main class="public-main">
