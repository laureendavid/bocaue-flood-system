<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$hotline_id = (int) ($_POST['id'] ?? 0);

if ($hotline_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid hotline ID.']);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM hotlines WHERE hotline_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $hotline_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Hotline deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);