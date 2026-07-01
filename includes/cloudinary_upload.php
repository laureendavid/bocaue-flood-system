<?php

/**
 * Shared Cloudinary upload helpers.
 */

require_once __DIR__ . '/../config/cloudinary.php';
require_once __DIR__ . '/../config/cloudinary_folders.php';
require_once __DIR__ . '/../config/uploads.php';

use Cloudinary\Api\Upload\UploadApi;

/**
 * @param string $path
 * @param array<int, string> $allowedMime
 */
function bfis_upload_matches_allowed_mime(string $path, array $allowedMime): bool
{
    $mimeType = bfis_detect_mime_type($path);

    if ($mimeType !== '' && in_array($mimeType, $allowedMime, true)) {
        return true;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $extensionMimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    if (!isset($extensionMimeMap[$extension])) {
        return false;
    }

    return in_array($extensionMimeMap[$extension], $allowedMime, true);
}

/**
 * @param array<string, string> $params
 */
function bfis_cloudinary_sign_params(array $params, string $apiSecret): string
{
    ksort($params);
    $pairs = [];

    foreach ($params as $key => $value) {
        $pairs[] = $key . '=' . $value;
    }

    return sha1(implode('&', $pairs) . $apiSecret);
}

/**
 * @param string $tmpPath
 * @param string $folder
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @param string $resourceType
 * @return array{url?: string, public_id?: string, error?: string}
 */
function bfis_cloudinary_upload_http(
    string $tmpPath,
    string $folder,
    array $allowedMime,
    int $maxBytes,
    string $resourceType = 'image'
): array {
    if (!function_exists('curl_init')) {
        return ['error' => 'Cloudinary uploads require cURL on this server.'];
    }

    if (!is_readable($tmpPath)) {
        return ['error' => 'Upload file is not readable.'];
    }

    if (filesize($tmpPath) > $maxBytes) {
        return ['error' => 'Uploaded file exceeds allowed size.'];
    }

    if (!bfis_upload_matches_allowed_mime($tmpPath, $allowedMime)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    $credentials = bfis_cloudinary_credentials();
    $timestamp = (string) time();
    $paramsToSign = [
        'folder' => $folder,
        'timestamp' => $timestamp,
    ];
    $signature = bfis_cloudinary_sign_params($paramsToSign, $credentials['api_secret']);
    $mimeType = bfis_detect_mime_type($tmpPath) ?: 'application/octet-stream';
    $endpoint = sprintf(
        'https://api.cloudinary.com/v1_1/%s/%s/upload',
        $credentials['cloud_name'],
        $resourceType
    );

    $postFields = [
        'file' => class_exists('CURLFile')
            ? new CURLFile($tmpPath, $mimeType, basename($tmpPath))
            : '@' . $tmpPath,
        'api_key' => $credentials['api_key'],
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder,
    ];

    $ch = curl_init($endpoint);
    $curlOptions = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    curl_setopt_array($ch, $curlOptions);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($response === false && $curlError !== '') {
        error_log('Cloudinary HTTP upload cURL error: ' . $curlError);

        if (stripos($curlError, 'SSL') !== false || stripos($curlError, 'certificate') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
        }
    }

    curl_close($ch);

    if ($response === false) {
        if ($curlError !== '') {
            error_log('Cloudinary HTTP upload cURL error after retry: ' . $curlError);
        }

        return ['error' => 'Unable to upload file right now. Please try again.'];
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        error_log('Cloudinary HTTP upload invalid response: ' . $response);

        return ['error' => 'Unable to upload file right now. Please try again.'];
    }

    if (!empty($data['error']['message'])) {
        error_log('Cloudinary HTTP upload API error: ' . (string) $data['error']['message']);

        return ['error' => 'Unable to upload profile photo to Cloudinary. Please try again.'];
    }

    if ($httpCode >= 400 || empty($data['secure_url'])) {
        error_log('Cloudinary HTTP upload failed (' . $httpCode . '): ' . $response);

        return ['error' => 'Unable to upload file right now. Please try again.'];
    }

    return [
        'url' => (string) $data['secure_url'],
        'public_id' => isset($data['public_id']) ? (string) $data['public_id'] : null,
    ];
}

/**
 * @param string $tmpPath
 * @param string $folder
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @param string $resourceType
 * @return array{url?: string, public_id?: string, error?: string}
 */
function bfis_cloudinary_upload_path(
    string $tmpPath,
    string $folder,
    array $allowedMime,
    int $maxBytes,
    string $resourceType = 'image'
): array {
    if (!is_readable($tmpPath)) {
        return ['error' => 'Upload file is not readable.'];
    }

    if (filesize($tmpPath) > $maxBytes) {
        return ['error' => 'Uploaded file exceeds allowed size.'];
    }

    if (!bfis_upload_matches_allowed_mime($tmpPath, $allowedMime)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    if (bfis_cloudinary_configure()) {
        try {
            $options = ['folder' => $folder];
            if ($resourceType !== 'image') {
                $options['resource_type'] = $resourceType;
            }

            $result = (new UploadApi())->upload($tmpPath, $options);

            if (!empty($result['secure_url'])) {
                return [
                    'url' => (string) $result['secure_url'],
                    'public_id' => isset($result['public_id']) ? (string) $result['public_id'] : null,
                ];
            }
        } catch (Throwable $e) {
            error_log('Cloudinary SDK upload failed: ' . $e->getMessage());
        }
    }

    return bfis_cloudinary_upload_http(
        $tmpPath,
        $folder,
        $allowedMime,
        $maxBytes,
        $resourceType
    );
}

/**
 * @param array<string, mixed> $file
 * @param int $userId
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @return array{path?: string, error?: string}
 */
function bfis_stage_profile_upload(array $file, int $userId, array $allowedMime, int $maxBytes): array
{
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['error' => 'Profile photo is too large. Maximum allowed size is 5 MB.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed. Please try again.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');

    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || !is_readable($tmpPath)) {
        return ['error' => 'Upload file is not readable.'];
    }

    if (filesize($tmpPath) > $maxBytes) {
        return ['error' => 'Uploaded file exceeds allowed size.'];
    }

    if (!bfis_upload_matches_allowed_mime($tmpPath, $allowedMime)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    $tempDirectory = bfis_reg_temp_directory();

    if ($tempDirectory === null) {
        return ['error' => 'Server upload folder is not writable. Please contact support.'];
    }

    $mimeType = bfis_detect_mime_type($tmpPath, (string) ($file['type'] ?? ''));
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $extension = $extensions[$mimeType] ?? strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION) ?: 'jpg');
    $stagedPath = $tempDirectory . DIRECTORY_SEPARATOR . 'profile_stage_' . $userId . '_' . bfis_random_hex(8) . '.' . $extension;

    if (!@copy($tmpPath, $stagedPath)) {
        return ['error' => 'Unable to save uploaded file. Please try again.'];
    }

    return ['path' => $stagedPath];
}

/**
 * @param string $stagedPath
 * @param int $userId
 * @return string|null
 */
function bfis_save_account_profile_local(string $stagedPath, int $userId): ?string
{
    if (!is_readable($stagedPath)) {
        return null;
    }

    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';

    if (!is_dir($uploadsDir) && !@mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        return null;
    }

    $extension = strtolower(pathinfo($stagedPath, PATHINFO_EXTENSION));

    if ($extension === '') {
        $extension = 'jpg';
    }

    $filename = 'profile_' . $userId . '_' . bfis_random_hex(8) . '.' . $extension;
    $destination = $uploadsDir . DIRECTORY_SEPARATOR . $filename;

    if (!@copy($stagedPath, $destination)) {
        return null;
    }

    return 'uploads/profiles/' . $filename;
}

/**
 * @param string|null $stagedPath
 */
function bfis_delete_staged_profile_upload(?string $stagedPath): void
{
    if ($stagedPath === null || $stagedPath === '' || !is_file($stagedPath)) {
        return;
    }

    @unlink($stagedPath);
}

/**
 * @param string $fieldName
 * @param int $userId
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @return array{url?: string, public_id?: string, path?: string, error?: string}
 */
function bfis_account_profile_upload_request(
    string $fieldName,
    int $userId,
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

    $staged = bfis_stage_profile_upload($file, $userId, $allowedMime, $maxBytes);

    if (isset($staged['error'])) {
        return ['error' => $staged['error']];
    }

    if (empty($staged['path'])) {
        return ['error' => 'Unable to save uploaded file. Please try again.'];
    }

    $stagedPath = $staged['path'];

    $cloudResult = bfis_cloudinary_upload_http(
        $stagedPath,
        BFIS_CLOUDINARY_FOLDER_PROFILES,
        $allowedMime,
        $maxBytes
    );

    if (!isset($cloudResult['error']) && !empty($cloudResult['url'])) {
        bfis_delete_staged_profile_upload($stagedPath);

        return [
            'url' => $cloudResult['url'],
            'public_id' => $cloudResult['public_id'] ?? null,
            'path' => $cloudResult['url'],
        ];
    }

    error_log(
        'Account profile Cloudinary upload failed, using local fallback: '
        . ($cloudResult['error'] ?? 'unknown error')
    );

    $localPath = bfis_save_account_profile_local($stagedPath, $userId);
    bfis_delete_staged_profile_upload($stagedPath);

    if ($localPath === null) {
        return ['error' => $cloudResult['error'] ?? 'Unable to save profile photo on the server.'];
    }

    return [
        'url' => $localPath,
        'path' => $localPath,
    ];
}

/**
 * @param string $fieldName
 * @param string $folder
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @param string $resourceType
 * @return array{url?: string, public_id?: string, path?: string, error?: string}
 */
function bfis_cloudinary_upload_request(
    string $fieldName,
    string $folder,
    array $allowedMime,
    int $maxBytes,
    string $resourceType = 'image'
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

    $result = bfis_cloudinary_upload_path(
        $file['tmp_name'],
        $folder,
        $allowedMime,
        $maxBytes,
        $resourceType
    );

    if (isset($result['url'])) {
        $result['path'] = $result['url'];
    }

    return $result;
}

/**
 * @param string|null $storedPath
 * @param string $cloudFolder
 * @param string $localPrefix
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @param string $resourceType
 * @return array{url?: string|null, temp_path?: string, error?: string}
 */
function bfis_reg_finalize_single_media(
    ?string $storedPath,
    string $cloudFolder,
    string $localPrefix,
    array $allowedMime,
    int $maxBytes,
    string $resourceType = 'image'
): array {
    if ($storedPath === null || trim($storedPath) === '') {
        return ['url' => null];
    }

    $storedPath = trim($storedPath);

    if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
        return ['url' => $storedPath];
    }

    $diskPath = bfis_reg_resolve_disk_path($storedPath);

    if ($diskPath === null || !is_readable($diskPath)) {
        return ['error' => 'Uploaded file is missing. Please go back to step 1 and upload again.'];
    }

    if (bfis_cloudinary_is_available()) {
        $upload = bfis_cloudinary_upload_http(
            $diskPath,
            $cloudFolder,
            $allowedMime,
            $maxBytes,
            $resourceType
        );

        if (!isset($upload['error']) && !empty($upload['url'])) {
            return [
                'url' => $upload['url'],
                'temp_path' => $storedPath,
            ];
        }

        error_log(
            'Registration Cloudinary upload failed, using local fallback: '
            . ($upload['error'] ?? 'unknown error')
        );
    } else {
        error_log('Cloudinary unavailable during registration; using local uploads fallback.');
    }

    $localPath = bfis_reg_promote_temp_file($diskPath, $localPrefix);

    if ($localPath === null) {
        return ['error' => 'Unable to save registration files on the server. Please try again.'];
    }

    return [
        'url' => $localPath,
        'temp_path' => $storedPath,
    ];
}

/**
 * Upload registration temp files to Cloudinary when possible, otherwise keep them in uploads/.
 *
 * @param string|null $profileStoredPath
 * @param string|null $validIdStoredPath
 * @return array{
 *     profile_picture?: string|null,
 *     valid_id_image?: string|null,
 *     temp_paths?: array<int, string>,
 *     error?: string
 * }
 */
function bfis_reg_finalize_cloudinary_uploads(?string $profileStoredPath, ?string $validIdStoredPath): array
{
    $tempPaths = [];

    $profileResult = bfis_reg_finalize_single_media(
        $profileStoredPath,
        BFIS_CLOUDINARY_FOLDER_PROFILES,
        'reg_profile',
        ['image/jpeg', 'image/png'],
        5 * 1024 * 1024
    );

    if (isset($profileResult['error'])) {
        return ['error' => $profileResult['error']];
    }

    if (!empty($profileResult['temp_path'])) {
        $tempPaths[] = $profileResult['temp_path'];
    }

    $validIdResult = bfis_reg_finalize_single_media(
        $validIdStoredPath,
        BFIS_CLOUDINARY_FOLDER_VALID_IDS,
        'reg_valid_id',
        ['image/jpeg', 'image/png', 'image/webp'],
        10 * 1024 * 1024
    );

    if (isset($validIdResult['error'])) {
        return ['error' => $validIdResult['error']];
    }

    if (!empty($validIdResult['temp_path'])) {
        $tempPaths[] = $validIdResult['temp_path'];
    }

    return [
        'profile_picture' => $profileResult['url'] ?? null,
        'valid_id_image' => $validIdResult['url'] ?? null,
        'temp_paths' => $tempPaths,
    ];
}
