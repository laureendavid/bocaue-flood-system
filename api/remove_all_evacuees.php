<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$center_id = intval($_POST['center_id'] ?? 0);
if (!$center_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid center.']);
    exit;
}

$conn->query("DELETE FROM evacuees WHERE center_id = $center_id");
$conn->query("UPDATE evacuation_centers SET occupancy = 0 WHERE center_id = $center_id");

echo json_encode(['success' => true, 'message' => 'All evacuees removed.']);