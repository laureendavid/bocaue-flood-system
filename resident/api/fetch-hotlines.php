<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

$dbPath = __DIR__ . '/../../config/db.php';

if (!file_exists($dbPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'db.php not found.',
    ]);
    exit;
}

require_once $dbPath;

if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown'),
    ]);
    exit;
}

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
    ]);
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
]);
