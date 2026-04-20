<?php
/**
 * main/login.php
 * Login page — shows the form and any error messages passed via GET.
 */

// ── Session setup ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// If already logged in, send to their dashboard right away
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $roleRedirects = [
        'LGU'      => '../lgu/index.php',
        'Rescuer'  => '../rescuer/index.php',
        'Resident' => '../resident/index.php',
    ];
    $redirect = $roleRedirects[$_SESSION['role']] ?? '../main/login.php';
    header("Location: $redirect");
    exit;
}

// ── Map GET error codes to human-readable messages ─────────────────────────
$errorMessages = [
    'empty_fields'        => 'Please fill in both email and password.',
    'invalid_credentials' => 'Incorrect email or password.',
    'not_verified'        => 'Your account has not been verified yet.',
    'server'              => 'A server error occurred. Please try again later.',
    'unknown_role'        => 'Your account role is not recognized. Contact support.',
    'timeout'             => 'Your session has expired. Please log in again.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue_FIS - Login</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="login-page">

  <!-- ══ Left Hero Panel ══ -->
  <aside class="login-hero">

    <div class="hero-brand">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
        </svg>
      </div>
      <span class="brand-name">Bocaue Flood Information System</span>
    </div>

    <div class="hero-content">
      <p class="hero-eyebrow">The BFIS System</p>
      <h1 class="hero-title">Authoritative<br>Civic Protection.</h1>
      <p class="hero-desc">
        Access the centralized flood monitoring and response infrastructure
        for the Municipality of Bocaue. Real-time data, predictive modeling,
        and secure communication for authorized personnel.
      </p>

      <div class="hero-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span>Secure Infrastructure &mdash; End-to-end encrypted monitoring protocols.</span>
      </div>
    </div>

    <div class="hero-map">
      <div class="hero-map-grid">
        <div class="map-pulse"></div>
      </div>
    </div>

  </aside>

  <!-- ══ Right Login Panel ══ -->
  <main class="login-panel">

    <h2 class="panel-title">Login</h2>
    <p class="panel-subtitle">Please enter your credentials to access the Dashboard.</p>

    <!-- Error alert -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger fade-up">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="alert-dismiss" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">&#x2715;</button>
    </div>
    <?php endif; ?>

    <!--
      FIXED: action now points to ../backend/login.php
      FIXED: input uses type="email" and name="email" to match the DB column
    -->
    <form method="POST" action="../backend/login.php" novalidate>

      <!-- Email -->
      <div class="form-group fade-up fade-up-delay-1">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            placeholder="juan@example.ph"
            value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>"
            required
            autocomplete="email"
          >
        </div>
      </div>

      <!-- Password -->
      <div class="form-group fade-up fade-up-delay-2">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            required
            autocomplete="current-password"
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

      <!-- Remember me & Forgot password -->
      <div class="check-row fade-up fade-up-delay-2">
        <label class="check-label">
          <input type="checkbox" name="remember_me"> Remember Me
        </label>
        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn btn-primary fade-up fade-up-delay-3">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/>
          <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Secure Login
      </button>

    </form>

    <div class="divider fade-up">New to the Bocaue Flood Information System?</div>

    <a href="register_step1.php" class="btn btn-outline fade-up fade-up-delay-1">
      Request Access
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="5" y1="12" x2="19" y2="12"/>
        <polyline points="12 5 19 12 12 19"/>
      </svg>
    </a>

    <p class="legal-note fade-up fade-up-delay-2">
      Authorized use only. All login attempts and session activities are logged
      for security compliance.
    </p>

  </main>

</div><!-- /.login-page -->

<script src="assets/js/script.js"></script>
</body>
</html>