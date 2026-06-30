<?php

/**
 * Central notification creation and schema helpers.
 * All notification inserts must go through this module to prevent duplicates.
 */

/**
 * Ensure the notifications table exists with required columns.
 */
function bfis_ensure_notifications_table(mysqli $conn): void
{
    $createSql = "
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
            INDEX idx_notifications_report (report_id),
            INDEX idx_notifications_user_type_report (user_id, type, report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    if (!$conn->query($createSql)) {
        throw new RuntimeException('Failed to ensure notifications table: ' . $conn->error);
    }

    $columns = [];
    $columnResult = $conn->query('SHOW COLUMNS FROM notifications');
    if ($columnResult) {
        while ($column = $columnResult->fetch_assoc()) {
            $columns[strtolower((string) ($column['Field'] ?? ''))] = true;
        }
    }

    if (!isset($columns['from_who'])) {
        $conn->query("ALTER TABLE notifications ADD COLUMN from_who VARCHAR(150) NOT NULL DEFAULT 'Bocaue LGU' AFTER report_id");
    }
    if (!isset($columns['type'])) {
        $conn->query("ALTER TABLE notifications ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'alert' AFTER message");
    }
    if (!isset($columns['is_read'])) {
        $conn->query('ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
    }
    if (!isset($columns['report_id'])) {
        $conn->query('ALTER TABLE notifications ADD COLUMN report_id INT NULL AFTER user_id');
    }
    if (!isset($columns['title'])) {
        $conn->query('ALTER TABLE notifications ADD COLUMN title VARCHAR(191) NULL AFTER from_who');
    }

    $conn->query('ALTER TABLE notifications MODIFY COLUMN report_id INT NULL');
}

/**
 * @return string
 */
function bfis_notification_sync_lock_name(int $userId): string
{
    return 'bfis_notification_sync_' . $userId;
}

/**
 * @return bool
 */
function bfis_notifications_acquire_sync_lock(mysqli $conn, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $lockName = bfis_notification_sync_lock_name($userId);
    $result = $conn->query("SELECT GET_LOCK('" . $conn->real_escape_string($lockName) . "', 10) AS lock_result");
    if (!$result) {
        return false;
    }

    $row = $result->fetch_assoc();
    $result->free();

    return (int) ($row['lock_result'] ?? 0) === 1;
}

function bfis_notifications_release_sync_lock(mysqli $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $lockName = bfis_notification_sync_lock_name($userId);
    $conn->query("SELECT RELEASE_LOCK('" . $conn->real_escape_string($lockName) . "')");
}

/**
 * @return bool
 */
function bfis_notification_exists(
    mysqli $conn,
    int $userId,
    string $type,
    ?int $reportId = null,
    ?string $title = null,
    ?string $message = null,
    ?string $createdAt = null
): bool {
    if ($reportId !== null && $reportId > 0) {
        $stmt = $conn->prepare('
            SELECT id
            FROM notifications
            WHERE user_id = ?
              AND type = ?
              AND report_id = ?
            LIMIT 1
        ');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isi', $userId, $type, $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    $stmt = $conn->prepare('
        SELECT id
        FROM notifications
        WHERE user_id = ?
          AND type = ?
          AND report_id IS NULL
          AND COALESCE(title, \'\') = ?
          AND message = ?
          AND created_at = ?
        LIMIT 1
    ');
    if (!$stmt) {
        return false;
    }
    $normalizedTitle = $title ?? '';
    $normalizedMessage = $message ?? '';
    $normalizedCreatedAt = $createdAt ?? '';
    $stmt->bind_param('issss', $userId, $type, $normalizedTitle, $normalizedMessage, $normalizedCreatedAt);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

/**
 * Create a report status notification once per report and resident.
 */
function bfis_create_report_update_notification(
    mysqli $conn,
    int $residentId,
    int $reportId,
    string $title,
    string $message,
    string $fromWho
): bool {
    if ($residentId <= 0 || $reportId <= 0) {
        return false;
    }

    $stmt = $conn->prepare('
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        SELECT ?, ?, ?, ?, ?, \'report_update\', 0, NOW()
        FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1
            FROM notifications n
            WHERE n.user_id = ?
              AND n.type = \'report_update\'
              AND n.report_id = ?
        )
    ');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iisssii', $residentId, $reportId, $fromWho, $title, $message, $residentId, $reportId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * Notify residents about a newly published announcement.
 */
function bfis_notify_residents_of_announcement(
    mysqli $conn,
    ?int $barangayId,
    string $title,
    string $message,
    string $createdAt,
    string $fromWho
): void {
    $normalizedTitle = trim($title) !== '' ? trim($title) : 'New Announcement';
    $normalizedMessage = trim($message);
    if ($normalizedMessage === '') {
        return;
    }

    if ($barangayId === null) {
        $sql = "
            INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
            SELECT
                u.user_id,
                NULL,
                ?,
                ?,
                ?,
                'announcement',
                0,
                ?
            FROM users u
            WHERE u.role = 'Resident'
              AND NOT EXISTS (
                SELECT 1
                FROM notifications n
                WHERE n.user_id = u.user_id
                  AND n.type = 'announcement'
                  AND n.report_id IS NULL
                  AND COALESCE(n.title, '') = ?
                  AND n.message = ?
                  AND n.created_at = ?
              )
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'sssssss',
            $fromWho,
            $normalizedTitle,
            $normalizedMessage,
            $createdAt,
            $normalizedTitle,
            $normalizedMessage,
            $createdAt
        );
        $stmt->execute();
        $stmt->close();

        return;
    }

    $sql = "
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        SELECT
            u.user_id,
            NULL,
            ?,
            ?,
            ?,
            'announcement',
            0,
            ?
        FROM users u
        WHERE u.role = 'Resident'
          AND u.barangay_id = ?
          AND NOT EXISTS (
            SELECT 1
            FROM notifications n
            WHERE n.user_id = u.user_id
              AND n.type = 'announcement'
              AND n.report_id IS NULL
              AND COALESCE(n.title, '') = ?
              AND n.message = ?
              AND n.created_at = ?
          )
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    $stmt->bind_param(
        'ssssisss',
        $fromWho,
        $normalizedTitle,
        $normalizedMessage,
        $createdAt,
        $barangayId,
        $normalizedTitle,
        $normalizedMessage,
        $createdAt
    );
    $stmt->execute();
    $stmt->close();
}

/**
 * Notify nearby residents about a newly submitted flood report.
 */
function bfis_notify_residents_of_flood_report(
    mysqli $conn,
    int $reportId,
    int $barangayId,
    int $reporterUserId,
    string $createdAt
): void {
    if ($reportId <= 0) {
        return;
    }

    $title = 'Nearby Flood Alert';
    $message = 'A flood has been reported near your area. Stay alert.';
    $fromWho = 'System Alert';

    if ($barangayId > 0) {
        $sql = "
            INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
            SELECT
                u.user_id,
                ?,
                ?,
                ?,
                ?,
                'alert',
                0,
                ?
            FROM users u
            WHERE u.role = 'Resident'
              AND u.barangay_id = ?
              AND u.user_id <> ?
              AND NOT EXISTS (
                SELECT 1
                FROM notifications n
                WHERE n.user_id = u.user_id
                  AND n.type = 'alert'
                  AND n.report_id = ?
              )
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'isssiiii',
            $reportId,
            $fromWho,
            $title,
            $message,
            $createdAt,
            $barangayId,
            $reporterUserId,
            $reportId
        );
        $stmt->execute();
        $stmt->close();

        return;
    }

    $reporterStmt = $conn->prepare('SELECT latitude, longitude FROM users WHERE user_id = ? LIMIT 1');
    if (!$reporterStmt) {
        return;
    }
    $reporterStmt->bind_param('i', $reporterUserId);
    $reporterStmt->execute();
    $reporter = $reporterStmt->get_result()->fetch_assoc();
    $reporterStmt->close();

    $latitude = isset($reporter['latitude']) ? (float) $reporter['latitude'] : 0.0;
    $longitude = isset($reporter['longitude']) ? (float) $reporter['longitude'] : 0.0;
    if ($latitude === 0.0 && $longitude === 0.0) {
        return;
    }

    $sql = "
        INSERT INTO notifications (user_id, report_id, from_who, title, message, type, is_read, created_at)
        SELECT
            u.user_id,
            ?,
            ?,
            ?,
            ?,
            'alert',
            0,
            ?
        FROM users u
        INNER JOIN reports r ON r.report_id = ?
        INNER JOIN locations l ON l.location_id = r.location_id
        WHERE u.role = 'Resident'
          AND u.user_id <> ?
          AND ABS(l.latitude - ?) <= 0.02
          AND ABS(l.longitude - ?) <= 0.02
          AND NOT EXISTS (
            SELECT 1
            FROM notifications n
            WHERE n.user_id = u.user_id
              AND n.type = 'alert'
              AND n.report_id = ?
          )
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    $stmt->bind_param(
        'issssiiddi',
        $reportId,
        $fromWho,
        $title,
        $message,
        $createdAt,
        $reportId,
        $reporterUserId,
        $latitude,
        $longitude,
        $reportId
    );
    $stmt->execute();
    $stmt->close();
}

/**
 * @return string
 */
function bfis_resolve_actor_name(mysqli $conn, int $userId, string $fallback = 'Bocaue LGU'): string
{
    if ($userId <= 0) {
        return $fallback;
    }

    $stmt = $conn->prepare('SELECT full_name FROM users WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return $fallback;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $name = trim((string) ($row['full_name'] ?? ''));

    return $name !== '' ? $name : $fallback;
}
