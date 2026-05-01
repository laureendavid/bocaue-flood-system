<?php
session_start();

if (empty($_SESSION['pending_password_user_id']) || empty($_SESSION['pending_password_role'])) {
    header('Location: login.php');
    exit;
}

$errorMessages = [
    'invalid' => 'Please provide a valid new password.',
    'mismatch' => 'Passwords do not match.',
    'server' => 'Unable to update password right now.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Set New Password</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="login-page">
    <main class="login-panel">
      <h2 class="panel-title">Set New Password</h2>
      <p class="panel-subtitle">Create a password to continue to your dashboard.</p>
      <?php if ($error !== ''): ?>
        <div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="POST" action="../backend/set_new_password.php" novalidate>
        <div class="form-group">
          <label class="form-label" for="password">New Password</label>
          <input type="password" id="password" name="password" class="form-control" required minlength="12">
        </div>
        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirm New Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="12">
        </div>
        <button type="submit" class="btn btn-primary">Save Password</button>
      </form>
    </main>
  </div>
</body>
</html>
