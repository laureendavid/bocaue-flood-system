<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// IMPORTANT: Fixes ñ and other special characters
mysqli_set_charset($conn, "utf8mb4");

$sql = "
    SELECT b.barangay_name AS barangay, h.hotline_name, h.contact_number
    FROM hotlines h
    INNER JOIN barangays b ON h.barangay_id = b.barangay_id
    ORDER BY b.barangay_name ASC, h.hotline_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $conn->error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$grouped = [];

while ($row = $result->fetch_assoc()) {
    $barangay = (string) $row['barangay'];

    $grouped[$barangay][] = [
        'hotline_name' => (string) $row['hotline_name'],
        'contact_number' => (string) $row['contact_number'],
    ];
}

echo json_encode([
    'success' => true,
    'count' => array_sum(array_map('count', $grouped)),
    'data' => $grouped,
], JSON_UNESCAPED_UNICODE);