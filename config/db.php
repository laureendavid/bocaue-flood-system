<?php
// config/db.php

$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP password
$db   = "flood_information";

// ── mysqli connection (used by backend/login.php) ──────────────────────────
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("mysqli connection failed: " . $conn->connect_error);
}

// ── PDO connection (used by register_step2.php and registerComplete.php) ───
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("PDO connection failed: " . $e->getMessage());
}
?>