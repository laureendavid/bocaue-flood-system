<?php

/**
 * backend/update_account_settings.php
 * Shared profile and password update handler for all roles.
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

/**
 * @param int $userId
 * @return array{url?: string, public_id?: string, error?: string}
 */
function handleProfileUpload(int $userId): array
{
    return bfis_cloudinary_upload_request(
        'profile_picture',
        BFIS_CLOUDINARY_FOLDER_PROFILES,
        ['image/jpeg', 'image/png'],
        5 * 1024 * 1024
    );
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

if ($action === 'profile') {
    $firstName = strtoupper(trim($_POST['first_name'] ?? ''));
    $lastName = strtoupper(trim($_POST['last_name'] ?? ''));
    $suffix = strtoupper(trim($_POST['suffix'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');

    if ($firstName === '' || $lastName === '') {
        redirectWithFlash($redirectBase, 'error', 'First name and last name are required.');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid email address.');
    }

    if ($phone === '' || !preg_match('/^09\d{9}$/', $phone)) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid PH phone number (e.g. 09XXXXXXXXX).');
    }

    if ($dateOfBirth === '') {
        redirectWithFlash($redirectBase, 'error', 'Date of birth is required.');
    }

    $dobDate = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
    if (!$dobDate || $dobDate->format('Y-m-d') !== $dateOfBirth) {
        redirectWithFlash($redirectBase, 'error', 'Please enter a valid date of birth.');
    }

    $existingUser = fetchAccountUser($conn, $userId);
    if (!$existingUser) {
        redirectWithFlash($redirectBase, 'error', 'Account not found.');
    }

    $emailCheck = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
    if (!$emailCheck) {
        redirectWithFlash($redirectBase, 'error', 'Unable to update profile right now.');
    }

    $emailCheck->bind_param('si', $email, $userId);
    $emailCheck->execute();
    $emailResult = $emailCheck->get_result();
    $emailTaken = $emailResult && $emailResult->fetch_assoc();
    $emailCheck->close();

    if ($emailTaken) {
        redirectWithFlash($redirectBase, 'error', 'That email address is already in use.');
    }

    $profilePicturePath = $existingUser['profile_picture'] ?? null;
    $uploadResult = handleProfileUpload($userId);

    if (isset($uploadResult['error'])) {
        redirectWithFlash($redirectBase, 'error', $uploadResult['error']);
    }

    if (!empty($uploadResult['url'])) {
        $profilePicturePath = $uploadResult['url'];
    }

    $fullName = trim($firstName . ' ' . $lastName . ($suffix !== '' ? ' ' . $suffix : ''));

    $sql = 'UPDATE users
            SET first_name = ?, last_name = ?, suffix = ?, full_name = ?, email = ?,
                phone = ?, date_of_birth = ?, profile_picture = ?
            WHERE user_id = ?';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        redirectWithFlash($redirectBase, 'error', 'Unable to update profile right now.');
    }

    $stmt->bind_param(
        'ssssssssi',
        $firstName,
        $lastName,
        $suffix,
        $fullName,
        $email,
        $phone,
        $dateOfBirth,
        $profilePicturePath,
        $userId
    );

    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    if (!$ok) {
        redirectWithFlash($redirectBase, 'error', 'Unable to save profile changes.');
    }

    $_SESSION['full_name'] = $fullName;
    redirectWithFlash($redirectBase, 'success', 'Your profile has been updated successfully.');
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
