<?php
// Only configure and start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.gc_maxlifetime', 1800);
    session_start();
}

// Send cache-control headers only if headers haven't been sent yet
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Not logged in at all
if (!isset($_SESSION['user_id'])) {
    header("Location: ../main/login.php");
    exit();
}

// Wrong role — redirect to their correct dashboard
if (isset($requiredRole) && $_SESSION['role'] !== $requiredRole) {
    // Redirect them to their correct dashboard
    $roleRedirects = [
        'LGU'      => '../lgu/main.php',
        'Rescuer'  => '../rescuer/main.php',
        'Resident' => '../resident/main.php',
    ];
    $redirect = $roleRedirects[$_SESSION['role']] ?? '../main/login.php';
    header("Location: $redirect");
    exit();
}
?>