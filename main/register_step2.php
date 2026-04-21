<?php
/**
 * register_step2.php — Step 2: Personal Profile
 *
 * SESSION KEYS SET HERE (names must match registerComplete.php exactly):
 *   reg_full_name       — full name
 *   reg_email           — email address
 *   reg_dob             — date of birth
 *   reg_age             — calculated age
 *   reg_barangay_slug   — slug string
 *   reg_barangay_id     — integer FK for barangays table
 *   reg_address         — current address text
 *   reg_lat / reg_lng   — map coordinates
 *   reg_profile_picture — upload path  ← key used by registerComplete
 *   reg_valid_id_image  — upload path  ← key used by registerComplete
 *   reg_step2_ok        — guard flag
 */

session_start();

// ── Step guard ─────────────────────────────────────────────────────────────
if (empty($_SESSION['reg_step1_ok'])) {
  header('Location: register_step1.php');
  exit;
}

// ── DB connection ──────────────────────────────────────────────────────────
// FIXED: path goes up ONE level from main/ to reach config/
require_once __DIR__ . '/../config/db.php'; // provides $pdo (PDO)

$error = '';

// ── Barangay map ───────────────────────────────────────────────────────────
$barangays = [
  'antipona' => ['id' => 1, 'label' => 'Antipona'],
  'bagumbayan' => ['id' => 2, 'label' => 'Bagumbayan'],
  'bambang' => ['id' => 3, 'label' => 'Bambang'],
  'batia' => ['id' => 4, 'label' => 'Batia'],
  'binang1' => ['id' => 5, 'label' => 'Biñang 1st'],
  'binang2' => ['id' => 6, 'label' => 'Biñang 2nd'],
  'bolacan' => ['id' => 7, 'label' => 'Bolacan'],
  'bundukan' => ['id' => 8, 'label' => 'Bundukan'],
  'bunlo' => ['id' => 9, 'label' => 'Bunlo'],
  'caingin' => ['id' => 10, 'label' => 'Caingin'],
  'duhat' => ['id' => 11, 'label' => 'Duhat'],
  'igulot' => ['id' => 12, 'label' => 'Igulot'],
  'lolomboy' => ['id' => 13, 'label' => 'Lolomboy'],
  'poblacion' => ['id' => 14, 'label' => 'Poblacion'],
  'sulucan' => ['id' => 15, 'label' => 'Sulucan'],
  'taal' => ['id' => 16, 'label' => 'Taal'],
  'tambobong' => ['id' => 17, 'label' => 'Tambobong'],
  'turo' => ['id' => 18, 'label' => 'Turo'],
  'wakas' => ['id' => 19, 'label' => 'Wakas'],
];

// ── Upload directory ───────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

if (!is_dir(UPLOAD_DIR)) {
  mkdir(UPLOAD_DIR, 0755, true);
}

// ── Upload helper ──────────────────────────────────────────────────────────
function handleUpload(string $fieldName, array $allowedMime, int $maxBytes): array
{
  if (empty($_FILES[$fieldName]['name'])) {
    return ['path' => null];
  }

  $file = $_FILES[$fieldName];
  $error = $file['error'];

  if ($error === UPLOAD_ERR_NO_FILE) {
    return ['path' => null];
  }
  if ($error !== UPLOAD_ERR_OK) {
    return ['error' => 'Upload error (code ' . $error . '). Please try again.'];
  }
  if ($file['size'] > $maxBytes) {
    $mb = round($maxBytes / 1048576);
    return ['error' => ucfirst(str_replace('_', ' ', $fieldName)) . ' exceeds the ' . $mb . 'MB limit.'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($file['tmp_name']);
  if (!in_array($mimeType, $allowedMime, true)) {
    return ['error' => 'Invalid file type for ' . $fieldName . '. Allowed: ' . implode(', ', $allowedMime)];
  }

  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $safeName = uniqid('reg_', true) . '.' . strtolower($ext);
  $destPath = UPLOAD_DIR . $safeName;

  if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    return ['error' => 'Could not save the uploaded file. Check server permissions.'];
  }

  return ['path' => UPLOAD_URL . $safeName];
}

// ── POST handler ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $dob = trim($_POST['date_of_birth'] ?? '');
  $age = (int) ($_POST['age_display'] ?? 0);
  $barangay_slug = trim($_POST['barangay'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $lat = trim($_POST['lat'] ?? '');
  $lng = trim($_POST['lng'] ?? '');

  // Validate text fields
  if (empty($full_name)) {
    $error = 'Full legal name is required.';
  } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif (empty($dob)) {
    $error = 'Date of birth is required.';
  } elseif (!isset($barangays[$barangay_slug])) {
    $error = 'Please select a valid barangay.';
  } elseif (empty($address)) {
    $error = 'Current residence address is required.';
  } else {
    // Early duplicate email check
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $error = 'That email address is already registered. Please use a different one or log in.';
    }
  }

  // Keep existing uploads if re-submitting after an error
  // FIXED: use the correct session key names that registerComplete expects
  $profilePicturePath = $_SESSION['reg_profile_picture'] ?? null;
  $validIdPath = $_SESSION['reg_valid_id_image'] ?? null;

  if (empty($error)) {
    $photoResult = handleUpload(
      'photo_upload',
      ['image/jpeg', 'image/png', 'image/webp'],
      5 * 1024 * 1024
    );
    if (isset($photoResult['error'])) {
      $error = $photoResult['error'];
    } elseif ($photoResult['path'] !== null) {
      $profilePicturePath = $photoResult['path'];
    }
  }

  if (empty($error)) {
    $idResult = handleUpload(
      'valid_id_image',
      ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
      10 * 1024 * 1024
    );
    if (isset($idResult['error'])) {
      $error = $idResult['error'];
    } elseif ($idResult['path'] !== null) {
      $validIdPath = $idResult['path'];
    }
  }

  if (empty($error)) {
    $_SESSION['reg_full_name'] = $full_name;
    $_SESSION['reg_email'] = $email;
    $_SESSION['reg_dob'] = $dob;
    $_SESSION['reg_age'] = $age;
    $_SESSION['reg_barangay_slug'] = $barangay_slug;
    $_SESSION['reg_barangay_id'] = $barangays[$barangay_slug]['id'];
    $_SESSION['reg_address'] = $address;
    $_SESSION['reg_lat'] = $lat;
    $_SESSION['reg_lng'] = $lng;
    // FIXED: session key names match what registerComplete.php reads
    $_SESSION['reg_profile_picture'] = $profilePicturePath;
    $_SESSION['reg_valid_id_image'] = $validIdPath;
    $_SESSION['reg_step2_ok'] = true;

    header('Location: register_step3.php');
    exit;
  }
}

// ── Sticky values for form repopulation after error ────────────────────────
$sticky = [
  'full_name' => htmlspecialchars($_POST['full_name'] ?? ''),
  'email' => htmlspecialchars($_POST['email'] ?? ''),
  'date_of_birth' => htmlspecialchars($_POST['date_of_birth'] ?? ''),
  'age_display' => htmlspecialchars($_POST['age_display'] ?? ''),
  'barangay' => $_POST['barangay'] ?? '',
  'address' => htmlspecialchars($_POST['address'] ?? ''),
  'lat' => htmlspecialchars($_POST['lat'] ?? ''),
  'lng' => htmlspecialchars($_POST['lng'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS — Step 2: Personal Profile</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
</head>

<body>

  <div class="reg-page">

    <!-- ══ Sidebar Progress ══ -->
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

      <p class="sidebar-section-label">Registration<br>Verify your Account</p>

      <ul class="sidebar-steps">
        <li class="step-item done">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta">
            <span class="step-label">Verify</span>
            <span class="step-desc">Identity confirmed</span>
          </div>
        </li>
        <li class="step-item active">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </div>
          <div class="step-meta">
            <span class="step-label">Profile</span>
            <span class="step-desc">Personal details</span>
          </div>
        </li>
        <li class="step-item pending">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </div>
          <div class="step-meta">
            <span class="step-label">Security</span>
            <span class="step-desc">Set your password</span>
          </div>
        </li>
        <li class="step-item pending">
          <div class="step-dot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="step-meta">
            <span class="step-label">Complete</span>
            <span class="step-desc">All done!</span>
          </div>
        </li>
      </ul>

      <div class="sidebar-tip">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="8" x2="12" y2="12" />
          <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
        <p>Your information is encrypted and used only for emergency response purposes.</p>
      </div>
    </aside>

    <!-- ══ Main Content ══ -->
    <main class="reg-main">

      <div class="reg-header">
        <p class="step-tag">Step 02 of 03</p>
        <h1 class="reg-title fade-up">Personal Profile</h1>
        <p class="reg-subtitle fade-up fade-up-delay-1">
          Provide your details so responders can identify and assist you during emergencies.
        </p>
        <div class="progress-bar-wrap fade-up fade-up-delay-1">
          <div class="progress-track">
            <div class="progress-fill" style="width: 66%;"></div>
          </div>
          <span class="progress-pct">66% Complete</span>
        </div>
      </div>

      <!-- Error alert -->
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger fade-up" style="max-width:800px;" role="alert">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            aria-hidden="true">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <?php echo htmlspecialchars($error); ?>
          <button type="button" class="alert-dismiss" aria-label="Dismiss">&#x2715;</button>
        </div>
      <?php endif; ?>

      <div class="reg-card fade-up fade-up-delay-1">

        <form method="POST" action="register_step2.php" enctype="multipart/form-data" novalidate id="profileForm">

          <!-- ══ Upload Row ══ -->
          <div class="upload-row">

            <!-- Profile Photo -->
            <div class="upload-block">
              <div class="upload-block-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                Profile Photo
              </div>
              <label for="photo_upload" class="upload-circle-label">
                <div class="photo-circle" id="photoCircle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                  <div class="upload-overlay">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="17 8 12 3 7 8" />
                      <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                  </div>
                </div>
              </label>
              <p class="upload-hint">Clear facial photo<br><span>JPG, PNG — max 5MB</span></p>
              <input type="file" id="photo_upload" name="photo_upload" accept="image/*">
            </div>

            <!-- Valid ID Upload -->
            <div class="upload-block upload-block--id">
              <div class="upload-block-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="2" y="5" width="20" height="14" rx="2" />
                  <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
                Valid Government ID
              </div>
              <label for="valid_id_upload" class="id-upload-zone" id="idUploadZone">
                <div class="id-upload-placeholder" id="idPlaceholder">
                  <div class="id-upload-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="1.5">
                      <rect x="2" y="5" width="20" height="14" rx="2" />
                      <circle cx="8" cy="12" r="2" />
                      <line x1="13" y1="10" x2="19" y2="10" />
                      <line x1="13" y1="13" x2="17" y2="13" />
                    </svg>
                  </div>
                  <p class="id-upload-text">Drag &amp; drop or <span class="id-upload-link">browse</span></p>
                  <p class="id-upload-sub">Passport · Driver's License · PhilSys · SSS · UMID</p>
                  <p class="id-upload-sub">JPG, PNG, PDF — max 10MB</p>
                </div>
                <div class="id-preview-wrap" id="idPreviewWrap" style="display:none;">
                  <img id="idPreviewImg" src="" alt="ID Preview">
                  <div class="id-preview-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="2.5">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                    ID Uploaded
                  </div>
                  <button type="button" class="id-preview-remove" id="idRemoveBtn" title="Remove">&#x2715;</button>
                </div>
              </label>
              <!-- FIXED: name="valid_id_image" matches handleUpload() and DB column -->
              <input type="file" id="valid_id_upload" name="valid_id_image" accept="image/*,application/pdf">
            </div>

          </div><!-- /.upload-row -->

          <!-- ══ Text fields ══ -->
          <div class="reg-form-grid">

            <div class="form-group span-full">
              <label class="form-label" for="full_name">Full Legal Name</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                </span>
                <input type="text" id="full_name" name="full_name" class="form-control"
                  placeholder="e.g. Juan dela Cruz" value="<?php echo $sticky['full_name']; ?>" required>
              </div>
            </div>

            <div class="form-group span-full">
              <label class="form-label" for="email">Email Address</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                  </svg>
                </span>
                <input type="email" id="email" name="email" class="form-control" placeholder="juan@example.ph"
                  value="<?php echo $sticky['email']; ?>" required autocomplete="email">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="date_of_birth">Date of Birth</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                  </svg>
                </span>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                  value="<?php echo $sticky['date_of_birth']; ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="age_display">Age</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                </span>
                <input type="number" id="age_display" name="age_display" class="form-control"
                  value="<?php echo $sticky['age_display']; ?>" placeholder="Auto-calculated" readonly>
              </div>
              <p class="form-hint">Calculated from date of birth</p>
            </div>

            <div class="form-group span-full">
              <label class="form-label" for="barangay">Barangay</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                  </svg>
                </span>
                <select id="barangay" name="barangay" class="form-control" required>
                  <option value="" disabled <?php echo empty($sticky['barangay']) ? 'selected' : ''; ?> hidden>
                    Select your barangay
                  </option>
                  <?php foreach ($barangays as $slug => $info): ?>
                    <option value="<?php echo $slug; ?>" <?php echo ($sticky['barangay'] === $slug) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($info['label']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <span class="select-chevron">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                  </svg>
                </span>
              </div>
            </div>

            <div class="form-group span-full">
              <label class="form-label" for="address">
                Current Residence
                <span class="label-badge" id="addressAutofillBadge" style="display:none;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  Auto-filled from map
                </span>
              </label>
              <div class="input-wrap">
                <span class="input-icon input-icon--top">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="10" r="3" />
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                  </svg>
                </span>
                <textarea id="address" name="address" class="form-control form-control--textarea"
                  placeholder="Street, Barangay, Municipality, Province" rows="2"
                  required><?php echo $sticky['address']; ?></textarea>
              </div>
            </div>

          </div><!-- /.reg-form-grid -->

          <!-- ══ Map Section ══ -->
          <div class="map-section">

            <div class="map-section-header">
              <div class="map-section-label">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <circle cx="12" cy="10" r="3" />
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                </svg>
                Pin Your Location
              </div>
              <button type="button" id="useLocationBtn" class="btn-use-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <circle cx="12" cy="12" r="3" />
                  <path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
                </svg>
                <span id="locBtnText">Use My GPS Location</span>
              </button>
            </div>

            <div id="locationStatus" class="location-status" style="display:none;" role="alert" aria-live="polite">
            </div>

            <div class="leaflet-map-wrapper">
              <div id="bocaueMap"></div>
            </div>

            <div class="pin-info-strip" id="pinInfoStrip" style="display:none;">
              <div class="pin-strip-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="10" r="3" />
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                </svg>
              </div>
              <div class="pin-strip-body">
                <span class="pin-address-text" id="pinAddress">Locating…</span>
                <span class="pin-coords-text" id="pinCoords"></span>
              </div>
              <div class="pin-strip-actions">
                <button type="button" class="pin-fill-btn" id="pinFillBtn" title="Apply to address field">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  Use this address
                </button>
              </div>
            </div>

            <p class="map-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="8" />
                <line x1="12" y1="12" x2="12" y2="16" />
              </svg>
              Restricted to Bocaue, Bulacan. Click to pin or use GPS. Drag the marker to adjust.
            </p>

            <input type="hidden" id="lat" name="lat" value="<?php echo $sticky['lat']; ?>">
            <input type="hidden" id="lng" name="lng" value="<?php echo $sticky['lng']; ?>">

          </div><!-- /.map-section -->

          <div class="reg-nav">
            <a href="register_step1.php" class="btn-back">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6" />
              </svg>
              Go Back
            </a>
            <button type="submit" class="btn-next">
              Save &amp; Continue
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="5" y1="12" x2="19" y2="12" />
                <polyline points="12 5 19 12 12 19" />
              </svg>
            </button>
          </div>

        </form>

      </div><!-- /.reg-card -->

    </main>
  </div><!-- /.reg-page -->

  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="../main/assets/js/script.js"></script>

</body>

</html>