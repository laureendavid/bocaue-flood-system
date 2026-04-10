<?php
/**
 * register_step1.php — Step 1: Identity Verification
 * Collects mobile number and validates a 6-digit OTP code.
 *
 * SESSION KEYS SET HERE:
 *   $_SESSION['reg_mobile']   — e.g. "+639171234567"
 *   $_SESSION['reg_step1_ok'] — true after OTP passes
 */

session_start();

$error   = '';
$success = '';

// ── Handle "Request a New Code" ────────────────────────────────────────────
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    /*
     * TODO (production): Generate OTP, store in session, send via SMS gateway.
     *   $_SESSION['otp_code']    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
     *   $_SESSION['otp_expires'] = time() + 300;
     *   // call your SMS API here
     */
    $success = 'A new OTP code has been sent to your mobile number. (Demo OTP: 123456)';
}

// ── Sticky mobile value ────────────────────────────────────────────────────
$prev_mobile = '';

// ── POST handler ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mobile = trim($_POST['mobile'] ?? '');
    $otp    = trim($_POST['otp_combined'] ?? '');

    // Preserve sticky — strip leading zero if user typed "09XX…"
    $prev_mobile = htmlspecialchars(ltrim($mobile, '0'));

    if (empty($mobile)) {
        $error = 'Please enter your mobile number.';
    } elseif (!preg_match('/^9\d{9}$/', $mobile)) {
        $error = 'Enter a valid Philippine mobile number starting with 9 (e.g., 917 000 0000).';
    } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
        $error = 'Please fill in all 6 digits of the OTP code.';
    } else {
        /*
         * OTP VERIFICATION
         * Demo: hardcoded "123456" is accepted.
         * Production: compare against $_SESSION['otp_code'] and check expiry.
         */
        $otp_is_valid = ($otp === '123456');

        if ($otp_is_valid) {
            session_regenerate_id(true);

            // ── IMPORTANT: key is reg_mobile ─────────────────────────────
            // registerComplete.php reads this same key.
            $_SESSION['reg_mobile']   = '+63' . $mobile;
            $_SESSION['reg_step1_ok'] = true;

            unset($_SESSION['otp_code'], $_SESSION['otp_expires']);

            header('Location: register_step2.php');
            exit;
        } else {
            $error = 'Incorrect OTP code. Please try again or request a new code.';
        }
    }
}

// ── Restore OTP digits on failed submit ────────────────────────────────────
$prev_otp = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw      = trim($_POST['otp_combined'] ?? '');
    $prev_otp = preg_replace('/\D/', '', $raw);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS — Step 1: Identity Verification</title>
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

    <p class="sidebar-section-label">Registration<br>Verify your Number</p>

    <ul class="sidebar-steps">
      <li class="step-item active">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
          </svg>
        </div>
        <span class="step-label">Verify</span>
      </li>
      <li class="step-item pending">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
        </div>
        <span class="step-label">Profile</span>
      </li>
      <li class="step-item pending">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </div>
        <span class="step-label">Security</span>
      </li>
      <li class="step-item pending">
        <div class="step-dot">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <span class="step-label">Complete</span>
      </li>
    </ul>
  </aside>

  <!-- ══ Main Content ══ -->
  <main class="reg-main">

    <div class="reg-header">
      <p class="step-tag">Step 01 of 03</p>
      <h1 class="reg-title fade-up">Identity Verification</h1>

      <div class="progress-bar-wrap fade-up fade-up-delay-1">
        <div class="progress-track">
          <div class="progress-fill" style="width: 33%;"></div>
        </div>
        <span class="progress-pct">33% Complete</span>
      </div>
    </div>

    <!-- Error alert -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger fade-up" style="max-width:720px;" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="alert-dismiss" aria-label="Dismiss">&#x2715;</button>
    </div>
    <?php endif; ?>

    <!-- Success alert -->
    <?php if (!empty($success)): ?>
    <div class="alert alert-success fade-up" style="max-width:720px;" role="status">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <?php echo htmlspecialchars($success); ?>
      <button type="button" class="alert-dismiss" aria-label="Dismiss">&#x2715;</button>
    </div>
    <?php endif; ?>

    <div class="step1-layout fade-up fade-up-delay-1">

      <!-- Left: form -->
      <div class="reg-card">
        <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.75rem; line-height:1.7;">
          To ensure the integrity of the Bocaue Flood Information System, we require a verified
          mobile number. This helps us confirm you are a real resident of Bocaue.
        </p>

        <form method="POST" action="register_step1.php" id="step1Form" novalidate>

          <!-- Mobile Number -->
          <div class="form-group">
            <label class="form-label" for="mobile">Mobile Number</label>
            <div class="phone-wrap">
              <span class="phone-prefix" aria-hidden="true">+63</span>
              <input
                type="tel"
                id="mobile"
                name="mobile"
                class="phone-input"
                placeholder="9XX XXX XXXX"
                value="<?php echo $prev_mobile; ?>"
                maxlength="10"
                inputmode="numeric"
                autocomplete="tel-national"
                required
                aria-label="Mobile number without country code"
              >
            </div>
            <p class="form-hint">Enter 10 digits starting with 9 — e.g., 917 000 0000</p>
          </div>

          <!-- OTP -->
          <div class="form-group">
            <label class="form-label" id="otp-label">Enter 6-Digit OTP Code</label>
            <div class="otp-group" role="group" aria-labelledby="otp-label">
              <?php
                for ($i = 1; $i <= 6; $i++):
                    $digit = (strlen($prev_otp) >= $i)
                        ? htmlspecialchars($prev_otp[$i - 1])
                        : '';
              ?>
              <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
                pattern="[0-9]"
                value="<?php echo $digit; ?>"
                aria-label="OTP digit <?php echo $i; ?>"
                autocomplete="one-time-code"
              >
              <?php endfor; ?>
            </div>

            <p class="form-hint" style="margin-top:0.4rem;">
              <?php if (!empty($error) && strpos($error, 'OTP') !== false): ?>
                <span style="color:var(--danger); font-weight:600;">
                  Hint (demo only): use <strong>123456</strong>.
                </span>
              <?php else: ?>
                Check your SMS for the 6-digit verification code.
              <?php endif; ?>
            </p>

            <input type="hidden" name="otp_combined" id="otp_combined">
          </div>

          <button type="submit" class="btn-next" style="margin-top:0.5rem;" id="verifyBtn">
            Verify and Continue
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </button>

        </form>

        <div style="margin-top:1.25rem; text-align:center;">
          <a
            href="register_step1.php?resend=1"
            id="resendLink"
            style="font-size:0.8rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--teal);"
          >
            Request a New Code
          </a>
        </div>
      </div>

      <!-- Right: info card -->
      <div class="secure-portal-card">
        <div class="sp-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
          </svg>
          Secure Portal
        </div>
        <h4>Your data is protected.</h4>
        <p>
          Your data is encrypted using security protocols.
          We only use your number for emergency flood notifications and identity verification.
        </p>
        <div class="sp-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            <circle cx="12" cy="16" r="1.5" fill="currentColor"/>
          </svg>
        </div>
      </div>

    </div><!-- /.step1-layout -->

    <div class="reg-nav" style="max-width:720px;">
      <a href="login.php" class="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Back to Login
      </a>
    </div>

  </main>

</div><!-- /.reg-page -->

<script src="/main/assets/js/script.js"></script>

<script>
(function () {
  'use strict';

  var form      = document.getElementById('step1Form');
  var mobileIn  = document.getElementById('mobile');
  var otpBoxes  = document.querySelectorAll('.otp-box');
  var hiddenOtp = document.getElementById('otp_combined');

  if (!form) return;

  if (mobileIn) {
    mobileIn.addEventListener('input', function () {
      var pos   = this.selectionStart;
      var clean = this.value.replace(/\D/g, '').slice(0, 10);
      this.value = clean;
      try { this.setSelectionRange(pos, pos); } catch (e) {}
    });
  }

  form.addEventListener('submit', function (e) {
    var mobile = mobileIn ? mobileIn.value.trim() : '';
    var digits = '';
    otpBoxes.forEach(function (b) { digits += b.value.trim(); });

    if (hiddenOtp) hiddenOtp.value = digits;

    var errors = [];
    if (!/^9\d{9}$/.test(mobile)) {
      errors.push('Please enter a valid 10-digit mobile number starting with 9.');
    }
    if (digits.length !== 6 || !/^\d{6}$/.test(digits)) {
      errors.push('Please enter all 6 digits of your OTP code.');
    }

    if (errors.length === 0) return;

    e.preventDefault();

    var prev = form.querySelector('.js-alert');
    if (prev) prev.remove();

    var alertEl = document.createElement('div');
    alertEl.className = 'alert alert-danger js-alert';
    alertEl.setAttribute('role', 'alert');
    alertEl.innerHTML =
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
        '<circle cx="12" cy="12" r="10"/>' +
        '<line x1="12" y1="8" x2="12" y2="12"/>' +
        '<line x1="12" y1="16" x2="12.01" y2="16"/>' +
      '</svg>' +
      errors.join(' ') +
      '<button type="button" class="alert-dismiss" aria-label="Dismiss">&#x2715;</button>';

    form.insertBefore(alertEl, form.firstChild);
    alertEl.querySelector('.alert-dismiss').addEventListener('click', function () {
      alertEl.remove();
    });
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  var resendLink = document.getElementById('resendLink');
  if (resendLink) {
    resendLink.addEventListener('click', function () {
      var el = this;
      el.style.pointerEvents = 'none';
      el.style.opacity       = '0.5';
      setTimeout(function () {
        el.style.pointerEvents = '';
        el.style.opacity       = '';
      }, 4000);
    });
  }

}());
</script>

</body>
</html>