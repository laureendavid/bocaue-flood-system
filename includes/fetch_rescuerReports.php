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
        r.rescue_people_count,
        r.rescue_description,
        u.full_name,
        u.profile_picture,
        wl.level_name    AS water_level,
        fs.severity_name AS severity,
        rs.status_name   AS rescue_status,
        rs.rescue_status_id,
        l.full_address,
        l.latitude,
        l.longitude,
        b.barangay_name,
        b.municipality,
        b.province
    FROM reports r
    JOIN users u          ON r.user_id           = u.user_id
    JOIN locations l      ON r.location_id        = l.location_id
    JOIN barangays b      ON l.barangay_id         = b.barangay_id
    LEFT JOIN water_levels   wl ON r.water_level_id  = wl.water_level_id
    LEFT JOIN flood_severity fs ON r.severity_id     = fs.severity_id
    LEFT JOIN rescue_status  rs ON r.rescue_status_id = rs.rescue_status_id
    WHERE r.status_id = 2
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($report = $result->fetch_assoc()):

        $hasImage = !empty($report['report_image']);

        /* Profile picture */
        $profilePic = !empty($report['profile_picture'])
            ? (filter_var($report['profile_picture'], FILTER_VALIDATE_URL)
                ? $report['profile_picture']
                : '/' . ltrim($report['profile_picture'], '/'))
            : '/assets/img/default-avatar.png';

        /* Report image */
        $reportImage = trim($report['report_image'] ?? '');
        $imageSrc = filter_var($reportImage, FILTER_VALIDATE_URL)
            ? $reportImage
            : ($reportImage ? '/' . ltrim($reportImage, '/') : '');

        /* Address */
        $address = !empty($report['full_address'])
            ? htmlspecialchars($report['full_address'])
            : htmlspecialchars($report['barangay_name'] . ', ' . $report['municipality'] . ', ' . $report['province']);

        /* Date */
        $date = date('F j, Y, g:i a', strtotime($report['created_at']));

        /* ─────────────────────────────────────────────────
         * Flow:  Rescue Needed (2) → Being Rescued (3) → Rescued (4)
         *
         * Only "Rescue Needed" is clickable on load.
         * After clicking → updates to "Being Rescued" (still clickable).
         * After clicking again → updates to "Rescued" (static).
         * "Not Required" (1) is always static.
         * ───────────────────────────────────────────────── */
        $rescueStatus = $report['rescue_status'] ?? 'Not Required';
        $rescueLabel = htmlspecialchars($rescueStatus);

        $nextStatusId = null;   // what clicking will advance to
        $modalType = null;   // which confirmation copy to show
        $rescueBadgeClass = 'badge--neutral';

        switch ($rescueStatus) {
            case 'Rescue Needed':
                $rescueBadgeClass = 'badge--danger';
                $nextStatusId = 3;        // → Being Rescued
                $modalType = 'start';
                break;
            case 'Being Rescued':
                $rescueBadgeClass = 'badge--warning';
                $nextStatusId = 4;        // → Rescued
                $modalType = 'finish';
                break;
            case 'Rescued':
                $rescueBadgeClass = 'badge--success';
                break;
            case 'Not Required':
            default:
                $rescueBadgeClass = 'badge--neutral';
                break;
        }

        $isClickable = $nextStatusId !== null;

        /* Severity class */
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
        ?>

        <article class="post-card" data-report-id="<?= (int) $report['report_id'] ?>">

            <!-- HEADER -->
            <div class="post-card__header">
                <div class="post-card__user">
                    <img src="<?= htmlspecialchars($profilePic) ?>" class="post-card__avatar"
                        onerror="this.src='/assets/img/default-avatar.png'">
                    <div class="post-card__user-info">
                        <span class="post-card__name"><?= htmlspecialchars($report['full_name']) ?></span>
                        <span class="post-card__meta"><?= $date ?> &bull; <?= $address ?></span>
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

                    <?php if (!empty($report['rescue_people_count']) || !empty($report['rescue_description'])): ?>
                        <div class="post-card__rescue-info">
                            <?php if (!empty($report['rescue_people_count'])): ?>
                                <span class="rescue-info-item">
                                    👥 <?= (int) $report['rescue_people_count'] ?> person(s) need rescue
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($report['rescue_description'])): ?>
                                <p class="rescue-info-desc">
                                    <?= nl2br(htmlspecialchars($report['rescue_description'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-card__tags">
                        <?php if (!empty($report['water_level'])): ?>
                            <span class="post-tag post-tag--water">
                                💧 Water: <?= htmlspecialchars($report['water_level']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($report['severity'])): ?>
                            <span class="post-tag <?= $severityClass ?>">
                                ⚠️ Severity: <?= htmlspecialchars($report['severity']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- FOOTER -->
            <div class="post-card__footer">

                <?php if ($isClickable): ?>
                    <button type="button" class="rescue-badge rescue-badge--btn <?= $rescueBadgeClass ?>"
                        data-report-id="<?= (int) $report['report_id'] ?>" data-next-status-id="<?= $nextStatusId ?>"
                        data-modal-type="<?= $modalType ?>" data-reporter="<?= htmlspecialchars($report['full_name']) ?>"
                        title="Click to update rescue status">
                        <?= $rescueLabel ?>
                    </button>
                <?php else: ?>
                    <span class="rescue-badge <?= $rescueBadgeClass ?>">
                        <?= $rescueLabel ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                    <button type="button" class="btn-map" data-lat="<?= $report['latitude'] ?>"
                        data-lng="<?= $report['longitude'] ?>" data-name="<?= htmlspecialchars($report['full_name']) ?>">
                        View on Map 📍
                    </button>
                <?php endif; ?>

            </div>

        </article>

    <?php endwhile;
endif;
?>