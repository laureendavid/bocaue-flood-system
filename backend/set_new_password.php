<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/login.php');
    exit;
}

$userId = (int) ($_SESSION['pending_password_user_id'] ?? 0);
$email = trim($_SESSION['pending_password_email'] ?? '');
$role = trim($_SESSION['pending_password_role'] ?? '');

if ($userId <= 0 || $email === '' || $role === '') {
    header('Location: ../main/login.php');
    exit;
}

$password = $_POST['password'] ?? '';
$passwordConfirmation = $_POST['password_confirmation'] ?? '';

if (strlen($password) < 12 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    header('Location: ../main/set_new_password.php?error=invalid');
    exit;
}

if ($password !== $passwordConfirmation) {
    header('Location: ../main/set_new_password.php?error=mismatch');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare('UPDATE users SET password = ?, first_login = 1 WHERE user_id = ? AND email = ?');

if (!$stmt) {
    error_log('Set password prepare failed: ' . $conn->error);
    header('Location: ../main/set_new_password.php?error=server');
    exit;
}

$stmt->bind_param('sis', $hash, $userId, $email);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header('Location: ../main/set_new_password.php?error=server');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = $role;
$_SESSION['last_activity'] = time();
unset(
    $_SESSION['pending_password_user_id'],
    $_SESSION['pending_password_email'],
    $_SESSION['pending_password_role'],
    $_SESSION['password_reset_from_forgot']
);

switch ($role) {
    case 'LGU':
        header('Location: ../lgu/index.php');
        break;
    case 'Rescuer':
        header('Location: ../rescuer/index.php');
        break;
    case 'Resident':
        header('Location: ../resident/index.php');
        break;
    default:
        header('Location: ../main/login.php?error=unknown_role');
        break;
}
exit;
