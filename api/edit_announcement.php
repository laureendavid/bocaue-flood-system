<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$target_area = trim($_POST['target_area'] ?? '');
$expiry_date = trim($_POST['expiry_date'] ?? '');

if ($id <= 0 || !$title || !$message || !$target_area) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

/* Get barangay_id */
$barangay_id = null;

if ($target_area !== 'All Barangays') {
    $b = mysqli_prepare($conn, "SELECT barangay_id FROM barangays WHERE barangay_name = ?");
    mysqli_stmt_bind_param($b, 's', $target_area);
    mysqli_stmt_execute($b);
    $res = mysqli_stmt_get_result($b);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($b);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Invalid barangay selected.']);
        exit;
    }

    $barangay_id = (int) $row['barangay_id'];
}

$expiry = !empty($expiry_date) ? $expiry_date : null;

/* Update query — split to handle NULL barangay_id */
if ($barangay_id === null) {
    $stmt = mysqli_prepare($conn, "
        UPDATE announcements 
        SET title = ?, message = ?, barangay_id = NULL, expiry_date = ?
        WHERE announcement_id = ?
    ");
    mysqli_stmt_bind_param($stmt, 'sssi', $title, $message, $expiry, $id);
} else {
    $stmt = mysqli_prepare($conn, "
        UPDATE announcements 
        SET title = ?, message = ?, barangay_id = ?, expiry_date = ?
        WHERE announcement_id = ?
    ");
    mysqli_stmt_bind_param($stmt, 'ssisi', $title, $message, $barangay_id, $expiry, $id);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Announcement updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);