<?php
header('Content-Type: application/json');

$requiredRole = 'LGU';
require_once '../config/auth.php';
require_once '../config/db.php';
require_once __DIR__ . '/notifications_service.php';

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
    bfis_ensure_notifications_table($conn);

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

    $residentId = (int) $report['user_id'];
    bfis_create_report_update_notification(
        $conn,
        $residentId,
        $reportId,
        $notificationTitle,
        $notificationMessage,
        $actorName
    );

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
