<?php
require_once '../config/db.php';

$sql = "SELECT ec.center_id, ec.center_name, ec.capacity, ec.occupancy,
               l.full_address,
               CASE
                   WHEN ec.occupancy >= ec.capacity THEN 'Full'
                   WHEN ec.occupancy >= ec.capacity * 0.8 THEN 'Near Full'
                   ELSE 'Available'
               END AS status
        FROM evacuation_centers ec
        LEFT JOIN locations l ON ec.location_id = l.location_id
        ORDER BY ec.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        $statusClass = match ($row['status']) {
            'Full' => 'badge-full',
            'Near Full' => 'badge-nearfull',
            default => 'badge-available',
        };
        ?>
        <tr>
            <td>
                <div>
                    <?= htmlspecialchars($row['center_name']) ?>
                </div>
                <small style="color:var(--text-muted); font-size:0.78rem;">
                    <?= htmlspecialchars($row['full_address'] ?? '—') ?>
                </small>
            </td>
            <td>
                <?= $row['occupancy'] ?>/
                <?= $row['capacity'] ?>
            </td>
            <td><span class="badge <?= $statusClass ?>">
                    <?= $row['status'] ?>
                </span></td>
            <td class="action-cell">
                <!-- Add Evacuee -->
                <button class="btn-evac-action btn-add-evacuee" title="Add Evacuee" data-id="<?= $row['center_id'] ?>"
                    data-name="<?= htmlspecialchars($row['center_name']) ?>">
                    <span class="material-symbols-outlined">group_add</span>
                </button>
                <!-- Remove Evacuee -->
                <button class="btn-evac-action btn-remove-evacuee" title="Remove Evacuee" data-id="<?= $row['center_id'] ?>"
                    data-name="<?= htmlspecialchars($row['center_name']) ?>">
                    <span class="material-symbols-outlined">group_remove</span>
                </button>
                <!-- Edit Center -->
                <button class="btn-evac-action btn-edit-center" title="Edit Center" data-id="<?= $row['center_id'] ?>"
                    data-name="<?= htmlspecialchars($row['center_name']) ?>"
                    data-address="<?= htmlspecialchars($row['full_address'] ?? '') ?>" data-capacity="<?= $row['capacity'] ?>">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <!-- Delete Center -->
                <button class="btn-evac-action btn-delete-center" title="Delete Center" data-id="<?= $row['center_id'] ?>"
                    data-name="<?= htmlspecialchars($row['center_name']) ?>">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
        <?php
    endwhile;
else:
    ?>
    <tr class="empty-row">
        <td colspan="4">No evacuation centers to display.</td>
    </tr>
<?php endif; ?>