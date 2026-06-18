<?php

/**
 * Media URL helpers for Cloudinary and legacy local paths.
 */

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

        if (str_starts_with($projectRoot, $documentRoot)) {
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

    if (str_starts_with($relative, 'uploads/')) {
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
