<?php
/* ================================================================
   login.php — Bocaue Community Flood Information System
   Place this file in: C:\xampp\htdocs\soe\main\login.php
   ================================================================ */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// If already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
  switch ($_SESSION['role']) {
    case 'LGU':
      header('Location: ../lgu/index.php');
      break;
    case 'Rescuer':
      header('Location: ../rescuer/index.php');
      break;
    case 'Resident':
      header('Location: ../resident/index.php');
      break;
    default:
      header('Location: ../main/login.php');
  }
  exit;
}

// ===== DB CONNECTION =====
require_once '../config/db.php';

$error = '';

// Show timeout message if redirected due to inactivity
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Fetch user from DB
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, is_verified FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {

            // Check if account is verified
            if ($user['is_verified'] != 1) {
                $error = 'Please verify your email before logging in.';
            } else {
                // Login success — set session
                $_SESSION['user_id']       = $user['user_id'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['email']         = $user['email'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();

                // Redirect based on role
                switch ($user['role']) {
                    case 'LGU':
                        header('Location: ../lgu/index.php');
                        break;
                    case 'Rescuer':
                        header('Location: ../rescuer/index.php');
                        break;
                    case 'Resident':
                        header('Location: ../resident/index.php');
                        break;
                    default:
                        header('Location: ../main/login.php');
                }
                exit; // Stop execution after redirect
            }

        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
} // <-- this closes the POST check properly

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Bocaue Community Flood Information System</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/login.css" />
</head>
<body>

<div class="page">

  <!-- ===== LEFT PANEL ===== -->
  <div class="left-panel">
    <div class="blob-mid"></div>

    <div class="left-content">
      <svg class="shield-svg" viewBox="0 0 200 230" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 10 L180 45 L180 120 C180 170 100 215 100 215 C100 215 20 170 20 120 L20 45 Z"
              fill="white" stroke="#1a3a5c" stroke-width="6"/>
        <path d="M35 155 Q55 145 75 155 Q95 165 115 155 Q135 145 165 155" stroke="#1a5276" stroke-width="5" fill="none" stroke-linecap="round"/>
        <path d="M35 168 Q55 158 75 168 Q95 178 115 168 Q135 158 165 168" stroke="#1a5276" stroke-width="4" fill="none" stroke-linecap="round"/>
        <circle cx="75" cy="115" r="12" fill="#1a3a5c"/>
        <path d="M55 138 Q75 128 95 138" stroke="#1a3a5c" stroke-width="8" fill="none" stroke-linecap="round"/>
        <circle cx="125" cy="115" r="12" fill="#1a3a5c"/>
        <path d="M105 138 Q125 128 145 138" stroke="#1a3a5c" stroke-width="8" fill="none" stroke-linecap="round"/>
        <circle cx="100" cy="100" r="11" fill="#1a3a5c"/>
        <path d="M82 126 Q100 116 118 126" stroke="#1a3a5c" stroke-width="8" fill="none" stroke-linecap="round"/>
        <circle cx="100" cy="52" r="10" fill="#dc2626"/>
        <path d="M100 62 L100 75" stroke="#dc2626" stroke-width="4" stroke-linecap="round"/>
        <path d="M70 80 L100 55 L130 80" stroke="#dc2626" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>

      <h1 class="system-name">
        Bocaue Community<br/>
        <span>Flood Information</span><br/>
        System
      </h1>
    </div>

    <svg class="blob-divider" viewBox="0 0 120 800" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M120 0 C80 100, 40 200, 70 300 C100 400, 30 500, 60 600 C90 700, 50 750, 120 800 L120 0 Z" fill="white"/>
    </svg>
  </div>

  <!-- ===== RIGHT PANEL ===== -->
  <div class="right-panel">
    <div class="right-blob-1"></div>
    <div class="right-blob-2"></div>

    <div class="form-box">
      <p class="form-tagline">Flood Awareness.<br/>Community Safety.<br/>Unified Response.</p>

      <!-- Timeout message -->
      <?php if ($timeout): ?>
        <div class="alert-error" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
          <span class="material-symbols-outlined">schedule</span>
          Your session expired due to inactivity. Please log in again.
        </div>
      <?php endif; ?>

      <!-- Error message from PHP -->
      <?php if (!empty($error)): ?>
        <div class="alert-error">
          <span class="material-symbols-outlined">error</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form id="login-form" method="POST" action="">

        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <div class="input-wrap">
            <span class="material-symbols-outlined">mail</span>
            <input class="form-input" type="email" id="email" name="email"
              placeholder="you@example.com" autocomplete="email"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <span class="material-symbols-outlined">lock</span>
            <input class="form-input" type="password" id="password" name="password"
              placeholder="••••••••" autocomplete="current-password" />
            <button class="toggle-pw" id="toggle-pw" type="button" aria-label="Toggle password visibility">
              <span class="material-symbols-outlined" id="pw-icon">visibility_off</span>
            </button>
          </div>
          <div class="forgot-row">
            <span class="forgot-link">Forgot Password?</span>
          </div>
        </div>

        <!-- Login Button -->
        <button class="btn-login" id="btn-login" type="submit">Login</button>

      </form>

      <!-- Divider -->
      <div class="divider">
        <div class="divider-line"></div>
      </div>

      <!-- Sign up -->
      <div class="signup-row">
        Don't have an account? <a href="../main/register.php">Sign Up</a>
      </div>
    </div>
  </div>

</div>

<script src="assets/js/login.js"></script>
</body>
</html>