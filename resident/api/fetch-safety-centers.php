<?php
/* =============================================================
   api/fetch-safety-centers.php
   Place in: resident/api/fetch-safety-centers.php
   ============================================================= */
header('Content-Type: application/json');

// ── Step 1: find and load db.php ──────────────────────────────
// Adjust this path if your folder structure is different.
// From resident/api/ we go up two levels to reach config/
$dbPath = __DIR__ . '/../../config/db.php';

if (!file_exists($dbPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'db.php not found at: ' . $dbPath,
    ]);
    exit;
}

require_once $dbPath;

// ── Step 2: check $conn ────────────────────────────────────────
if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown'),
    ]);
    exit;
}

// ── Step 3: query ──────────────────────────────────────────────
$sql = "
    SELECT
        ec.center_id,
        ec.center_name,
        ec.capacity,
        ec.occupancy,
        l.full_address,
        l.barangay,
        l.municipality,
        l.province,
        l.latitude,
        l.longitude
    FROM evacuation_centers ec
    JOIN locations l ON ec.location_id = l.location_id
    ORDER BY l.barangay ASC, ec.center_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $conn->error,
    ]);
    exit;
}

// ── Step 4: build response ─────────────────────────────────────
$centers = [];
while ($row = $result->fetch_assoc()) {
    $centers[] = [
        'center_id'    => (int) $row['center_id'],
        'center_name'  => $row['center_name'],
        'capacity'     => (int) $row['capacity'],
        'occupancy'    => (int) $row['occupancy'],
        'full_address' => $row['full_address']
                          ?: ($row['barangay'] . ', ' . $row['municipality']),
        'barangay'     => $row['barangay'],
        'municipality' => $row['municipality'],
        'province'     => $row['province'],
        'latitude'     => $row['latitude'],
        'longitude'    => $row['longitude'],
        'contact'      => null, // add a contact_number column or hotline join here later
    ];
}

// ── Step 5: return (empty array is still success) ──────────────
echo json_encode([
    'success' => true,
    'count'   => count($centers),
    'data'    => $centers,
]);