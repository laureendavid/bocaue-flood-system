<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$hotlines_sql = "
    SELECT b.barangay_name AS barangay, h.hotline_name, h.contact_number
    FROM hotlines h
    JOIN barangays b ON h.barangay_id = b.barangay_id
    ORDER BY b.barangay_name ASC
";

$hotlines_result = $conn->query($hotlines_sql);
$grouped = [];

if ($hotlines_result && $hotlines_result->num_rows > 0) {
    while ($row = $hotlines_result->fetch_assoc()) {
        $grouped[$row['barangay']][] = [
            'hotline_name'   => $row['hotline_name'],
            'contact_number' => $row['contact_number']
        ];
    }
}

echo json_encode(['success' => true, 'data' => $grouped]);
?>