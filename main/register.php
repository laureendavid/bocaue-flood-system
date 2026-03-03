<?php
/* ================================================================
   register.php — Bocaue Community Flood Information System
   Place this file in: C:\xampp\htdocs\soe\resident\register.php
   ================================================================ */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false, // set true on production HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ../resident/index.php');
    exit;
}

require_once '../config/db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';
    $contact    = trim($_POST['contact'] ?? '');
    $barangay   = trim($_POST['barangay'] ?? '');

    // Validation
    if (!$first_name || !$last_name || !$email || !$password || !$confirm || !$contact || !$barangay) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email or phone already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $check->bind_param('ss', $email, $contact);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Email or phone number is already registered.';
        } else {
            $check->close();

            $full_name  = $first_name . ' ' . $last_name;
            $hashed     = password_hash($password, PASSWORD_DEFAULT);
            $role       = 'Resident';
            $is_verified = 0;

            // Generate verification token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Get barangay_id from barangay name
            $getBarangay = $conn->prepare("SELECT barangay_id FROM barangays WHERE barangay_name = ? LIMIT 1");
            $getBarangay->bind_param("s", $barangay);
            $getBarangay->execute();
            $result = $getBarangay->get_result();
            $row = $result->fetch_assoc();
            $barangay_id = $row['barangay_id'];
            $getBarangay->close();

            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password, role, phone, barangay_id, is_verified)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssis", $full_name, $email, $hashed, $role, $contact, $barangay_id, $is_verified);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $stmt->close();

                // Insert token into email_verifications table
                $stmt2 = $conn->prepare("
                    INSERT INTO email_verifications (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt2->bind_param("iss", $user_id, $token, $expires);
                $stmt2->execute();
                $stmt2->close();

                // ===== SEND VERIFICATION EMAIL =====
                $verify_link = "https://bocauefloodinformation.infinityfreeapp.com/soe/main/verify.php?token=" . $token;

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'bocauefloodinformation@gmail.com';      // replace with your email
                    $mail->Password   = 'ofvgybsduabhablr';   // Gmail App Password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('bocauefloodinformation@gmail.com', 'Bocaue Flood Info System');
                    $mail->addAddress($email, $full_name);
                    $mail->Subject = 'Verify your account';
                    $mail->isHTML(true);
                    $mail->Body = "
                        <h2>Welcome, $full_name!</h2>
                        <p>Click the link below to verify your account:</p>
                        <a href='$verify_link'>$verify_link</a>
                        <p>This link expires in 24 hours.</p>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    // If email fails, still allow registration but notify user
                    $error = "Account created, but verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

                $success = true;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up — Bocaue Community Flood Information System</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/register.css" />
</head>
<body>

<div class="page">

  <?php if ($success): ?>
  <!-- ==================== SUCCESS STEP ==================== -->
  <div class="step active" id="step-2">
    <div class="left-panel">
      <div class="blob-mid"></div>
      <div class="left-content">
        <div class="success-circle">
          <span class="material-symbols-outlined">how_to_reg</span>
        </div>
        <h1 class="system-name">Bocaue Community<br/><span>Flood Information</span><br/>System</h1>
      </div>
      <svg class="blob-divider" viewBox="0 0 120 800" preserveAspectRatio="none">
        <path d="M120 0 C80 100, 40 200, 70 300 C100 400, 30 500, 60 600 C90 700, 50 750, 120 800 L120 0 Z" fill="white"/>
      </svg>
    </div>
    <div class="right-panel">
      <div class="right-blob-1"></div>
      <div class="right-blob-2"></div>
      <div class="form-box" style="text-align:center;">
        <h2 class="success-title">Account Created!</h2>
        <p class="success-desc">
          We've sent a verification link to your email.<br/>
          Please check your inbox and click the link to activate your account before logging in.
        </p>
        <button class="btn-primary" onclick="window.location.href='../main/login.php'">
          Go to Login
        </button>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- ==================== SIGN UP STEP ==================== -->
  <div class="step active" id="step-1">

    <div class="left-panel">
      <div class="blob-mid"></div>
      <div class="left-content">
        <svg class="shield-svg" viewBox="0 0 200 230" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M100 10 L180 45 L180 120 C180 170 100 215 100 215 C100 215 20 170 20 120 L20 45 Z" fill="white" stroke="#1a3a5c" stroke-width="6"/>
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
        <h1 class="system-name">Bocaue Community<br/><span>Flood Information</span><br/>System</h1>
      </div>
      <svg class="blob-divider" viewBox="0 0 120 800" preserveAspectRatio="none">
        <path d="M120 0 C80 100, 40 200, 70 300 C100 400, 30 500, 60 600 C90 700, 50 750, 120 800 L120 0 Z" fill="white"/>
      </svg>
    </div>

    <div class="right-panel">
      <div class="right-blob-1"></div>
      <div class="right-blob-2"></div>
      <div class="form-box">

        <h2 class="step-title">Sign Up</h2>

        <!-- Error -->
        <div id="reg-error" class="alert-error" style="display:<?= $error ? 'flex' : 'none' ?>">
          <span class="material-symbols-outlined">error</span>
          <span class="msg"><?= htmlspecialchars($error) ?></span>
        </div>

        <form id="register-form" method="POST" action="">

          <!-- Name -->
          <div class="name-row">
            <div class="input-wrap">
              <span class="material-symbols-outlined">person</span>
              <input class="form-input" type="text" name="first_name" placeholder="First Name"
                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" />
            </div>
            <div class="input-wrap">
              <span class="material-symbols-outlined">person</span>
              <input class="form-input" type="text" name="last_name" placeholder="Last Name"
                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" />
            </div>
          </div>

          <!-- Email -->
          <div class="form-group">
            <div class="input-wrap">
              <span class="material-symbols-outlined">mail</span>
              <input class="form-input" type="email" name="email" placeholder="Email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <div class="input-wrap">
              <span class="material-symbols-outlined">lock</span>
              <input class="form-input" type="password" name="password" id="reg-password"
                placeholder="Password" oninput="updateStrength(this.value)" />
              <button class="toggle-pw" type="button" onclick="togglePw('reg-password', this)">
                <span class="material-symbols-outlined">visibility_off</span>
              </button>
            </div>
            <div class="pw-strength">
              <div class="pw-bar" id="bar1"></div>
              <div class="pw-bar" id="bar2"></div>
              <div class="pw-bar" id="bar3"></div>
              <div class="pw-bar" id="bar4"></div>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group">
            <div class="input-wrap">
              <span class="material-symbols-outlined">lock</span>
              <input class="form-input" type="password" name="confirm" id="reg-confirm"
                placeholder="Confirm Password" />
              <button class="toggle-pw" type="button" onclick="togglePw('reg-confirm', this)">
                <span class="material-symbols-outlined">visibility_off</span>
              </button>
            </div>
          </div>

          <!-- Contact -->
          <div class="form-group">
            <div class="input-wrap">
              <span class="material-symbols-outlined">phone</span>
              <input class="form-input" type="tel" name="contact" placeholder="Contact Number"
                value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" />
            </div>
          </div>

          <!-- Barangay -->
          <div class="form-group">
            <div class="select-wrap">
              <span class="material-symbols-outlined select-icon">location_city</span>
              <select class="form-select" name="barangay" id="barangay" onchange="updateMap()">
                <option value="">Select Your Barangay</option>
                <option value="antipona"   <?= (($_POST['barangay'] ?? '') === 'antipona')   ? 'selected' : '' ?>>Antipona</option>
                <option value="bagumbayan" <?= (($_POST['barangay'] ?? '') === 'bagumbayan') ? 'selected' : '' ?>>Bagumbayan</option>
                <option value="bambang"    <?= (($_POST['barangay'] ?? '') === 'bambang')    ? 'selected' : '' ?>>Bambang</option>
                <option value="batia"      <?= (($_POST['barangay'] ?? '') === 'batia')      ? 'selected' : '' ?>>Batia</option>
                <option value="binang1"    <?= (($_POST['barangay'] ?? '') === 'binang1')    ? 'selected' : '' ?>>Biñang 1st</option>
                <option value="binang2"    <?= (($_POST['barangay'] ?? '') === 'binang2')    ? 'selected' : '' ?>>Biñang 2nd</option>
                <option value="bolacan"    <?= (($_POST['barangay'] ?? '') === 'bolacan')    ? 'selected' : '' ?>>Bolacan</option>
                <option value="bundukan"   <?= (($_POST['barangay'] ?? '') === 'bundukan')   ? 'selected' : '' ?>>Bundukan</option>
                <option value="bunlo"      <?= (($_POST['barangay'] ?? '') === 'bunlo')      ? 'selected' : '' ?>>Bunlo</option>
                <option value="caingin"    <?= (($_POST['barangay'] ?? '') === 'caingin')    ? 'selected' : '' ?>>Caingin</option>
                <option value="duhat"      <?= (($_POST['barangay'] ?? '') === 'duhat')      ? 'selected' : '' ?>>Duhat</option>
                <option value="igulot"     <?= (($_POST['barangay'] ?? '') === 'igulot')     ? 'selected' : '' ?>>Igulot</option>
                <option value="lolomboy"   <?= (($_POST['barangay'] ?? '') === 'lolomboy')   ? 'selected' : '' ?>>Lolomboy</option>
                <option value="poblacion"  <?= (($_POST['barangay'] ?? '') === 'poblacion')  ? 'selected' : '' ?>>Poblacion</option>
                <option value="sulucan"    <?= (($_POST['barangay'] ?? '') === 'sulucan')    ? 'selected' : '' ?>>Sulucan</option>
                <option value="taal"       <?= (($_POST['barangay'] ?? '') === 'taal')       ? 'selected' : '' ?>>Taal</option>
                <option value="tambobong"  <?= (($_POST['barangay'] ?? '') === 'tambobong')  ? 'selected' : '' ?>>Tambobong</option>
                <option value="turo"       <?= (($_POST['barangay'] ?? '') === 'turo')       ? 'selected' : '' ?>>Turo</option>
                <option value="wakas"      <?= (($_POST['barangay'] ?? '') === 'wakas')      ? 'selected' : '' ?>>Wakas</option>
              </select>
              <span class="material-symbols-outlined chevron">expand_more</span>
            </div>
          </div>

          <!-- Mini Map -->
          <div class="map-preview">
            <div id="mini-map"></div>
          </div>

          <button class="btn-primary" type="submit">Create Account</button>

        </form>

        <div class="login-row">
          Already have an account? <a href="../main/login.php">Login</a>
        </div>

      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="assets/js/register.js"></script>
</body>
</html>