<?php

/**
 * backend/update_account_settings.php
 * Shared profile and password update handler for all roles.
 * Supports partial profile updates — only changed fields are written to the database.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/account_settings_data.php';
require_once __DIR__ . '/../includes/cloudinary_upload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$allowedRoles = ['Resident', 'LGU', 'Rescuer'];
$redirectBase = bfis_account_settings_redirect_url();

/**
 * @param string $base
 * @param string $type
 * @param string $message
 */
function redirectWithFlash(string $base, string $type, string $message): void
{
    $_SESSION['account_settings_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
    header('Location: ' . $base);
    exit;
}

/**
 * @param mixed $value
 */
function bfis_normalize_account_date($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
        return $matches[1];
    }

    return $value;
}

/**
 * Resolve a submitted profile field without clobbering existing data with empty POST values.
 *
 * @param string|null $posted
 * @param mixed $existing
 * @param bool $allowEmptyUpdate When true, an empty POST value is saved (optional fields only).
 */
function bfis_resolve_profile_field(?string $posted, $existing, bool $allowEmptyUpdate = false): string
{
    $existingValue = trim((string) $existing);
    $postedValue = $posted !== null ? trim($posted) : null;

    if ($postedValue === null) {
        return $existingValue;
    }

    if ($postedValue === '' && !$allowEmptyUpdate && $existingValue !== '') {
        return $existingValue;
    }

    return $postedValue;
}

/**
 * @param mysqli $conn
 * @param int $userId
 * @return array<string, mixed>|null
 */
function fetchAccountUser(mysqli $conn, int $userId): ?array
{
    $sql = "SELECT user_id, first_name, last_name, suffix, full_name, email, phone,
                   date_of_birth, profile_picture, current_address, latitude, longitude
            FROM users
            WHERE user_id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

/**
 * @param string $email
 * @param int $userId
 */
function bfis_email_is_taken(mysqli $conn, string $email, int $userId): bool
{
    $emailCheck = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
    if (!$emailCheck) {
        return true;
    }

    $emailCheck->bind_param('si', $email, $userId);
    $emailCheck->execute();
    $emailResult = $emailCheck->get_result();
    $emailTaken = $emailResult && $emailResult->fetch_assoc();
    $emailCheck->close();

    return (bool) $emailTaken;
}

/**
 * @param int $userId
 * @return array{url?: string, public_id?: string, error?: string}
 */
function handleProfileUpload(int $userId): array
{
    return bfis_account_profile_upload_request(
        'profile_picture',
        $userId,
        ['image/jpeg', 'image/png'],
        5 * 1024 * 1024
    );
}

/**
 * @param array<string, mixed> $existingUser
 * @param array<string, string> $resolved
 * @return array<string, string>
 */
function bfis_build_profile_changes(array $existingUser, array $resolved): array
{
    $changes = [];

    $comparisons = [
        'first_name' => static fn (string $value): string => strtoupper($value),
        'last_name' => static fn (string $value): string => strtoupper($value),
        'suffix' => static fn (string $value): string => strtoupper($value),
        'email' => static fn (string $value): string => strtolower($value),
        'phone' => static fn (string $value): string => $value,
        'date_of_birth' => static fn (string $value): string => bfis_normalize_account_date($value),
    ];

    foreach ($comparisons as $field => $normalize) {
        $current = $normalize(trim((string) ($existingUser[$field] ?? '')));
        $next = $normalize($resolved[$field]);

        if ($field === 'date_of_birth') {
            $current = bfis_normalize_account_date($current);
            $next = bfis_normalize_account_date($next);
        }

        if ($next !== $current) {
            $changes[$field] = $resolved[$field];
        }
    }

    return $changes;
}

/**
 * @param array<int, mixed> $params
 */
function bfis_stmt_bind_params(mysqli_stmt $stmt, string $types, array $params): bool
{
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }

    return call_user_func_array([$stmt, 'bind_param'], $bind);
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
    header('Location: ../main/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectBase);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = trim($_POST['form_action'] ?? '');

if ($action === 'profile') {
    try {
        $existingUser = fetchAccountUser($conn, $userId);
        if (!$existingUser) {
            redirectWithFlash($redirectBase, 'error', 'Account not found.');
        }

        $resolved = [
        'first_name' => strtoupper(bfis_resolve_profile_field(
            array_key_exists('first_name', $_POST) ? (string) $_POST['first_name'] : null,
            $existingUser['first_name'] ?? ''
        )),
        'last_name' => strtoupper(bfis_resolve_profile_field(
            array_key_exists('last_name', $_POST) ? (string) $_POST['last_name'] : null,
            $existingUser['last_name'] ?? ''
        )),
        'suffix' => strtoupper(bfis_resolve_profile_field(
            array_key_exists('suffix', $_POST) ? (string) $_POST['suffix'] : null,
            $existingUser['suffix'] ?? '',
            true
        )),
        'email' => strtolower(bfis_resolve_profile_field(
            array_key_exists('email', $_POST) ? (string) $_POST['email'] : null,
            $existingUser['email'] ?? ''
        )),
        'phone' => bfis_resolve_profile_field(
            array_key_exists('phone', $_POST) ? (string) $_POST['phone'] : null,
            $existingUser['phone'] ?? ''
        ),
        'date_of_birth' => bfis_normalize_account_date(bfis_resolve_profile_field(
            array_key_exists('date_of_birth', $_POST) ? (string) $_POST['date_of_birth'] : null,
            $existingUser['date_of_birth'] ?? ''
        )),
    ];

    if ($resolved['first_name'] === '') {
        redirectWithFlash($redirectBase, 'error', 'First name is required.');
    }

    if ($resolved['last_name'] === '') {
        redirectWithFlash($redirectBase, 'error', 'Last name is required.');
    }

    if ($resolved['email'] === '' || !filter_var($resolved['email'], FILTER_VALIDATE_EMAIL)) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid email address.');
    }

    if ($resolved['phone'] === '' || !preg_match('/^09\d{9}$/', $resolved['phone'])) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid PH phone number (e.g. 09XXXXXXXXX).');
    }

    if ($resolved['date_of_birth'] === '') {
        redirectWithFlash($redirectBase, 'error', 'Date of birth is required.');
    }

    $dobDate = DateTime::createFromFormat('Y-m-d', $resolved['date_of_birth']);
    if (!$dobDate || $dobDate->format('Y-m-d') !== $resolved['date_of_birth']) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid date of birth.');
    }

    $changes = bfis_build_profile_changes($existingUser, $resolved);

    if (isset($changes['email']) && bfis_email_is_taken($conn, $changes['email'], $userId)) {
        redirectWithFlash($redirectBase, 'error', 'That email address is already in use.');
    }

    $profilePicturePath = trim((string) ($existingUser['profile_picture'] ?? ''));
    $profilePictureChanged = false;
    $uploadResult = handleProfileUpload($userId);

    if (isset($uploadResult['error'])) {
        redirectWithFlash($redirectBase, 'error', $uploadResult['error']);
    }

    if (!empty($uploadResult['url'])) {
        $profilePicturePath = $uploadResult['url'];
        $profilePictureChanged = $profilePicturePath !== trim((string) ($existingUser['profile_picture'] ?? ''));
    }

    $nameFieldsChanged = isset($changes['first_name']) || isset($changes['last_name']) || isset($changes['suffix']);
    $fullName = trim(
        $resolved['first_name']
        . ' '
        . $resolved['last_name']
        . ($resolved['suffix'] !== '' ? ' ' . $resolved['suffix'] : '')
    );

    if ($nameFieldsChanged) {
        $changes['full_name'] = $fullName;
    }

    if ($profilePictureChanged) {
        $changes['profile_picture'] = $profilePicturePath;
    }

    if ($changes === []) {
        redirectWithFlash($redirectBase, 'info', 'No changes were made to your profile.');
    }

    $allowedColumns = [
        'first_name',
        'last_name',
        'suffix',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'profile_picture',
    ];

    $setParts = [];
    $types = '';
    $params = [];

    foreach ($changes as $column => $value) {
        if (!in_array($column, $allowedColumns, true)) {
            continue;
        }
        $setParts[] = $column . ' = ?';
        $types .= 's';
        $params[] = $value;
    }

    if ($setParts === []) {
        redirectWithFlash($redirectBase, 'info', 'No changes were made to your profile.');
    }

    $types .= 'i';
    $params[] = $userId;

    $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE user_id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        redirectWithFlash($redirectBase, 'error', 'Unable to update profile right now.');
    }

    if (!bfis_stmt_bind_params($stmt, $types, $params)) {
        redirectWithFlash($redirectBase, 'error', 'Unable to update profile right now.');
    }

    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    if (!$ok) {
        redirectWithFlash($redirectBase, 'error', 'Unable to save profile changes.');
    }

    if ($nameFieldsChanged) {
        $_SESSION['full_name'] = $fullName;
    }

    $successMessage = isset($changes['profile_picture'])
        ? 'Your profile photo has been updated successfully.'
        : 'Your profile has been updated successfully.';

    redirectWithFlash($redirectBase, 'success', $successMessage);
    } catch (Throwable $e) {
        error_log('Account profile update failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        redirectWithFlash($redirectBase, 'error', 'Unable to save profile changes. Please try again.');
    }
}

if ($action === 'password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        redirectWithFlash($redirectBase, 'error', 'Please fill in all password fields.');
    }

    if (
        strlen($newPassword) < 12
        || !preg_match('/[A-Za-z]/', $newPassword)
        || !preg_match('/[0-9]/', $newPassword)
    ) {
        redirectWithFlash(
            $redirectBase,
            'error',
            'New password must be at least 12 characters and include letters and numbers.'
        );
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithFlash($redirectBase, 'error', 'New password and confirmation do not match.');
    }

    $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        redirectWithFlash($redirectBase, 'error', 'Unable to change password right now.');
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        $conn->close();
        redirectWithFlash($redirectBase, 'error', 'Current password is incorrect.');
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $update = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    if (!$update) {
        $conn->close();
        redirectWithFlash($redirectBase, 'error', 'Unable to change password right now.');
    }

    $update->bind_param('si', $hash, $userId);
    $ok = $update->execute();
    $update->close();
    $conn->close();

    if (!$ok) {
        redirectWithFlash($redirectBase, 'error', 'Unable to save your new password.');
    }

    redirectWithFlash($redirectBase, 'success', 'Your password has been changed successfully.');
}

redirectWithFlash($redirectBase, 'error', 'Invalid request.');
