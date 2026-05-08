<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// rescue_status_id:
// 2 = Rescue Needed
// 3 = Being Rescued  
// 4 = Rescued

$sql = "
    SELECT 
        rs.rescue_status_id,
        rs.status_name,
        COUNT(r.report_id) AS total
    FROM rescue_status rs
    LEFT JOIN reports r 
        ON r.rescue_status_id = rs.rescue_status_id
        AND r.status_id = 2
    WHERE rs.rescue_status_id IN (2, 3, 4)
    GROUP BY rs.rescue_status_id, rs.status_name
";

$result = $conn->query($sql);

$stats = [
    'needing' => 0,   // rescue_status_id = 2
    'inprogress' => 0,   // rescue_status_id = 3
    'rescued' => 0,   // rescue_status_id = 4
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        switch ((int) $row['rescue_status_id']) {
            case 2:
                $stats['needing'] = (int) $row['total'];
                break;
            case 3:
                $stats['inprogress'] = (int) $row['total'];
                break;
            case 4:
                $stats['rescued'] = (int) $row['total'];
                break;
        }
    }
}

echo json_encode(['success' => true, 'data' => $stats]);
$conn->close();
?>