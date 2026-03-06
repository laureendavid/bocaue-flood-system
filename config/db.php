<?php
/* ================================================================
   db.php — Database Connection
   Place this file in: C:\xampp\htdocs\soe\config\db.php
   ================================================================ */

$host = 'localhost';
$db = 'flood_information';
$user = 'root';
$pass = 'password';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Poor man's cron — runs on every page load since db.php is included everywhere.
// Deletes all unverified users whose token has already expired.
// ON DELETE CASCADE on email_verifications handles token cleanup automatically.
$conn->query("
    DELETE FROM users
    WHERE is_verified = 0
    AND user_id IN (
        SELECT user_id FROM email_verifications
        WHERE expires_at < NOW() - INTERVAL 7 DAY
    )
");