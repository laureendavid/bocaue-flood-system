<?php
/* ================================================================
   session.php — Session timeout handler
   ================================================================ */

// ===== SESSION COOKIE SETTINGS (must be BEFORE session_start) =====
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

define('SESSION_TIMEOUT', 30 * 60);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../main/login.php');
    exit;
}

if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > SESSION_TIMEOUT) {
        $_SESSION = [];                          // ✅ clear array first
        session_destroy();
        setcookie(session_name(), '', [          // ✅ manually expire the cookie
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        header('Location: ../main/login.php?timeout=1');
        exit;
    }
}

$_SESSION['last_activity'] = time();