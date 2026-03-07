<?php
require_once '../config/db.php';

$sql = "SELECT h.hotline_id, b.barangay_name, h.hotline_name, h.contact_number
        FROM hotlines h
        JOIN barangays b ON h.barangay_id = b.barangay_id
        ORDER BY h.created_at DESC";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= htmlspecialchars($row['barangay_name']) ?></td>
            <td><?= htmlspecialchars($row['hotline_name']) ?></td>
            <td><?= htmlspecialchars($row['contact_number']) ?></td>
            <td class="action-cell">
                <button class="btn-icon btn-edit-hotline" title="Edit Hotline" data-id="<?= $row['hotline_id'] ?>"
                    data-barangay="<?= htmlspecialchars($row['barangay_name']) ?>"
                    data-name="<?= htmlspecialchars($row['hotline_name']) ?>"
                    data-number="<?= htmlspecialchars($row['contact_number']) ?>">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button class="btn-icon btn-delete-hotline" title="Delete Hotline" data-id="<?= $row['hotline_id'] ?>"
                    data-name="<?= htmlspecialchars($row['hotline_name']) ?>">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
    <?php endwhile;
else: ?>
    <tr class="empty-row">
        <td colspan="4">No hotlines to display.</td>
    </tr>
<?php endif; ?>