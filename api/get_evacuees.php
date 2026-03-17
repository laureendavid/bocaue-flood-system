<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$center_id = intval($_GET['center_id'] ?? 0);
if (!$center_id) {
    echo json_encode(['success' => false, 'evacuees' => []]);
    exit;
}

$result = $conn->query("SELECT evacuee_id, representative, number_of_people, contact_number FROM evacuees WHERE center_id = $center_id ORDER BY created_at DESC");
$evacuees = [];
while ($row = $result->fetch_assoc()) {
    $evacuees[] = $row;
}

echo json_encode(['success' => true, 'evacuees' => $evacuees]);