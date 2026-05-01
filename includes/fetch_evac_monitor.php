<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "
SELECT 
    ec.center_id,
    ec.center_name,
    ec.capacity,
    ec.occupancy,
    
    COALESCE(
        l.full_address,
        CONCAT(b.barangay_name, ', ', b.municipality, ', ', b.province)
    ) AS location,

    l.latitude,
    l.longitude

FROM evacuation_centers ec
LEFT JOIN locations l ON ec.location_id = l.location_id
LEFT JOIN barangays b ON l.barangay_id = b.barangay_id

ORDER BY ec.center_name ASC
";

$result = $conn->query($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {

        $data[] = [
            "center_id" => $row["center_id"],
            "center_name" => $row["center_name"],
            "capacity" => (int) $row["capacity"],
            "occupancy" => (int) $row["occupancy"],
            "location" => $row["location"],
            "latitude" => $row["latitude"],
            "longitude" => $row["longitude"]
        ];
    }
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>