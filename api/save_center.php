<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$center_name = trim($_POST['center_name'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';
$address = trim($_POST['address'] ?? '');

if (!$center_name || !$capacity || !$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$barangay = 'Bocaue';
$municipality = 'Bocaue';
$province = 'Bulacan';

$bgy_result = $conn->query("SELECT barangay_name FROM barangays");
while ($bgy = $bgy_result->fetch_assoc()) {
    if (stripos($address, $bgy['barangay_name']) !== false) {
        $barangay = $bgy['barangay_name'];
        break;
    }
}

$stmt = $conn->prepare("INSERT INTO locations 
    (location_type, location_name, barangay, municipality, province, latitude, longitude, full_address) 
    VALUES ('Evacuation Center', ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $center_name, $barangay, $municipality, $province, $lat, $lng, $address);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save location: ' . $stmt->error]);
    exit;
}

$location_id = $conn->insert_id;

$stmt2 = $conn->prepare("INSERT INTO evacuation_centers 
    (center_name, location_id, capacity, occupancy) 
    VALUES (?, ?, ?, 0)");
$stmt2->bind_param("sii", $center_name, $location_id, $capacity);

if (!$stmt2->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save evacuation center: ' . $stmt2->error]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Evacuation center added successfully.']);