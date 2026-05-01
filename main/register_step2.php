<?php
session_start();

if (empty($_SESSION['reg_step1_ok'])) {
    header('Location: register_step1.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$error = '';
$debugDetail = '';

function ensureEmailVerificationTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function getEmailVerificationColumns(PDO $pdo): array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM email_verifications');
    $columns = [];
    foreach ($stmt as $row) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function sendVerificationEmail(string $toEmail, string $token): void
{
    $verifyUrl = sprintf(
        '%s://%s/bocaue-flood-system/main/verify-email.php?token=%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        urlencode($token)
    );

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'itsdanecalma@gmail.com';
    $mail->Password = 'nstdugapmsfayanv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 20;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom('itsdanecalma@gmail.com', 'Bocaue Flood Information System');
    $mail->addAddress($toEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Verify your BFIS account';
    $mail->Body = 'Your account has been created.<br><br>Please verify your email by clicking this link:<br><a href="' .
        htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '</a>';
    $mail->AltBody = 'Verify your account by opening this link: ' . $verifyUrl;
    $mail->send();
}

function isWeakPassword(string $password): bool
{
    $normalized = strtolower($password);
    $weakPasswords = [
        'password',
        'password1',
        'password123',
        'admin123',
        'qwerty123',
        '12345678',
        '123456789',
        '11111111',
        'abc12345',
    ];

    if (in_array($normalized, $weakPasswords, true)) {
        return true;
    }

    if (preg_match('/^(.)\1+$/', $password)) {
        return true;
    }

    if (preg_match('/(01234567|12345678|23456789|abcdefgh|qwertyui)/i', $password)) {
        return true;
    }

    return false;
}

function validatePassword(string $password, string $confirmPassword): string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        return 'Password must contain at least one letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $password)) {
        return 'Password may only contain letters, numbers, and underscore.';
    }
    if (!preg_match('/_/', $password) && !(preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password))) {
        return 'Password must include an underscore or a valid alphanumeric mix.';
    }
    if (isWeakPassword($password)) {
        return 'Password is too common or too simple. Please use a stronger password.';
    }
    if ($password !== $confirmPassword) {
        return 'Passwords do not match.';
    }

    return '';
}

function normalizeCoordinate(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    return number_format((float) $value, 8, '.', '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $error = validatePassword($password, $confirmPassword);

    if ($error === '') {
        $email = $_SESSION['reg_email'] ?? '';
        $fullName = trim($_SESSION['reg_full_name'] ?? '');
        $firstName = trim($_SESSION['reg_first_name'] ?? '');
        $lastName = trim($_SESSION['reg_last_name'] ?? '');
        $suffix = trim($_SESSION['reg_suffix'] ?? '');
        $dateOfBirth = $_SESSION['reg_dob'] ?? null;
        $phone = $_SESSION['reg_phone'] ?? null;
        $barangayId = $_SESSION['reg_barangay_id'] ?? null;
        $address = $_SESSION['reg_address'] ?? null;
        $latitude = normalizeCoordinate($_SESSION['reg_lat'] ?? null);
        $longitude = normalizeCoordinate($_SESSION['reg_lng'] ?? null);
        $validIdImage = $_SESSION['reg_valid_id_image'] ?? null;
        $profilePicture = $_SESSION['reg_profile_picture'] ?? null;
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid registration session. Please restart registration.';
        } else {
            try {
                $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'Email is already registered. Please log in or use another email.';
                } else {
                    ensureEmailVerificationTable($pdo);
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
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 3, ?, 0, 1)'
                    );

                    $insertStmt->execute([
                        $firstName,
                        $lastName,
                        $suffix === '' ? null : $suffix,
                        $fullName,
                        $email,
                        $dateOfBirth,
                        $phone,
                        $validIdImage,
                        $profilePicture,
                        $barangayId,
                        $address,
                        $latitude,
                        $longitude,
                        $passwordHash,
                    ]);

                    $userId = (int) $pdo->lastInsertId();
                    $token = bin2hex(random_bytes(32));
                    $verificationColumns = getEmailVerificationColumns($pdo);
                    $hasEmailColumn = in_array('email', $verificationColumns, true);
                    $hasUserIdColumn = in_array('user_id', $verificationColumns, true);

                    if ($hasEmailColumn) {
                        $cleanupTokenStmt = $pdo->prepare('DELETE FROM email_verifications WHERE email = ?');
                        $cleanupTokenStmt->execute([$email]);
                    } elseif ($hasUserIdColumn) {
                        $cleanupTokenStmt = $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
                        $cleanupTokenStmt->execute([$userId]);
                    }

                    if ($hasEmailColumn && $hasUserIdColumn) {
                        $tokenStmt = $pdo->prepare(
                            'INSERT INTO email_verifications (user_id, email, token, expires_at)
                             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
                        );
                        $tokenStmt->execute([$userId, $email, $token]);
                    } elseif ($hasUserIdColumn) {
                        $tokenStmt = $pdo->prepare(
                            'INSERT INTO email_verifications (user_id, token, expires_at)
                             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
                        );
                        $tokenStmt->execute([$userId, $token]);
                    } else {
                        throw new RuntimeException('email_verifications table missing required user_id/email columns.');
                    }

                    $pdo->commit();

                    try {
                        sendVerificationEmail($email, $token);
                        $_SESSION['reg_mail_status'] = 'sent';
                        $_SESSION['reg_mail_error'] = '';
                    } catch (Exception $mailException) {
                        error_log('Registration verification email failed: ' . $mailException->getMessage());
                        $_SESSION['reg_mail_status'] = 'failed';
                        $_SESSION['reg_mail_error'] = $mailException->getMessage();
                    }

                    $_SESSION['reg_completion_email'] = $email;
                    $_SESSION['reg_completion_user_id'] = $userId;
                    $_SESSION['reg_step3_ok'] = true;

                    $keys = [
                        'reg_first_name', 'reg_last_name', 'reg_suffix', 'reg_full_name', 'reg_email',
                        'reg_dob', 'reg_phone', 'reg_barangay_slug', 'reg_barangay_id', 'reg_address',
                        'reg_lat', 'reg_lng', 'reg_profile_picture', 'reg_valid_id_image', 'reg_step1_ok'
                    ];
                    foreach ($keys as $key) {
                        unset($_SESSION[$key]);
                    }

                    header('Location: register_step3.php');
                    exit;
                }
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Registration save failed: ' . $throwable->getMessage());
                $debugDetail = $throwable->getMessage();
                $error = 'Unable to complete registration right now. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Step 2: Create Secure Password</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
  <style>
    .password-shell { width: min(980px, 100%); margin: 0 auto; display: grid; gap: 1.35rem; padding: 0.5rem 0.75rem 2rem; }
    .password-header { background: linear-gradient(135deg, #0b3a75, #3f8fe8); color: #fff; border-radius: 16px; padding: 1.2rem 1.4rem; }
    .password-card { background: #fff; border: 1px solid #dbe7f3; border-radius: 16px; box-shadow: 0 12px 30px rgba(18, 62, 117, 0.08); padding: 1.35rem; }
    .password-grid { display: grid; gap: 0.95rem; }
    .password-input-wrap { position: relative; }
    .password-input-wrap .form-control { padding-right: 2.7rem; }
    .toggle-password {
      position: absolute; top: 50%; right: 0.65rem; transform: translateY(-50%);
      border: none; background: transparent; color: #2b578e; cursor: pointer; font-size: 0.8rem; font-weight: 700;
    }
    .rules-list { list-style: none; padding: 0; margin: 0.6rem 0 0; display: grid; gap: 0.4rem; }
    .rules-list li { font-size: 0.82rem; display: flex; align-items: center; gap: 0.45rem; color: #b91c1c; font-weight: 600; }
    .rules-list li.valid { color: #0f7a33; }
    .rule-dot { width: 9px; height: 9px; border-radius: 50%; background: currentColor; display: inline-block; }
    .strength-warning { font-size: 0.8rem; color: #b91c1c; min-height: 1rem; margin-top: 0.25rem; }
    .match-text { font-size: 0.8rem; min-height: 1rem; margin-top: 0.25rem; color: #1e40af; font-weight: 600; }
    .btn-next[disabled] { opacity: 0.55; cursor: not-allowed; }
    @media (max-width: 900px) { .password-shell { padding: 0.3rem 0.2rem 1.2rem; } }
  </style>
</head>
<body>
  <div class="reg-page">
    <aside class="reg-sidebar">
      <div class="sidebar-brand"><div class="brand-name">Bocaue Flood Information System</div></div>
      <p class="sidebar-section-label">Registration<br>Secure Password</p>
      <ul class="sidebar-steps">
        <li class="step-item done"><div class="step-dot">1</div><span class="step-label">Profile</span></li>
        <li class="step-item active"><div class="step-dot">2</div><span class="step-label">Password</span></li>
        <li class="step-item pending"><div class="step-dot">3</div><span class="step-label">Complete</span></li>
      </ul>
    </aside>
    <main class="reg-main">
      <div class="password-shell">
        <section class="password-header fade-up">
          <p class="step-tag" style="color:#cfe5ff;">Step 02 of 03</p>
          <h1 class="reg-title" style="color:#fff; margin-bottom:0.35rem;">Create Secure Password</h1>
          <p style="margin:0; color:#e5f1ff; font-size:0.92rem;">Set a strong password for your resident account. Username: <strong><?php echo htmlspecialchars($_SESSION['reg_email'] ?? ''); ?></strong></p>
          <div class="progress-bar-wrap" style="margin-top:0.85rem;">
            <div class="progress-track"><div class="progress-fill" style="width: 66%;"></div></div>
            <span class="progress-pct">66% Complete</span>
          </div>
        </section>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger fade-up" role="alert"><?php echo htmlspecialchars($error); ?></div>
          <?php if ($debugDetail !== ''): ?>
            <div class="alert alert-danger fade-up" role="alert" style="font-size:0.8rem;opacity:0.9;">
              Debug: <?php echo htmlspecialchars($debugDetail); ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <section class="password-card fade-up fade-up-delay-1">
          <form method="POST" action="register_step2.php" novalidate id="passwordStepForm" class="password-grid">
            <div class="form-group">
              <label class="form-label" for="password">Password</label>
              <div class="password-input-wrap">
                <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required>
                <button type="button" class="toggle-password" data-target="password">SHOW</button>
              </div>
              <ul class="rules-list" id="passwordRules">
                <li id="rule-length"><span class="rule-dot"></span>At least 8 characters</li>
                <li id="rule-letters"><span class="rule-dot"></span>Contains letters (A-Z / a-z)</li>
                <li id="rule-numbers"><span class="rule-dot"></span>Contains numbers (0-9)</li>
                <li id="rule-underscore"><span class="rule-dot"></span>Contains underscore or valid allowed characters</li>
              </ul>
              <p class="strength-warning" id="weakWarning"></p>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm Password</label>
              <div class="password-input-wrap">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password" required>
                <button type="button" class="toggle-password" data-target="confirm_password">SHOW</button>
              </div>
              <p class="match-text" id="matchText"></p>
            </div>

            <div class="reg-nav" style="margin-top:0.4rem;">
              <a href="register_step1.php" class="btn-back">Back</a>
              <button type="submit" class="btn-next" id="nextButton" disabled>Create Account</button>
            </div>
          </form>
        </section>
      </div>
    </main>
  </div>
  <script src="../main/assets/js/script.js"></script>
  <script>
    (function () {
      var passwordInput = document.getElementById('password');
      var confirmInput = document.getElementById('confirm_password');
      var nextButton = document.getElementById('nextButton');
      var weakWarning = document.getElementById('weakWarning');
      var matchText = document.getElementById('matchText');
      var weakSet = ['password', 'password1', 'password123', 'admin123', 'qwerty123', '12345678', '123456789', '11111111', 'abc12345'];

      function hasOnlyAllowedChars(password) {
        return /^[A-Za-z0-9_]+$/.test(password);
      }

      function isWeakPassword(password) {
        var normalized = (password || '').toLowerCase();
        if (weakSet.indexOf(normalized) !== -1) {
          return true;
        }
        if (/^(.)\1+$/.test(password)) {
          return true;
        }
        if (/(01234567|12345678|23456789|abcdefgh|qwertyui)/i.test(password)) {
          return true;
        }
        return false;
      }

      function setRuleState(id, valid) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('valid', !!valid);
      }

      function validate() {
        var password = passwordInput.value || '';
        var confirm = confirmInput.value || '';

        var hasLength = password.length >= 8;
        var hasLetters = /[A-Za-z]/.test(password);
        var hasNumbers = /[0-9]/.test(password);
        var underscoreOrValid = /_/.test(password) || (hasLetters && hasNumbers && hasOnlyAllowedChars(password));
        var allowedChars = hasOnlyAllowedChars(password);
        var isWeak = isWeakPassword(password);
        var matches = password !== '' && password === confirm;

        setRuleState('rule-length', hasLength);
        setRuleState('rule-letters', hasLetters);
        setRuleState('rule-numbers', hasNumbers);
        setRuleState('rule-underscore', underscoreOrValid && allowedChars);

        if (!allowedChars && password.length > 0) {
          weakWarning.textContent = 'Use letters, numbers, and underscore only.';
        } else if (isWeak && password.length > 0) {
          weakWarning.textContent = 'This password is too simple or commonly used.';
        } else {
          weakWarning.textContent = '';
        }

        if (confirm.length === 0) {
          matchText.textContent = '';
        } else if (matches) {
          matchText.textContent = 'Passwords match.';
          matchText.style.color = '#0f7a33';
        } else {
          matchText.textContent = 'Passwords do not match.';
          matchText.style.color = '#b91c1c';
        }

        var isValid = hasLength && hasLetters && hasNumbers && allowedChars && underscoreOrValid && !isWeak && matches;
        nextButton.disabled = !isValid;
      }

      document.querySelectorAll('.toggle-password').forEach(function (toggleButton) {
        toggleButton.addEventListener('click', function () {
          var target = document.getElementById(this.getAttribute('data-target'));
          if (!target) return;
          var show = target.type === 'password';
          target.type = show ? 'text' : 'password';
          this.textContent = show ? 'HIDE' : 'SHOW';
        });
      });

      passwordInput.addEventListener('input', validate);
      confirmInput.addEventListener('input', validate);
      validate();
    })();
  </script>
</body>
</html>
