<?php
/**
 * Database connection using mysqli with prepared statements.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'finova_bank');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
}

$conn->set_charset('utf8mb4');

// Keep PHP and MySQL datetimes aligned for NOW() comparisons.
date_default_timezone_set('Asia/Kolkata');
$conn->query("SET time_zone = '+05:30'");
