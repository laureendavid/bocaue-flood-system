<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

// rescue_status_id: 2 = Rescue Needed | 3 = Being Rescued | 4 = Rescued
// status_id = 2 means approved report

/* ── Overall stats (all rescuers) ─────────────────────────── */
$sql_overall = "
    SELECT 
        rs.rescue_status_id,
        COUNT(r.report_id) AS total
    FROM rescue_status rs
    LEFT JOIN reports r 
        ON r.rescue_status_id = rs.rescue_status_id
        AND r.status_id = 2
    WHERE rs.rescue_status_id IN (2, 3, 4)
    GROUP BY rs.rescue_status_id
";

$overall = ['needing' => 0, 'inprogress' => 0, 'rescued' => 0];

$result = $conn->query($sql_overall);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        switch ((int) $row['rescue_status_id']) {
            case 2:
                $overall['needing'] = (int) $row['total'];
                break;
            case 3:
                $overall['inprogress'] = (int) $row['total'];
                break;
            case 4:
                $overall['rescued'] = (int) $row['total'];
                break;
        }
    }
}

/* ── Personal stats (logged-in rescuer only) ──────────────── */
$personal = ['inprogress' => 0, 'rescued' => 0];

$rescuer_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($rescuer_id > 0) {
    $sql_personal = "
        SELECT 
            rs.rescue_status_id,
            COUNT(r.report_id) AS total
        FROM rescue_status rs
        LEFT JOIN reports r 
            ON r.rescue_status_id = rs.rescue_status_id
            AND r.status_id = 2
            AND r.assigned_rescuer_id = ?
        WHERE rs.rescue_status_id IN (3, 4)
        GROUP BY rs.rescue_status_id
    ";

    $stmt = $conn->prepare($sql_personal);
    $stmt->bind_param("i", $rescuer_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        switch ((int) $row['rescue_status_id']) {
            case 3:
                $personal['inprogress'] = (int) $row['total'];
                break;
            case 4:
                $personal['rescued'] = (int) $row['total'];
                break;
        }
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'overall' => $overall,
    'personal' => $personal,
]);

$conn->close();
?>