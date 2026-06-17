<?php
// includes/fetch_barangays.php
require_once '../config/db.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name ASC");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
?>