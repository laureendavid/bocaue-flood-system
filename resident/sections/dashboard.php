<?php
/* =============================================================
   sections/dashboard.php
   Included by main.php when ?page=dashboard
   $conn is available from main.php via require_once config/db.php
   ============================================================= */

// ── Safety centers ────────────────────────────────────────────
$safetyCenters = [];
$sc_sql = "
    SELECT ec.center_name, ec.capacity, ec.occupancy,
           l.barangay, l.full_address
    FROM evacuation_centers ec
    JOIN locations l ON ec.location_id = l.location_id
    ORDER BY (ec.occupancy / ec.capacity) ASC
    LIMIT 4
";
$sc_result = $conn->query($sc_sql);
if ($sc_result) {
    while ($row = $sc_result->fetch_assoc()) {
        $pct = $row['capacity'] > 0
            ? ($row['occupancy'] / $row['capacity']) * 100 : 0;
        $row['status'] = $pct >= 100 ? 'full' : ($pct >= 75 ? 'limited' : 'available');
        $safetyCenters[] = $row;
    }
}

// ── Announcements ─────────────────────────────────────────────
$announcements = [];
$ann_sql = "
    SELECT
        a.title,
        a.message,
        a.created_at,
        b.barangay_name,
        u.full_name  AS author,
        u.role       AS author_role,
        u.profile_picture AS author_pic
    FROM announcements a
    LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
    LEFT JOIN users u     ON a.created_by  = u.user_id
    WHERE (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
    ORDER BY a.created_at DESC
    LIMIT 3
";
$ann_result = $conn->query($ann_sql);
if ($ann_result) {
    while ($row = $ann_result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// ── Community posts ───────────────────────────────────────────
// Fetches post content + author info + linked report data
$posts = [];
$posts_sql = "
    SELECT
        cp.post_id,
        cp.content,
        cp.post_image,
        cp.created_at,
        u.full_name,
        u.role            AS user_role,
        u.profile_picture,
        b.barangay_name,
        l.municipality,
        r.flood_severity,
        r.water_level,
        r.rescue_status,
        r.status          AS report_status
    FROM community_posts cp
    INNER JOIN users     u  ON cp.user_id    = u.user_id
    INNER JOIN barangays b  ON u.barangay_id = b.barangay_id
    LEFT  JOIN reports   r  ON cp.report_id  = r.report_id
    LEFT  JOIN locations l  ON r.location_id = l.location_id
    ORDER BY cp.created_at DESC
    LIMIT 10
";
$posts_result = $conn->query($posts_sql);
if ($posts_result) {
    while ($row = $posts_result->fetch_assoc()) {
        $posts[] = $row;
    }
}

// ── Helper: formatted date ─────────────────────────────────────
function fmtDate(string $dt): string {
    return date('F j, Y \a\t g:iA', strtotime($dt));
}

// ── Helper: role/rescue badge (top-right of card) ─────────────
function roleBadge(string $role, ?string $rescueStatus): string {
    if ($rescueStatus === 'Rescue Needed')   return '<span class="feed-role-badge badge-rescue">Rescue Needed</span>';
    if ($rescueStatus === 'Being Rescued')   return '<span class="feed-role-badge badge-being-rescued">Being Rescued</span>';
    if ($rescueStatus === 'Rescued')         return '<span class="feed-role-badge badge-rescued">Rescued</span>';
    if ($role === 'LGU')                     return '<span class="feed-role-badge badge-official">Official</span>';
    return '';
}

// ── Helper: flood severity dot-tag ────────────────────────────
function floodTag(?string $sev): string {
    if (!$sev) return '';
    $map = [
        'Impassable'     => ['#ef4444', 'High-Level Flood'],
        'Limited Access' => ['#f59e0b', 'Mid-Level Flood'],
        'Passable'       => ['#22c55e', 'Low-Level Flood'],
    ];
    [$color, $label] = $map[$sev] ?? ['#94a3b8', htmlspecialchars($sev)];
    return '<span class="feed-tag flood-tag">'
         . '<span class="feed-dot" style="background:' . $color . ';"></span>'
         . htmlspecialchars($label)
         . '</span>';
}

// ── Helper: water level dot-tag ───────────────────────────────
function waterTag(?string $wl): string {
    if (!$wl || $wl === 'none') return '';
    $map = [
        'ankle' => ['#22c55e', 'Ankle-Level'],
        'knee'  => ['#f59e0b', 'Knee-Level'],
        'waist' => ['#f59e0b', 'Waist-Level'],
        'chest' => ['#ef4444', 'Chest-Level'],
        'above' => ['#ef4444', 'Above Head'],
    ];
    [$color, $label] = $map[strtolower($wl)] ?? ['#f59e0b', ucfirst($wl) . '-Level'];
    return '<span class="feed-tag water-tag">'
         . '<span class="feed-dot" style="background:' . $color . ';"></span>'
         . htmlspecialchars($label)
         . '</span>';
}
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

      <!-- ══════════ ANNOUNCEMENTS ══════════ -->
      <?php if (!empty($announcements)): ?>
        <div class="dash-section-label">
          <span class="material-symbols-outlined">campaign</span>
          Announcements
        </div>

        <?php foreach ($announcements as $ann): ?>
          <div class="feed-card ann-feed-card">

            <!-- Header -->
            <div class="feed-card-header">
              <div class="feed-user-info">
                <div class="feed-avatar">
                  <?php if (!empty($ann['author_pic'])): ?>
                    <img src="<?= htmlspecialchars($ann['author_pic']) ?>" alt="avatar"/>
                  <?php else: ?>
                    <span class="material-symbols-outlined">campaign</span>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="feed-username">
                    <?= htmlspecialchars($ann['author'] ?? 'LGU Bocaue') ?>
                  </div>
                  <div class="feed-timestamp"><?= fmtDate($ann['created_at']) ?></div>
                </div>
              </div>
              <span class="feed-role-badge badge-official">Official</span>
            </div>

            <!-- Announcement title -->
            <div class="ann-card-title"><?= htmlspecialchars($ann['title']) ?></div>

            <!-- Message body -->
            <div class="feed-content-area">
              <div class="feed-text">
                <?= nl2br(htmlspecialchars($ann['message'])) ?>
              </div>
            </div>

            <!-- Tags -->
            <div class="feed-tags-row">
              <span class="feed-tag location-tag">
                <span class="material-symbols-outlined">location_on</span>
                <?= htmlspecialchars($ann['barangay_name'] ?? 'All Barangays') ?>, Bulacan
              </span>
              <span class="feed-tag ann-type-tag">
                <span class="feed-dot" style="background:#2563eb;"></span>
                Announcement
              </span>
            </div>

            <!-- Action bar -->
            <div class="feed-action-bar">
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">verified</span>
                Trusted Report
              </button>
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">chat_bubble</span>
                Comment
              </button>
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">repeat</span>
                Repost
              </button>
            </div>

          </div><!-- end ann feed-card -->
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- ══════════ COMMUNITY FEED ══════════ -->
      <div class="dash-section-label">
        <span class="material-symbols-outlined">groups</span>
        Community Feed
      </div>

      <?php if (empty($posts)): ?>
        <div class="feed-card">
          <div class="feed-empty">No community posts yet. Be the first to report!</div>
        </div>

      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <div class="feed-card">

            <!-- Header -->
            <div class="feed-card-header">
              <div class="feed-user-info">
                <div class="feed-avatar">
                  <?php if (!empty($post['profile_picture'])): ?>
                    <img
                      src="<?= htmlspecialchars($post['profile_picture']) ?>"
                      alt="<?= htmlspecialchars($post['full_name']) ?>"
                    />
                  <?php else: ?>
                    <span class="material-symbols-outlined">person</span>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="feed-username"><?= htmlspecialchars($post['full_name']) ?></div>
                  <div class="feed-timestamp"><?= fmtDate($post['created_at']) ?></div>
                </div>
              </div>
              <?= roleBadge($post['user_role'] ?? '', $post['rescue_status'] ?? null) ?>
            </div>

            <!-- Post text -->
            <div class="feed-content-area">
              <div class="feed-text">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
              </div>
            </div>

            <!-- Post image (if any) -->
            <?php if (!empty($post['post_image'])): ?>
              <div class="feed-image-wrap">
                <img
                  src="<?= htmlspecialchars($post['post_image']) ?>"
                  alt="Post image"
                  loading="lazy"
                />
              </div>
            <?php endif; ?>

            <!-- Tags row -->
            <div class="feed-tags-row">
              <span class="feed-tag location-tag">
                <span class="material-symbols-outlined">location_on</span>
                <?= htmlspecialchars($post['barangay_name']) ?>,
                <?= htmlspecialchars($post['municipality'] ?? 'Bulacan') ?>
              </span>
              <?= floodTag($post['flood_severity'] ?? null) ?>
              <?= waterTag($post['water_level']    ?? null) ?>
            </div>

            <!-- Action bar -->
            <div class="feed-action-bar">
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">verified</span>
                Trusted Report
              </button>
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">chat_bubble</span>
                Comment
              </button>
              <button class="feed-action-btn">
                <span class="material-symbols-outlined">repeat</span>
                Repost
              </button>
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
            <span id="weather-condition-text">Heavy Rain</span>
          </div>
          <div class="weather-time" id="weather-time">--:--<br>--- --/--</div>
        </div>
        <div class="weather-temp"><span id="weather-temp">--</span><sup>°</sup></div>
        <div class="weather-range" id="weather-range">--° / --°</div>
        <div class="weather-location">
          <span class="material-symbols-outlined">location_on</span>
          Bocaue, Bulacan
        </div>
        <div class="weather-forecast" id="weather-forecast">
          <div class="forecast-day"><div class="day-label">TUES</div><span class="material-symbols-outlined">rainy</span></div>
          <div class="forecast-day"><div class="day-label">WED</div><span class="material-symbols-outlined">rainy</span></div>
          <div class="forecast-day"><div class="day-label">THURS</div><span class="material-symbols-outlined">thunderstorm</span></div>
          <div class="forecast-day"><div class="day-label">FRI</div><span class="material-symbols-outlined">partly_cloudy_day</span></div>
        </div>
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
              $stLabel  = ['available' => 'Available', 'limited' => 'Limited', 'full' => 'Full'][$stCls];
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