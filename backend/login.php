<?php
/**
 * backend/login.php
 * Handles all login logic: DB lookup, password verify, role redirect.
 */

require_once '../config/db.php'; // provides $conn (mysqli)

// ── Session setup ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Only accept POST ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/login.php');
    exit;
}

// ── Read and sanitize inputs ───────────────────────────────────────────────
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// ── Basic presence / format check ─────────────────────────────────────────
if (empty($email) || empty($password)) {
    header('Location: ../main/login.php?error=empty_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../main/login.php?error=invalid_credentials');
    exit;
}

// ── Fetch user + role from DB (prepared statement, JOIN on roles) ──────────
$sql = "SELECT u.user_id,
               u.full_name,
               u.password,
               u.is_verified,
               r.role_name AS role
        FROM   users u
        JOIN   roles r ON r.role_id = u.role_id
        WHERE  u.email = ?
        LIMIT  1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Login prepare failed: ' . $conn->error);
    header('Location: ../main/login.php?error=server');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();
$conn->close();

// ── Check user exists ──────────────────────────────────────────────────────
if (!$user) {
    header('Location: ../main/login.php?error=invalid_credentials');
    exit;
}

// ── Verify password against bcrypt hash ───────────────────────────────────
if (!password_verify($password, $user['password'])) {
    header('Location: ../main/login.php?error=invalid_credentials');
    exit;
}

// ── Check account is verified ──────────────────────────────────────────────
if (!(bool) $user['is_verified']) {
    header('Location: ../main/login.php?error=not_verified');
    exit;
}

// ── Regenerate session ID (prevents session fixation) ─────────────────────
session_regenerate_id(true);

// ── Store session data ─────────────────────────────────────────────────────
$_SESSION['user_id']       = $user['user_id'];
$_SESSION['full_name']     = $user['full_name'];
$_SESSION['role']          = $user['role'];
$_SESSION['last_activity'] = time();

// ── Redirect based on role ─────────────────────────────────────────────────
switch ($user['role']) {
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
}
exit;
?>