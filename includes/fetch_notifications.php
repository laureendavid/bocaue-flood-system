<?php
header('Content-Type: application/json');

$requiredRole = 'Resident';
require_once '../config/auth.php';
require_once '../config/db.php';

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

$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        report_id INT NULL,
        from_who VARCHAR(150) NOT NULL DEFAULT 'Bocaue LGU',
        title VARCHAR(191) NULL,
        message TEXT NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'alert',
        status VARCHAR(20) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user_read (user_id, is_read),
        INDEX idx_notifications_report (report_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$columnCheckResult = $conn->query("SHOW COLUMNS FROM notifications");
$notificationColumns = [];
if ($columnCheckResult) {
    while ($column = $columnCheckResult->fetch_assoc()) {
        $notificationColumns[] = strtolower((string) ($column['Field'] ?? ''));
    }
}
if (!in_array('from_who', $notificationColumns, true)) {
    $conn->query("ALTER TABLE notifications ADD COLUMN from_who VARCHAR(150) NOT NULL DEFAULT 'Bocaue LGU' AFTER report_id");
}
if (!in_array('type', $notificationColumns, true)) {
    $conn->query("ALTER TABLE notifications ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'alert' AFTER message");
}
if (!in_array('is_read', $notificationColumns, true)) {
    $conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}
if (!in_array('report_id', $notificationColumns, true)) {
    $conn->query("ALTER TABLE notifications ADD COLUMN report_id INT NULL AFTER user_id");
}
if (!in_array('title', $notificationColumns, true)) {
    $conn->query("ALTER TABLE notifications ADD COLUMN title VARCHAR(191) NULL AFTER from_who");
}

$conn->query("ALTER TABLE notifications MODIFY COLUMN report_id INT NULL");

$residentStmt = $conn->prepare('
    SELECT u.barangay_id, u.latitude, u.longitude, COALESCE(u.full_name, "Resident") AS resident_name
    FROM users u
    WHERE u.user_id = ?
    LIMIT 1
');
$residentStmt->bind_param('i', $userId);
$residentStmt->execute();
$resident = $residentStmt->get_result()->fetch_assoc();
$residentStmt->close();

$residentBarangayId = isset($resident['barangay_id']) ? (int) $resident['barangay_id'] : 0;
$residentLatitude = isset($resident['latitude']) ? (float) $resident['latitude'] : 0.0;
$residentLongitude = isset($resident['longitude']) ? (float) $resident['longitude'] : 0.0;

// Insert announcement notifications in one optimized query (location-aware by barangay).
$announcementInsertSql = "
    INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
    SELECT
        ?,
        NULL,
        COALESCE(creator.full_name, 'Bocaue LGU'),
        COALESCE(NULLIF(TRIM(a.title), ''), 'New Announcement'),
        a.message,
        'announcement',
        0,
        a.created_at
    FROM announcements a
    LEFT JOIN users creator ON creator.user_id = a.created_by
    LEFT JOIN notifications n
        ON n.user_id = ?
        AND n.type = 'announcement'
        AND n.title = COALESCE(NULLIF(TRIM(a.title), ''), 'New Announcement')
        AND n.message = a.message
    WHERE a.message IS NOT NULL
      AND TRIM(a.message) <> ''
      AND (a.barangay_id IS NULL OR a.barangay_id = ?)
      AND n.id IS NULL
";
$announcementInsertStmt = $conn->prepare($announcementInsertSql);
if ($announcementInsertStmt) {
    $announcementInsertStmt->bind_param('iii', $userId, $userId, $residentBarangayId);
    $announcementInsertStmt->execute();
    $announcementInsertStmt->close();
}

// Insert nearby flood alert notifications in one optimized query.
if ($residentBarangayId > 0) {
    $alertInsertSql = "
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        SELECT
            ?,
            r.report_id,
            'System Alert',
            'Nearby Flood Alert',
            'A flood has been reported near your area. Stay alert.',
            'alert',
            0,
            r.created_at
        FROM reports r
        INNER JOIN locations l ON l.location_id = r.location_id
        LEFT JOIN notifications n
            ON n.user_id = ?
            AND n.report_id = r.report_id
            AND n.type = 'alert'
        WHERE l.barangay_id = ?
          AND r.user_id <> ?
          AND n.id IS NULL
    ";
    $alertInsertStmt = $conn->prepare($alertInsertSql);
    if ($alertInsertStmt) {
        $alertInsertStmt->bind_param('iiii', $userId, $userId, $residentBarangayId, $userId);
        $alertInsertStmt->execute();
        $alertInsertStmt->close();
    }
}

// Optional coordinate-aware alert inclusion when barangay is not available.
if ($residentBarangayId <= 0 && $residentLatitude !== 0.0 && $residentLongitude !== 0.0) {
    $coordAlertInsertSql = "
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        SELECT
            ?,
            r.report_id,
            'System Alert',
            'Nearby Flood Alert',
            'A flood has been reported near your area. Stay alert.',
            'alert',
            0,
            r.created_at
        FROM reports r
        INNER JOIN locations l ON l.location_id = r.location_id
        LEFT JOIN notifications n
            ON n.user_id = ?
            AND n.report_id = r.report_id
            AND n.type = 'alert'
        WHERE r.user_id <> ?
          AND n.id IS NULL
          AND ABS(l.latitude - ?) <= 0.02
          AND ABS(l.longitude - ?) <= 0.02
    ";
    $coordAlertInsertStmt = $conn->prepare($coordAlertInsertSql);
    if ($coordAlertInsertStmt) {
        $coordAlertInsertStmt->bind_param('iiidd', $userId, $userId, $userId, $residentLatitude, $residentLongitude);
        $coordAlertInsertStmt->execute();
        $coordAlertInsertStmt->close();
    }
}

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
        ORDER BY created_at DESC
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
        ORDER BY created_at DESC
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
