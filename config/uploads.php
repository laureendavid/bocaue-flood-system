<?php

/**
 * Media URL helpers for Cloudinary and legacy local paths.
 */

/**
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function bfis_str_starts_with(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

/**
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function bfis_str_contains(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    return strpos($haystack, $needle) !== false;
}

/**
 * @return string
 */
function bfis_random_hex(int $bytes = 8): string
{
    try {
        return bin2hex(random_bytes($bytes));
    } catch (Throwable $e) {
        return substr(md5(uniqid((string) mt_rand(), true)), 0, $bytes * 2);
    }
}

/**
 * @param string $path
 * @param string $fallbackMime
 * @return string
 */
function bfis_detect_mime_type(string $path, string $fallbackMime = ''): string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }
    }

    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($path);

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }
    }

    return $fallbackMime;
}

/**
 * @param string|null $storedPath
 * @return string
 */
function bfis_upload_public_url(?string $storedPath): string
{
    if ($storedPath === null || trim($storedPath) === '') {
        return '';
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return $storedPath;
    }

    $relative = ltrim(str_replace('\\', '/', $storedPath), '/');
    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

    if ($projectRoot !== false && $documentRoot !== false) {
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $documentRoot = str_replace('\\', '/', $documentRoot);

        if (bfis_str_starts_with($projectRoot, $documentRoot)) {
            $basePath = substr($projectRoot, strlen($documentRoot));

            return rtrim($basePath, '/') . '/' . $relative;
        }
    }

    return '/' . $relative;
}

/**
 * @param string|null $storedPath
 * @return string|null
 */
function bfis_upload_disk_path(?string $storedPath): ?string
{
    if ($storedPath === null || trim($storedPath) === '') {
        return null;
    }

    if (filter_var(trim($storedPath), FILTER_VALIDATE_URL)) {
        return null;
    }

    $relative = ltrim(str_replace('\\', '/', trim($storedPath)), '/');

    if (bfis_str_starts_with($relative, 'uploads/')) {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($relative);
}

/**
 * @param string|null $storedPath
 * @param string $fallback
 * @return string
 */
function bfis_resolve_media_url(?string $storedPath, string $fallback = ''): string
{
    if ($storedPath === null || trim($storedPath) === '') {
        return $fallback;
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return $storedPath;
    }

    $diskPath = bfis_upload_disk_path($storedPath);
    if ($diskPath !== null && !is_file($diskPath)) {
        return $fallback;
    }

    $publicUrl = bfis_upload_public_url($storedPath);

    return $publicUrl !== '' ? $publicUrl : $fallback;
}

/**
 * @param string|null $storedPath
 * @param string $fallback
 * @return string
 */
function bfis_profile_photo_url(?string $storedPath, string $fallback = ''): string
{
    return bfis_resolve_media_url(
        $storedPath,
        $fallback !== '' ? $fallback : 'https://placehold.co/160x160/e2e8f0/475569?text=U'
    );
}

/**
 * @param string|null $storedPath
 * @return bool
 */
function bfis_has_stored_media(?string $storedPath): bool
{
    if ($storedPath === null || trim($storedPath) === '') {
        return false;
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return true;
    }

    $diskPath = bfis_upload_disk_path($storedPath);

    return $diskPath !== null && is_file($diskPath);
}

/**
 * @return string|null
 */
function bfis_reg_temp_directory(): ?string
{
    $candidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reg_temp',
        rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'bfis_reg_temp',
    ];

    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }
    }

    return null;
}

/**
 * @param string|null $storedPath
 * @return string|null
 */
function bfis_reg_resolve_disk_path(?string $storedPath): ?string
{
    if ($storedPath === null || trim($storedPath) === '') {
        return null;
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return null;
    }

    if (is_file($storedPath)) {
        return $storedPath;
    }

    return bfis_upload_disk_path($storedPath);
}

/**
 * @param string|null $storedPath
 * @return bool
 */
function bfis_reg_is_temp_path(?string $storedPath): bool
{
    if ($storedPath === null || trim($storedPath) === '') {
        return false;
    }

    $normalized = str_replace('\\', '/', trim($storedPath));

    if (bfis_str_contains($normalized, 'uploads/reg_temp/')) {
        return true;
    }

    return bfis_str_contains($normalized, '/bfis_reg_temp/');
}

/**
 * @param string|null $storedPath
 * @return void
 */
function bfis_reg_delete_temp_path(?string $storedPath): void
{
    if (!bfis_reg_is_temp_path($storedPath)) {
        return;
    }

    $diskPath = bfis_reg_resolve_disk_path($storedPath);

    if ($diskPath !== null && is_file($diskPath)) {
        @unlink($diskPath);
    }
}

/**
 * @param array<int, string|null> $storedPaths
 * @return void
 */
function bfis_reg_delete_temp_paths(array $storedPaths): void
{
    foreach ($storedPaths as $storedPath) {
        bfis_reg_delete_temp_path($storedPath);
    }
}

/**
 * @param string $tmpPath
 * @param string $prefix
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @return array{path?: string, error?: string}
 */
function bfis_reg_store_upload_path(
    string $tmpPath,
    string $prefix,
    array $allowedMime,
    int $maxBytes,
    string $fallbackMime = ''
): array {
    if (!is_uploaded_file($tmpPath) && !is_readable($tmpPath)) {
        return ['error' => 'Upload file is not readable.'];
    }

    if (filesize($tmpPath) > $maxBytes) {
        return ['error' => 'Uploaded file exceeds allowed size.'];
    }

    $mimeType = bfis_detect_mime_type($tmpPath, $fallbackMime);

    if ($mimeType === '' || !in_array($mimeType, $allowedMime, true)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    $extension = $extensions[$mimeType] ?? 'bin';
    $tempDirectory = bfis_reg_temp_directory();

    if ($tempDirectory === null) {
        return ['error' => 'Server upload folder is not writable. Please contact support.'];
    }

    $filename = $prefix . '_' . bfis_random_hex(8) . '.' . $extension;
    $destination = $tempDirectory . DIRECTORY_SEPARATOR . $filename;
    $moved = is_uploaded_file($tmpPath)
        ? move_uploaded_file($tmpPath, $destination)
        : @rename($tmpPath, $destination);

    if (!$moved) {
        return ['error' => 'Unable to save uploaded file. Please try again.'];
    }

    $projectTempDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reg_temp';
    $storedPath = str_replace('\\', '/', $destination);

    if (str_replace('\\', '/', $tempDirectory) === str_replace('\\', '/', $projectTempDir)) {
        $storedPath = 'uploads/reg_temp/' . $filename;
    }

    return ['path' => $storedPath];
}

/**
 * @param string $fieldName
 * @param string $prefix
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @return array{path?: string, error?: string}
 */
function bfis_reg_store_upload_request(
    string $fieldName,
    string $prefix,
    array $allowedMime,
    int $maxBytes
): array {
    if (empty($_FILES[$fieldName]['name'])) {
        return [];
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed. Please try again.'];
    }

    return bfis_reg_store_upload_path(
        $file['tmp_name'],
        $prefix,
        $allowedMime,
        $maxBytes,
        (string) ($file['type'] ?? '')
    );
}

/**
 * @param string|null $storedPath
 * @param string $fallback
 * @return string
 */
function bfis_reg_preview_url(?string $storedPath, string $fallback = ''): string
{
    if ($storedPath === null || trim($storedPath) === '') {
        return $fallback;
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return $storedPath;
    }

    $diskPath = bfis_reg_resolve_disk_path($storedPath);

    if ($diskPath !== null && is_file($diskPath) && bfis_reg_is_temp_path($storedPath)) {
        $kind = bfis_str_contains(str_replace('\\', '/', $storedPath), 'valid_id') ? 'valid_id' : 'profile';

        return 'reg_media.php?kind=' . $kind;
    }

    return bfis_resolve_media_url($storedPath, $fallback);
}

/**
 * @param string $diskPath
 * @param string $prefix
 * @return string|null
 */
function bfis_reg_promote_temp_file(string $diskPath, string $prefix): ?string
{
    if (!is_readable($diskPath)) {
        return null;
    }

    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';

    if (!is_dir($uploadsDir) && !@mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        return null;
    }

    $extension = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));

    if ($extension === '') {
        $mimeType = bfis_detect_mime_type($diskPath);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        $extension = $extensions[$mimeType] ?? 'bin';
    }

    $filename = $prefix . '_' . bfis_random_hex(8) . '.' . $extension;
    $destination = $uploadsDir . DIRECTORY_SEPARATOR . $filename;

    if (!@copy($diskPath, $destination)) {
        return null;
    }

    return 'uploads/' . $filename;
}

/**
 * @param string|null $storedPath
 * @param string $localPrefix
 * @return array{path?: string|null, temp_path?: string, error?: string}
 */
function bfis_reg_finalize_single_local_media(?string $storedPath, string $localPrefix): array
{
    if ($storedPath === null || trim($storedPath) === '') {
        return ['path' => null];
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return ['path' => $storedPath];
    }

    $diskPath = bfis_reg_resolve_disk_path($storedPath);

    if ($diskPath === null || !is_readable($diskPath)) {
        return ['error' => 'Uploaded file is missing. Please go back to step 1 and upload again.'];
    }

    if (!bfis_reg_is_temp_path($storedPath)) {
        return ['path' => $storedPath];
    }

    $localPath = bfis_reg_promote_temp_file($diskPath, $localPrefix);

    if ($localPath === null) {
        return ['error' => 'Unable to save registration files on the server. Please try again.'];
    }

    return [
        'path' => $localPath,
        'temp_path' => $storedPath,
    ];
}

/**
 * @param string|null $profileStoredPath
 * @param string|null $validIdStoredPath
 * @return array{
 *     profile_picture?: string|null,
 *     valid_id_image?: string|null,
 *     temp_paths?: array<int, string>,
 *     error?: string
 * }
 */
function bfis_reg_finalize_local_uploads(?string $profileStoredPath, ?string $validIdStoredPath): array
{
    $tempPaths = [];

    $profileResult = bfis_reg_finalize_single_local_media($profileStoredPath, 'reg_profile');

    if (isset($profileResult['error'])) {
        return ['error' => $profileResult['error']];
    }

    if (!empty($profileResult['temp_path'])) {
        $tempPaths[] = $profileResult['temp_path'];
    }

    $validIdResult = bfis_reg_finalize_single_local_media($validIdStoredPath, 'reg_valid_id');

    if (isset($validIdResult['error'])) {
        return ['error' => $validIdResult['error']];
    }

    if (!empty($validIdResult['temp_path'])) {
        $tempPaths[] = $validIdResult['temp_path'];
    }

    return [
        'profile_picture' => $profileResult['path'] ?? null,
        'valid_id_image' => $validIdResult['path'] ?? null,
        'temp_paths' => $tempPaths,
    ];
}
