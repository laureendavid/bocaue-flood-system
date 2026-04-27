<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$center_name = trim($_POST['center_name'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$address = trim($_POST['address'] ?? '');

if (!$center_name || !$capacity || !$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

/* =========================
   1. GET DEFAULT BARANGAY
   (fallback only)
========================= */
$barangay_id = null;

if (!empty($_POST['barangay_id'])) {
    $barangay_id = intval($_POST['barangay_id']);
} else {

    // fallback: pick first barangay if none provided
    $res = $conn->query("SELECT barangay_id FROM barangays LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $barangay_id = $row['barangay_id'];
    }
}

/* =========================
   2. INSERT LOCATION (FIXED)
========================= */
$stmt = $conn->prepare("
    INSERT INTO locations 
    (barangay_id, latitude, longitude, full_address)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "idds",
    $barangay_id,
    $lat,
    $lng,
    $address
);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save location',
        'error' => $stmt->error
    ]);
    exit;
}

$location_id = $conn->insert_id;

/* =========================
   3. INSERT EVAC CENTER
========================= */
$stmt2 = $conn->prepare("
    INSERT INTO evacuation_centers 
    (center_name, location_id, capacity, occupancy)
    VALUES (?, ?, ?, 0)
");

$stmt2->bind_param(
    "sii",
    $center_name,
    $location_id,
    $capacity
);

if (!$stmt2->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save evacuation center',
        'error' => $stmt2->error
    ]);
    exit;
}

/* =========================
   SUCCESS
========================= */
echo json_encode([
    'success' => true,
    'message' => 'Evacuation center added successfully',
    'location_id' => $location_id
]);
?>