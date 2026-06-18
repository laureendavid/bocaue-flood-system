<?php

/**
 * backend/forgot_password.php
 * Validates account email and starts the password reset session.
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/forgot_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../main/forgot_password.php?error=invalid_email&email=' . urlencode($email));
    exit;
}

$sql = "SELECT u.user_id,
               u.is_verified,
               r.role_name AS role
        FROM users u
        INNER JOIN roles r ON r.role_id = u.role_id
        WHERE u.email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Forgot password prepare failed: ' . $conn->error);
    header('Location: ../main/forgot_password.php?error=server');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    header('Location: ../main/forgot_password.php?error=not_found&email=' . urlencode($email));
    exit;
}

if (!(bool) $user['is_verified']) {
    header('Location: ../main/forgot_password.php?error=not_verified&email=' . urlencode($email));
    exit;
}

$_SESSION['pending_password_user_id'] = (int) $user['user_id'];
$_SESSION['pending_password_email'] = $email;
$_SESSION['pending_password_role'] = (string) $user['role'];
$_SESSION['password_reset_from_forgot'] = true;

header('Location: ../main/set_new_password.php');
exit;
