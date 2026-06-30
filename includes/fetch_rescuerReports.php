<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config/uploads.php';

$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

// Optional server-side status filter
$statusFilter = '';
$myRescuesFilter = false;
if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'my-rescues') {
        $myRescuesFilter = true;
        $statusFilter = "AND r.assigned_rescuer_id = $currentUserId
                 AND $currentUserId > 0
                 AND rs.status_name IN ('Being Rescued', 'Rescued')";
    } else {
        $safeStatus = $conn->real_escape_string($_GET['status']);
        $statusFilter = "AND rs.status_name = '$safeStatus'";
    }
}

// Barangay filter — match barangay_name against full_address
$barangayFilter = '';
if (!empty($_GET['barangay_id'])) {
    $safeBarangayId = (int) $_GET['barangay_id'];
    $bResult = $conn->query("SELECT barangay_name FROM barangays WHERE barangay_id = $safeBarangayId");
    if ($bResult && $bRow = $bResult->fetch_assoc()) {
        $safeName = $conn->real_escape_string($bRow['barangay_name']);
        $barangayFilter = "AND l.full_address LIKE '%$safeName%'";
    }
}
$searchFilter = '';
if (!empty($_GET['search'])) {
    $safeSearch = $conn->real_escape_string($_GET['search']);
    $searchFilter = "AND u.full_name LIKE '%$safeSearch%'";
}

$sql = "
    SELECT
        r.report_id,
        r.description,
        r.report_image,
        r.created_at,
        r.rescue_people_count,
        r.rescue_description,
        r.assigned_rescuer_id,
        u.full_name,
        u.profile_picture,
        wl.level_name    AS water_level,
        fs.severity_name AS severity,
        rs.status_name   AS rescue_status,
        rs.rescue_status_id,
        l.full_address,
        l.latitude,
        l.longitude,
        b.barangay_id,
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
    $statusFilter
    $barangayFilter
    $searchFilter
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($report = $result->fetch_assoc()):

        $hasImage = !empty($report['report_image']);

        $profilePic = bfis_resolve_media_url($report['profile_picture'] ?? '', '');

        $reportImage = trim($report['report_image'] ?? '');
        $imageSrc = bfis_resolve_media_url($reportImage, '');

        $address = !empty($report['full_address'])
            ? htmlspecialchars($report['full_address'])
            : htmlspecialchars($report['barangay_name'] . ', ' . $report['municipality'] . ', ' . $report['province']);

        $date = date('F j, Y, g:i a', strtotime($report['created_at']));
        $createdAtDate = date('Y-m-d', strtotime($report['created_at']));

        $rescueStatus = $report['rescue_status'] ?? 'Not Required';
        $rescueLabel = htmlspecialchars($rescueStatus);
        $assignedRescuerId = $report['assigned_rescuer_id'] ? (int) $report['assigned_rescuer_id'] : null;
        $assignedRescuerName = htmlspecialchars($report['assigned_rescuer_name'] ?? '');

        $isAssignedToMe = $assignedRescuerId && ($assignedRescuerId === $currentUserId);
        $isAssignedToOther = $assignedRescuerId && ($assignedRescuerId !== $currentUserId);

        $nextStatusId = null;
        $modalType = null;
        $rescueBadgeClass = 'badge--neutral';

        switch ($rescueStatus) {
            case 'Rescue Needed':
                $rescueBadgeClass = 'badge--danger';
                if (!$isAssignedToOther) {
                    $nextStatusId = 3;
                    $modalType = 'start';
                }
                break;
            case 'Being Rescued':
                $rescueBadgeClass = 'badge--warning';
                if ($isAssignedToMe) {
                    $nextStatusId = 4;
                    $modalType = 'finish';
                }
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

        <article class="post-card" data-report-id="<?= (int) $report['report_id'] ?>" data-created-at="<?= $createdAtDate ?>"
            data-rescue-status="<?= htmlspecialchars($rescueStatus) ?>" data-barangay-id="<?= (int) $report['barangay_id'] ?>"
            data-assigned-to-me="<?= $isAssignedToMe ? '1' : '0' ?>">

            <!-- HEADER -->
            <div class="post-card__header">
                <div class="post-card__user">
                    <?php
                    $nameParts = explode(' ', trim($report['full_name']));
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                    $avatarColors = ['#1d4ed8', '#1e5bb8', '#0b1f47', '#2563eb', '#1e40af', '#1d4ed8'];
                    $colorIndex = abs(crc32($report['full_name'])) % count($avatarColors);
                    $avatarBg = $avatarColors[$colorIndex];
                    ?>
                    <div class="post-card__avatar profile-trigger"
                        style="cursor:pointer; position:relative; overflow:hidden; padding:0;"
                        data-reporter-name="<?= htmlspecialchars($report['full_name']) ?>"
                        title="View posts by <?= htmlspecialchars($report['full_name']) ?>">
                        <?php if (!empty($profilePic)): ?>
                            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile"
                                style="width:100%; height:100%; border-radius:50%; object-fit:cover; display:block;"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="post-card__avatar--initials"
                                style="display:none; background:<?= $avatarBg ?>; width:100%; height:100%; border-radius:50%;">
                                <?= $initials ?>
                            </span>
                        <?php else: ?>
                            <span class="post-card__avatar--initials"
                                style="background:<?= $avatarBg ?>; width:100%; height:100%; border-radius:50%; display:flex;">
                                <?= $initials ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="post-card__user-info">
                        <span class="post-card__name profile-trigger"
                            style="cursor:pointer; text-decoration:underline dotted; text-underline-offset:3px;"
                            data-reporter-name="<?= htmlspecialchars($report['full_name']) ?>"
                            title="View posts by <?= htmlspecialchars($report['full_name']) ?>">
                            <?= htmlspecialchars($report['full_name']) ?>
                        </span>
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
                        <?php if ($isAssignedToMe): ?><small>(You)</small><?php endif; ?>
                    </button>

                <?php elseif ($isAssignedToOther): ?>
                    <span class="rescue-badge badge--warning rescue-badge--locked"
                        title="Being rescued by <?= $assignedRescuerName ?>">
                        🔒 Being Rescued <small>by <?= $assignedRescuerName ?></small>
                    </span>

                <?php else: ?>
                    <span class="rescue-badge <?= $rescueBadgeClass ?>">
                        <?= $rescueLabel ?>
                    </span>
                <?php endif; ?>

                <div class="post-card__map-btns">
                    <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                        <button type="button" class="btn-map" data-lat="<?= $report['latitude'] ?>"
                            data-lng="<?= $report['longitude'] ?>" data-name="<?= htmlspecialchars($report['full_name']) ?>"
                            data-address="<?= $address ?>" data-date="<?= htmlspecialchars($date) ?>"
                            data-description="<?= htmlspecialchars($report['description']) ?>"
                            data-image="<?= htmlspecialchars($imageSrc) ?>"
                            data-water="<?= htmlspecialchars($report['water_level'] ?? '') ?>"
                            data-severity="<?= htmlspecialchars($report['severity'] ?? '') ?>"
                            data-rescue-status="<?= htmlspecialchars($rescueStatus) ?>"
                            data-people="<?= (int) ($report['rescue_people_count'] ?? 0) ?>">
                            View on Map 📍
                        </button>
                        <a class="btn-gmaps"
                            href="https://www.google.com/maps?q=<?= $report['latitude'] ?>,<?= $report['longitude'] ?>"
                            target="_blank" rel="noopener noreferrer">
                            View in Google Maps 🗺️
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        </article>

    <?php endwhile;
endif;
?>