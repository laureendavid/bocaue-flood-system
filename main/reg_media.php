<?php
session_start();

require_once __DIR__ . '/../config/uploads.php';

$kind = $_GET['kind'] ?? '';
$sessionKey = $kind === 'valid_id' ? 'reg_valid_id_image' : 'reg_profile_picture';
$storedPath = $_SESSION[$sessionKey] ?? '';
$diskPath = bfis_reg_resolve_disk_path($storedPath);

if ($diskPath === null || !is_file($diskPath) || !bfis_reg_is_temp_path($storedPath)) {
    http_response_code(404);
    exit;
}

$mimeType = bfis_detect_mime_type($diskPath);

if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($diskPath));
header('Cache-Control: private, no-store, no-cache, must-revalidate');
readfile($diskPath);
