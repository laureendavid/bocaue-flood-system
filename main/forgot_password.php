<?php

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

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $roleRedirects = [
        'LGU' => '../lgu/index.php',
        'Rescuer' => '../rescuer/index.php',
        'Resident' => '../resident/index.php',
    ];
    header('Location: ' . ($roleRedirects[$_SESSION['role']] ?? 'login.php'));
    exit;
}

$errorMessages = [
    'invalid_email' => 'Please enter a valid email address.',
    'not_found' => 'No account was found with that email address.',
    'not_verified' => 'This account is not verified yet. Contact your administrator.',
    'server' => 'A server error occurred. Please try again later.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';
$emailValue = htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Forgot Password</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="login-page">
    <main class="login-panel">
      <h2 class="panel-title">Forgot Password</h2>
      <p class="panel-subtitle">Enter your registered email address. You will be redirected to set a new password.</p>

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="../backend/forgot_password.php" novalidate>
        <div class="form-group">
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
              value="<?php echo $emailValue; ?>"
              required
              autocomplete="email"
            >
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Continue</button>
      </form>

      <p class="legal-note" style="margin-top: 1.25rem;">
        <a href="login.php" class="forgot-link">Back to Login</a>
      </p>
    </main>
  </div>
  <script src="assets/js/script.js"></script>
</body>
</html>
