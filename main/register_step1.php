<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

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

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

if (!is_dir(UPLOAD_DIR)) {
  mkdir(UPLOAD_DIR, 0755, true);
}

function handleUpload(string $fieldName, array $allowedMime, int $maxBytes): array
{
  if (empty($_FILES[$fieldName]['name'])) {
    return ['path' => null];
  }

  $file = $_FILES[$fieldName];

  if ($file['error'] === UPLOAD_ERR_NO_FILE) {
    return ['path' => null];
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    return ['error' => 'Upload failed. Please try again.'];
  }

  if ($file['size'] > $maxBytes) {
    return ['error' => 'Uploaded file exceeds allowed size.'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($file['tmp_name']);

  if (!in_array($mimeType, $allowedMime, true)) {
    return ['error' => 'Invalid file type uploaded.'];
  }

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $safeName = uniqid('reg_', true) . '.' . $ext;
  $destPath = UPLOAD_DIR . $safeName;

  if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    return ['error' => 'Could not save uploaded file.'];
  }

  return ['path' => UPLOAD_URL . $safeName];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = strtoupper(trim($_POST['first_name'] ?? ''));
  $lastName = strtoupper(trim($_POST['last_name'] ?? ''));
  $suffix = strtoupper(trim($_POST['suffix'] ?? ''));
  $email = trim($_POST['email'] ?? '');
  $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $barangaySlug = trim($_POST['barangay'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $lat = trim($_POST['lat'] ?? '');
  $lng = trim($_POST['lng'] ?? '');

  if ($firstName === '' || $lastName === '') {
    $error = 'First name and last name are required.';
  } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif ($dateOfBirth === '') {
    $error = 'Date of birth is required.';
  } elseif ($phone === '' || !preg_match('/^09\d{9}$/', $phone)) {
    $error = 'Please enter a valid PH phone number (e.g. 09XXXXXXXXX).';
  } elseif (!isset($barangays[$barangaySlug])) {
    $error = 'Please select a valid barangay.';
  } elseif ($address === '') {
    $error = 'Current address is required.';
  } else {
    $emailCheckStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $emailCheckStmt->execute([$email]);
    if ($emailCheckStmt->fetch()) {
      $error = 'Email is already registered. Please use a different email or log in.';
    }
  }

  if ($error === '') {
    $profilePicturePath = $_SESSION['reg_profile_picture'] ?? null;
    $validIdPath = $_SESSION['reg_valid_id_image'] ?? null;

    $photoResult = handleUpload('photo_upload', ['image/jpeg', 'image/png'], 5 * 1024 * 1024);
    if (isset($photoResult['error'])) {
      $error = $photoResult['error'];
    } elseif ($photoResult['path'] !== null) {
      $profilePicturePath = $photoResult['path'];
    }

    if ($error === '') {
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

    if ($error === '') {
      $fullName = trim($firstName . ' ' . $lastName . ($suffix !== '' ? ' ' . $suffix : ''));

      $_SESSION['reg_first_name'] = $firstName;
      $_SESSION['reg_last_name'] = $lastName;
      $_SESSION['reg_suffix'] = $suffix;
      $_SESSION['reg_full_name'] = $fullName;
      $_SESSION['reg_email'] = $email;
      $_SESSION['reg_dob'] = $dateOfBirth;
      $_SESSION['reg_phone'] = $phone;
      $_SESSION['reg_barangay_slug'] = $barangaySlug;
      $_SESSION['reg_barangay_id'] = $barangays[$barangaySlug]['id'];
      $_SESSION['reg_address'] = $address;
      $_SESSION['reg_lat'] = $lat;
      $_SESSION['reg_lng'] = $lng;
      $_SESSION['reg_profile_picture'] = $profilePicturePath;
      $_SESSION['reg_valid_id_image'] = $validIdPath;
      $_SESSION['reg_step1_ok'] = true;

      header('Location: register_step2.php');
      exit;
    }
  }
}

// Load existing profile picture if user ID is provided (for editing)
$existingProfilePicture = null;
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
  $userStmt = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id = ? LIMIT 1');
  $userStmt->execute([$userId]);
  $user = $userStmt->fetch(PDO::FETCH_ASSOC);
  if ($user && !empty($user['profile_picture'])) {
    $existingProfilePicture = $user['profile_picture'];
  }
}

$sticky = [
  'first_name' => htmlspecialchars($_POST['first_name'] ?? ($_SESSION['reg_first_name'] ?? '')),
  'last_name' => htmlspecialchars($_POST['last_name'] ?? ($_SESSION['reg_last_name'] ?? '')),
  'suffix' => htmlspecialchars($_POST['suffix'] ?? ($_SESSION['reg_suffix'] ?? '')),
  'email' => htmlspecialchars($_POST['email'] ?? ($_SESSION['reg_email'] ?? '')),
  'date_of_birth' => htmlspecialchars($_POST['date_of_birth'] ?? ($_SESSION['reg_dob'] ?? '')),
  'phone' => htmlspecialchars($_POST['phone'] ?? ($_SESSION['reg_phone'] ?? '')),
  'barangay' => $_POST['barangay'] ?? ($_SESSION['reg_barangay_slug'] ?? ''),
  'address' => htmlspecialchars($_POST['address'] ?? ($_SESSION['reg_address'] ?? '')),
  'lat' => htmlspecialchars($_POST['lat'] ?? ($_SESSION['reg_lat'] ?? '')),
  'lng' => htmlspecialchars($_POST['lng'] ?? ($_SESSION['reg_lng'] ?? '')),
  'profile_picture' => $_SESSION['reg_profile_picture'] ?? $existingProfilePicture,
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bocaue FIS - Step 1: Profiling</title>
  <link rel="stylesheet" href="../main/assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <style>
    .reg-page {
      min-height: 100vh;
      align-items: stretch;
    }

    .reg-sidebar {
      min-height: 100vh;
      position: sticky;
      top: 0;
      align-self: start;
    }

    .reg-main {
      min-height: 100vh;
      padding: 1.1rem 1.4rem 2rem;
    }

    .profile-shell {
      width: min(1280px, 100%);
      margin: 0 auto;
      display: grid;
      gap: 1.5rem;
      padding: 0.5rem 0.75rem 2rem;
    }

    .profile-card {
      background: #fff;
      border: 1px solid #dbe7f3;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(18, 62, 117, 0.08);
      padding: 1.35rem;
      min-width: 0;
      overflow: hidden;
    }

    .profile-header {
      background: linear-gradient(135deg, #0b3a75, #3f8fe8);
      color: #fff;
      border-radius: 16px;
      padding: 1.2rem 1.4rem;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: minmax(340px, 390px) minmax(0, 1fr);
      gap: 1.25rem;
      align-items: start;
    }

    .upload-column {
      display: grid;
      gap: 1rem;
      align-content: start;
    }

    .avatar-dropzone {
      border: 2px dashed #8bb7ea;
      border-radius: 16px;
      background: #f7fbff;
      padding: 1.1rem 1rem 1rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
      display: grid;
      gap: 0.6rem;
      justify-items: center;
    }

    .avatar-dropzone.is-dragging,
    .avatar-dropzone:hover {
      border-color: #2f74cb;
      background: #eaf4ff;
    }

    .profile-file-input {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      margin-top: 0.75rem;
      width: 100%;
      border: 1px solid #cadef4;
      border-radius: 10px;
      padding: 0.55rem 0.65rem;
      background: #f9fcff;
      color: #204f85;
      font-size: 0.82rem;
      cursor: pointer;
    }

    .profile-file-input::file-selector-button {
      border: none;
      background: #2f74cb;
      color: #fff;
      border-radius: 8px;
      padding: 0.45rem 0.75rem;
      margin-right: 0.7rem;
      cursor: pointer;
    }

    .avatar-preview {
      width: 138px;
      height: 138px;
      border-radius: 999px;
      overflow: hidden;
      margin: 0 auto 0.75rem;
      border: 3px solid #fff;
      box-shadow: 0 8px 20px rgba(15, 77, 146, 0.2);
      background: #d8e8fa;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #174a8a;
      font-weight: 700;
    }

    .upload-title {
      margin: 0 0 0.75rem;
      font-size: 1rem;
      color: #0e355f;
    }

    .upload-card-body {
      display: grid;
      gap: 0.75rem;
    }

    .upload-divider {
      margin: 0.2rem 0;
      border: none;
      border-top: 1px solid #e4edf7;
    }

    .avatar-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .hint-text {
      font-size: 0.78rem;
      color: #4a6179;
      line-height: 1.5;
    }

    .map-toolbar {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      align-items: center;
      margin-bottom: 0.75rem;
      flex-wrap: wrap;
    }

    .status-pill {
      font-size: 0.75rem;
      border-radius: 999px;
      padding: 0.3rem 0.75rem;
      background: #edf4fc;
      color: #1d4f86;
      font-weight: 600;
    }

    #bocaueMap {
      height: 430px;
      border-radius: 14px;
      border: 1px solid #dbe7f3;
    }

    .field-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.9rem;
    }

    .uppercase-input {
      text-transform: uppercase;
    }

    @media (max-width: 900px) {

      .reg-sidebar,
      .reg-main {
        min-height: auto;
        position: static;
      }

      .reg-main {
        padding: 0.8rem 0.6rem 1rem;
      }

      .profile-grid {
        grid-template-columns: 1fr;
      }

      .field-row {
        grid-template-columns: 1fr;
      }

      .profile-shell {
        padding: 0.3rem 0.2rem 1.2rem;
      }
    }
  </style>
</head>

<body>
  <div class="reg-page">
    <aside class="reg-sidebar">
      <div class="sidebar-brand">
        <div class="brand-name">Bocaue Flood Information System</div>
      </div>
      <p class="sidebar-section-label">Registration<br>Resident Profiling</p>
      <ul class="sidebar-steps">
        <li class="step-item active">
          <div class="step-dot">1</div><span class="step-label">Profile</span>
        </li>
        <li class="step-item pending">
          <div class="step-dot">2</div><span class="step-label">Password</span>
        </li>
        <li class="step-item pending">
          <div class="step-dot">3</div><span class="step-label">Complete</span>
        </li>
      </ul>
    </aside>
    <main class="reg-main">
      <div class="profile-shell">
        <section class="profile-header fade-up">
          <p class="step-tag" style="color:#cfe5ff;">Step 01 of 03</p>
          <h1 class="reg-title" style="color:#fff; margin-bottom:0.35rem;">Resident Profile Setup</h1>
          <p style="margin:0; color:#e5f1ff; font-size:0.92rem;">Complete your profile with an accurate location pin for
            faster emergency response.</p>
          <div class="progress-bar-wrap" style="margin-top:0.85rem;">
            <div class="progress-track">
              <div class="progress-fill" style="width: 33%;"></div>
            </div>
            <span class="progress-pct">33% Complete</span>
          </div>
        </section>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger fade-up" role="alert">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register_step1.php" enctype="multipart/form-data" novalidate id="profileForm"
          class="profile-grid fade-up fade-up-delay-1">
          <div class="upload-column">
            <section class="profile-card">
              <h3 class="upload-title">Profile Picture</h3>
              <div class="upload-card-body">
                <label for="photo_upload" class="avatar-dropzone" id="avatarDropzone">
                  <div class="avatar-preview" id="avatarPreview">No photo</div>
                  <strong style="display:block;">Drag & drop image here</strong>
                  <span class="hint-text">or click to browse JPG/PNG (max 5MB)</span>
                </label>
                <input type="file" id="photo_upload" name="photo_upload" class="profile-file-input"
                  accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <p class="hint-text" id="avatarValidation"></p>
              </div>
            </section>

            <section class="profile-card">
              <h3 class="upload-title">Valid Government ID</h3>
              <div class="upload-card-body">
                <label class="form-label" for="valid_id_upload">Upload Valid ID</label>
                <input type="file" id="valid_id_upload" name="valid_id_image" class="profile-file-input"
                  accept="image/*,application/pdf">
                <p class="hint-text">Accepted: JPG, PNG, PDF (max 10MB)</p>
              </div>
            </section>
          </div>

          <section class="profile-card">
            <h3 style="margin:0 0 0.85rem; font-size:1rem; color:#0e355f;">Personal Information</h3>
            <div class="field-row">
              <div class="form-group">
                <label class="form-label" for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control uppercase-input" required
                  value="<?php echo $sticky['first_name']; ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control uppercase-input" required
                  value="<?php echo $sticky['last_name']; ?>">
              </div>
            </div>
            <div class="field-row">
              <div class="form-group">
                <label class="form-label" for="suffix">Suffix</label>
                <select id="suffix" name="suffix" class="form-control uppercase-input">
                  <option value="" <?php echo ($sticky['suffix'] ?? '') === '' ? 'selected' : ''; ?>>Select suffix
                    (optional)</option>
                  <option value="JR" <?php echo strtoupper($sticky['suffix'] ?? '') === 'JR' ? 'selected' : ''; ?>>JR
                  </option>
                  <option value="SR" <?php echo strtoupper($sticky['suffix'] ?? '') === 'SR' ? 'selected' : ''; ?>>SR
                  </option>
                  <option value="II" <?php echo strtoupper($sticky['suffix'] ?? '') === 'II' ? 'selected' : ''; ?>>II
                  </option>
                  <option value="III" <?php echo strtoupper($sticky['suffix'] ?? '') === 'III' ? 'selected' : ''; ?>>III
                  </option>
                  <option value="IV" <?php echo strtoupper($sticky['suffix'] ?? '') === 'IV' ? 'selected' : ''; ?>>IV
                  </option>
                  <option value="V" <?php echo strtoupper($sticky['suffix'] ?? '') === 'V' ? 'selected' : ''; ?>>V
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="form-control" id="fullNamePreview"
                  style="display:flex;align-items:center;background:#f3f8fe;">
                  <?php echo htmlspecialchars(trim(($sticky['first_name'] ?? '') . ' ' . ($sticky['last_name'] ?? '') . ' ' . ($sticky['suffix'] ?? ''))); ?>
                </div>
                <p class="hint-text" style="margin-top:0.35rem;">Auto-generated from first name, last name, and suffix.
                </p>
              </div>
            </div>
            <div class="field-row">
              <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                  value="<?php echo $sticky['email']; ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" required maxlength="11"
                  placeholder="09XXXXXXXXX" value="<?php echo $sticky['phone']; ?>">
              </div>
            </div>
            <div class="field-row">
              <div class="form-group">
                <label class="form-label" for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required
                  value="<?php echo $sticky['date_of_birth']; ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="barangay">Barangay</label>
                <select id="barangay" name="barangay" class="form-control" required>
                  <option value="" disabled <?php echo $sticky['barangay'] === '' ? 'selected' : ''; ?>>Select your
                    barangay</option>
                  <?php foreach ($barangays as $slug => $info): ?>
                    <option value="<?php echo $slug; ?>" <?php echo $sticky['barangay'] === $slug ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($info['label']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </section>

          <section class="profile-card" style="grid-column: 1 / -1;">
            <h3 style="margin:0 0 0.75rem; font-size:1rem; color:#0e355f;">Address and Location</h3>
            <div class="map-toolbar">
              <span class="status-pill" id="mapStatus">Map ready. Drag pin or click map.</span>
              <button type="button" class="btn-back" id="gpsBtn">Use Current Location</button>
            </div>
            <div id="bocaueMap" aria-label="Bocaue location map"></div>
            <div class="field-row" style="margin-top:0.8rem;">
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control form-control--textarea" rows="2"
                  required><?php echo $sticky['address']; ?></textarea>
                <p class="hint-text" style="margin-top:0.45rem;">Auto-filled from map pin. You can manually edit if
                  needed.</p>
              </div>
            </div>
            <input type="hidden" id="lat" name="lat" value="<?php echo $sticky['lat']; ?>">
            <input type="hidden" id="lng" name="lng" value="<?php echo $sticky['lng']; ?>">
          </section>

          <div class="reg-nav" style="grid-column: 1 / -1;">
            <a href="login.php" class="btn-back">Back to Login</a>
            <button type="submit" class="btn-next">Save and Continue</button>
          </div>
        </form>
      </div>
    </main>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="../main/assets/js/script.js"></script>
  <script>
    (function () {
      var firstName = document.getElementById('first_name');
      var lastName = document.getElementById('last_name');
      var suffix = document.getElementById('suffix');
      var fullNamePreview = document.getElementById('fullNamePreview');
      var phone = document.getElementById('phone');
      var avatarInput = document.getElementById('photo_upload');
      var avatarDropzone = document.getElementById('avatarDropzone');
      var avatarPreview = document.getElementById('avatarPreview');
      var avatarValidation = document.getElementById('avatarValidation');
      var form = document.getElementById('profileForm');
      var mapStatus = document.getElementById('mapStatus');
      var addressInput = document.getElementById('address');
      var latInput = document.getElementById('lat');
      var lngInput = document.getElementById('lng');
      var gpsBtn = document.getElementById('gpsBtn');

      function updateFullName() {
        var composed = [firstName.value.trim(), lastName.value.trim(), suffix.value.trim()].filter(Boolean).join(' ');
        if (fullNamePreview) {
          fullNamePreview.textContent = composed;
        }
      }

      [firstName, lastName, suffix].forEach(function (field) {
        if (field) {
          field.addEventListener('input', updateFullName);
          field.addEventListener('change', updateFullName);
        }
      });

      [firstName, lastName].forEach(function (field) {
        if (field) {
          field.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
          });
        }
      });

      if (phone) {
        phone.addEventListener('input', function () {
          this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
      }

      function setAvatarMessage(message, isError) {
        avatarValidation.textContent = message;
        avatarValidation.style.color = isError ? '#b91c1c' : '#35516d';
      }

      function previewAvatar(file) {
        if (!file) {
          return;
        }
        var allowed = ['image/jpeg', 'image/png'];
        if (allowed.indexOf(file.type) === -1) {
          setAvatarMessage('Only JPG and PNG files are allowed.', true);
          avatarInput.value = '';
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          setAvatarMessage('Image exceeds 5MB limit.', true);
          avatarInput.value = '';
          return;
        }
        var reader = new FileReader();
        reader.onload = function (event) {
          avatarPreview.innerHTML = '<img src="' + event.target.result + '" alt="Profile preview">';
          setAvatarMessage('Image ready for upload.', false);
        };
        reader.readAsDataURL(file);
      }

      avatarDropzone.addEventListener('dragover', function (event) {
        event.preventDefault();
        avatarDropzone.classList.add('is-dragging');
      });
      avatarDropzone.addEventListener('dragleave', function () {
        avatarDropzone.classList.remove('is-dragging');
      });
      avatarDropzone.addEventListener('drop', function (event) {
        event.preventDefault();
        avatarDropzone.classList.remove('is-dragging');
        if (!event.dataTransfer.files || !event.dataTransfer.files[0]) {
          return;
        }
        avatarInput.files = event.dataTransfer.files;
        previewAvatar(event.dataTransfer.files[0]);
      });
      avatarInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
          previewAvatar(this.files[0]);
        }
      });

      var bocaueBoundary = [
        [14.8450, 120.8670],
        [14.8420, 120.9020],
        [14.8350, 120.9320],
        [14.8250, 120.9660],
        [14.8100, 120.9890],
        [14.7860, 120.9870],
        [14.7640, 120.9710],
        [14.7510, 120.9490],
        [14.7480, 120.9200],
        [14.7500, 120.8900],
        [14.7600, 120.8720],
        [14.7840, 120.8660],
        [14.8120, 120.8640],
        [14.8350, 120.8650]
      ];

      var map = L.map('bocaueMap', {
        zoomControl: true,
        minZoom: 13,
        maxZoom: 18,
        maxBoundsViscosity: 1.0
      });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        noWrap: true,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      var boundaryLayer = L.polygon(bocaueBoundary, {
        color: '#2a6fc0',
        weight: 2,
        fillColor: '#7fb4ef',
        fillOpacity: 0.15
      }).addTo(map);

      var bocaueBounds = boundaryLayer.getBounds();
      var strictBounds = bocaueBounds.pad(0.02);
      map.setView([14.7995, 120.9260], 14);
      map.fitBounds(bocaueBounds, { padding: [14, 14], maxZoom: 14 });
      map.setMaxBounds(strictBounds);

      var defaultLat = parseFloat(latInput.value || '14.7962');
      var defaultLng = parseFloat(lngInput.value || '120.9260');
      var marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

      var suppressMarkerMove = false;

      function setMapStatus(text) {
        mapStatus.textContent = text;
      }

      function updateLatLng(latlng) {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);
      }

      function keepInsideBounds(latlng) {
        return L.latLng(
          Math.min(Math.max(latlng.lat, strictBounds.getSouth()), strictBounds.getNorth()),
          Math.min(Math.max(latlng.lng, strictBounds.getWest()), strictBounds.getEast())
        );
      }

      function reverseGeocode(latlng, updateAddressField) {
        setMapStatus('Resolving address...');
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=' +
          encodeURIComponent(latlng.lat) + '&lon=' + encodeURIComponent(latlng.lng);
        fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            var displayAddress = data && data.display_name ? data.display_name : '';
            if (updateAddressField && displayAddress) {
              addressInput.value = displayAddress;
            }
            setMapStatus(displayAddress ? 'Address updated from map pin.' : 'Location updated. Address unavailable.');
          })
          .catch(function () {
            setMapStatus('Unable to fetch address. You can input it manually.');
          });
      }

      function moveMarker(latlng, shouldReverse) {
        var safeLatLng = keepInsideBounds(latlng);
        marker.setLatLng(safeLatLng);
        updateLatLng(safeLatLng);
        if (shouldReverse) {
          reverseGeocode(safeLatLng, true);
        }
      }

      map.on('click', function (event) {
        moveMarker(event.latlng, true);
      });

      marker.on('dragend', function () {
        moveMarker(marker.getLatLng(), true);
      });

      gpsBtn.addEventListener('click', function () {
        if (!navigator.geolocation) {
          setMapStatus('Geolocation is not supported by this browser.');
          return;
        }
        setMapStatus('Detecting current location...');
        navigator.geolocation.getCurrentPosition(function (position) {
          var latlng = keepInsideBounds(L.latLng(position.coords.latitude, position.coords.longitude));
          map.panTo(latlng);
          moveMarker(latlng, true);
        }, function () {
          setMapStatus('Unable to retrieve your current location.');
        }, {
          enableHighAccuracy: true,
          timeout: 10000
        });
      });

      addressInput.addEventListener('blur', function () {
        var value = this.value.trim();
        if (!value || suppressMarkerMove) {
          return;
        }
        setMapStatus('Matching typed address to map...');
        var query = encodeURIComponent(value + ', Bocaue, Bulacan, Philippines');
        fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + query, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (!data || !data.length) {
              setMapStatus('Address kept as manual entry.');
              return;
            }
            var latlng = L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon));
            suppressMarkerMove = true;
            latlng = keepInsideBounds(latlng);
            marker.setLatLng(latlng);
            updateLatLng(latlng);
            map.panTo(latlng);
            suppressMarkerMove = false;
            setMapStatus('Marker updated from typed address.');
          })
          .catch(function () {
            setMapStatus('Address updated manually.');
          });
      });

      updateFullName();
      updateLatLng(marker.getLatLng());
      if (!addressInput.value.trim()) {
        reverseGeocode(marker.getLatLng(), true);
      }

      form.addEventListener('submit', function (event) {
        if (avatarInput.files && avatarInput.files[0]) {
          var file = avatarInput.files[0];
          if ((file.type !== 'image/jpeg' && file.type !== 'image/png') || file.size > 5 * 1024 * 1024) {
            event.preventDefault();
            setAvatarMessage('Profile image must be JPG/PNG and up to 5MB only.', true);
            avatarDropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      });
    })();
  </script>
</body>

</html>