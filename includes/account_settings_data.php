<?php

/**
 * Shared account settings data loader.
 */

require_once __DIR__ . '/../config/uploads.php';

/**
 * @return array<string, mixed>|null
 */
function bfis_load_account_settings_profile(mysqli $conn, int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT u.first_name,
               u.last_name,
               u.suffix,
               u.full_name,
               u.email,
               u.phone,
               u.date_of_birth,
               u.profile_picture,
               u.current_address,
               u.latitude,
               u.longitude,
               b.barangay_name,
               b.municipality,
               b.province
        FROM users u
        LEFT JOIN barangays b ON b.barangay_id = u.barangay_id
        WHERE u.user_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$profile) {
        return null;
    }

    $defaultAvatarUrl = 'https://placehold.co/160x160/e2e8f0/475569?text=U';
    $storedProfilePath = trim((string) ($profile['profile_picture'] ?? ''));
    $hasProfilePhoto = bfis_has_stored_media($storedProfilePath);

    $barangayLabel = trim(
        ($profile['barangay_name'] ?? '')
        . ', '
        . ($profile['municipality'] ?? 'Bocaue')
        . ', '
        . ($profile['province'] ?? 'Bulacan')
    );
    $locationAddress = trim((string) ($profile['current_address'] ?? ''));
    $lat = $profile['latitude'] ?? '';
    $lng = $profile['longitude'] ?? '';
    $locationCoords = '';

    if ($lat !== '' && $lng !== '') {
        $locationCoords = number_format((float) $lat, 6) . ', ' . number_format((float) $lng, 6);
    }

    $dobValue = $profile['date_of_birth'] ?? '';
    if ($dobValue !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $dobValue)) {
        $dobValue = substr($dobValue, 0, 10);
    }

    $flash = $_SESSION['account_settings_flash'] ?? null;
    unset($_SESSION['account_settings_flash']);

    return [
        'profile' => $profile,
        'defaultAvatarUrl' => $defaultAvatarUrl,
        'avatarUrl' => bfis_profile_photo_url($storedProfilePath, $defaultAvatarUrl),
        'hasProfilePhoto' => $hasProfilePhoto,
        'barangayLabel' => $barangayLabel,
        'locationAddress' => $locationAddress,
        'locationCoords' => $locationCoords,
        'dobValue' => $dobValue,
        'flash' => $flash,
    ];
}

/**
 * @return string
 */
function bfis_account_settings_redirect_url(): string
{
    return match ($_SESSION['role'] ?? '') {
        'LGU' => '../lgu/account-settings.php',
        'Rescuer' => '../rescuer/account-settings.php',
        'Resident' => '../resident/main.php?page=account-settings',
        default => '../main/login.php',
    };
}
