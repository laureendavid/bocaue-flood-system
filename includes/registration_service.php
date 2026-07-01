<?php

/**
 * Multi-step resident registration helpers (session staging, email verification, completion).
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/registration.php';
require_once __DIR__ . '/../config/uploads.php';
require_once __DIR__ . '/cloudinary_upload.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * @return string
 */
function bfis_registration_generate_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * @return array<int, string>
 */
function bfis_email_verification_columns(PDO $pdo): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    bfis_ensure_email_verification_table($pdo);

    $columns = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM email_verifications');

    foreach ($stmt as $row) {
        $columns[] = (string) $row['Field'];
    }

    return $columns;
}

/**
 * @return void
 */
function bfis_ensure_email_verification_table(PDO $pdo): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS email_verifications (
                verification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL DEFAULT 0,
                email VARCHAR(255) NOT NULL DEFAULT \'\',
                token VARCHAR(128) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (verification_id),
                UNIQUE KEY uniq_email_verifications_token (token),
                KEY idx_email_verifications_email (email),
                KEY idx_email_verifications_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (Throwable $throwable) {
        error_log('email_verifications table ensure failed: ' . $throwable->getMessage());
    }

    $ensured = true;
}

/**
 * @return array{
 *     id: string,
 *     token: string,
 *     expires_at: string,
 *     email: string,
 *     user_id: string,
 *     has_email: bool,
 *     has_user_id: bool
 * }
 */
function bfis_email_verification_schema(PDO $pdo): array
{
    $columns = bfis_email_verification_columns($pdo);

    $resolve = static function (array $candidates) use ($columns): string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return '';
    };

    return [
        'id' => bfis_email_verification_id_column($columns),
        'token' => $resolve(['token', 'verification_token', 'verify_token']),
        'expires_at' => $resolve(['expires_at', 'expiry', 'expires_on', 'expired_at']),
        'email' => $resolve(['email', 'user_email']),
        'user_id' => $resolve(['user_id']),
        'has_email' => in_array('email', $columns, true) || in_array('user_email', $columns, true),
        'has_user_id' => in_array('user_id', $columns, true),
    ];
}

/**
 * @param array<string, mixed> $schema
 */
function bfis_registration_pending_user_id(array $schema): int
{
    return $schema['has_user_id'] ? 0 : 0;
}

/**
 * @param array<int, string> $columns
 */
function bfis_email_verification_id_column(array $columns): string
{
    if (in_array('id', $columns, true)) {
        return 'id';
    }

    if (in_array('verification_id', $columns, true)) {
        return 'verification_id';
    }

    return '';
}

/**
 * @return string
 */
function bfis_registration_verification_url(string $token): string
{
    return bfis_app_base_url() . '/main/verify.php?token=' . rawurlencode($token);
}

/**
 * @return bool
 */
function bfis_is_cloudinary_media_url(?string $url): bool
{
    return is_string($url) && $url !== '' && strpos($url, 'res.cloudinary.com') !== false;
}

/**
 * @param string $fieldName
 * @param string $cloudFolder
 * @param array<int, string> $allowedMime
 * @param int $maxBytes
 * @param string $label
 * @return array{url?: string, public_id?: string|null, error?: string}
 */
function bfis_reg_image_upload_request(
    string $fieldName,
    string $cloudFolder,
    array $allowedMime,
    int $maxBytes,
    string $label = 'File'
): array {
    if (empty($_FILES[$fieldName]['name'])) {
        return [];
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [];
    }

    $staged = bfis_stage_profile_upload($file, 0, $allowedMime, $maxBytes);

    if (isset($staged['error'])) {
        return ['error' => $staged['error']];
    }

    if (empty($staged['path'])) {
        return ['error' => 'Unable to save uploaded file. Please try again.'];
    }

    $stagedPath = $staged['path'];

    $cloudResult = bfis_cloudinary_upload_http(
        $stagedPath,
        $cloudFolder,
        $allowedMime,
        $maxBytes
    );

    bfis_delete_staged_profile_upload($stagedPath);

    if (!isset($cloudResult['error']) && !empty($cloudResult['url'])) {
        return [
            'url' => $cloudResult['url'],
            'public_id' => $cloudResult['public_id'] ?? null,
        ];
    }

    return [
        'error' => $cloudResult['error'] ?? 'Unable to upload ' . $label . ' to Cloudinary. Please try again.',
    ];
}

/**
 * @return bool
 */
function bfis_registration_has_staged_file(?string $storedPath): bool
{
    if ($storedPath === null || trim($storedPath) === '') {
        return false;
    }

    if (filter_var(trim($storedPath), FILTER_VALIDATE_URL)) {
        return true;
    }

    $diskPath = bfis_reg_resolve_disk_path($storedPath);

    return $diskPath !== null && is_readable($diskPath);
}

/**
 * @return array{error?: string}
 */
function bfis_registration_validate_upload_results(
    ?string $profileStoredPath,
    ?string $validIdStoredPath,
    array $uploadResult
): array {
    if (isset($uploadResult['error'])) {
        return ['error' => (string) $uploadResult['error']];
    }

    $checks = [
        'profile_picture' => $profileStoredPath,
        'valid_id_image' => $validIdStoredPath,
    ];

    foreach ($checks as $resultKey => $stagedPath) {
        if (!bfis_registration_has_staged_file($stagedPath)) {
            continue;
        }

        if (bfis_is_cloudinary_media_url($stagedPath)) {
            continue;
        }

        $savedPath = $uploadResult[$resultKey] ?? null;

        if ($savedPath === null || trim((string) $savedPath) === '') {
            return ['error' => 'Unable to upload registration files. Please go back to step 1 and try again.'];
        }

        if (bfis_cloudinary_is_available() && !bfis_is_cloudinary_media_url((string) $savedPath)) {
            return ['error' => 'Unable to upload registration files to Cloudinary. Please try again.'];
        }
    }

    return [];
}

/**
 * @return array{token?: string, error?: string}
 */
function bfis_registration_store_verification_token(PDO $pdo, string $email): array
{
    $email = trim($email);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid email address for verification.'];
    }

    $schema = bfis_email_verification_schema($pdo);

    if ($schema['id'] === '' || $schema['token'] === '' || $schema['expires_at'] === '') {
        return ['error' => 'Email verification is not configured on the server.'];
    }

    $token = bfis_registration_generate_token();
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);
    $pendingUserId = bfis_registration_pending_user_id($schema);

    try {
        $pdo->beginTransaction();

        if ($schema['has_email']) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM email_verifications WHERE ' . $schema['email'] . ' = ?'
            );
            $deleteStmt->execute([$email]);
        } elseif ($schema['has_user_id']) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM email_verifications WHERE ' . $schema['user_id'] . ' = ?'
            );
            $deleteStmt->execute([$pendingUserId]);
        }

        $fields = [$schema['token'], $schema['expires_at']];
        $placeholders = ['?', '?'];
        $values = [$token, $expiresAt];

        if ($schema['has_email']) {
            $fields[] = $schema['email'];
            $placeholders[] = '?';
            $values[] = $email;
        }

        if ($schema['has_user_id']) {
            $fields[] = $schema['user_id'];
            $placeholders[] = '?';
            $values[] = $pendingUserId;
        }

        $sql = 'INSERT INTO email_verifications (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $insertStmt = $pdo->prepare($sql);
        $insertStmt->execute($values);

        $verificationId = (int) $pdo->lastInsertId();

        $pdo->commit();

        $_SESSION['reg_verification_email'] = $email;
        $_SESSION['reg_verification_id'] = $verificationId > 0 ? $verificationId : null;
        $_SESSION['reg_email_verified'] = false;

        return ['token' => $token];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Registration verification token store failed: ' . $throwable->getMessage());

        return ['error' => 'Unable to store verification token. Please try again.'];
    }
}

/**
 * @return array{success?: bool, error?: string}
 */
function bfis_registration_send_verification_email(string $email, string $fullName, string $token): array
{
    $mailConfig = bfis_mail_config();

    if ($mailConfig['username'] === '' || $mailConfig['password'] === '' || $mailConfig['from_email'] === '') {
        return ['error' => 'Email service is not configured. Please contact support.'];
    }

    $verifyLink = bfis_registration_verification_url($token);
    $recipientName = $fullName !== '' ? $fullName : $email;
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');
    $year = date('Y');

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailConfig['port'];
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addReplyTo($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($email, $recipientName);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Priority = 3;
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
        $mail->Subject = 'Activate your Bocaue Flood Info System account';
        $mail->AltBody = "Hi {$recipientName},\r\n\r\n"
            . "Thank you for registering with the Bocaue Community Flood Information System.\r\n\r\n"
            . "Please verify your email address by visiting the link below:\r\n"
            . "{$verifyLink}\r\n\r\n"
            . "This link will expire in 24 hours.\r\n\r\n"
            . "If you did not create this account, you can safely ignore this email.\r\n\r\n"
            . '— Bocaue Community Flood Information System';
        $mail->isHTML(true);
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;">
        <tr>
          <td style="background:#1a3a5c;padding:28px 40px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:0.5px;">
              Bocaue Community Flood Information System
            </h1>
          </td>
        </tr>
        <tr>
          <td style="padding:40px 40px 30px;color:#333333;">
            <p style="margin:0 0 16px;font-size:16px;">Hi <strong>{$safeName}</strong>,</p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#555555;">
              Thank you for registering. Please confirm your email address to activate your account
              and start receiving flood alerts for your barangay.
            </p>
            <p style="margin:24px 0;text-align:center;">
              <a href="{$safeLink}"
                 style="display:inline-block;background:#1a3a5c;color:#ffffff;text-decoration:none;
                        padding:14px 32px;border-radius:6px;font-size:15px;font-weight:600;">
                Verify My Email Address
              </a>
            </p>
            <p style="margin:0 0 8px;font-size:13px;color:#888888;">
              Or copy and paste this link into your browser:
            </p>
            <p style="margin:0 0 24px;font-size:12px;color:#aaaaaa;word-break:break-all;">
              {$safeLink}
            </p>
            <p style="margin:0;font-size:13px;color:#999999;">
              This link expires in <strong>24 hours</strong>. If you did not create this account,
              you can safely ignore this email.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f4f6f9;padding:20px 40px;text-align:center;border-top:1px solid #e8eaed;">
            <p style="margin:0;font-size:12px;color:#aaaaaa;">
              &copy; {$year} Bocaue Community Flood Information System &bull; Bocaue, Bulacan
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        $mail->send();

        return ['success' => true];
    } catch (MailerException $exception) {
        error_log('Registration verification email failed: ' . $mail->ErrorInfo);

        return ['error' => 'Verification email could not be sent. Please try again.'];
    }
}

/**
 * @return bool
 */
function bfis_registration_is_email_verified(PDO $pdo, string $email): bool
{
    $email = trim($email);

    if ($email === '') {
        return false;
    }

    if (
        !empty($_SESSION['reg_email_verified'])
        && trim((string) ($_SESSION['reg_verification_email'] ?? $_SESSION['reg_email'] ?? '')) === $email
    ) {
        return true;
    }

    try {
        $userStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND is_verified = 1 LIMIT 1');
        $userStmt->execute([$email]);

        if ($userStmt->fetch()) {
            return true;
        }
    } catch (Throwable $throwable) {
        error_log('Registration verified user lookup failed: ' . $throwable->getMessage());
    }

    return false;
}

/**
 * @return array{status: string, message: string, email?: string, is_registration?: bool}
 */
function bfis_registration_peek_verification_token(PDO $pdo, string $token): array
{
    if ($token === '' || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return [
            'status' => 'invalid',
            'message' => 'Invalid or expired verification link.',
        ];
    }

    if (
        !empty($_SESSION['reg_verified_token_hash'])
        && hash_equals((string) $_SESSION['reg_verified_token_hash'], hash('sha256', $token))
    ) {
        return [
            'status' => 'already',
            'message' => 'This verification link has already been used. You can log in or continue to step 3.',
            'email' => trim((string) ($_SESSION['reg_verification_email'] ?? $_SESSION['reg_email'] ?? '')),
            'is_registration' => true,
        ];
    }

    $schema = bfis_email_verification_schema($pdo);
    $idColumn = $schema['id'];
    $tokenColumn = $schema['token'];
    $expiresColumn = $schema['expires_at'];
    $emailColumn = $schema['email'];
    $userIdColumn = $schema['user_id'];

    if ($idColumn === '' || $tokenColumn === '' || $expiresColumn === '') {
        return [
            'status' => 'error',
            'message' => 'Email verification is not configured on the server.',
        ];
    }

    $selectParts = [
        "{$idColumn} AS row_id",
        "{$expiresColumn} AS expires_at",
    ];

    if ($schema['has_user_id'] && $userIdColumn !== '') {
        $selectParts[] = "{$userIdColumn} AS user_id";
    }

    if ($schema['has_email'] && $emailColumn !== '') {
        $selectParts[] = "{$emailColumn} AS email";
    }

    $stmt = $pdo->prepare(
        'SELECT ' . implode(', ', $selectParts) . "
         FROM email_verifications
         WHERE {$tokenColumn} = ?
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $verification = $stmt->fetch();

    if (!$verification) {
        return [
            'status' => 'invalid',
            'message' => 'Invalid or expired verification link, or email already verified.',
        ];
    }

    if (strtotime((string) $verification['expires_at']) < time()) {
        $deleteStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ?");
        $deleteStmt->execute([(int) $verification['row_id']]);

        return [
            'status' => 'expired',
            'message' => 'This verification link has expired. Please register again or request a new link.',
            'email' => trim((string) ($verification['email'] ?? '')),
        ];
    }

    $userId = isset($verification['user_id']) ? (int) $verification['user_id'] : 0;

    return [
        'status' => 'valid',
        'message' => 'Ready to verify.',
        'email' => trim((string) ($verification['email'] ?? '')),
        'is_registration' => $userId <= 0,
    ];
}

/**
 * @return array{status: string, message: string, email?: string, is_registration?: bool}
 */
function bfis_registration_verify_token(PDO $pdo, string $token): array
{
    if ($token === '' || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return [
            'status' => 'invalid',
            'message' => 'Invalid or expired verification link.',
        ];
    }

    $schema = bfis_email_verification_schema($pdo);
    $idColumn = $schema['id'];
    $tokenColumn = $schema['token'];
    $expiresColumn = $schema['expires_at'];
    $emailColumn = $schema['email'];
    $userIdColumn = $schema['user_id'];
    $hasEmailColumn = $schema['has_email'];

    if ($idColumn === '' || $tokenColumn === '' || $expiresColumn === '') {
        return [
            'status' => 'error',
            'message' => 'Email verification is not configured on the server.',
        ];
    }

    try {
        $pdo->beginTransaction();

        $selectParts = [
            "{$idColumn} AS row_id",
            "{$expiresColumn} AS expires_at",
        ];

        if ($schema['has_user_id'] && $userIdColumn !== '') {
            $selectParts[] = "{$userIdColumn} AS user_id";
        }

        if ($hasEmailColumn && $emailColumn !== '') {
            $selectParts[] = "{$emailColumn} AS email";
        }

        $selectColumns = implode(', ', $selectParts);

        $stmt = $pdo->prepare(
            "SELECT {$selectColumns}
             FROM email_verifications
             WHERE {$tokenColumn} = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$token]);
        $verification = $stmt->fetch();

        if (!$verification) {
            $pdo->rollBack();

            $peek = bfis_registration_peek_verification_token($pdo, $token);

            if ($peek['status'] === 'already') {
                return $peek;
            }

            return [
                'status' => 'invalid',
                'message' => 'Invalid or expired verification link, or email already verified.',
            ];
        }

        $isExpired = strtotime((string) $verification['expires_at']) < time();

        if ($isExpired) {
            $deleteStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ?");
            $deleteStmt->execute([(int) $verification['row_id']]);
            $pdo->commit();

            return [
                'status' => 'expired',
                'message' => 'This verification link has expired. Please register again or request a new link.',
            ];
        }

        $userId = isset($verification['user_id']) ? (int) $verification['user_id'] : 0;
        $verificationEmail = trim((string) ($verification['email'] ?? ($_SESSION['reg_verification_email'] ?? '')));

        if ($userId > 0) {
            $updateStmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
            $updateStmt->execute([$userId]);

            $deleteStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ?");
            $deleteStmt->execute([(int) $verification['row_id']]);
            $pdo->commit();

        return [
            'status' => 'success',
            'message' => 'Your email has been verified successfully. You can now log in.',
            'email' => $verificationEmail,
            'is_registration' => false,
        ];
        }

        $markStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ? AND {$tokenColumn} = ?");
        $markStmt->execute([(int) $verification['row_id'], $token]);

        if ($markStmt->rowCount() !== 1) {
            $pdo->rollBack();

            return [
                'status' => 'invalid',
                'message' => 'Invalid or expired verification link, or email already verified.',
            ];
        }

        $pdo->commit();

        $_SESSION['reg_email_verified'] = true;
        $_SESSION['reg_verified_token_hash'] = hash('sha256', $token);

        if ($verificationEmail !== '') {
            $_SESSION['reg_verification_email'] = $verificationEmail;

            if (trim((string) ($_SESSION['reg_email'] ?? '')) === '') {
                $_SESSION['reg_email'] = $verificationEmail;
            }
        }

        if (!empty($_SESSION['reg_verification_id']) && (int) $_SESSION['reg_verification_id'] === (int) $verification['row_id']) {
            unset($_SESSION['reg_verification_id']);
        }

        return [
            'status' => 'success',
            'message' => 'Your email has been verified successfully. Return to registration step 3 to finish creating your account.',
            'email' => $verificationEmail,
            'is_registration' => true,
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Registration email verification failed: ' . $throwable->getMessage());

        return [
            'status' => 'error',
            'message' => 'We could not process this verification link right now.',
        ];
    }
}

/**
 * @return array{success?: bool, email?: string, error?: string}
 */
function bfis_registration_issue_verification(PDO $pdo, string $email, string $fullName): array
{
    $tokenResult = bfis_registration_store_verification_token($pdo, $email);

    if (isset($tokenResult['error'])) {
        return ['error' => $tokenResult['error']];
    }

    $mailResult = bfis_registration_send_verification_email($email, $fullName, (string) $tokenResult['token']);

    if (isset($mailResult['error'])) {
        return ['error' => $mailResult['error']];
    }

    return ['success' => true];
}

/**
 * @return bool
 */
function bfis_registration_can_complete_session(array $session): bool
{
    return !empty($session['reg_step1_ok'])
        && !empty($session['reg_step2_ok'])
        && trim((string) ($session['reg_password_hash'] ?? '')) !== '';
}

/**
 * @return int
 */
function bfis_registration_resident_role_id(PDO $pdo): int
{
    static $resolvedRoleId = null;

    if ($resolvedRoleId !== null) {
        return $resolvedRoleId;
    }

    try {
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE LOWER(role_name) = 'resident' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            $resolvedRoleId = (int) $row['role_id'];

            return $resolvedRoleId;
        }
    } catch (Throwable $throwable) {
        error_log('Resident role lookup failed: ' . $throwable->getMessage());
    }

    $resolvedRoleId = (int) BFIS_REGISTRATION_RESIDENT_ROLE_ID;

    return $resolvedRoleId;
}

/**
 * @return bool
 */
function bfis_registration_barangay_exists(PDO $pdo, int $barangayId): bool
{
    if ($barangayId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT barangay_id FROM barangays WHERE barangay_id = ? LIMIT 1');
        $stmt->execute([$barangayId]);

        return (bool) $stmt->fetch();
    } catch (Throwable $throwable) {
        error_log('Barangay lookup failed: ' . $throwable->getMessage());

        return false;
    }
}

/**
 * @param array<string, mixed> $session
 * @return array<string, mixed>
 */
function bfis_registration_prepare_completion_payload(array $session): array
{
    $suffix = trim((string) ($session['reg_suffix'] ?? ''));
    $dateOfBirth = trim((string) ($session['reg_dob'] ?? ''));
    $phone = trim((string) ($session['reg_phone'] ?? ''));
    $address = trim((string) ($session['reg_address'] ?? ''));

    return [
        'email' => trim((string) ($session['reg_email'] ?? '')),
        'full_name' => trim((string) ($session['reg_full_name'] ?? '')),
        'first_name' => trim((string) ($session['reg_first_name'] ?? '')),
        'last_name' => trim((string) ($session['reg_last_name'] ?? '')),
        'suffix' => $suffix === '' ? null : $suffix,
        'date_of_birth' => $dateOfBirth === '' ? null : $dateOfBirth,
        'phone' => $phone === '' ? null : $phone,
        'barangay_id' => (int) ($session['reg_barangay_id'] ?? 0),
        'address' => $address === '' ? null : $address,
        'latitude' => bfis_registration_normalize_coordinate($session['reg_lat'] ?? null),
        'longitude' => bfis_registration_normalize_coordinate($session['reg_lng'] ?? null),
        'valid_id_image' => trim((string) ($session['reg_valid_id_image'] ?? '')) ?: null,
        'profile_picture' => trim((string) ($session['reg_profile_picture'] ?? '')) ?: null,
        'password_hash' => (string) ($session['reg_password_hash'] ?? ''),
    ];
}

/**
 * @param string|null $value
 * @param int $maxLength
 * @return string|null
 */
function bfis_registration_truncate_db_string(?string $value, int $maxLength = 255): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);

    if ($value === '') {
        return null;
    }

    if (strlen($value) > $maxLength) {
        return null;
    }

    return $value;
}

/**
 * @return string
 */
function bfis_registration_completion_error_message(Throwable $throwable): string
{
    $message = $throwable->getMessage();

    if (stripos($message, 'Unknown column') !== false) {
        return 'Server database is missing required registration fields. Please contact the administrator.';
    }

    if (stripos($message, 'foreign key constraint') !== false || strpos($message, '1452') !== false) {
        return 'Invalid barangay or role configuration. Please go back to step 1 and try again.';
    }

    if (stripos($message, 'Duplicate entry') !== false) {
        if (stripos($message, 'phone') !== false) {
            return 'This phone number is already registered. Please use a different number or log in.';
        }

        return 'Email is already registered. Please log in or use another email.';
    }

    if (stripos($message, 'Data too long') !== false) {
        return 'Uploaded image reference is too long. Please go back to step 1 and re-upload your files.';
    }

    if (stripos($message, 'Incorrect date') !== false || strpos($message, '22007') !== false) {
        return 'Invalid date of birth. Please go back to step 1 and correct it.';
    }

    if (bfis_registration_debug_enabled()) {
        return 'Unable to complete registration: ' . $message;
    }

    return 'Unable to complete registration right now. Please try again.';
}

/**
 * @param array<string, mixed> $session
 * @return array{success?: bool, email?: string, error?: string}
 */
function bfis_registration_complete(PDO $pdo, array $session): array
{
    $payload = bfis_registration_prepare_completion_payload($session);
    $email = (string) $payload['email'];
    $fullName = (string) $payload['full_name'];
    $passwordHash = (string) $payload['password_hash'];
    $barangayId = (int) $payload['barangay_id'];
    $profilePicture = $payload['profile_picture'];
    $validIdImage = $payload['valid_id_image'];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid registration session. Please restart registration.'];
    }

    if ($passwordHash === '') {
        return ['error' => 'Password setup is incomplete. Please return to step 2.'];
    }

    if ($fullName === '') {
        return ['error' => 'Profile details are incomplete. Please return to step 1.'];
    }

    if ($barangayId <= 0 || !bfis_registration_barangay_exists($pdo, $barangayId)) {
        return ['error' => 'Please select a valid barangay on step 1 before completing registration.'];
    }

    if (!bfis_registration_is_email_verified($pdo, $email)) {
        return ['error' => 'Please verify your email before completing registration.'];
    }

    $roleId = bfis_registration_resident_role_id($pdo);

    if ($roleId <= 0) {
        return ['error' => 'Resident role is not configured on the server. Please contact the administrator.'];
    }

    try {
        $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            return ['error' => 'Email is already registered. Please log in or use another email.'];
        }

        if ($payload['phone'] !== null) {
            $phoneStmt = $pdo->prepare('SELECT user_id FROM users WHERE phone = ? LIMIT 1');
            $phoneStmt->execute([$payload['phone']]);

            if ($phoneStmt->fetch()) {
                return ['error' => 'This phone number is already registered. Please use a different number or log in.'];
            }
        }
    } catch (Throwable $throwable) {
        error_log('Registration pre-check failed: ' . $throwable->getMessage());
    }

    try {
        $uploadResult = bfis_reg_finalize_cloudinary_uploads($profilePicture, $validIdImage);
    } catch (Throwable $throwable) {
        error_log('Registration upload finalize failed: ' . $throwable->getMessage());

        return ['error' => 'Unable to upload registration files. Please go back to step 1 and try again.'];
    }

    $uploadValidation = bfis_registration_validate_upload_results($profilePicture, $validIdImage, $uploadResult);

    if (isset($uploadValidation['error'])) {
        return ['error' => $uploadValidation['error']];
    }

    $profilePicture = bfis_registration_truncate_db_string($uploadResult['profile_picture'] ?? $profilePicture);
    $validIdImage = bfis_registration_truncate_db_string($uploadResult['valid_id_image'] ?? $validIdImage);

    if (
        ($profilePicture === null && bfis_registration_has_staged_file($payload['profile_picture']))
        || ($validIdImage === null && bfis_registration_has_staged_file($payload['valid_id_image']))
    ) {
        return ['error' => 'Uploaded image reference is too long. Please go back to step 1 and re-upload your files.'];
    }

    try {
        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare(
            'INSERT INTO users (
                first_name,
                last_name,
                suffix,
                full_name,
                email,
                date_of_birth,
                phone,
                valid_id_image,
                profile_picture,
                barangay_id,
                current_address,
                latitude,
                longitude,
                role_id,
                password,
                is_verified,
                first_login
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)'
        );

        $insertStmt->execute([
            $payload['first_name'],
            $payload['last_name'],
            $payload['suffix'],
            $fullName,
            $email,
            $payload['date_of_birth'],
            $payload['phone'],
            $validIdImage,
            $profilePicture,
            $barangayId,
            $payload['address'],
            $payload['latitude'],
            $payload['longitude'],
            $roleId,
            $passwordHash,
        ]);

        $schema = bfis_email_verification_schema($pdo);

        if ($schema['has_email']) {
            $cleanupStmt = $pdo->prepare(
                'DELETE FROM email_verifications WHERE ' . $schema['email'] . ' = ?'
            );
            $cleanupStmt->execute([$email]);
        } elseif (!empty($session['reg_verification_id']) && $schema['id'] !== '') {
            $cleanupStmt = $pdo->prepare('DELETE FROM email_verifications WHERE ' . $schema['id'] . ' = ?');
            $cleanupStmt->execute([(int) $session['reg_verification_id']]);
        }

        $pdo->commit();

        if (!empty($uploadResult['temp_paths'])) {
            bfis_reg_delete_temp_paths($uploadResult['temp_paths']);
        }

        return [
            'success' => true,
            'email' => $email,
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Registration completion failed: ' . $throwable->getMessage());

        return ['error' => bfis_registration_completion_error_message($throwable)];
    }
}

/**
 * @param mixed $value
 */
function bfis_registration_normalize_coordinate($value): ?string
{
    $value = trim((string) $value);

    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    return number_format((float) $value, 8, '.', '');
}

/**
 * @return void
 */
function bfis_registration_clear_session(): void
{
    $keys = [
        'reg_first_name',
        'reg_last_name',
        'reg_suffix',
        'reg_full_name',
        'reg_email',
        'reg_dob',
        'reg_phone',
        'reg_barangay_slug',
        'reg_barangay_id',
        'reg_address',
        'reg_lat',
        'reg_lng',
        'reg_profile_picture',
        'reg_valid_id_image',
        'reg_password_hash',
        'reg_step1_ok',
        'reg_step2_ok',
        'reg_email_verified',
        'reg_verification_email',
        'reg_verification_id',
        'reg_verified_token_hash',
    ];

    foreach ($keys as $key) {
        unset($_SESSION[$key]);
    }
}
