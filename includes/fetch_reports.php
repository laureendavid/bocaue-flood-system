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

// ── STATUS ID LOOKUP ─────────────────────────────────────────────────
$pendingId = $approvedId = $rejectedId = null;
$sRes = $conn->query("SELECT status_id, status_name FROM report_status");
while ($sRow = $sRes->fetch_assoc()) {
    match (strtolower($sRow['status_name'])) {
        'pending' => $pendingId = (int) $sRow['status_id'],
        'approved' => $approvedId = (int) $sRow['status_id'],
        'rejected' => $rejectedId = (int) $sRow['status_id'],
        default => null,
    };
}

if ($pendingId && $approvedId && $rejectedId) {

    // Kunin lahat ng pending reports para sa auto-rules
    $autoStmt = $conn->prepare("
        SELECT r.report_id, r.report_image, r.rescue_people_count, r.description,
               r.created_at, l.barangay_id
        FROM reports r
        LEFT JOIN locations l ON r.location_id = l.location_id
        WHERE r.status_id = ?
    ");
    $autoStmt->bind_param("i", $pendingId);
    $autoStmt->execute();
    $autoRows = $autoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $autoStmt->close();

    // ── RULE 1: AUTO-EXPIRE (> 24 hours, walang image) ───────────────
    foreach ($autoRows as $ar) {
        $hasImage = trim((string) ($ar['report_image'] ?? '')) !== '';
        $hasCount = isset($ar['rescue_people_count']) && (int) $ar['rescue_people_count'] > 0;
        $hasDesc = trim((string) ($ar['description'] ?? '')) !== '';
        $createdAt = strtotime((string) ($ar['created_at'] ?? 'now'));
        $ageHours = (time() - $createdAt) / 3600;

        if ($hasImage && $hasCount) {
            // Auto-approve: may image + may rescue count
            $upd = $conn->prepare("UPDATE reports SET status_id = ? WHERE report_id = ?");
            $upd->bind_param("ii", $approvedId, $ar['report_id']);
            $upd->execute();
            $upd->close();
        } elseif (!$hasImage && !$hasDesc) {
            // Auto-reject: walang image at walang description
            $upd = $conn->prepare("UPDATE reports SET status_id = ? WHERE report_id = ?");
            $upd->bind_param("ii", $rejectedId, $ar['report_id']);
            $upd->execute();
            $upd->close();
        } elseif ($ageHours > 24 && !$hasImage) {
            // Auto-expire: > 24 hours na at walang image pa rin
            $upd = $conn->prepare("UPDATE reports SET status_id = ? WHERE report_id = ?");
            $upd->bind_param("ii", $rejectedId, $ar['report_id']);
            $upd->execute();
            $upd->close();
        }
    }

    // ── RULE 2: DUPLICATE DETECTION (same barangay, within 30 mins) ──
    // I-fetch ang remaining pending reports pagkatapos ng auto-rules
    $dupStmt = $conn->prepare("
        SELECT r.report_id, l.barangay_id, r.created_at
        FROM reports r
        LEFT JOIN locations l ON r.location_id = l.location_id
        WHERE r.status_id = ?
        ORDER BY l.barangay_id ASC, r.created_at ASC
    ");
    $dupStmt->bind_param("i", $pendingId);
    $dupStmt->execute();
    $dupRows = $dupStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $dupStmt->close();

    // Group by barangay, i-check kung may reports within 30 mins ng isa't isa
    $barangayReports = [];
    foreach ($dupRows as $dr) {
        $bId = (int) ($dr['barangay_id'] ?? 0);
        if ($bId === 0)
            continue;
        if (!isset($barangayReports[$bId]))
            $barangayReports[$bId] = [];
        $barangayReports[$bId][] = [
            'report_id' => (int) $dr['report_id'],
            'created_at' => strtotime((string) $dr['created_at']),
        ];
    }

    $duplicateReportIds = [];
    foreach ($barangayReports as $bId => $reports) {
        if (count($reports) < 2)
            continue;
        // Ang una sa grupo ay ang "original" — ang susunod na within 30 mins ay duplicate
        $original = $reports[0];
        for ($i = 1; $i < count($reports); $i++) {
            $diffMins = ($reports[$i]['created_at'] - $original['created_at']) / 60;
            if ($diffMins <= 30) {
                $duplicateReportIds[] = $reports[$i]['report_id'];
            }
        }
    }

    // Re-run ang main query para makuha ang updated statuses
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
}
// ── END AUTO-RULES ────────────────────────────────────────────────────

if (!$result || $result->num_rows === 0): ?>
    <tr class="empty-row">
        <td colspan="9">No reports to display.</td>
    </tr>
    <?php
    $stmt->close();
    return;
endif;

// ── RULE 3: PRIORITY SORT (rescue_people_count DESC, then created_at DESC) ──
$rows = $result->fetch_all(MYSQLI_ASSOC);

usort($rows, function ($a, $b) {
    $countA = isset($a['rescue_people_count']) ? (int) $a['rescue_people_count'] : 0;
    $countB = isset($b['rescue_people_count']) ? (int) $b['rescue_people_count'] : 0;

    if ($countB !== $countA)
        return $countB - $countA; // mas mataas ang rescue count, mas taas
    return strtotime($b['created_at']) - strtotime($a['created_at']); // tiebreak: pinaka-bago
});

$stmt->close();

foreach ($rows as $row):

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

    // ── IMAGE HANDLING (CLOUDINARY SAFE) ──
    $reportImage = trim((string) ($row['report_image'] ?? ''));
    $imageSrc = '';

    if ($reportImage !== '') {
        if (filter_var($reportImage, FILTER_VALIDATE_URL)) {
            $imageSrc = $reportImage;
        } else {
            $imageSrc = '/' . ltrim($reportImage, '/');
        }
    }

    // ── DUPLICATE FLAG ──
    $reportId = (int) $row['report_id'];
    $isDuplicate = isset($duplicateReportIds) && in_array($reportId, $duplicateReportIds, true);

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
        data-report-id="<?= $reportId ?>" <?= $isDuplicate ? 'data-duplicate="1"' : '' ?>>

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
                    data-image-alt="Report image #<?= $reportId ?>" aria-label="View report image">
                    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="Report image #<?= $reportId ?>"
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
            <?php if ($isDuplicate): ?>
                <span class="badge badge-duplicate" title="Possible duplicate report from same barangay within 30 mins">
                    Possible Duplicate
                </span>
            <?php else: ?>
                <span class="badge <?= $statusClass ?> js-report-status">
                    <?= htmlspecialchars($status) ?>
                </span>
            <?php endif; ?>
        </td>

        <td>
            <div class="report-actions">
                <button type="button" class="btn-report-action btn-report-verify" data-action="Approved"
                    data-report-id="<?= $reportId ?>" <?= $normalizedStatus !== 'pending' ? 'disabled' : '' ?>>
                    Approve
                </button>
                <button type="button" class="btn-report-action btn-report-reject" data-action="Rejected"
                    data-report-id="<?= $reportId ?>" <?= $normalizedStatus !== 'pending' ? 'disabled' : '' ?>>
                    Reject
                </button>
            </div>
        </td>
    </tr>

<?php endforeach; ?>