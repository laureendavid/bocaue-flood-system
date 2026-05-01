<?php
require_once '../config/db.php';

$sql = "
    SELECT
        r.report_id,
        r.user_id,
        u.full_name,
        l.full_address,
        b.barangay_name,
        r.description,
        r.rescue_people_count,
        r.rescue_description,
        r.report_image,
        COALESCE(rs.status_name, 'Pending') AS report_status,
        r.created_at
    FROM reports r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN locations l ON r.location_id = l.location_id
    LEFT JOIN barangays b ON l.barangay_id = b.barangay_id
    LEFT JOIN report_status rs ON r.status_id = rs.status_id
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt || !$stmt->execute()) { ?>
    <tr class="empty-row">
        <td colspan="9">Failed to load reports.</td>
    </tr>
    <?php
    return;
}

$result = $stmt->get_result();
if (!$result || $result->num_rows === 0): ?>
    <tr class="empty-row">
        <td colspan="9">No reports to display.</td>
    </tr>
    <?php
    $stmt->close();
    return;
endif;

while ($row = $result->fetch_assoc()):

    $status = trim((string) ($row['report_status'] ?? 'Pending'));
    $normalizedStatus = strtolower($status);

    $statusClass = match ($normalizedStatus) {
        'approved', 'verified' => 'badge-approved',
        'rejected' => 'badge-rejected',
        default => 'badge-pending',
    };

    $location = trim((string) ($row['full_address'] ?? ''));
    $barangay = trim((string) ($row['barangay_name'] ?? ''));

    if ($location === '') {
        $location = $barangay !== '' ? $barangay : 'N/A';
    }

    $residentName = trim((string) ($row['full_name'] ?? ''));
    if ($residentName === '') {
        $residentName = 'Resident #' . (int) $row['user_id'];
    }

    $description = (string) ($row['description'] ?? '');

    $rescuePeopleCount = isset($row['rescue_people_count']) && $row['rescue_people_count'] !== null
        ? (int) $row['rescue_people_count']
        : null;

    $rescueDescription = trim((string) ($row['rescue_description'] ?? ''));

    // =========================
    // ✅ FIXED IMAGE HANDLING (CLOUDINARY SAFE)
    // =========================
    $reportImage = trim((string) ($row['report_image'] ?? ''));
    $imageSrc = '';

    if ($reportImage !== '') {
        if (filter_var($reportImage, FILTER_VALIDATE_URL)) {
            // Cloudinary or any full URL
            $imageSrc = $reportImage;
        } else {
            // fallback for local images
            $imageSrc = '/' . ltrim($reportImage, '/');
        }
    }

    $searchBlob = strtolower(
        $residentName . ' ' .
        $location . ' ' .
        $barangay . ' ' .
        $description . ' ' .
        ($rescuePeopleCount !== null ? (string) $rescuePeopleCount : '') . ' ' .
        $rescueDescription . ' ' .
        $status
    );
    ?>
    <tr data-status="<?= htmlspecialchars($status) ?>" data-search="<?= htmlspecialchars($searchBlob) ?>"
        data-report-id="<?= (int) $row['report_id'] ?>">
        <td>
            <div><?= htmlspecialchars($residentName) ?></div>
            <small class="report-meta-text">ID: <?= (int) $row['user_id'] ?></small>
        </td>

        <td>
            <div><?= htmlspecialchars($location) ?></div>
            <?php if ($barangay !== ''): ?>
                <small class="report-meta-text"><?= htmlspecialchars($barangay) ?></small>
            <?php endif; ?>
        </td>

        <td class="report-desc-cell"><?= nl2br(htmlspecialchars($description)) ?></td>

        <td><?= $rescuePeopleCount !== null ? htmlspecialchars((string) $rescuePeopleCount) : '-' ?></td>

        <td class="report-desc-cell">
            <?= $rescueDescription !== '' ? nl2br(htmlspecialchars($rescueDescription)) : '-' ?>
        </td>

        <td>
            <?php if ($imageSrc !== ''): ?>
                <button type="button" class="report-image-trigger" data-image-src="<?= htmlspecialchars($imageSrc) ?>"
                    data-image-alt="Report image #<?= (int) $row['report_id'] ?>" aria-label="View report image">
                    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="Report image #<?= (int) $row['report_id'] ?>"
                        onerror="this.style.display='none'; this.parentNode.innerHTML='Image failed to load';" />
                </button>
            <?php else: ?>
                <span class="report-image-empty">No image</span>
            <?php endif; ?>
        </td>

        <td>
            <?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $row['created_at']))) ?>
        </td>

        <td>
            <span class="badge <?= $statusClass ?> js-report-status">
                <?= htmlspecialchars($status) ?>
            </span>
        </td>

        <td>
            <div class="report-actions">
                <button type="button" class="btn-report-action btn-report-verify" data-action="Approved"
                    data-report-id="<?= (int) $row['report_id'] ?>" <?= $normalizedStatus !== 'pending' ? 'disabled' : '' ?>>
                    Approve
                </button>

                <button type="button" class="btn-report-action btn-report-reject" data-action="Rejected"
                    data-report-id="<?= (int) $row['report_id'] ?>" <?= $normalizedStatus !== 'pending' ? 'disabled' : '' ?>>
                    Reject
                </button>
            </div>
        </td>
    </tr>

<?php endwhile;
$stmt->close();
?>