<?php
/* =============================================================
   sections/dashboard.php
   Included by main.php when ?page=dashboard
   $conn is available from main.php via require_once config/db.php
   ============================================================= */

// ── Safety centers ────────────────────────────────────────────
$safetyCenters = [];
$scStmt = $conn->prepare("
    SELECT
        ec.center_id,
        ec.center_name,
        ec.capacity,
        ec.occupancy,
        COALESCE(l.full_address, 'Bocaue, Bulacan') AS barangay,
        l.full_address
    FROM evacuation_centers ec
    JOIN locations l ON ec.location_id = l.location_id
    GROUP BY ec.center_id, ec.center_name, ec.capacity, ec.occupancy, l.full_address
    ORDER BY (ec.capacity - ec.occupancy) DESC, ec.center_name ASC
");
if ($scStmt) {
    if ($scStmt->execute()) {
        $scResult = $scStmt->get_result();
        while ($row = $scResult->fetch_assoc()) {
            $pct = (int) ($row['capacity'] ?? 0) > 0 ? ((int) $row['occupancy'] / (int) $row['capacity']) * 100 : 0;
            $row['status'] = $pct >= 100 ? 'full' : ($pct >= 75 ? 'limited' : 'available');
            $safetyCenters[] = $row;
        }
    } else {
        error_log('Dashboard safety center query failed: ' . $scStmt->error);
    }
    $scStmt->close();
} else {
    error_log('Dashboard safety center prepare failed: ' . $conn->error);
}

function relativeTime(string $dateTime): string
{
    $timestamp = strtotime($dateTime);
    if (!$timestamp) {
        return 'Just now';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 172800) {
        return 'Yesterday';
    }

    return date('M d, Y h:i A', $timestamp);
}

function normalizeRescueStatus(?string $rescueStatus): array
{
    $value = strtolower(trim((string) $rescueStatus));
    if ($value === 'rescue needed' || $value === 'needed') {
        return ['Needed', 'feed-role-badge badge-rescue'];
    }
    if ($value === 'not required' || $value === 'not needed') {
        return ['Not Needed', 'feed-role-badge badge-rescued'];
    }
    return ['Not Specified', 'feed-role-badge badge-being-rescued'];
}

$placeholderAvatar = 'https://placehold.co/56x56/e2e8f0/475569?text=U';
$feedItems = [];
$feedSeen = [];

function detectFirstExistingColumn(mysqli $conn, string $tableName, array $candidates): ?string
{
    $lookupStmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    if (!$lookupStmt) {
        return null;
    }

    foreach ($candidates as $candidate) {
        $lookupStmt->bind_param('ss', $tableName, $candidate);
        if ($lookupStmt->execute()) {
            $result = $lookupStmt->get_result()->fetch_assoc();
            if (!empty($result['COLUMN_NAME'])) {
                $lookupStmt->close();
                return $candidate;
            }
        }
    }

    $lookupStmt->close();
    return null;
}

$announcementIdColumn = detectFirstExistingColumn($conn, 'announcements', ['announcement_id', 'id']);
$reportIdColumn = detectFirstExistingColumn($conn, 'reports', ['report_id', 'id']);
$rescueStatusColumn = detectFirstExistingColumn($conn, 'reports', ['rescue_status']);

if ($announcementIdColumn) {
    $announcementSql = "
        SELECT DISTINCT
            a.`" . $announcementIdColumn . "` AS source_id,
            a.created_at,
            COALESCE(u.full_name, 'LGU Bocaue') AS posted_by,
            COALESCE(u.profile_picture, '') AS avatar,
            TRIM(CONCAT(COALESCE(a.title, ''), '\n', COALESCE(a.message, ''))) AS content,
            CONCAT(COALESCE(b.barangay_name, 'Bocaue'), ', Bulacan') AS location
        FROM announcements a
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        LEFT JOIN users u ON a.created_by = u.user_id
        ORDER BY a.created_at DESC
    ";
    $announcementStmt = $conn->prepare($announcementSql);
    if ($announcementStmt && $announcementStmt->execute()) {
        $announcementResult = $announcementStmt->get_result();
        while ($row = $announcementResult->fetch_assoc()) {
            $recordKey = 'announcement:' . (string) $row['source_id'];
            if (isset($feedSeen[$recordKey])) {
                continue;
            }
            $feedSeen[$recordKey] = true;
            $feedItems[] = [
                'type' => 'announcement',
                'source_id' => (string) $row['source_id'],
                'created_at' => $row['created_at'],
                'posted_by' => $row['posted_by'] ?: 'LGU Bocaue',
                'avatar' => !empty($row['avatar']) ? $row['avatar'] : $placeholderAvatar,
                'content' => $row['content'] ?: 'No content available.',
                'location' => trim((string) ($row['location'] ?? 'Bocaue, Bulacan')) ?: 'Bocaue, Bulacan',
                'image' => '',
                'rescue' => normalizeRescueStatus(null),
                'icon' => 'campaign',
            ];
        }
        $announcementStmt->close();
    } else {
        error_log('Dashboard announcement query failed: ' . ($announcementStmt ? $announcementStmt->error : $conn->error));
    }
}

if ($reportIdColumn) {
    $rescueExpr = $rescueStatusColumn
        ? "COALESCE(r.`" . $rescueStatusColumn . "`, 'Not Specified')"
        : "'Not Specified'";

    $reportSql = "
        SELECT DISTINCT
            r.`" . $reportIdColumn . "` AS source_id,
            r.created_at,
            COALESCE(u.full_name, 'Resident') AS posted_by,
            COALESCE(u.profile_picture, '') AS avatar,
            COALESCE(r.description, 'No description provided.') AS content,
            COALESCE(l.full_address, 'Bocaue, Bulacan') AS location,
            COALESCE(r.report_image, '') AS image,
            " . $rescueExpr . " AS rescue_status
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN locations l ON r.location_id = l.location_id
        ORDER BY r.created_at DESC
    ";
    $reportStmt = $conn->prepare($reportSql);
    if ($reportStmt && $reportStmt->execute()) {
        $reportResult = $reportStmt->get_result();
        while ($row = $reportResult->fetch_assoc()) {
            $recordKey = 'report:' . (string) $row['source_id'];
            if (isset($feedSeen[$recordKey])) {
                continue;
            }
            $feedSeen[$recordKey] = true;
            $feedItems[] = [
                'type' => 'report',
                'source_id' => (string) $row['source_id'],
                'created_at' => $row['created_at'],
                'posted_by' => $row['posted_by'] ?: 'Resident',
                'avatar' => !empty($row['avatar']) ? $row['avatar'] : $placeholderAvatar,
                'content' => $row['content'] ?: 'No content available.',
                'location' => trim((string) ($row['location'] ?? 'Bocaue, Bulacan')) ?: 'Bocaue, Bulacan',
                'image' => trim((string) ($row['image'] ?? '')),
                'rescue' => normalizeRescueStatus($row['rescue_status'] ?? null),
                'icon' => 'flood',
            ];
        }
        $reportStmt->close();
    } else {
        error_log('Dashboard report query failed: ' . ($reportStmt ? $reportStmt->error : $conn->error));
    }
}

usort($feedItems, static function (array $a, array $b): int {
    return strtotime((string) ($b['created_at'] ?? '')) <=> strtotime((string) ($a['created_at'] ?? ''));
});
?>

<section id="page-dashboard" class="page active">
  <div class="dashboard-grid">

    <!-- ===== LEFT COLUMN ===== -->
    <div class="dashboard-left">

      <!-- FLOOD MAP -->
      <div class="map-card card">
        <h2 class="card-section-label">Flood Map</h2>
        <div class="map-embed-wrap">
          <iframe
            class="map-embed"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15440.123456789!2d120.9!3d14.8!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b0e2d3e3e3e3%3A0x1234567890abcdef!2sBocaue%2C+Bulacan!5e0!3m2!1sen!2sph!4v1234567890"
            allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Flood Map — Bocaue, Bulacan">
          </iframe>
        </div>
      </div>

      <div class="dash-section-label">
        <span class="material-symbols-outlined">groups</span>
        Flood Feed
      </div>

      <?php if (empty($feedItems)): ?>
        <div class="feed-card">
          <div class="feed-empty">No posts to display yet.</div>
        </div>

      <?php else: ?>
        <?php foreach ($feedItems as $item): ?>
          <div class="feed-card" id="feed-<?= htmlspecialchars($item['type'] . '-' . $item['source_id']) ?>">

            <!-- Header -->
            <div class="feed-card-header">
              <div class="feed-user-info">
                <div class="feed-avatar">
                  <img src="<?= htmlspecialchars($item['avatar']) ?>" alt="<?= htmlspecialchars($item['posted_by']) ?>" />
                </div>
                <div>
                  <div class="feed-username"><?= htmlspecialchars($item['posted_by']) ?></div>
                  <div class="feed-timestamp"><?= htmlspecialchars(relativeTime($item['created_at'])) ?></div>
                </div>
              </div>
              <span class="<?= htmlspecialchars($item['rescue'][1]) ?>"><?= htmlspecialchars($item['rescue'][0]) ?></span>
            </div>

            <div class="feed-content-area">
              <div class="feed-text">
                <?= nl2br(htmlspecialchars($item['content'])) ?>
              </div>
            </div>

            <?php if (!empty($item['image'])): ?>
              <div class="feed-image-wrap">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="Post image" loading="lazy" />
              </div>
            <?php endif; ?>

            <div class="feed-tags-row">
              <span class="feed-tag location-tag">
                <span class="material-symbols-outlined">location_on</span>
                <?= htmlspecialchars($item['location']) ?>
              </span>
              <span class="feed-tag ann-type-tag">
                <span class="material-symbols-outlined" style="font-size:14px;"><?= htmlspecialchars($item['icon']) ?></span>
                <?= $item['type'] === 'announcement' ? 'Announcement' : 'Flood Report' ?>
              </span>
            </div>

          </div><!-- end post feed-card -->
        <?php endforeach; ?>
      <?php endif; ?>

    </div><!-- end dashboard-left -->

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="dashboard-right">

      <!-- WEATHER WIDGET -->
      <div class="weather-widget card" id="weather-widget">
        <div class="weather-top">
          <div class="weather-condition">
            <span class="material-symbols-outlined" id="weather-icon">thunderstorm</span>
            <span id="weather-condition-text">Loading weather...</span>
          </div>
          <div class="weather-time" id="weather-time">--:--<br>-- ---</div>
        </div>
        <div class="weather-temp"><span id="weather-temp">--</span><sup>°C</sup></div>
        <div class="weather-range" id="weather-range">H: --°C / L: --°C</div>
        <div class="weather-location">
          <span class="material-symbols-outlined">location_on</span>
          Bocaue, Bulacan
        </div>
        <div class="weather-meta" id="weather-meta">
          <div class="weather-meta-item">
            <span class="material-symbols-outlined">humidity_percentage</span>
            <span id="weather-humidity">--%</span>
          </div>
          <div class="weather-meta-item">
            <span class="material-symbols-outlined">air</span>
            <span id="weather-wind">-- km/h</span>
          </div>
          <div class="weather-meta-item">
            <span class="material-symbols-outlined">rainy</span>
            <span id="weather-rain">--%</span>
          </div>
        </div>
        <div class="weather-forecast" id="weather-forecast">
          <div class="forecast-day"><div class="day-label">--</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
          <div class="forecast-day"><div class="day-label">--</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
          <div class="forecast-day"><div class="day-label">--</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
          <div class="forecast-day"><div class="day-label">--</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
          <div class="forecast-day"><div class="day-label">--</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
        </div>
        <div class="weather-error" id="weather-error" style="display:none;"></div>
      </div>

      <!-- SAFETY CENTERS -->
      <div class="safety-card card">
        <h3 class="card-section-label">Safety Centers</h3>
        <ul id="dash-safety-list" class="safety-center-list">

          <?php if (empty($safetyCenters)): ?>
            <li class="feed-empty">No safety centers available.</li>
          <?php else: ?>
            <?php foreach ($safetyCenters as $sc):
              $pct      = $sc['capacity'] > 0
                          ? min(round(($sc['occupancy'] / $sc['capacity']) * 100), 100) : 0;
              $stCls    = $sc['status'];
              $stLabel  = ['available' => 'Available', 'limited' => 'Nearly Full', 'full' => 'Full'][$stCls];
              $barColor = ['available' => '#22c55e', 'limited' => '#f59e0b', 'full' => '#ef4444'][$stCls];
            ?>
              <li class="safety-center-item">
                <div class="sc-item-top">
                  <span class="sc-item-name"><?= htmlspecialchars($sc['center_name']) ?></span>
                  <span class="sc-item-badge sc-badge-<?= $stCls ?>"><?= $stLabel ?></span>
                </div>
                <div class="sc-item-addr">
                  <span class="material-symbols-outlined">location_on</span>
                  <?= htmlspecialchars($sc['barangay']) ?>
                </div>
                <div class="sc-item-bar-row">
                  <div class="sc-item-bar-bg">
                    <div class="sc-item-bar-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                  </div>
                  <span class="sc-item-count"><?= $sc['occupancy'] ?>/<?= $sc['capacity'] ?></span>
                </div>
              </li>
            <?php endforeach; ?>
            <li>
              <a href="?page=safety-centers" class="sc-view-all">
                View all safety centers
                <span class="material-symbols-outlined">arrow_forward</span>
              </a>
            </li>
          <?php endif; ?>

        </ul>
      </div>

    </div><!-- end dashboard-right -->
  </div><!-- end dashboard-grid -->
</section>