<?php
/**
 * update_rescue_status.php
 * POST handler — updates the rescue_status_id on a report.
 * Called via fetch() from the rescuer community page.
 */

header('Content-Type: application/json');
require_once '../config/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$report_id = isset($_POST['report_id']) ? (int) $_POST['report_id'] : 0;
$new_status_id = isset($_POST['new_status_id']) ? (int) $_POST['new_status_id'] : 0;

if ($report_id <= 0 || $new_status_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Validate that new_status_id exists in rescue_status table
$check = $conn->prepare("SELECT status_name FROM rescue_status WHERE rescue_status_id = ?");
$check->bind_param("i", $new_status_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rescue status']);
    exit;
}

$statusRow = $checkResult->fetch_assoc();
$statusName = $statusRow['status_name'];

// Update the report
$stmt = $conn->prepare("UPDATE reports SET rescue_status_id = ?, updated_at = NOW() WHERE report_id = ?");
$stmt->bind_param("ii", $new_status_id, $report_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Rescue status updated successfully',
        'status_id' => $new_status_id,
        'status_name' => $statusName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>