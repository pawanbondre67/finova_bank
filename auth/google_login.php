<?php
/**
 * Redirect user to Google OAuth consent screen.
 */
session_start();
require_once __DIR__ . '/../config/google.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Run composer install in finova_bank folder first (google/apiclient, phpmailer, fpdf).');
}
require_once $autoload;

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit;
