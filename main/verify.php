<?php
/* ================================================================
   verify.php — Bocaue Community Flood Information System
   Place this file in: C:\xampp\htdocs\soe\main\verify.php
   ================================================================ */

session_start();
require_once '../config/db.php';

$status = '';
$msg = '';

// Get token from URL
$token = $_GET['token'] ?? '';

if ($token) {
  // Find the verification record
  $stmt = $conn->prepare("
        SELECT ev.user_id, ev.expires_at, u.is_verified
        FROM email_verifications ev
        JOIN users u ON ev.user_id = u.user_id
        WHERE ev.token = ? LIMIT 1
    ");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  if ($row) {
    if ($row['is_verified']) {
      $status = 'already';
      $msg = 'Your account is already verified. You can login now.';

    } elseif (strtotime($row['expires_at']) < time()) {
      $status = 'expired';
      $msg = 'This verification link has expired. Please register again.';

      // Delete the unverified user so they can register again
      // ON DELETE CASCADE handles email_verifications cleanup automatically
      $del = $conn->prepare("DELETE FROM users WHERE user_id = ? AND is_verified = 0");
      $del->bind_param("i", $row['user_id']);
      $del->execute();
      $del->close();

    } else {
      // Token is valid — mark user as verified
      $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
      $stmt->bind_param("i", $row['user_id']);
      $stmt->execute();
      $stmt->close();

      // Delete the used token
      $stmt = $conn->prepare("DELETE FROM email_verifications WHERE token = ?");
      $stmt->bind_param("s", $token);
      $stmt->execute();
      $stmt->close();

      $status = 'success';
      $msg = 'Your account has been successfully verified! You can now login.';
    }
  } else {
    // Token not found — already cleaned up by the 7-day cron in db.php
    $status = 'expired';
    $msg = 'This verification link has expired or is invalid. Please register again.';
  }
} else {
  $status = 'invalid';
  $msg = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Account — Bocaue Community Flood Information System</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/register.css" />
</head>

<body>
  <div class="page">

    <div class="step active" id="step-2">
      <div class="left-panel">
        <div class="blob-mid"></div>
        <div class="left-content">
          <div class="success-circle">
            <span class="material-symbols-outlined">
              <?= ($status === 'success' || $status === 'already') ? 'check_circle' : 'error' ?>
            </span>
          </div>
          <h1 class="system-name">Bocaue Community<br /><span>Flood Information</span><br />System</h1>
        </div>
        <svg class="blob-divider" viewBox="0 0 120 800" preserveAspectRatio="none">
          <path d="M120 0 C80 100, 40 200, 70 300 C100 400, 30 500, 60 600 C90 700, 50 750, 120 800 L120 0 Z"
            fill="white" />
        </svg>
      </div>

      <div class="right-panel">
        <div class="right-blob-1"></div>
        <div class="right-blob-2"></div>
        <div class="form-box" style="text-align:center;">
          <h2 class="success-title">
            <?php
            if ($status === 'success')
              echo 'Account Verified!';
            elseif ($status === 'already')
              echo 'Already Verified';
            else
              echo 'Verification Failed';
            ?>
          </h2>
          <p class="success-desc"><?= htmlspecialchars($msg) ?></p>

          <?php if ($status === 'success' || $status === 'already'): ?>
            <button class="btn-primary" onclick="window.location.href='../main/login.php'">Go to Login</button>
          <?php else: ?>
            <button class="btn-primary" onclick="window.location.href='../main/register.php'">Register Again</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</body>

</html>