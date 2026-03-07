<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$hotline_id = (int) ($_POST['id'] ?? 0);
$hotline_name = trim($_POST['hotline_name'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');

if ($hotline_id <= 0 || !$hotline_name || !$contact_number) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE hotlines SET hotline_name = ?, contact_number = ? WHERE hotline_id = ?");
mysqli_stmt_bind_param($stmt, 'ssi', $hotline_name, $contact_number, $hotline_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Hotline updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);