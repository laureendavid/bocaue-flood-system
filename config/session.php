<?php
/* ================================================================
   session.php — Session timeout handler
   ================================================================ */

// ===== SESSION COOKIE SETTINGS (must be BEFORE session_start) =====
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,   // ✅ changed
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// ===== SETTINGS =====
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes inactivity timeout

// ===== CHECK IF LOGGED IN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: ../main/login.php');
    exit;
}

// ===== CHECK INACTIVITY TIMEOUT =====
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ../main/login.php?timeout=1');
        exit;
    }
}

// ===== UPDATE LAST ACTIVITY TIME =====
$_SESSION['last_activity'] = time();