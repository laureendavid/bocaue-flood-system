<?php
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

/* Force UTF-8 */
if (method_exists($conn, 'set_charset')) {
    $conn->set_charset('utf8mb4');
}

$sql = "SELECT barangay_id, barangay_name
        FROM barangays
        ORDER BY barangay_name ASC";

$result = $conn->query($sql);

$rows = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode(
    $rows,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
exit;
?>