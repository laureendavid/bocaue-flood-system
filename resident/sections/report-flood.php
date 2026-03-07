<?php
/* =============================================================
   sections/report-flood.php
   Included by main.php when ?page=report-flood
   Requires: $conn (mysqli) from config/db.php
             assets/css/report-flood.css
             assets/js/report-flood.js
   ============================================================= */

$userBarangay     = 'Bocaue, Bulacan';
$userBarangayName = 'Bocaue';
$userMunicipality = 'Bocaue';
$userProvince     = 'Bulacan';

if (isset($_SESSION['user_id'])) {
    $uid  = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT b.barangay_name, b.municipality, b.province
        FROM users u
        JOIN barangays b ON u.barangay_id = b.barangay_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($userBarangayName, $userMunicipality, $userProvince);
    if ($stmt->fetch()) {
        $userBarangay = $userBarangayName . ', ' . $userMunicipality;
    }
    $stmt->close();
}

$submitError   = '';
$submitSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $userId        = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $description   = trim($_POST['flood_desc']     ?? '');
    $waterLevel    = trim($_POST['water_level']     ?? '');
    $severityRaw   = trim($_POST['severity']        ?? '');
    $floodDate     = trim($_POST['flood_date']      ?? '');
    $floodTime     = trim($_POST['flood_time']      ?? '');
    $latitude      = trim($_POST['latitude']        ?? '') ?: null;
    $longitude     = trim($_POST['longitude']       ?? '') ?: null;
    $pinnedAddress = trim($_POST['pinned_address']  ?? '');
    $rescueStatus  = trim($_POST['rescue_status']   ?? 'Not Required');
    $rescuePeople  = trim($_POST['rescue_people']   ?? '');
    $rescueNote    = trim($_POST['rescue_note']     ?? '');

    // Validate rescue_status against DB enum
    $allowedRescue = ['Not Required', 'Rescue Needed'];
    if (!in_array($rescueStatus, $allowedRescue)) $rescueStatus = 'Not Required';

    // Map severity → DB enum
    $severityMap = [
        'high'     => 'Impassable',
        'moderate' => 'Limited Access',
        'passable' => 'Passable',
    ];
    $floodSeverity = $severityMap[$severityRaw] ?? null;

    // Passable/rainy → force no flood values
    if ($severityRaw === 'passable') {
        $waterLevel   = 'none';
        $rescueStatus = 'Not Required';
    }

    // Append rescue details to description if rescue needed
    if ($rescueStatus === 'Rescue Needed') {
        $extra = [];
        if ($rescuePeople !== '') $extra[] = 'People needing rescue: ' . $rescuePeople;
        if ($rescueNote   !== '') $extra[] = 'Rescue note: ' . $rescueNote;
        if ($extra) $description .= "\n\n[RESCUE DETAILS]\n" . implode("\n", $extra);
    }

    $fullAddress = $pinnedAddress ?: $userBarangay;

    // Validation
    if (!$userId)            $submitError = 'You must be logged in to submit a report.';
    elseif (!$description)   $submitError = 'Please enter a description.';
    elseif (!$floodSeverity) $submitError = 'Please select a severity level.';
    elseif ($severityRaw !== 'passable' && !$waterLevel)
                             $submitError = 'Please select a water level.';
    elseif (!$latitude || !$longitude)
                             $submitError = 'Please tap the map to pin your flood location.';
    elseif (!in_array($rescueStatus, ['Not Required','Rescue Needed']))
                             $submitError = 'Please indicate if rescue is needed.';
    else {
        $conn->begin_transaction();
        try {
            // 1. Insert location
            $locStmt = $conn->prepare("
                INSERT INTO locations
                    (location_type, barangay, municipality, province,
                     latitude, longitude, full_address)
                VALUES ('Report', ?, ?, ?, ?, ?, ?)
            ");
            $locStmt->bind_param(
                'sssdds',
                $userBarangayName,
                $userMunicipality,
                $userProvince,
                $latitude,
                $longitude,
                $fullAddress
            );
            $locStmt->execute();
            $locationId = $conn->insert_id;
            $locStmt->close();

            // 2. Optional photo upload
            $reportImage = null;
            if (!empty($_FILES['report_photo']['tmp_name'])) {
                $uploadDir = __DIR__ . '/../uploads/reports/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext     = strtolower(pathinfo($_FILES['report_photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowed))
                    throw new Exception('Invalid file type. Only JPG, PNG, WEBP, GIF allowed.');
                $fileName = 'report_' . time() . '_' . $userId . '.' . $ext;
                if (!move_uploaded_file($_FILES['report_photo']['tmp_name'], $uploadDir . $fileName))
                    throw new Exception('Failed to save uploaded photo.');
                $reportImage = 'uploads/reports/' . $fileName;
            }

            // 3. Insert report
            $reportedAt = ($floodDate && $floodTime)
                ? $floodDate . ' ' . $floodTime . ':00'
                : date('Y-m-d H:i:s');

            $repStmt = $conn->prepare("
                INSERT INTO reports
                    (user_id, location_id, water_level, flood_severity,
                     report_image, description, status, rescue_status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
            ");
            $repStmt->bind_param(
                'iissssss',
                $userId, $locationId, $waterLevel,
                $floodSeverity, $reportImage, $description,
                $rescueStatus, $reportedAt
            );
            $repStmt->execute();
            $repStmt->close();

            $conn->commit();
            $submitSuccess = true;

        } catch (Exception $e) {
            $conn->rollback();
            $submitError = 'Submission failed: ' . $e->getMessage();
        }
    }
}

// Helpers for repopulating form on error
$postSeverity = $_POST['severity']      ?? '';
$postRescue   = $_POST['rescue_status'] ?? '';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

<section id="page-report-flood" class="page active">

  <div id="page-content-report">
    <div class="report-page-card">
      <h2>Report Flood</h2>
      <p class="subtitle">Tap anywhere on the map to pin your exact location, then fill in the flood details.</p>
      <p class="map-hint">
        <span class="material-symbols-outlined">touch_app</span>
        Click or tap the map to drop your pin. You can drag it to adjust.
      </p>

      <!-- Leaflet map -->
      <div id="report-map"></div>

      <!-- Pin info strip -->
      <div class="pin-info" id="pin-info">
        <span class="material-symbols-outlined">location_searching</span>
        <div class="pin-info-text">
          <span class="pin-address" id="pin-address">No location pinned yet — tap the map above.</span>
          <span class="pin-coords"  id="pin-coords"></span>
        </div>
      </div>

      <?php if ($submitError): ?>
        <div class="report-form-error"><?= htmlspecialchars($submitError) ?></div>
      <?php endif; ?>

      <form
        class="report-form"
        id="report-form"
        method="POST"
        action="main.php?page=report-flood"
        enctype="multipart/form-data"
      >
        <input type="hidden" name="submit_report"  value="1">
        <input type="hidden" name="latitude"       id="field-lat"     value="">
        <input type="hidden" name="longitude"      id="field-lng"     value="">
        <input type="hidden" name="pinned_address" id="field-address" value="">

        <!-- Description -->
        <div class="form-group">
          <label class="form-label" for="flood-desc">Description</label>
          <textarea
            class="form-input"
            id="flood-desc"
            name="flood_desc"
            placeholder="Describe the flood situation..."
            required
          ><?= htmlspecialchars($_POST['flood_desc'] ?? '') ?></textarea>
        </div>

        <!-- Severity -->
        <div class="form-group">
          <label class="form-label">Severity of Flood</label>
          <div class="severity-group">
            <?php
            $severities = [
              'high'     => ['label' => 'High',             'badge' => 'badge-high',     'desc' => 'Impassable'],
              'moderate' => ['label' => 'Moderate',         'badge' => 'badge-moderate', 'desc' => 'Limited Access'],
              'passable' => ['label' => 'Passable / Rainy', 'badge' => 'badge-rainy',    'desc' => 'No flooding, just rain'],
            ];
            foreach ($severities as $val => $s):
            ?>
            <label class="severity-option">
              <input
                type="radio" name="severity" value="<?= $val ?>"
                <?= $postSeverity === $val ? 'checked' : '' ?>
                required
              >
              <div class="radio-circle"><div class="radio-dot"></div></div>
              <span class="severity-label"><?= $s['label'] ?></span>
              <span class="severity-badge <?= $s['badge'] ?>"><?= $s['desc'] ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Water level -->
        <div class="form-group" id="water-level-group">
          <label class="form-label" for="water-level">Water Level</label>
          <select class="form-select" id="water-level" name="water_level">
            <option value="">-- Select water level --</option>
            <?php
            $levels = [
              'none'  => 'No flooding / Rainy only',
              'ankle' => 'Ankle-deep',
              'knee'  => 'Knee-deep',
              'waist' => 'Waist-deep',
              'chest' => 'Chest-deep',
              'above' => 'Above chest / Dangerous',
            ];
            $selLevel = $_POST['water_level'] ?? '';
            foreach ($levels as $val => $label):
            ?>
              <option value="<?= $val ?>" <?= $selLevel === $val ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Date & Time -->
        <div class="date-time-row">
          <div class="form-group">
            <label class="form-label" for="flood-date">Date</label>
            <input type="date" class="form-input" id="flood-date" name="flood_date"
              value="<?= htmlspecialchars($_POST['flood_date'] ?? date('Y-m-d')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="flood-time">Time</label>
            <input type="time" class="form-input" id="flood-time" name="flood_time"
              value="<?= htmlspecialchars($_POST['flood_time'] ?? date('H:i')) ?>" required>
          </div>
        </div>

        <hr class="form-divider">

        <!-- Pinned location display -->
        <div class="pinned-location-field">
          <label class="form-label">Pinned Location</label>
          <div class="pinned-location-display" id="pinned-location-display">
            <span class="material-symbols-outlined" id="pinned-location-icon">location_off</span>
            <span id="pinned-location-text">Tap the map above to pin your location.</span>
          </div>
        </div>

        <hr class="form-divider">

        <!-- ── Rescue Section ── -->
        <div class="form-group rescue-section <?= $postSeverity === 'passable' ? 'hidden' : '' ?>" id="rescue-section">
          <label class="form-label">Do you need rescue?</label>
          <div class="rescue-toggle-group">

            <!-- No rescue -->
            <label class="rescue-option">
              <input
                type="radio" name="rescue_status" value="Not Required"
                <?= ($postRescue === '' || $postRescue === 'Not Required') ? 'checked' : '' ?>
              >
              <div class="rescue-card">
                <span class="rescue-icon material-symbols-outlined">check_circle</span>
                <span class="rescue-label">No Rescue Needed</span>
                <span class="rescue-desc">I am safe and do not need assistance</span>
              </div>
            </label>

            <!-- Rescue needed -->
            <label class="rescue-option">
              <input
                type="radio" name="rescue_status" value="Rescue Needed"
                <?= $postRescue === 'Rescue Needed' ? 'checked' : '' ?>
              >
              <div class="rescue-card">
                <span class="rescue-icon material-symbols-outlined">sos</span>
                <span class="rescue-label">Rescue Needed</span>
                <span class="rescue-desc">I or others are trapped and need help</span>
              </div>
            </label>

          </div>

          <!-- Extra rescue details — shown only when Rescue Needed is picked -->
          <div class="rescue-details <?= $postRescue === 'Rescue Needed' ? 'visible' : '' ?>" id="rescue-details">
            <div class="form-group">
              <label class="form-label" for="rescue-people">Number of people needing rescue</label>
              <input
                type="number"
                class="form-input"
                id="rescue-people"
                name="rescue_people"
                placeholder="e.g. 4"
                min="1"
                value="<?= htmlspecialchars($_POST['rescue_people'] ?? '') ?>"
              >
            </div>
            <div class="form-group">
              <label class="form-label" for="rescue-note">Additional rescue details</label>
              <textarea
                class="form-input"
                id="rescue-note"
                name="rescue_note"
                placeholder="e.g. Elderly and children present, located on 2nd floor..."
                style="min-height:60px;"
              ><?= htmlspecialchars($_POST['rescue_note'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <hr class="form-divider">

        <!-- Photo attach -->
        <div class="form-group">
          <button type="button" class="photo-attach-btn" id="attach-photo-btn">
            <span class="material-symbols-outlined">photo_camera</span>
            Attach Photo (optional)
          </button>
          <input type="file" id="photo-input" name="report_photo" accept="image/*">
          <div class="photo-preview" id="photo-preview">
            <img id="photo-img" src="" alt="Flood photo preview">
          </div>
        </div>

        <button type="submit" class="btn-report-submit" id="submit-btn">
          Submit Report
        </button>

      </form>
    </div>
  </div>

</section>

<!-- Success modal -->
<div class="report-modal-overlay <?= $submitSuccess ? 'visible' : '' ?>" id="success-overlay">
  <div class="report-success-modal">
    <div class="report-success-icon">
      <span class="material-symbols-outlined">check</span>
    </div>
    <h3>Flood Report created successfully!</h3>
    <p>Please wait while our local authorities verify your report. You'll be notified once it's approved.</p>
    <button class="btn-report-ok" id="ok-btn">Ok</button>
  </div>
</div>

<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="assets/js/report-flood.js"></script>