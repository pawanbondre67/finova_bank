<?php
/**
 * Logout - clears user or admin session.
 */
require_once __DIR__ . '/../includes/functions.php';

$is_admin = isset($_GET['type']) && $_GET['type'] === 'admin';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

if ($is_admin) {
    header('Location: ' . url('admin/login.php'));
} else {
    header('Location: ' . url('auth/login.php'));
}
exit;
