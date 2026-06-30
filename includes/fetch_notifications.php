<?php
header('Content-Type: application/json');

$requiredRole = 'Resident';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/notifications_service.php';

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
if ($limit < 1) {
    $limit = 20;
}
if ($limit > 50) {
    $limit = 50;
}
if ($offset < 0) {
    $offset = 0;
}

bfis_ensure_notifications_table($conn);

$hasIsRead = false;
$isReadColumnCheck = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
if ($isReadColumnCheck && $isReadColumnCheck->num_rows > 0) {
    $hasIsRead = true;
}

$listSql = $hasIsRead
    ? '
        SELECT
            id,
            report_id,
            title,
            message,
            from_who,
            type,
            is_read,
            CASE WHEN is_read = 1 THEN "read" ELSE "unread" END AS status,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT ?
        OFFSET ?
    '
    : '
        SELECT
            id,
            report_id,
            title,
            message,
            COALESCE(from_who, "Bocaue LGU") AS from_who,
            COALESCE(type, "alert") AS type,
            0 AS is_read,
            CASE WHEN LOWER(COALESCE(status, "unread")) = "read" THEN "read" ELSE "unread" END AS status,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT ?
        OFFSET ?
    ';

$listStmt = $conn->prepare($listSql);
$listStmt->bind_param('iii', $userId, $limit, $offset);
$listStmt->execute();
$rows = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

foreach ($rows as &$notificationRow) {
    $createdBy = trim((string) ($notificationRow['from_who'] ?? ''));
    $notificationRow['created_by'] = $createdBy !== '' ? $createdBy : 'Bocaue LGU';
}
unset($notificationRow);

$countSql = $hasIsRead
    ? '
        SELECT COUNT(*) AS unread_count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    '
    : '
        SELECT COUNT(*) AS unread_count
        FROM notifications
        WHERE user_id = ? AND LOWER(COALESCE(status, "unread")) = "unread"
    ';
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param('i', $userId);
$countStmt->execute();
$countRow = $countStmt->get_result()->fetch_assoc();
$countStmt->close();

$totalStmt = $conn->prepare('
    SELECT COUNT(*) AS total_count
    FROM notifications
    WHERE user_id = ?
');
$totalStmt->bind_param('i', $userId);
$totalStmt->execute();
$totalRow = $totalStmt->get_result()->fetch_assoc();
$totalStmt->close();
$totalCount = (int) ($totalRow['total_count'] ?? 0);

echo json_encode([
    'success' => true,
    'notifications' => $rows,
    'unread_count' => (int) ($countRow['unread_count'] ?? 0),
    'offset' => $offset,
    'limit' => $limit,
    'has_more' => ($offset + count($rows)) < $totalCount,
]);
