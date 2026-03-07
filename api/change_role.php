<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = (int) ($_POST['user_id'] ?? 0);
$new_role = trim($_POST['role'] ?? '');
$allowed = ['LGU', 'Rescuer', 'Resident'];

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

if (!in_array($new_role, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

// Get current is_verified status
$check = mysqli_prepare($conn, "SELECT is_verified FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($check, 'i', $user_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

// Auto-verify if upgrading to LGU or Rescuer and not yet verified
$should_verify = ($new_role !== 'Resident' && $user['is_verified'] == 0);

if ($should_verify) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = ?, is_verified = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $new_role, $user_id);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $new_role, $user_id);
}

if (mysqli_stmt_execute($stmt)) {
    $msg = 'Role updated successfully.';
    if ($should_verify)
        $msg .= ' User has been automatically verified.';
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);