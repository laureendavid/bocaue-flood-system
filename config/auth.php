<?php
// ===== SESSION COOKIE SETTINGS (must be BEFORE session_start) =====
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,   // ✅ changed
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: private, no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../main/login.php");
    exit();
}
?>