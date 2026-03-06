<?php
require_once '../config/db.php';

$sql = "SELECT a.announcement_id, a.title, a.message, 
               COALESCE(b.barangay_name, 'All Barangays') AS target_area,
               a.barangay_id,
               a.expiry_date
        FROM announcements a
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        ORDER BY a.created_at DESC";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                <?= htmlspecialchars($row['title']) ?>
            </td>
            <td>
                <?= htmlspecialchars($row['message']) ?>
            </td>
            <td>
                <?= htmlspecialchars($row['target_area']) ?>
            </td>
            <td>
                <?= $row['expiry_date'] ? date('F j, Y', strtotime($row['expiry_date'])) : '—' ?>
            </td>
            <td class="action-cell">
                <button class="btn-icon btn-edit-announcement" title="Edit Announcement"
                    data-id="<?= $row['announcement_id'] ?>" data-title="<?= htmlspecialchars($row['title']) ?>"
                    data-message="<?= htmlspecialchars($row['message']) ?>"
                    data-area="<?= htmlspecialchars($row['target_area']) ?>" data-expiry="<?= $row['expiry_date'] ?? '' ?>">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button class="btn-icon btn-delete-announcement" title="Delete Announcement"
                    data-id="<?= $row['announcement_id'] ?>" data-title="<?= htmlspecialchars($row['title']) ?>">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
    <?php endwhile;
else: ?>
    <tr class="empty-row">
        <td colspan="5">No announcements to display.</td>
    </tr>
<?php endif; ?>