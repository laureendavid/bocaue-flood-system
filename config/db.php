<?php
// config/db.php

$host = "sql300.infinityfree.com";
$user = "if0_41283224";
$pass = "8EFGD1TLQzEmrjb"; 
$db = "if0_41283224_flood_information";

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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("PDO connection failed: " . $e->getMessage());
}