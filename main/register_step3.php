<?php
/**
 * register_step3.php — Step 3: Security Setup (Password)
 *
 * SESSION KEYS SET HERE:
 *   reg_password_hashed — bcrypt hash of the password
 *   reg_step3_ok        — guard flag
 *
 * NOTE: There is no separate "username" field in the users table,
 * so reg_identifier is stored in session but NOT inserted into the DB.
 * The users table uses email as the login identifier.
 */

session_start();

// ── Guard ──────────────────────────────────────────────────────────────────
if (empty($_SESSION['reg_step2_ok'])) {
    header('Location: register_step2.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $access_key  = $_POST['access_key']            ?? '';
    $confirm_key = $_POST['confirm_authorization'] ?? '';

    if (strlen($access_key) < 12) {
        $error = 'Password must be at least 12 characters long.';

    } elseif (!preg_match('/[a-zA-Z]/', $access_key) || !preg_match('/[0-9]/', $access_key)) {
        $error = 'Password must contain both letters and numbers.';

    } elseif ($access_key !== $confirm_key) {
        $error = 'Passwords do not match. Please confirm your password carefully.';

    } else {
        // Hash immediately — raw password never travels further
        $_SESSION['reg_password_hashed'] = password_hash($access_key, PASSWORD_BCRYPT);
        $_SESSION['reg_step3_ok']        = true;

        header('Location: registerComplete.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS — Step 3: Security Setup</title>
  <link rel="stylesheet" href="/main/assets/css/styles.css">
</head>
<body>

<div class="reg-page">

  <!-- ══ Sidebar Progress ══ -->
  <aside class="reg-sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
        </svg>
      </div>
      <div>
        <div class="brand-name">Bocaue Flood Information System</div>
      </div>
    </div>

    <p class="sidebar-section-label">Registration<br>Secure your Account</p>

    <ul class="sidebar-steps">
      <li class="step-item done">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="step-meta">
          <span class="step-label">Verify</span>
          <span class="step-desc">Phone &amp; OTP</span>
        </div>
      </li>
      <li class="step-item done">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="step-meta">
          <span class="step-label">Profile</span>
          <span class="step-desc">Personal details</span>
        </div>
      </li>
      <li class="step-item active">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
          </svg>
        </div>
        <div class="step-meta">
          <span class="step-label">Security</span>
          <span class="step-desc">Username &amp; password</span>
        </div>
      </li>
      <li class="step-item pending">
        <div class="step-dot">4</div>
        <div class="step-meta">
          <span class="step-label">Complete</span>
          <span class="step-desc">Account created</span>
        </div>
      </li>
    </ul>

    <div class="sidebar-tip">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <p>Use a strong password with letters, numbers, and special characters for best security.</p>
    </div>
  </aside>

  <!-- ══ Main Content ══ -->
  <main class="reg-main">

    <div class="reg-header">
      <p class="step-tag">Step 03 of 03</p>
      <h1 class="reg-title fade-up">Secure your Account</h1>
      <p class="reg-subtitle fade-up">Choose a strong password to protect your account.</p>

      <div class="progress-bar-wrap fade-up fade-up-delay-1">
        <div class="progress-track">
          <div class="progress-fill" style="width: 90%;"></div>
        </div>
        <span class="progress-pct">Almost done!</span>
      </div>
    </div>

    <!-- Error alert -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger fade-up" style="max-width:800px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?php echo htmlspecialchars($error); ?>
      <button class="alert-dismiss" type="button">&#x2715;</button>
    </div>
    <?php endif; ?>

    <div class="security-card-grid fade-up fade-up-delay-1" style="max-width:800px;">

      <!-- Left: form -->
      <div class="reg-card">

        <form method="POST" action="register_step3.php" novalidate id="step3Form">

          <!-- Password -->
          <div class="form-group">
            <label class="form-label" for="access_key">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              Password
            </label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input
                type="password"
                id="access_key"
                name="access_key"
                class="form-control"
                placeholder="Min. 12 characters"
                required
                autocomplete="new-password"
              >
              <button type="button" class="toggle-pass" aria-label="Toggle password visibility">
                <span class="icon-eye" style="display:block;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </span>
                <span class="icon-eye-off" style="display:none;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                </span>
              </button>
            </div>

            <!-- Strength meter -->
            <div class="strength-wrap" style="margin-top:0.5rem;">
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill" style="width:0%;"></div>
              </div>
              <span class="strength-text" id="strengthText"></span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group">
            <label class="form-label" for="confirm_authorization">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              Confirm Password
            </label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input
                type="password"
                id="confirm_authorization"
                name="confirm_authorization"
                class="form-control"
                placeholder="Re-enter your password"
                required
                autocomplete="new-password"
              >
              <button type="button" class="toggle-pass" aria-label="Toggle confirm password visibility">
                <span class="icon-eye" style="display:block;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </span>
                <span class="icon-eye-off" style="display:none;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                </span>
              </button>
            </div>
            <p id="matchHint" class="form-hint" style="display:none; margin-top:0.35rem;"></p>
          </div>

          <div class="reg-nav" style="margin-top:1.5rem;">
            <a href="register_step2.php" class="btn-back">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
              Go Back
            </a>
            <button type="submit" class="btn-next" id="submitBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
              </svg>
              Complete Registration
            </button>
          </div>

        </form>
      </div><!-- /.reg-card -->

      <!-- Right: info panels -->
      <div style="display:flex; flex-direction:column; gap:1rem;">

        <div class="security-info-card">
          <h4>End-to-End Protection</h4>
          <p>
            Your account is protected with bcrypt hashing.
            We never store raw passwords — only a secure hash.
          </p>
        </div>

        <div class="security-info-card">
          <h4 style="margin-bottom:0.75rem;">Password Requirements</h4>
          <ul class="req-list">
            <li class="unmet" id="req-min">
              <svg class="req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
              </svg>
              Minimum 12 characters
            </li>
            <li class="unmet" id="req-alpha">
              <svg class="req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
              </svg>
              Letters &amp; numbers (alphanumeric)
            </li>
            <li class="unmet" id="req-special">
              <svg class="req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
              </svg>
              Special characters (!%&amp;@#)
            </li>
          </ul>
        </div>

        <div style="
          background: linear-gradient(135deg, var(--navy-mid), var(--navy));
          border-radius: var(--radius-lg); padding: 1.25rem;
          display: flex; flex-direction: column; align-items: center;
          justify-content: center; min-height: 100px; text-align: center;
        ">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
               stroke="rgba(186,230,253,0.7)" stroke-width="1.5">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
            <polyline points="9 12 11 14 15 10"/>
          </svg>
          <p style="font-size:0.75rem; color:rgba(186,230,253,0.8); margin-top:0.5rem; line-height:1.4;">
            Final Step — your account<br>will be saved on the next page.
          </p>
        </div>

      </div><!-- /right column -->

    </div><!-- /.security-card-grid -->

  </main>

</div><!-- /.reg-page -->

<script src="/main/assets/js/script.js"></script>
<script>
(function () {
  'use strict';

  var passInput    = document.getElementById('access_key');
  var confirmInput = document.getElementById('confirm_authorization');
  var matchHint    = document.getElementById('matchHint');
  var submitBtn    = document.getElementById('submitBtn');

  function checkMatch() {
    if (!confirmInput.value) { matchHint.style.display = 'none'; return; }
    matchHint.style.display = 'block';
    if (passInput.value === confirmInput.value) {
      matchHint.textContent = '✓ Passwords match.';
      matchHint.style.color = 'var(--success)';
    } else {
      matchHint.textContent = '✗ Passwords do not match yet.';
      matchHint.style.color = 'var(--danger)';
    }
  }

  if (passInput)    passInput.addEventListener('input',    checkMatch);
  if (confirmInput) confirmInput.addEventListener('input', checkMatch);

  var form = document.getElementById('step3Form');
  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.disabled  = true;
      submitBtn.innerHTML = 'Saving&hellip;';
    });
  }
})();
</script>
</body>
</html>