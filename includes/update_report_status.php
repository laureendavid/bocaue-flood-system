<?php
header('Content-Type: application/json');

$requiredRole = 'LGU';
require_once '../config/auth.php';
require_once '../config/db.php';

/**
 * Ensure notifications table structure and foreign keys.
 */
function ensureNotificationsTable(mysqli $conn): void
{
    $createSql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            report_id INT NULL,
            from_who VARCHAR(150) NOT NULL DEFAULT 'LGU Bocaue',
            title VARCHAR(191) NULL,
            message TEXT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'alert',
            status VARCHAR(20) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user_read (user_id, is_read),
            INDEX idx_notifications_report (report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    if (!$conn->query($createSql)) {
        throw new RuntimeException('Failed to create notifications table: ' . $conn->error);
    }

    $columns = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM notifications");
    if (!$columnResult) {
        throw new RuntimeException('Failed to inspect notifications columns: ' . $conn->error);
    }
    while ($column = $columnResult->fetch_assoc()) {
        $columns[strtolower((string) $column['Field'])] = $column;
    }

    if (!isset($columns['report_id']) && !$conn->query("ALTER TABLE notifications ADD COLUMN report_id INT NULL AFTER user_id")) {
        throw new RuntimeException('Failed to add report_id on notifications: ' . $conn->error);
    }
    if (!isset($columns['from_who']) && !$conn->query("ALTER TABLE notifications ADD COLUMN from_who VARCHAR(150) NOT NULL DEFAULT 'LGU Bocaue' AFTER report_id")) {
        throw new RuntimeException('Failed to add from_who on notifications: ' . $conn->error);
    }
    if (!isset($columns['type']) && !$conn->query("ALTER TABLE notifications ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'alert' AFTER message")) {
        throw new RuntimeException('Failed to add type on notifications: ' . $conn->error);
    }
    if (!isset($columns['is_read']) && !$conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER status")) {
        throw new RuntimeException('Failed to add is_read on notifications: ' . $conn->error);
    }
    if (!$conn->query("ALTER TABLE notifications MODIFY COLUMN report_id INT NULL")) {
        throw new RuntimeException('Failed to set report_id nullable on notifications: ' . $conn->error);
    }

    if (isset($columns['status'])) {
        if (!$conn->query("
            UPDATE notifications
            SET is_read = CASE WHEN LOWER(COALESCE(status, '')) = 'read' THEN 1 ELSE 0 END
            WHERE is_read IS NULL OR is_read NOT IN (0, 1)
        ")) {
            throw new RuntimeException('Failed to sync status to is_read: ' . $conn->error);
        }
    }

    if (!$conn->query("DELETE n FROM notifications n LEFT JOIN users u ON u.user_id = n.user_id WHERE u.user_id IS NULL")) {
        throw new RuntimeException('Failed to clean invalid notification users: ' . $conn->error);
    }
    if (!$conn->query("DELETE n FROM notifications n LEFT JOIN reports r ON r.report_id = n.report_id WHERE r.report_id IS NULL")) {
        throw new RuntimeException('Failed to clean invalid notification reports: ' . $conn->error);
    }

    $dbRow = $conn->query("SELECT DATABASE() AS db_name")->fetch_assoc();
    $dbName = $dbRow['db_name'] ?? '';
    if ($dbName === '') {
        throw new RuntimeException('Unable to resolve active database name.');
    }

    $escapedDbName = $conn->real_escape_string($dbName);
    $fkUsageRows = $conn->query("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '{$escapedDbName}'
          AND TABLE_NAME = 'notifications'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    if (!$fkUsageRows) {
        throw new RuntimeException('Failed to inspect notification foreign keys: ' . $conn->error);
    }
    $hasUserFk = false;
    $hasReportFk = false;
    while ($fkUsage = $fkUsageRows->fetch_assoc()) {
        if (($fkUsage['COLUMN_NAME'] ?? '') === 'user_id' && ($fkUsage['REFERENCED_TABLE_NAME'] ?? '') === 'users') {
            $hasUserFk = true;
        }
        if (($fkUsage['COLUMN_NAME'] ?? '') === 'report_id' && ($fkUsage['REFERENCED_TABLE_NAME'] ?? '') === 'reports') {
            $hasReportFk = true;
        }
    }

    if (!$hasUserFk) {
        if (!$conn->query("
            ALTER TABLE notifications
            ADD CONSTRAINT fk_notifications_user
            FOREIGN KEY (user_id) REFERENCES users(user_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ")) {
            throw new RuntimeException('Failed to add fk_notifications_user: ' . $conn->error);
        }
    }

    if (!$hasReportFk) {
        if (!$conn->query("
            ALTER TABLE notifications
            ADD CONSTRAINT fk_notifications_report
            FOREIGN KEY (report_id) REFERENCES reports(report_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ")) {
            throw new RuntimeException('Failed to add fk_notifications_report: ' . $conn->error);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$reportId = isset($_POST['report_id']) ? (int) $_POST['report_id'] : 0;
$status = trim($_POST['status'] ?? '');
$allowedStatus = ['Approved', 'Rejected'];

if ($reportId <= 0 || !in_array($status, $allowedStatus, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid report data.']);
    exit;
}

$conn->begin_transaction();

try {
    ensureNotificationsTable($conn);

    $statusMapStmt = $conn->prepare('SELECT status_id, status_name FROM report_status WHERE status_name IN ("Pending", "Approved", "Rejected")');
    $statusMapStmt->execute();
    $statusRows = $statusMapStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $statusMapStmt->close();

    $statusMap = [];
    foreach ($statusRows as $statusRow) {
        $statusMap[strtolower((string) $statusRow['status_name'])] = (int) $statusRow['status_id'];
    }

    $pendingStatusId = $statusMap['pending'] ?? null;
    $targetStatusId = $statusMap[strtolower($status)] ?? null;
    if (!$pendingStatusId || !$targetStatusId) {
        throw new RuntimeException('Report status mapping is not configured in database.');
    }

    $reportStmt = $conn->prepare('SELECT user_id, status_id FROM reports WHERE report_id = ? LIMIT 1');
    $reportStmt->bind_param('i', $reportId);
    $reportStmt->execute();
    $report = $reportStmt->get_result()->fetch_assoc();
    $reportStmt->close();

    if (!$report) {
        throw new RuntimeException('Report not found.');
    }

    if ((int) ($report['status_id'] ?? 0) !== $pendingStatusId) {
        throw new RuntimeException('Only pending reports can be updated.');
    }

    $updateStmt = $conn->prepare('UPDATE reports SET status_id = ?, verified_by = ?, verified_at = NOW() WHERE report_id = ?');
    $lguUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $updateStmt->bind_param('iii', $targetStatusId, $lguUserId, $reportId);
    $updateStmt->execute();
    $updateStmt->close();

    $notificationTitle = $status === 'Approved'
        ? 'Report Approved'
        : 'Report Rejected';
    $notificationMessage = $status === 'Approved'
        ? 'Your flood report has been approved by LGU Bocaue.'
        : 'Your flood report has been rejected.';

    $actorName = 'LGU Bocaue';
    if (!empty($_SESSION['user_id'])) {
        $sessionUserId = (int) $_SESSION['user_id'];
        $actorStmt = $conn->prepare('SELECT full_name FROM users WHERE user_id = ? LIMIT 1');
        $actorStmt->bind_param('i', $sessionUserId);
        $actorStmt->execute();
        $actorRow = $actorStmt->get_result()->fetch_assoc();
        $actorStmt->close();
        $candidateName = trim((string) ($actorRow['full_name'] ?? ''));
        if ($candidateName !== '') {
            $actorName = $candidateName;
        }
    }

    $notificationType = 'report_update';
    $notifStmt = $conn->prepare('
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ');
    $residentId = (int) $report['user_id'];
    $notifStmt->bind_param(
        'iissss',
        $residentId,
        $reportId,
        $actorName,
        $notificationTitle,
        $notificationMessage,
        $notificationType
    );
    $notifStmt->execute();
    $notifStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Report updated successfully.',
        'status' => $status,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
