<?php
// FILE: api/handle_add_user.php
// Called via AJAX POST from the Add User modal in the LGU portal.

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Only authenticated LGU sessions can call this
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (($_POST['action'] ?? '') !== 'add_user_lgu') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

/* ---- Collect & sanitize ---- */
$firstName = strtoupper(trim($_POST['first_name'] ?? ''));
$lastName = strtoupper(trim($_POST['last_name'] ?? ''));
$suffix = strtoupper(trim($_POST['suffix'] ?? ''));
$email = trim($_POST['email'] ?? '');
$barangayId = (int) ($_POST['barangay_id'] ?? 0);
$roleId = (int) ($_POST['role_id'] ?? 0);
$address = trim($_POST['current_address'] ?? '');
$lat = trim($_POST['latitude'] ?? '');
$lng = trim($_POST['longitude'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

/* ---- Validate ---- */
if ($firstName === '' || $lastName === '') {
    echo json_encode(['success' => false, 'message' => 'First and last name are required.']);
    exit;
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if ($barangayId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid barangay.']);
    exit;
}
if ($roleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid role.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

/* ---- Check duplicate email ---- */
$checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
$checkStmt->execute([$email]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
    exit;
}

/* ---- Build full name ---- */
$fullName = trim($firstName . ' ' . $lastName . ($suffix !== '' ? ' ' . $suffix : ''));

/* ---- Normalize coordinates ---- */
function normCoord(string $v): ?string
{
    $v = trim($v);
    if ($v === '' || !is_numeric($v))
        return null;
    return number_format((float) $v, 8, '.', '');
}
$latitude = normCoord($lat);
$longitude = normCoord($lng);

/* ---- Hash password ---- */
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

/* ---- Insert ---- */
// FIXED: is_verified is always set to 1 regardless of role.
// All users added by LGU admin are considered verified immediately.
try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (
            first_name, last_name, suffix, full_name,
            email,
            barangay_id, role_id,
            current_address, latitude, longitude,
            password,
            is_verified,
            first_login
        ) VALUES (
            ?, ?, ?, ?,
            ?,
            ?, ?,
            ?, ?, ?,
            ?,
            1,
            0
        )'
    );
    $stmt->execute([
        $firstName,
        $lastName,
        $suffix !== '' ? $suffix : null,
        $fullName,
        $email,
        $barangayId,
        $roleId,
        $address !== '' ? $address : null,
        $latitude,
        $longitude,
        $passwordHash,
    ]);

    echo json_encode(['success' => true, 'message' => 'User added successfully.']);

} catch (Throwable $e) {
    error_log('handle_add_user.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}