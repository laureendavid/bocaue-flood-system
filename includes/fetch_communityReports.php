<?php
// fetch_communityReports.php
// Fetches all approved flood reports for Community Posts display

$sql = "
    SELECT 
        r.report_id,
        r.description,
        r.report_image,
        r.created_at,
        u.full_name,
        u.profile_picture,
        wl.level_name AS water_level,
        fs.severity_name AS severity,
        rs.status_name AS rescue_status,
        l.full_address,
        b.barangay_name,
        b.municipality,
        b.province
    FROM reports r
    JOIN users u ON r.user_id = u.user_id
    JOIN locations l ON r.location_id = l.location_id
    JOIN barangays b ON l.barangay_id = b.barangay_id
    LEFT JOIN water_levels wl ON r.water_level_id = wl.water_level_id
    LEFT JOIN flood_severity fs ON r.severity_id = fs.severity_id
    LEFT JOIN rescue_status rs ON r.rescue_status_id = rs.rescue_status_id
    WHERE r.status_id = 2
    ORDER BY r.created_at DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($report = $result->fetch_assoc()):
        $hasImage = !empty($report['report_image']);

        // Rescue status badge color
        $rescueBadgeClass = 'badge--neutral';
        $rescueLabel = htmlspecialchars($report['rescue_status'] ?? 'N/A');
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
            case 'Not Required':
                $rescueBadgeClass = 'badge--neutral';
                break;
        }

        // Severity badge color
        $severityClass = 'badge--neutral';
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

        // Format address
        $address = !empty($report['full_address'])
            ? htmlspecialchars($report['full_address'])
            : htmlspecialchars($report['barangay_name'] . ', ' . $report['municipality'] . ', ' . $report['province']);

        // Format date
        $date = date('F j, Y, \a\t g:i a', strtotime($report['created_at']));

        // Profile picture fallback
        $profilePic = !empty($report['profile_picture'])
            ? '/' . ltrim($report['profile_picture'], '/')
            : '/assets/img/default-avatar.png';
        ?>

        <article class="post-card" data-report-id="<?= $report['report_id'] ?>">

            <div class="post-card__header">
                <div class="post-card__user">
                    <img src="<?= htmlspecialchars($profilePic) ?>" alt="<?= htmlspecialchars($report['full_name']) ?>'s avatar"
                        class="post-card__avatar" onerror="this.src='/assets/img/default-avatar.png'">
                    <div class="post-card__user-info">
                        <span class="post-card__name">
                            <?= htmlspecialchars($report['full_name']) ?>
                        </span>
                        <span class="post-card__meta">
                            <?= $date ?> &bull;
                            <?= $address ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="post-card__body <?= $hasImage ? 'post-card__body--with-image' : '' ?>">
                <?php if ($hasImage): ?>
                    <div class="post-card__image-wrap">
                        <img src="/<?= ltrim(htmlspecialchars($report['report_image']), '/') ?>" alt="Flood report image"
                            class="post-card__image" loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="post-card__content">
                    <?php if (!empty($report['description'])): ?>
                        <p class="post-card__description">
                            <?= nl2br(htmlspecialchars($report['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="post-card__tags">
                        <?php if (!empty($report['water_level'])): ?>
                            <span class="post-tag post-tag--water">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                                </svg>
                                Water Level:
                                <?= htmlspecialchars($report['water_level']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($report['severity'])): ?>
                            <span class="post-tag <?= $severityClass ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                                </svg>
                                Flood Severity:
                                <?= htmlspecialchars($report['severity']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="post-card__footer">
                <span class="rescue-badge <?= $rescueBadgeClass ?>">
                    <?= $rescueLabel ?>
                </span>
            </div>

        </article>

        <?php
    endwhile;
else:
    ?>
    <article class="post-card post-card--empty">
        <p>No community reports to display.</p>
    </article>
<?php endif; ?>