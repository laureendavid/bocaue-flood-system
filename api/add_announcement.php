<?php
require_once '../config/db.php';
require_once '../includes/notifications_service.php';
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
    $announcementId = (int) mysqli_insert_id($conn);
    $fromWho = bfis_resolve_actor_name($conn, (int) ($created_by ?? 0), 'Bocaue LGU');
    $createdAt = date('Y-m-d H:i:s');

    $createdAtStmt = mysqli_prepare($conn, 'SELECT created_at FROM announcements WHERE announcement_id = ? LIMIT 1');
    if ($createdAtStmt) {
        mysqli_stmt_bind_param($createdAtStmt, 'i', $announcementId);
        mysqli_stmt_execute($createdAtStmt);
        $createdAtResult = mysqli_stmt_get_result($createdAtStmt);
        $createdAtRow = mysqli_fetch_assoc($createdAtResult);
        mysqli_stmt_close($createdAtStmt);
        if (!empty($createdAtRow['created_at'])) {
            $createdAt = (string) $createdAtRow['created_at'];
        }
    }

    try {
        bfis_ensure_notifications_table($conn);
        bfis_notify_residents_of_announcement(
            $conn,
            $barangay_id !== null ? (int) $barangay_id : null,
            $title,
            $message,
            $createdAt,
            $fromWho
        );
    } catch (Throwable $e) {
        error_log('Announcement notification fan-out failed: ' . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Announcement added successfully.', 'announcement_id' => $announcementId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);