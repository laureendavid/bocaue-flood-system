<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$center_id = intval($_POST['center_id'] ?? 0);
$name = trim($_POST['center_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);

if (!$center_id || !$name || !$capacity) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("UPDATE evacuation_centers SET center_name = ?, capacity = ? WHERE center_id = ?");
$stmt->bind_param("sii", $name, $capacity, $center_id);

if ($stmt->execute()) {
    $upd = $conn->prepare("UPDATE locations l JOIN evacuation_centers ec ON ec.location_id = l.location_id SET l.full_address = ? WHERE ec.center_id = ?");
    $upd->bind_param("si", $address, $center_id);
    $upd->execute();
    echo json_encode(['success' => true, 'message' => 'Center updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update center: ' . $stmt->error]);
}