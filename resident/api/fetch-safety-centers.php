<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

$dbPath = __DIR__ . '/../../config/db.php';

if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'message' => 'db.php not found.']);
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

/**
 * @param mysqli $connection
 */
function safetyCentersColumnExists($connection, string $column): bool
{
    $safeColumn = $connection->real_escape_string($column);
    $result = $connection->query("SHOW COLUMNS FROM evacuation_centers LIKE '{$safeColumn}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

$optionalColumns = '';
if (safetyCentersColumnExists($conn, 'description')) {
    $optionalColumns .= ', ec.description';
}
if (safetyCentersColumnExists($conn, 'operating_hours')) {
    $optionalColumns .= ', ec.operating_hours';
}
if (safetyCentersColumnExists($conn, 'contact_number')) {
    $optionalColumns .= ', ec.contact_number AS center_contact_number';
}

$sql = "
    SELECT
        ec.center_id,
        ec.center_name,
        ec.capacity,
        ec.occupancy
        {$optionalColumns},
        l.full_address,
        l.barangay_id,
        b.barangay_name AS barangay,
        b.municipality,
        b.province,
        l.latitude,
        l.longitude,
        (
            SELECT h.contact_number
            FROM hotlines h
            WHERE h.barangay_id = l.barangay_id
            ORDER BY h.hotline_name ASC
            LIMIT 1
        ) AS contact_number
    FROM evacuation_centers ec
    INNER JOIN locations l ON ec.location_id = l.location_id
    LEFT JOIN barangays b ON l.barangay_id = b.barangay_id
    ORDER BY b.barangay_name ASC, ec.center_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $conn->error,
    ]);
    exit;
}

$centers = [];

while ($row = $result->fetch_assoc()) {
    $barangay = (string) ($row['barangay'] ?? '');
    $municipality = (string) ($row['municipality'] ?? 'Bocaue');
    $province = (string) ($row['province'] ?? 'Bulacan');
    $addressFallback = trim($barangay . ', ' . $municipality . ', ' . $province, ', ');
    $address = trim((string) ($row['full_address'] ?? '')) ?: $addressFallback;
    $lat = $row['latitude'] !== null ? (float) $row['latitude'] : null;
    $lng = $row['longitude'] !== null ? (float) $row['longitude'] : null;

    $centers[] = [
        'center_id' => (int) $row['center_id'],
        'center_name' => (string) $row['center_name'],
        'capacity' => (int) $row['capacity'],
        'occupancy' => (int) $row['occupancy'],
        'address' => $address,
        'full_address' => $address,
        'barangay' => $barangay,
        'municipality' => $municipality,
        'province' => $province,
        'latitude' => $lat,
        'longitude' => $lng,
        'contact' => trim((string) ($row['center_contact_number'] ?? $row['contact_number'] ?? '')) ?: null,
        'description' => trim((string) ($row['description'] ?? '')) ?: null,
        'operating_hours' => trim((string) ($row['operating_hours'] ?? '')) ?: null,
        'has_coordinates' => $lat !== null && $lng !== null,
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($centers),
    'data' => $centers,
]);
