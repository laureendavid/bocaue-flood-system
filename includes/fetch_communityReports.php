<?php
require_once '../config/db.php';

$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT
        r.report_id,
        r.description,
        r.report_image,
        r.created_at,
        u.full_name,
        u.profile_picture,
        wl.level_name    AS water_level,
        fs.severity_name AS severity,
        rs.status_name   AS rescue_status,
        l.full_address,
        l.latitude,
        l.longitude,
        b.barangay_name,
        b.municipality,
        b.province,
        ru.full_name     AS assigned_rescuer_name
    FROM reports r
    JOIN  users          u  ON r.user_id              = u.user_id
    JOIN  locations      l  ON r.location_id           = l.location_id
    JOIN  barangays      b  ON l.barangay_id            = b.barangay_id
    LEFT JOIN water_levels   wl ON r.water_level_id    = wl.water_level_id
    LEFT JOIN flood_severity fs ON r.severity_id       = fs.severity_id
    LEFT JOIN rescue_status  rs ON r.rescue_status_id  = rs.rescue_status_id
    LEFT JOIN users          ru ON r.assigned_rescuer_id = ru.user_id
    WHERE r.status_id = 2
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($report = $result->fetch_assoc()):

        $hasImage = !empty($report['report_image']);

        /* ── Profile picture ── */
        $profilePic = !empty($report['profile_picture'])
            ? (filter_var($report['profile_picture'], FILTER_VALIDATE_URL)
                ? $report['profile_picture']
                : '/' . ltrim($report['profile_picture'], '/'))
            : '/assets/img/default-avatar.png';

        /* ── Report image ── */
        $reportImage = trim($report['report_image'] ?? '');
        $imageSrc = filter_var($reportImage, FILTER_VALIDATE_URL)
            ? $reportImage
            : ($reportImage ? '/' . ltrim($reportImage, '/') : '');

        /* ── Address ── */
        $address = !empty($report['full_address'])
            ? htmlspecialchars($report['full_address'])
            : htmlspecialchars($report['barangay_name'] . ', ' . $report['municipality'] . ', ' . $report['province']);

        /* ── Date ── */
        $date = date('F j, Y, g:i a', strtotime($report['created_at']));

        /* ── Rescue badge class ── */
        $rescueBadgeClass = 'badge--neutral';
        switch ($report['rescue_status']) {
            case 'Rescue Needed':
                $rescueBadgeClass = 'badge--danger';
                break;
            case 'Being Rescued':
                $rescueBadgeClass = 'badge--warning';
                break;
            case 'Rescued':
                $rescueBadgeClass = 'badge--success';
                break;
        }

        /* ── Severity class ── */
        $severityClass = 'severity--neutral';
        switch ($report['severity']) {
            case 'Impassable':
                $severityClass = 'severity--impassable';
                break;
            case 'Limited Access':
                $severityClass = 'severity--limited';
                break;
            case 'Passable':
                $severityClass = 'severity--passable';
                break;
        }

        /* ── Assigned rescuer ── */
        $assignedRescuerName = !empty($report['assigned_rescuer_name'])
            ? htmlspecialchars($report['assigned_rescuer_name'])
            : null;
        ?>

        <article class="post-card" data-report-id="<?= (int) $report['report_id'] ?>">

            <!-- HEADER -->
            <div class="post-card__header">
                <div class="post-card__user">
                    <?php
                    $nameParts = explode(' ', trim($report['full_name']));
                    $initials = strtoupper(
                        substr($nameParts[0], 0, 1) .
                        (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
                    );
                    $avatarColors = ['#1d4ed8', '#1e5bb8', '#0b1f47', '#2563eb', '#1e40af', '#1d4ed8'];
                    $colorIndex = abs(crc32($report['full_name'])) % count($avatarColors);
                    $avatarBg = $avatarColors[$colorIndex];
                    ?>
                    <div class="post-card__avatar post-card__avatar--initials" style="background:<?= $avatarBg ?>;">
                        <?= $initials ?>
                    </div>
                    <div class="post-card__user-info">
                        <span class="post-card__name">
                            <?= htmlspecialchars($report['full_name']) ?>
                        </span>
                        <span class="post-card__meta">
                            <?= $date ?> • <?= $address ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- BODY -->
            <div class="post-card__body <?= $hasImage ? 'post-card__body--with-image' : '' ?>">

                <?php if ($hasImage): ?>
                    <div class="post-card__image-wrap">
                        <img src="<?= htmlspecialchars($imageSrc) ?>" class="post-card__image" loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="post-card__content">

                    <p class="post-card__description">
                        <?= nl2br(htmlspecialchars($report['description'])) ?>
                    </p>

                    <div class="post-card__tags">
                        <?php if (!empty($report['water_level'])): ?>
                            <span class="post-tag post-tag--water">
                                Water Level: <?= htmlspecialchars($report['water_level']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($report['severity'])): ?>
                            <span class="post-tag <?= $severityClass ?>">
                                Severity: <?= htmlspecialchars($report['severity']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- FOOTER -->
            <div class="post-card__footer">

                <?php if ($assignedRescuerName): ?>
                    <!-- May naka-assign na rescuer: ipakita sa loob ng badge, same style sa rescuer view -->
                    <span class="rescue-badge rescue-badge--locked <?= $rescueBadgeClass ?>">
                        🔒 <?= htmlspecialchars($report['rescue_status'] ?? 'Being Rescued') ?>
                        <small>by <?= $assignedRescuerName ?></small>
                    </span>
                <?php else: ?>
                    <!-- Walang rescuer pa o Rescued na / Not Required -->
                    <span class="rescue-badge <?= $rescueBadgeClass ?>">
                        <?= htmlspecialchars($report['rescue_status'] ?? 'Not Required') ?>
                    </span>
                <?php endif; ?>

                <!-- Map buttons -->
                <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                    <div class="post-card__map-btns">
                        <button type="button" class="btn-map" data-lat="<?= $report['latitude'] ?>"
                            data-lng="<?= $report['longitude'] ?>" data-name="<?= htmlspecialchars($report['full_name']) ?>">
                            View on Map 📍
                        </button>
                        <a class="btn-gmaps"
                            href="https://www.google.com/maps?q=<?= $report['latitude'] ?>,<?= $report['longitude'] ?>"
                            target="_blank" rel="noopener noreferrer">
                            Google Maps 🗺️
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </article>

    <?php endwhile;
endif;
?>