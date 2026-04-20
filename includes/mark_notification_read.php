<?php
header('Content-Type: application/json');

$requiredRole = 'Resident';
require_once '../config/auth.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

if ($userId <= 0 || $notificationId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid notification data.']);
    exit;
}

$hasIsRead = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasIsRead = true;
}

$updateSql = $hasIsRead
    ? '
        UPDATE notifications
        SET is_read = 1
        WHERE id = ? AND user_id = ? AND is_read = 0
    '
    : '
        UPDATE notifications
        SET status = "read"
        WHERE id = ? AND user_id = ? AND LOWER(COALESCE(status, "unread")) = "unread"
    ';

$stmt = $conn->prepare($updateSql);
$stmt->bind_param('ii', $notificationId, $userId);
$stmt->execute();
$updated = $stmt->affected_rows;
$stmt->close();

echo json_encode([
    'success' => true,
    'updated' => (int) $updated,
]);
