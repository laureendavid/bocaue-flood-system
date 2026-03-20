<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT ec.center_name, ec.capacity, ec.occupancy,
               COALESCE(l.full_address, CONCAT(l.barangay, ', ', l.municipality)) AS location,
               l.latitude, l.longitude
        FROM evacuation_centers ec
        LEFT JOIN locations l ON ec.location_id = l.location_id
        ORDER BY ec.center_name ASC";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
?>