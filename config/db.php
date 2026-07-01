<?php
// config/db.php — safe defaults for repo; override in config/db.local.php (gitignored)

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'flood_information';

$localDbFile = __DIR__ . '/db.local.php';

if (is_file($localDbFile)) {
    require $localDbFile;
}

// ── mysqli connection (used by backend/login.php) ──────────────────────────
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('mysqli connection failed: ' . $conn->connect_error);
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
    die('PDO connection failed: ' . $e->getMessage());
}

$cronFile = __DIR__ . '/../includes/db_cron.php';

if (is_file($cronFile)) {
    require_once $cronFile;

    if (function_exists('bfis_run_verification_db_cron')) {
        bfis_run_verification_db_cron($pdo, $conn);
    }
}
