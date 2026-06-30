<?php
header('Content-Type: application/json');

$requiredRole = 'Resident';
require_once __DIR__ . '/../config/auth.php';

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

echo json_encode([
    'success' => true,
    'synced' => false,
]);
