<?php
require_once '../config/db.php';
header('Content-Type: application/json');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$target_area = trim($_POST['target_area'] ?? '');
$expiry_date = trim($_POST['expiry_date'] ?? '');
$created_by = $_SESSION['user_id'] ?? null;

if (!$title || !$message || !$target_area) {
    echo json_encode(['success' => false, 'message' => 'Title, message, and target area are required.']);
    exit;
}

// Get barangay_id — NULL if All Barangays
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
    $barangay_id = $row['barangay_id'];
}

// Handle optional expiry date
$expiry = !empty($expiry_date) ? $expiry_date : null;

$stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, message, barangay_id, expiry_date, created_by) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'ssisi', $title, $message, $barangay_id, $expiry, $created_by);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Announcement added successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);