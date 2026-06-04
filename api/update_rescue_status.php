<?php
/**
 * update_rescue_status.php
 * POST handler — updates the rescue_status_id on a report.
 * Also assigns the rescuer on first claim and locks it from others.
 * Called via fetch() from the rescuer community page.
 */

session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// ── 1. Only accept POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── 2. Must be logged in ─────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$reportId = isset($_POST['report_id']) ? (int) $_POST['report_id'] : 0;
$newStatusId = isset($_POST['new_status_id']) ? (int) $_POST['new_status_id'] : 0;

// ── 3. Basic input validation ────────────────────────────────────────────────
if ($reportId <= 0 || $newStatusId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// ── 4. Only allow valid forward transitions ──────────────────────────────────
//    Rescue Needed (2) → Being Rescued (3) → Rescued (4)
$allowedTransitions = [2 => 3, 3 => 4];

// ── 5. Fetch the current state of the report ─────────────────────────────────
$fetch = $conn->prepare("
    SELECT rescue_status_id, assigned_rescuer_id
    FROM   reports
    WHERE  report_id = ? AND status_id = 2
");
$fetch->bind_param('i', $reportId);
$fetch->execute();
$row = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Report not found.']);
    exit;
}

$currentStatusId = (int) $row['rescue_status_id'];
$assignedRescuerId = $row['assigned_rescuer_id'] ? (int) $row['assigned_rescuer_id'] : null;

// ── 6. Validate the transition is allowed ────────────────────────────────────
if (!isset($allowedTransitions[$currentStatusId]) || $allowedTransitions[$currentStatusId] !== $newStatusId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status transition.']);
    exit;
}

// ── 7. Assignment / lock checks ──────────────────────────────────────────────
if ($currentStatusId === 2) {
    // "Rescue Needed" → "Being Rescued"
    // If someone else already claimed it, block this rescuer
    if ($assignedRescuerId && $assignedRescuerId !== $currentUserId) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This rescue has already been claimed by another rescuer.']);
        exit;
    }
    // Assign this rescuer
    $newAssignedId = $currentUserId;

} elseif ($currentStatusId === 3) {
    // "Being Rescued" → "Rescued"
    // Only the assigned rescuer can mark it as done
    if ($assignedRescuerId !== $currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only the assigned rescuer can complete this rescue.']);
        exit;
    }
    // Keep the same assigned rescuer
    $newAssignedId = $assignedRescuerId;
}

// ── 8. Validate new_status_id exists and get its name ────────────────────────
$check = $conn->prepare("SELECT status_name FROM rescue_status WHERE rescue_status_id = ?");
$check->bind_param('i', $newStatusId);
$check->execute();
$checkRow = $check->get_result()->fetch_assoc();
$check->close();

if (!$checkRow) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rescue status.']);
    exit;
}

$statusName = $checkRow['status_name'];

// ── 9. Perform the update ────────────────────────────────────────────────────
$stmt = $conn->prepare("
    UPDATE reports
    SET    rescue_status_id    = ?,
           assigned_rescuer_id = ?,
           updated_at          = NOW()
    WHERE  report_id = ?
");
$stmt->bind_param('iii', $newStatusId, $newAssignedId, $reportId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Rescue status updated successfully',
        'status_id' => $newStatusId,
        'status_name' => $statusName,
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>