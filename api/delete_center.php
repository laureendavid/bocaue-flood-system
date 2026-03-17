<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$center_id = intval($_POST['center_id'] ?? 0);
if (!$center_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid center.']);
    exit;
}

$center = $conn->query("SELECT location_id FROM evacuation_centers WHERE center_id = $center_id")->fetch_assoc();

$conn->query("DELETE FROM evacuees WHERE center_id = $center_id");
$conn->query("DELETE FROM evacuation_centers WHERE center_id = $center_id");
if ($center) {
    $conn->query("DELETE FROM locations WHERE location_id = {$center['location_id']}");
}

echo json_encode(['success' => true, 'message' => 'Center deleted successfully.']);