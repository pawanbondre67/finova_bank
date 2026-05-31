<?php
/**
 * Google OAuth callback - login or auto-register user.
 */
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../mail/mailer.php';

if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    setFlash('error', 'Invalid OAuth state. Please try again.');
    header('Location: ' . url('auth/login.php'));
    exit;
}
unset($_SESSION['oauth_state']);

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    setFlash('error', 'Google login failed. No code received.');
    header('Location: ' . url('auth/login.php'));
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    setFlash('error', 'Google login failed: ' . $token['error']);
    header('Location: ' . url('auth/login.php'));
    exit;
}
$client->setAccessToken($token);

$google_service = new Google\Service\Oauth2($client);
$google_user = $google_service->userinfo->get();

$google_id = $google_user->getId();
$email = $google_user->getEmail();
$full_name = $google_user->getName();
$profile_pic = $google_user->getPicture();

$stmt = $conn->prepare('SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $google_id, $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    if ($user['google_id'] !== $google_id) {
        $upd = $conn->prepare("UPDATE users SET google_id=?, profile_pic=?, auth_provider='google' WHERE id=?");
        $upd->bind_param('ssi', $google_id, $profile_pic, $user['id']);
        $upd->execute();
    }

    $acc = $conn->prepare('SELECT status FROM accounts WHERE user_id = ? LIMIT 1');
    $acc->bind_param('i', $user['id']);
    $acc->execute();
    $account = $acc->get_result()->fetch_assoc();
    if ($account && $account['status'] === 'frozen') {
        setFlash('error', 'Your account is frozen. Contact support.');
        header('Location: ' . url('auth/login.php'));
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $email;
    $_SESSION['profile_pic'] = $profile_pic;
    $_SESSION['role'] = 'user';

    setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
    header('Location: ' . url('user/dashboard.php'));
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, google_id, profile_pic, auth_provider, status) VALUES (?, ?, NULL, ?, ?, 'google', 'active')");
$stmt->bind_param('ssss', $full_name, $email, $google_id, $profile_pic);
$stmt->execute();
$new_user_id = $conn->insert_id;

$account_no = generateAccountNo();
$acc_stmt = $conn->prepare("INSERT INTO accounts (user_id, account_no, account_type, balance, open_date, status) VALUES (?, ?, 'savings', 0.00, CURDATE(), 'active')");
$acc_stmt->bind_param('is', $new_user_id, $account_no);
$acc_stmt->execute();

$welcome_body = "<h2>Welcome to Finova Bank, " . e($full_name) . "!</h2><p>Your account has been created via Google login.</p><p><strong>Account Number:</strong> " . e($account_no) . "</p>";
@sendMail($email, 'Welcome to Finova Bank', $welcome_body);

session_regenerate_id(true);
$_SESSION['user_id'] = $new_user_id;
$_SESSION['user_name'] = $full_name;
$_SESSION['user_email'] = $email;
$_SESSION['profile_pic'] = $profile_pic;
$_SESSION['role'] = 'user';

setFlash('success', 'Account created! Welcome to Finova Bank, ' . $full_name . '.');
header('Location: ' . url('user/dashboard.php'));
exit;
