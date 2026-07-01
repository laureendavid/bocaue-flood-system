<?php

require_once __DIR__ . '/../includes/session_bootstrap.php';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/registration_service.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$status = 'invalid';
$message = 'No verification token provided.';
$email = '';
$isCompleted = false;
$completionError = '';
$isRegistration = false;
$showConfirmForm = false;
$viewState = 'invalid';

if ($token !== '') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_verification'])) {
        $verifyResult = bfis_registration_verify_token($pdo, $token);
        $status = (string) ($verifyResult['status'] ?? 'invalid');
        $message = (string) ($verifyResult['message'] ?? '');
        $email = trim((string) ($verifyResult['email'] ?? ''));
        $isRegistration = !empty($verifyResult['is_registration']);

        if ($status === 'success' && $isRegistration) {
            if (bfis_registration_can_complete_session($_SESSION)) {
                $completionResult = bfis_registration_complete($pdo, $_SESSION);

                if (!empty($completionResult['success'])) {
                    $isCompleted = true;
                    $email = (string) ($completionResult['email'] ?? $email);
                    $message = 'Your account has been successfully verified and created! You can now log in.';
                    bfis_registration_clear_session();
                } elseif (isset($completionResult['error'])) {
                    $completionError = (string) $completionResult['error'];
                    $message = 'Your email was verified, but we could not finish creating your account yet.';
                }
            } else {
                $message = 'Your email has been verified. Return to registration step 3 in the same browser to finish creating your account.';
            }
        } elseif ($status === 'success' && !$isRegistration) {
            $message = 'Your account has been successfully verified! You can now log in.';
        }
    } else {
        $peekResult = bfis_registration_peek_verification_token($pdo, $token);
        $status = (string) ($peekResult['status'] ?? 'invalid');
        $message = (string) ($peekResult['message'] ?? '');
        $email = trim((string) ($peekResult['email'] ?? ''));
        $isRegistration = !empty($peekResult['is_registration']);
        $showConfirmForm = $status === 'valid';
    }
}

if ($showConfirmForm) {
    $viewState = 'confirm';
} elseif ($isCompleted || ($status === 'success' && !$isRegistration)) {
    $viewState = 'complete';
} elseif ($status === 'success' && $isRegistration) {
    $viewState = 'verified_pending';
} elseif ($status === 'already') {
    $viewState = 'already';
} else {
    $viewState = 'failed';
}

$pageTitles = [
    'confirm' => 'Confirm Email',
    'complete' => 'Account Verified',
    'verified_pending' => 'Email Verified',
    'already' => 'Already Verified',
    'failed' => 'Verification Failed',
];
$pageTitle = $pageTitles[$viewState] ?? 'Verification';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> — Bocaue Community Flood Information System</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .verify-shell {
      width: min(720px, 100%);
      margin: 2rem auto;
      padding: 0 0.75rem 2rem;
    }

    .verify-card {
      background: #fff;
      border: 1px solid #dbe7f3;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(18, 62, 117, 0.08);
      padding: 2rem 1.5rem;
      text-align: center;
    }

    .verify-icon {
      width: 72px;
      height: 72px;
      border-radius: 999px;
      margin: 0 auto 1rem;
      display: grid;
      place-items: center;
      font-size: 2rem;
      font-weight: 700;
      color: #fff;
    }

    .verify-icon.success {
      background: linear-gradient(135deg, #0ea5e9, #2563eb);
    }

    .verify-icon.pending {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .verify-icon.error {
      background: linear-gradient(135deg, #ef4444, #b91c1c);
    }

    .verify-actions {
      margin-top: 1.25rem;
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .verify-actions a,
    .verify-actions button {
      text-decoration: none;
    }

    .confirm-note {
      margin: 1rem auto 0;
      max-width: 520px;
      font-size: 0.88rem;
      color: #27486f;
      line-height: 1.5;
    }

    .verify-error {
      margin: 0.75rem auto 0;
      max-width: 560px;
      color: #b91c1c;
      font-size: 0.92rem;
      line-height: 1.5;
    }
  </style>
</head>

<body>
  <div class="verify-shell">
    <section class="verify-card">
      <?php if ($viewState === 'confirm'): ?>
        <div class="verify-icon pending">@</div>
        <h1 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Confirm Your Email</h1>
        <p class="complete-desc" style="max-width:560px; margin:0 auto;">
          Click the button below to verify your email and continue registration.
        </p>
        <?php if ($email !== ''): ?>
          <p style="margin-top:0.75rem; font-size:0.88rem; color:#27486f;">
            <strong><?php echo htmlspecialchars($email); ?></strong>
          </p>
        <?php endif; ?>
        <p class="confirm-note">
          This extra step helps prevent email scanners from using your link before you do.
        </p>
        <div class="verify-actions">
          <form method="POST" action="verify.php" style="margin:0;">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" name="confirm_verification" value="1" class="btn-next">Verify My Email</button>
          </form>
        </div>
      <?php elseif ($viewState === 'complete'): ?>
        <div class="verify-icon success">&#10003;</div>
        <h1 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Account Verified!</h1>
        <p class="complete-desc" style="max-width:560px; margin:0 auto;">
          <?php echo htmlspecialchars($message); ?>
        </p>
        <?php if ($email !== ''): ?>
          <p style="margin-top:0.75rem; font-size:0.88rem; color:#27486f;">
            <strong><?php echo htmlspecialchars($email); ?></strong>
          </p>
        <?php endif; ?>
        <div class="verify-actions">
          <a href="login.php" class="btn-next">Go to Login</a>
        </div>
      <?php elseif ($viewState === 'verified_pending'): ?>
        <div class="verify-icon pending">&#10003;</div>
        <h1 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Email Verified</h1>
        <p class="complete-desc" style="max-width:560px; margin:0 auto;">
          <?php echo htmlspecialchars($message); ?>
        </p>
        <?php if ($completionError !== ''): ?>
          <p class="verify-error"><?php echo htmlspecialchars($completionError); ?></p>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <p style="margin-top:0.75rem; font-size:0.88rem; color:#27486f;">
            <strong><?php echo htmlspecialchars($email); ?></strong>
          </p>
        <?php endif; ?>
        <p class="confirm-note">
          Open step 3 in the same browser where you started registration, then refresh that page to finish creating your account.
        </p>
        <div class="verify-actions">
          <a href="register_step3.php" class="btn-next">Go to Step 3</a>
          <a href="register_step1.php" class="btn-back">Register Again</a>
        </div>
      <?php elseif ($viewState === 'already'): ?>
        <div class="verify-icon pending">&#10003;</div>
        <h1 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Already Verified</h1>
        <p class="complete-desc" style="max-width:560px; margin:0 auto;">
          <?php echo htmlspecialchars($message); ?>
        </p>
        <?php if ($email !== ''): ?>
          <p style="margin-top:0.75rem; font-size:0.88rem; color:#27486f;">
            <strong><?php echo htmlspecialchars($email); ?></strong>
          </p>
        <?php endif; ?>
        <div class="verify-actions">
          <a href="register_step3.php" class="btn-next">Go to Step 3</a>
          <a href="login.php" class="btn-back">Go to Login</a>
        </div>
      <?php else: ?>
        <div class="verify-icon error">!</div>
        <h1 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Verification Failed</h1>
        <p class="complete-desc" style="max-width:560px; margin:0 auto;">
          <?php echo htmlspecialchars($message); ?>
        </p>
        <?php if ($email !== ''): ?>
          <p style="margin-top:0.75rem; font-size:0.88rem; color:#27486f;">
            <strong><?php echo htmlspecialchars($email); ?></strong>
          </p>
        <?php endif; ?>
        <div class="verify-actions">
          <a href="register_step1.php" class="btn-next">Register Again</a>
          <a href="login.php" class="btn-back">Go to Login</a>
        </div>
      <?php endif; ?>
    </section>
  </div>
</body>

</html>
