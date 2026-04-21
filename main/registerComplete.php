<?php
/**
 * registerComplete.php — Step 4: DB insert + confirmation screen
 *
 * ALL FIXES APPLIED:
 *  1. Uses $pdo from config/db.php — no more hardcoded db_user/db_password
 *  2. Removed "username" from INSERT (column does not exist in users table)
 *  3. Session keys corrected:
 *       reg_mobile          (was reg_phone)
 *       reg_profile_picture (was reg_profile_pic)
 *       reg_valid_id_image  (was reg_valid_id)
 *  4. clearRegistrationSession() uses the correct key names
 *  5. role_id defaults to 3 (Resident) — all public registrants are residents
 *  6. is_verified set to 0 — admin must verify before user can log in
 */

session_start();

// ── Guard ──────────────────────────────────────────────────────────────────
if (empty($_SESSION['reg_step3_ok'])) {
  header('Location: register_step1.php');
  exit;
}

// ── DB connection via shared config ───────────────────────────────────────
// FIXED: use the $pdo already created in db.php — no duplicate credentials
require_once __DIR__ . '/../config/db.php'; // provides $pdo

// ── Helpers ────────────────────────────────────────────────────────────────

/** Returns true if a value already exists in a column. */
function isDuplicate(PDO $pdo, string $table, string $column, string $value): bool
{
  $stmt = $pdo->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
  $stmt->execute([$value]);
  return (bool) $stmt->fetch();
}

/**
 * Insert the complete user record from session data.
 * FIXED: "username" column removed — not in the DB schema.
 * FIXED: session keys corrected to match register_step1/step2.
 */
function insertUser(PDO $pdo): int
{
  $stmt = $pdo->prepare("
        INSERT INTO users
            (full_name, email, phone,
             valid_id_image, profile_picture,
             barangay_id, current_address,
             role_id, password, is_verified, created_at)
        VALUES
            (:full_name, :email, :phone,
             :valid_id, :profile_pic,
             :barangay_id, :address,
             :role_id, :password, :is_verified, NOW())
    ");

  $stmt->execute([
    ':full_name' => $_SESSION['reg_full_name'] ?? '',
    ':email' => $_SESSION['reg_email'] ?? '',
    // FIXED: key is reg_mobile (set in register_step1.php)
    ':phone' => $_SESSION['reg_mobile'] ?? null,
    // FIXED: key is reg_valid_id_image (set in register_step2.php)
    ':valid_id' => $_SESSION['reg_valid_id_image'] ?? null,
    // FIXED: key is reg_profile_picture (set in register_step2.php)
    ':profile_pic' => $_SESSION['reg_profile_picture'] ?? null,
    ':barangay_id' => $_SESSION['reg_barangay_id'] ?? null,
    ':address' => $_SESSION['reg_address'] ?? null,
    // role_id 3 = Resident (all public registrants are residents)
    ':role_id' => 3,
    ':password' => $_SESSION['reg_password_hashed'] ?? '',
    // is_verified = 0: account exists but cannot log in until admin verifies
    ':is_verified' => 0,
  ]);

  return (int) $pdo->lastInsertId();
}

/** Wipe all reg_* session keys after successful registration. */
function clearRegistrationSession(): void
{
  // FIXED: key names match what was actually stored in steps 1-3
  $keys = [
    'reg_mobile',           // step 1
    'reg_step1_ok',
    'reg_full_name',        // step 2
    'reg_email',
    'reg_dob',
    'reg_age',
    'reg_barangay_slug',
    'reg_barangay_id',
    'reg_address',
    'reg_lat',
    'reg_lng',
    'reg_profile_picture',  // step 2 — FIXED (was reg_profile_pic)
    'reg_valid_id_image',   // step 2 — FIXED (was reg_valid_id)
    'reg_step2_ok',
    'reg_password_hashed',  // step 3
    'reg_step3_ok',
  ];
  foreach ($keys as $key) {
    unset($_SESSION[$key]);
  }
}

// ── Snapshot display values BEFORE session is cleared ─────────────────────
$display_name = $_SESSION['reg_full_name'] ?? 'Personnel';
$display_email = $_SESSION['reg_email'] ?? '';
$display_phone = $_SESSION['reg_mobile'] ?? ''; // FIXED key
$reg_date = date('F j, Y \a\t g:i A');

// ── DB Insert (runs only once; refresh guard via reg_insert_done) ──────────
$dbError = '';

if (empty($_SESSION['reg_insert_done'])) {

  try {
    // Duplicate checks
    if (!empty($display_email) && isDuplicate($pdo, 'users', 'email', $display_email)) {
      $dbError = 'An account with this email address already exists.';

    } elseif (!empty($display_phone) && isDuplicate($pdo, 'users', 'phone', $display_phone)) {
      $dbError = 'An account with this phone number already exists.';

    } else {
      $pdo->beginTransaction();
      $newUserId = insertUser($pdo);
      $pdo->commit();

      $_SESSION['reg_insert_done'] = true;
      $_SESSION['reg_new_user_id'] = $newUserId;

      clearRegistrationSession();
    }

  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('[Bocaue FIS] Registration DB error: ' . $e->getMessage());
    $dbError = 'A server error occurred while saving your account. Please try again or contact support.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS — Registration Complete</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
  <style>
    .confirm-card {
      background: var(--white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      padding: 2rem 2.25rem;
      width: 100%;
      max-width: 440px;
      box-shadow: var(--shadow-md);
      margin-bottom: 1.75rem;
    }

    .confirm-card-title {
      font-family: var(--font-display);
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .confirm-row {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 0.9rem 0;
      border-bottom: 1px solid var(--border-light);
    }

    .confirm-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .confirm-row:first-of-type {
      padding-top: 0;
    }

    .confirm-icon {
      width: 36px;
      height: 36px;
      border-radius: 9px;
      background: rgba(8, 145, 178, 0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: var(--teal-light);
    }

    .confirm-icon svg {
      width: 16px;
      height: 16px;
    }

    .confirm-meta {
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
      min-width: 0;
    }

    .confirm-label {
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    .confirm-value {
      font-size: 0.92rem;
      font-weight: 600;
      color: var(--navy);
      word-break: break-all;
    }

    .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: var(--success-bg);
      border: 1px solid var(--success-border);
      color: #166534;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 0.3rem 0.8rem;
      border-radius: 99px;
      margin-bottom: 1.25rem;
    }

    .verified-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--success);
      animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {

      0%,
      100% {
        opacity: 1
      }

      50% {
        opacity: .35
      }
    }

    .phone-verified-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.68rem;
      font-weight: 700;
      color: var(--success);
      background: var(--success-bg);
      border: 1px solid var(--success-border);
      padding: 0.15rem 0.5rem;
      border-radius: 99px;
      margin-top: 0.2rem;
    }

    .info-note {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      background: rgba(8, 145, 178, 0.06);
      border: 1px solid rgba(8, 145, 178, 0.15);
      border-radius: var(--radius);
      padding: 0.85rem 1rem;
      font-size: 0.82rem;
      color: var(--text-mid);
      line-height: 1.55;
      max-width: 440px;
      margin-bottom: 2rem;
    }

    .info-note svg {
      flex-shrink: 0;
      margin-top: 1px;
      color: var(--teal-light);
    }

    /* Pending verification notice */
    .pending-notice {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      background: rgba(245, 158, 11, 0.08);
      border: 1px solid rgba(245, 158, 11, 0.25);
      border-radius: var(--radius);
      padding: 0.85rem 1rem;
      font-size: 0.82rem;
      color: var(--text-mid);
      line-height: 1.55;
      max-width: 440px;
      margin-bottom: 1.5rem;
    }
  </style>
</head>

<body>

  <div class="complete-page">

    <!-- ══ Sidebar ══ -->
    <aside class="reg-sidebar">
      <div class="sidebar-brand">
        <div class="brand-icon">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z" />
          </svg>
        </div>
        <div>
          <div class="brand-name">Bocaue Flood Information System</div>
        </div>
      </div>

      <p class="sidebar-section-label">Registration<br>Complete</p>

      <ul class="sidebar-steps">
        <li class="step-item done">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta"><span class="step-label">Verify</span><span class="step-desc">Phone &amp; OTP</span>
          </div>
        </li>
        <li class="step-item done">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta"><span class="step-label">Profile</span><span class="step-desc">Personal details</span>
          </div>
        </li>
        <li class="step-item done">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta"><span class="step-label">Security</span><span class="step-desc">Password set</span>
          </div>
        </li>
        <li class="step-item active">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta"><span class="step-label">Complete</span><span class="step-desc">Account created</span>
          </div>
        </li>
      </ul>
    </aside>

    <!-- ══ Main Content ══ -->
    <main class="complete-main">

      <?php if (!empty($dbError)): ?>
        <!-- DB error state -->
        <div class="alert alert-danger fade-up" style="max-width:480px; margin-bottom:1.5rem;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <?php echo htmlspecialchars($dbError); ?>
        </div>
        <div class="complete-actions">
          <a href="register_step3.php" class="btn-back" style="text-decoration:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Step 3
          </a>
        </div>

      <?php else: ?>
        <!-- ══ SUCCESS UI ══ -->

        <div class="success-icon-wrap fade-up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </div>

        <div class="verified-badge fade-up">
          <span class="verified-dot"></span>
          Account Created Successfully
        </div>

        <h1 class="complete-title fade-up fade-up-delay-1">
          Welcome<?php
          $first = explode(' ', trim($display_name))[0];
          echo (!empty($first) && $first !== 'Personnel')
            ? ', ' . htmlspecialchars($first) . '!'
            : '!';
          ?>
        </h1>

        <p class="complete-desc fade-up fade-up-delay-1">
          Your account has been successfully created in the Bocaue Flood Information System.
        </p>

        <!-- Pending verification notice -->
        <div class="pending-notice fade-up fade-up-delay-1">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          Your account is pending admin verification. You will be able to log in once
          an administrator has reviewed and approved your registration.
        </div>

        <!-- Confirmation card -->
        <div class="confirm-card fade-up fade-up-delay-2">

          <div class="confirm-card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="9 11 12 14 22 4" />
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            Account Summary
          </div>

          <!-- Full Name -->
          <div class="confirm-row">
            <div class="confirm-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
            <div class="confirm-meta">
              <span class="confirm-label">Full Name</span>
              <span class="confirm-value"><?php echo htmlspecialchars($display_name); ?></span>
            </div>
          </div>

          <!-- Email -->
          <?php if (!empty($display_email)): ?>
            <div class="confirm-row">
              <div class="confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
              </div>
              <div class="confirm-meta">
                <span class="confirm-label">Email Address</span>
                <span class="confirm-value"><?php echo htmlspecialchars($display_email); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <!-- Phone -->
          <?php if (!empty($display_phone)): ?>
            <div class="confirm-row">
              <div class="confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07
                     A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41
                     2 2 0 0 1 3.6 1.23h3a2 2 0 0 1 2 1.72
                     12.84 12.84 0 0 0 .7 2.81
                     2 2 0 0 1-.45 2.11L8.09 9.91
                     a16 16 0 0 0 6 6l1.27-1.27
                     a2 2 0 0 1 2.11-.45
                     12.84 12.84 0 0 0 2.81.7
                     A2 2 0 0 1 22 16.92z" />
                </svg>
              </div>
              <div class="confirm-meta">
                <span class="confirm-label">Phone Number</span>
                <span class="confirm-value"><?php echo htmlspecialchars($display_phone); ?></span>
                <span class="phone-verified-chip">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  OTP Verified
                </span>
              </div>
            </div>
          <?php endif; ?>

          <!-- Registered On -->
          <div class="confirm-row">
            <div class="confirm-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
            </div>
            <div class="confirm-meta">
              <span class="confirm-label">Registered On</span>
              <span class="confirm-value"><?php echo htmlspecialchars($reg_date); ?></span>
            </div>
          </div>

        </div><!-- /.confirm-card -->

        <div class="info-note fade-up fade-up-delay-2">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="8.5" />
            <line x1="12" y1="12" x2="12" y2="16" />
          </svg>
          Once verified, you can log in and access flood alerts, report incidents, and
          view real-time monitoring data for Bocaue, Bulacan.
        </div>

        <div class="complete-actions fade-up fade-up-delay-3">
          <a href="login.php" class="btn-next" style="text-decoration:none; border-radius:var(--radius);">
            Go to Login
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12" />
              <polyline points="12 5 19 12 12 19" />
            </svg>
          </a>
        </div>

      <?php endif; ?>

    </main>

  </div><!-- /.complete-page -->

  <script src="../main/assets/js/script.js"></script>
</body>

</html>