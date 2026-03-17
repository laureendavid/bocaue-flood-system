<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$center_id = intval($_POST['center_id'] ?? 0);
$rep = trim($_POST['representative'] ?? '');
$count = intval($_POST['number_of_people'] ?? 0);
$contact = trim($_POST['contact_number'] ?? '');

if (!$center_id || !$rep || !$count || !$contact) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$check = $conn->prepare("SELECT capacity, occupancy FROM evacuation_centers WHERE center_id = ?");
$check->bind_param("i", $center_id);
$check->execute();
$center = $check->get_result()->fetch_assoc();

if (!$center) {
    echo json_encode(['success' => false, 'message' => 'Center not found.']);
    exit;
}

if (($center['occupancy'] + $count) > $center['capacity']) {
    echo json_encode(['success' => false, 'message' => 'Not enough capacity in this center.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO evacuees (center_id, representative, number_of_people, contact_number) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isis", $center_id, $rep, $count, $contact);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to add evacuee: ' . $stmt->error]);
    exit;
}

$upd = $conn->prepare("UPDATE evacuation_centers SET occupancy = occupancy + ? WHERE center_id = ?");
$upd->bind_param("ii", $count, $center_id);
$upd->execute();

echo json_encode(['success' => true, 'message' => 'Evacuee added successfully.']);