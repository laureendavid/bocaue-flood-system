<?php
/**
 * fetch_flood_severity_map.php
 * Returns approved flood reports with severity, coordinates, and barangay info
 * for rendering color-coded markers on the map.
 *
 * Place this file inside: includes/fetch_flood_severity_map.php
 */

header('Content-Type: application/json');

// ── DB connection ────────────────────────────────────────────────────────────
// Adjust path to your actual db config/connection file
require_once __DIR__ . '/../config/db.php'; // expects a $conn (mysqli) or $pdo (PDO)

try {
    /*
     * Join chain:
     *   reports  → locations  → barangays
     *            → flood_severity
     *            → report_status   (only approved = status_id 2)
     *            → water_levels
     *
     * We only show reports that are APPROVED (status_id = 2) so the map
     * reflects verified information.
     */
    $sql = "
        SELECT
            r.report_id,
            r.description,
            r.created_at,

            -- Severity
            fs.severity_id,
            fs.severity_name,

            -- Water level
            wl.level_name   AS water_level,
            wl.level_order,

            -- Report status
            rs.status_name  AS report_status,

            -- Location
            l.latitude,
            l.longitude,
            l.full_address,

            -- Barangay
            b.barangay_name,
            b.municipality,
            b.province,

            -- Reporter (first name only for privacy)
            u.full_name     AS reported_by

        FROM reports r
        JOIN locations      l   ON l.location_id  = r.location_id
        JOIN barangays      b   ON b.barangay_id  = l.barangay_id
        JOIN flood_severity fs  ON fs.severity_id = r.severity_id
        JOIN report_status  rs  ON rs.status_id   = r.status_id
        LEFT JOIN water_levels wl ON wl.water_level_id = r.water_level_id
        LEFT JOIN users     u   ON u.user_id       = r.user_id

        WHERE r.status_id = 2          -- Approved only
          AND r.severity_id IS NOT NULL
          AND l.latitude  IS NOT NULL
          AND l.longitude IS NOT NULL

        ORDER BY r.created_at DESC
    ";

    // ── MySQLi branch ────────────────────────────────────────────────────────
    if (isset($conn) && $conn instanceof mysqli) {
        $result = $conn->query($sql);
        if (!$result) {
            throw new RuntimeException($conn->error);
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // ── PDO branch ───────────────────────────────────────────────────────────
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        throw new RuntimeException('No valid database connection found ($conn or $pdo).');
    }

    // ── Enrich each row with colour/label metadata ───────────────────────────
    $severityMeta = [
        1 => ['color' => '#22c55e', 'label' => 'Passable', 'icon' => 'water_drop'],
        2 => ['color' => '#eab308', 'label' => 'Limited Access', 'icon' => 'warning'],
        3 => ['color' => '#ef4444', 'label' => 'Impassable', 'icon' => 'flood'],
    ];

    foreach ($rows as &$row) {
        $sid = (int) $row['severity_id'];
        $row['severity_color'] = $severityMeta[$sid]['color'] ?? '#94a3b8';
        $row['severity_icon'] = $severityMeta[$sid]['icon'] ?? 'location_on';
        $row['latitude'] = (float) $row['latitude'];
        $row['longitude'] = (float) $row['longitude'];
    }
    unset($row);

    echo json_encode(['success' => true, 'data' => $rows]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}