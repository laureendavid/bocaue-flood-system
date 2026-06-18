<?php

/**
 * Shared Cloudinary upload helpers.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/cloudinary.php';
require_once __DIR__ . '/../config/cloudinary_folders.php';

use Cloudinary\Api\Upload\UploadApi;

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

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    if (!in_array($mimeType, $allowedMime, true)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    try {
        $options = ['folder' => $folder];
        if ($resourceType !== 'image') {
            $options['resource_type'] = $resourceType;
        }

        $result = (new UploadApi())->upload($tmpPath, $options);

        if (empty($result['secure_url'])) {
            return ['error' => 'Cloudinary upload did not return a secure URL.'];
        }

        return [
            'url' => (string) $result['secure_url'],
            'public_id' => isset($result['public_id']) ? (string) $result['public_id'] : null,
        ];
    } catch (Throwable $e) {
        error_log('Cloudinary upload failed: ' . $e->getMessage());

        return ['error' => 'Unable to upload file right now. Please try again.'];
    }
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
