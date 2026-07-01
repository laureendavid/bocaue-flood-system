<?php
require_once __DIR__ . '/../includes/session_bootstrap.php';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/registration_service.php';

$completionEmail = trim((string) ($_SESSION['reg_completion_email'] ?? ''));
$hasActiveRegistration = !empty($_SESSION['reg_step1_ok']) && !empty($_SESSION['reg_step2_ok']);
$canCompleteRegistration = bfis_registration_can_complete_session($_SESSION);

if (!$hasActiveRegistration && $completionEmail === '') {
  header('Location: register_step1.php');
  exit;
}

$error = '';
$infoMessage = '';
$email = $hasActiveRegistration ? trim((string) ($_SESSION['reg_email'] ?? '')) : $completionEmail;
$isCompleted = $completionEmail !== '';
$emailVerified = $isCompleted || bfis_registration_is_email_verified($pdo, $email);

if ($hasActiveRegistration && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
  $verificationResult = bfis_registration_issue_verification(
    $pdo,
    $email,
    trim((string) ($_SESSION['reg_full_name'] ?? ''))
  );

  if (isset($verificationResult['error'])) {
    $error = $verificationResult['error'];
  } else {
    unset($_SESSION['reg_email_verified']);
    $infoMessage = 'A new verification email has been sent. Please check your inbox.';
    $emailVerified = false;
  }
}

if ($hasActiveRegistration && $emailVerified && !$isCompleted && $canCompleteRegistration) {
  $completionResult = bfis_registration_complete($pdo, $_SESSION);

  if (isset($completionResult['error'])) {
    $error = $completionResult['error'];
    $emailVerified = bfis_registration_is_email_verified($pdo, $email);
  } elseif (!empty($completionResult['success'])) {
    $completionEmail = (string) ($completionResult['email'] ?? $email);
    $_SESSION['reg_completion_email'] = $completionEmail;
    bfis_registration_clear_session();
    $email = $completionEmail;
    $isCompleted = true;
    $hasActiveRegistration = false;
  }
}

if ($isCompleted) {
  unset($_SESSION['reg_completion_email']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Step 3: Complete</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
  <style>
    .completion-shell {
      width: min(980px, 100%);
      margin: 0 auto;
      display: grid;
      gap: 1.35rem;
      padding: 0.5rem 0.75rem 2rem;
    }

    .completion-header {
      background: linear-gradient(135deg, #0b3a75, #3f8fe8);
      color: #fff;
      border-radius: 16px;
      padding: 1.2rem 1.4rem;
    }

    .completion-card {
      background: #fff;
      border: 1px solid #dbe7f3;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(18, 62, 117, 0.08);
      padding: 1.5rem;
      text-align: center;
    }

    .success-icon-modern {
      width: 72px;
      height: 72px;
      border-radius: 999px;
      margin: 0 auto 1rem;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #0ea5e9, #2563eb);
      color: #fff;
      box-shadow: 0 12px 20px rgba(37, 99, 235, 0.25);
    }

    .success-icon-modern svg {
      width: 34px;
      height: 34px;
    }

    .pending-icon-modern {
      width: 72px;
      height: 72px;
      border-radius: 999px;
      margin: 0 auto 1rem;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff;
      box-shadow: 0 12px 20px rgba(217, 119, 6, 0.25);
      font-size: 2rem;
      font-weight: 700;
    }

    .info-strip {
      margin: 1rem auto 0;
      max-width: 620px;
      text-align: left;
      border: 1px solid #dbe7f3;
      background: #f7fbff;
      border-radius: 12px;
      padding: 0.9rem 1rem;
      font-size: 0.88rem;
      color: #27486f;
    }

    .email-pill {
      margin-top: 0.75rem;
      display: inline-block;
      background: #eef6ff;
      border: 1px solid #cddff5;
      color: #1f4f86;
      border-radius: 999px;
      padding: 0.35rem 0.8rem;
      font-weight: 700;
      font-size: 0.82rem;
      word-break: break-all;
    }

    .completion-actions {
      margin-top: 1.2rem;
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    @media (max-width: 900px) {
      .completion-shell {
        padding: 0.3rem 0.2rem 1.2rem;
      }
    }
  </style>
</head>

<body>
  <div class="reg-page">
    <aside class="reg-sidebar">
      <div class="sidebar-brand">
        <div class="brand-name">Bocaue Flood Information System</div>
      </div>
      <p class="sidebar-section-label">Registration<br><?php echo $isCompleted ? 'Completed' : 'Verify Email'; ?></p>
      <ul class="sidebar-steps">
        <li class="step-item done">
          <div class="step-dot">1</div><span class="step-label">Profile</span>
        </li>
        <li class="step-item done">
          <div class="step-dot">2</div><span class="step-label">Password</span>
        </li>
        <li class="step-item active">
          <div class="step-dot">3</div><span class="step-label">Complete</span>
        </li>
      </ul>
    </aside>
    <main class="reg-main">
      <div class="completion-shell">
        <section class="completion-header fade-up">
          <p class="step-tag" style="color:#cfe5ff;">Step 03 of 03</p>
          <h1 class="reg-title" style="color:#fff; margin-bottom:0.35rem;">
            <?php echo $isCompleted ? 'Registration Completed' : 'Verify Your Email'; ?>
          </h1>
          <p style="margin:0; color:#e5f1ff; font-size:0.92rem;">
            <?php if ($isCompleted): ?>
              Your profile, password, and email verification are complete. You can log in with your new account.
            <?php elseif ($emailVerified): ?>
              Your email is verified. Refresh this page to try completing your account again.
            <?php else: ?>
              We sent a verification link to your email. Open it to continue, then return to this page.
            <?php endif; ?>
          </p>
          <div class="progress-bar-wrap" style="margin-top:0.85rem;">
            <div class="progress-track">
              <div class="progress-fill" style="width: 100%;"></div>
            </div>
            <span class="progress-pct">100% Complete</span>
          </div>
        </section>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger fade-up" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($infoMessage !== ''): ?>
          <div class="alert alert-success fade-up" role="alert"><?php echo htmlspecialchars($infoMessage); ?></div>
        <?php endif; ?>

        <section class="completion-card fade-up fade-up-delay-1">
          <?php if ($isCompleted): ?>
            <div class="success-icon-modern">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </div>
            <h2 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Account Created Successfully</h2>
            <p class="complete-desc" style="max-width:650px; margin:0 auto;">
              Your account has been created and is ready to use.
              <?php if ($email !== ''): ?>
                You registered with <strong><?php echo htmlspecialchars($email); ?></strong>.
              <?php endif; ?>
            </p>
            <div class="info-strip">
              <strong>What happens next?</strong><br>
              Go to the login page and sign in with your email and password.
            </div>
            <div class="completion-actions">
              <a href="register_step1.php" class="btn-back" style="text-decoration:none;">Register Another Account</a>
              <a href="login.php" class="btn-next" style="text-decoration:none;">Go to Login</a>
            </div>
          <?php elseif (!$emailVerified): ?>
            <div class="pending-icon-modern">@</div>
            <h2 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Check Your Email</h2>
            <p class="complete-desc" style="max-width:650px; margin:0 auto;">
              Click the verification link we sent to finish creating your account.
            </p>
            <?php if ($email !== ''): ?>
              <span class="email-pill"><?php echo htmlspecialchars($email); ?></span>
            <?php endif; ?>
            <div class="info-strip">
              <strong>Did not receive the email?</strong><br>
              Check your spam folder, then use the button below to send another verification link.
              After verifying, refresh this page to complete registration.
            </div>
            <div class="completion-actions">
              <form method="POST" action="register_step3.php" style="margin:0;">
                <button type="submit" name="resend_verification" value="1" class="btn-back">Resend Verification Email</button>
              </form>
              <a href="register_step3.php" class="btn-next" style="text-decoration:none;">Refresh Page</a>
            </div>
          <?php elseif ($emailVerified && !$canCompleteRegistration): ?>
            <div class="pending-icon-modern">!</div>
            <h2 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Finish in the Same Browser</h2>
            <p class="complete-desc" style="max-width:650px; margin:0 auto;">
              Your email is verified, but this browser does not have your registration details anymore.
            </p>
            <?php if ($email !== ''): ?>
              <span class="email-pill"><?php echo htmlspecialchars($email); ?></span>
            <?php endif; ?>
            <div class="info-strip">
              <strong>What to do next</strong><br>
              Return to the browser where you completed steps 1 and 2, open step 3 there, and refresh the page.
              If that is not possible, start registration again from step 1.
            </div>
            <div class="completion-actions">
              <a href="register_step1.php" class="btn-back" style="text-decoration:none;">Register Again</a>
              <a href="register_step3.php" class="btn-next" style="text-decoration:none;">Refresh Page</a>
            </div>
          <?php else: ?>
            <div class="pending-icon-modern">!</div>
            <h2 class="reg-title" style="font-size:1.6rem; margin-bottom:0.55rem;">Almost Done</h2>
            <p class="complete-desc" style="max-width:650px; margin:0 auto;">
              Your email is verified, but we could not finish creating your account yet.
            </p>
            <?php if ($email !== ''): ?>
              <span class="email-pill"><?php echo htmlspecialchars($email); ?></span>
            <?php endif; ?>
            <div class="info-strip">
              <strong>What to do next</strong><br>
              Review the error message above, then refresh this page to try again.
              If uploads failed, go back to step 1 and re-upload your files.
            </div>
            <div class="completion-actions">
              <a href="register_step1.php" class="btn-back" style="text-decoration:none;">Back to Step 1</a>
              <a href="register_step3.php" class="btn-next" style="text-decoration:none;">Try Again</a>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </main>
  </div>
  <script src="../main/assets/js/script.js"></script>
</body>

</html>
