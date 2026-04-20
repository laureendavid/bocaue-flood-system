<?php
require_once '../config/db.php';

$sql = "SELECT u.user_id, u.full_name, u.email, u.phone, b.barangay_name, r.role_name AS role
        FROM users u
        JOIN barangays b ON u.barangay_id = b.barangay_id
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                <?= htmlspecialchars($row['full_name']) ?>
            </td>
            <td>
                <?= htmlspecialchars($row['email']) ?>
            </td>
            <td>
                <?= htmlspecialchars($row['phone'] ?? '—') ?>
            </td>
            <td>
                <?= htmlspecialchars($row['barangay_name']) ?>
            </td>
            <td>
                <span class="badge badge-<?= strtolower($row['role']) ?>">
                    <?= htmlspecialchars($row['role']) ?>
                </span>
            </td>
            <td class="action-cell">
                <button class="btn-icon btn-change-role" title="Change Role" data-id="<?= $row['user_id'] ?>"
                    data-name="<?= htmlspecialchars($row['full_name']) ?>" data-email="<?= htmlspecialchars($row['email']) ?>"
                    data-role="<?= $row['role'] ?>">
                    <span class="material-symbols-outlined">manage_accounts</span>
                </button>
                <button class="btn-icon btn-delete" title="Delete User" data-id="<?= $row['user_id'] ?>"
                    data-name="<?= htmlspecialchars($row['full_name']) ?>">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
    <?php endwhile;
else: ?>
    <tr class="empty-row">
        <td colspan="6">No users to display.</td>
    </tr>
<?php endif; ?>