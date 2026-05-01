<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$status = 'invalid';
$message = 'Invalid or expired verification link.';

$token = trim($_GET['token'] ?? '');

function getEmailVerificationColumns(PDO $pdo): array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM email_verifications');
    $columns = [];
    foreach ($stmt as $row) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function getVerificationIdColumn(array $columns): string
{
    if (in_array('id', $columns, true)) {
        return 'id';
    }
    if (in_array('verification_id', $columns, true)) {
        return 'verification_id';
    }
    return '';
}

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
    try {
        $pdo->beginTransaction();

        $columns = getEmailVerificationColumns($pdo);
        $hasEmailColumn = in_array('email', $columns, true);
        $idColumn = getVerificationIdColumn($columns);
        if ($idColumn === '') {
            throw new RuntimeException('email_verifications table missing id/verification_id column.');
        }

        $selectColumns = $hasEmailColumn
            ? "{$idColumn} AS row_id, user_id, email, expires_at"
            : "{$idColumn} AS row_id, user_id, expires_at";
        $stmt = $pdo->prepare(
            "SELECT {$selectColumns}
             FROM email_verifications
             WHERE token = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $verification = $stmt->fetch();

        if ($verification) {
            $isExpired = strtotime((string) $verification['expires_at']) < time();
            if (!$isExpired) {
                $updateStmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
                $updateStmt->execute([(int) $verification['user_id']]);

                $deleteStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ?");
                $deleteStmt->execute([(int) $verification['row_id']]);

                $pdo->commit();
                $status = 'success';
                $message = 'Your email has been verified successfully. You can now log in.';
            } else {
                $deleteStmt = $pdo->prepare("DELETE FROM email_verifications WHERE {$idColumn} = ?");
                $deleteStmt->execute([(int) $verification['row_id']]);
                $pdo->commit();
                $status = 'expired';
                $message = 'This verification link has expired. Please contact support.';
            }
        } else {
            $pdo->rollBack();
        }
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Email verification failed: ' . $throwable->getMessage());
        $status = 'error';
        $message = 'We could not process this verification link right now.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Email Verification</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
</head>
<body>
  <div class="login-page">
    <main class="login-panel" style="max-width:560px;margin:auto;">
      <h2 class="panel-title">Email Verification</h2>
      <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?> fade-up">
        <?php echo htmlspecialchars($message); ?>
      </div>
      <a href="login.php" class="btn btn-primary" style="text-decoration:none;">Go to Login</a>
    </main>
  </div>
</body>
</html>
