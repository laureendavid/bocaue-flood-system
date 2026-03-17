<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT center_name, capacity, occupancy 
            FROM evacuation_centers 
            ORDER BY center_name ASC";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
?>