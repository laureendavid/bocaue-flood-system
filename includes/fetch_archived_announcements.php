<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT a.announcement_id, a.title, a.message,
               COALESCE(b.barangay_name, 'All Barangays') AS target_area,
               a.expiry_date
        FROM announcements a
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        WHERE a.expiry_date IS NOT NULL AND a.expiry_date < CURDATE()
        ORDER BY a.expiry_date DESC";

$result = mysqli_query($conn, $sql);
$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

echo json_encode(['success' => true, 'data' => $rows]);