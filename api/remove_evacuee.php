<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$evacuee_id = intval($_POST['evacuee_id'] ?? 0);

if (!$evacuee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid evacuee.']);
    exit;
}

$row = $conn->query("SELECT center_id, number_of_people FROM evacuees WHERE evacuee_id = $evacuee_id")->fetch_assoc();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Evacuee not found.']);
    exit;
}

$conn->query("DELETE FROM evacuees WHERE evacuee_id = $evacuee_id");
$conn->query("UPDATE evacuation_centers SET occupancy = GREATEST(0, occupancy - {$row['number_of_people']}) WHERE center_id = {$row['center_id']}");

echo json_encode(['success' => true, 'message' => 'Evacuee removed.']);