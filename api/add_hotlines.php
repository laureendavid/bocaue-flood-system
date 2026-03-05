<?php
include('../config/db.php');

$barangay_id = intval($_POST['barangay_id'] ?? 0);
$name = trim($_POST['hotline_name'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');

if (!$barangay_id || !$name || !$contact) {
    echo "All fields are required";
    exit;
}

$stmt = $conn->prepare("INSERT INTO hotlines (barangay_id, hotline_name, contact_number) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $barangay_id, $name, $contact);

try {
    $stmt->execute();
    echo "success";
} catch (mysqli_sql_exception $e) {
    echo $e->getMessage();
}
$stmt->close();
$conn->close();
?>