<?php
// fetch_report_stats.php
// Include your DB connection
require_once '../config/db.php';

$pending_count = 0;
$approved_count = 0;

try {
    // Count Pending (status_id = 1)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM reports r
        JOIN report_status rs ON r.status_id = rs.status_id
        WHERE rs.status_name = 'Pending'
    ");
    $stmt->execute();
    $pending_count = $stmt->fetchColumn();

    // Count Approved (status_id = 2)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM reports r
        JOIN report_status rs ON r.status_id = rs.status_id
        WHERE rs.status_name = 'Approved'
    ");
    $stmt->execute();
    $approved_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Silently fail; counts remain 0
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>