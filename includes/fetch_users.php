<?php
require_once '../config/db.php';

$sql = "SELECT u.user_id, u.full_name, u.email, u.first_name, u.last_name, 
               u.phone, u.date_of_birth, u.profile_picture, u.current_address,
               u.created_at, b.barangay_name, r.role_name AS role
        FROM users u
        JOIN barangays b ON u.barangay_id = b.barangay_id
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo '<tr><td colspan="5">Query error: ' . mysqli_error($conn) . '</td></tr>';
    exit;
}

// Helper: resolve profile picture to a usable src or empty string
function resolveProfilePic($pic)
{
    if (empty($pic))
        return '';

    // Already a full URL (Cloudinary or any http/https)
    if (str_starts_with($pic, 'http://') || str_starts_with($pic, 'https://')) {
        return $pic;
    }

    // Local path — normalize: strip leading slash if any, prepend ../
    $pic = ltrim($pic, '/');
    return '../' . $pic;
}

// Helper: get initials from full_name
function getInitials($fullName)
{
    $parts = array_filter(explode(' ', trim($fullName)));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper($part[0]);
    }
    return $initials ?: '?';
}
?>

<?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)):
        $picSrc = resolveProfilePic($row['profile_picture']);
        $initials = getInitials($row['full_name']);
        ?>
        <tr>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['barangay_name']) ?></td>
            <td>
                <span class="badge badge-<?= strtolower($row['role']) ?>">
                    <?= htmlspecialchars($row['role']) ?>
                </span>
            </td>
            <td class="action-cell">
                <button class="btn-icon btn-view-details" title="View Details"
                    data-fullname="<?= htmlspecialchars($row['full_name'] ?? '') ?>"
                    data-email="<?= htmlspecialchars($row['email'] ?? '') ?>"
                    data-phone="<?= htmlspecialchars($row['phone'] ?? '') ?>"
                    data-dob="<?= htmlspecialchars($row['date_of_birth'] ?? '') ?>"
                    data-address="<?= htmlspecialchars($row['current_address'] ?? '') ?>"
                    data-barangay="<?= htmlspecialchars($row['barangay_name'] ?? '') ?>"
                    data-role="<?= htmlspecialchars($row['role'] ?? '') ?>"
                    data-joined="<?= htmlspecialchars($row['created_at'] ?? '') ?>"
                    data-profile="<?= htmlspecialchars($picSrc) ?>" data-initials="<?= htmlspecialchars($initials) ?>">
                    <span class="material-symbols-outlined">person</span>
                </button>

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
    <?php endwhile; ?>
<?php else: ?>
    <tr class="empty-row">
        <td colspan="5">No users to display.</td>
    </tr>
<?php endif; ?>


<!-- ===== MODAL ===== -->
<div id="userDetailsModal" class="ud-overlay" style="display:none;">
    <div class="ud-box">
        <button class="ud-close" id="closeUserDetailsModal">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="ud-header">
            <!-- Avatar: shows image OR initials fallback -->
            <div class="ud-avatar-wrap">
                <img id="udProfilePic" src="" alt="Profile" style="display:none;"
                    onload="this.style.display='block'; document.getElementById('udInitials').style.display='none';"
                    onerror="this.style.display='none'; document.getElementById('udInitials').style.display='flex';">
                <div id="udInitials" class="ud-initials" style="display:none;"></div>
            </div>
            <div>
                <h2 id="udFullName"></h2>
                <span id="udRoleBadge" class="badge"></span>
            </div>
        </div>

        <div class="ud-grid">
            <div class="ud-item">
                <span class="material-symbols-outlined">email</span>
                <div><label>Email</label>
                    <p id="udEmail"></p>
                </div>
            </div>
            <div class="ud-item">
                <span class="material-symbols-outlined">phone</span>
                <div><label>Phone</label>
                    <p id="udPhone"></p>
                </div>
            </div>
            <div class="ud-item">
                <span class="material-symbols-outlined">cake</span>
                <div><label>Date of Birth</label>
                    <p id="udDOB"></p>
                </div>
            </div>
            <div class="ud-item">
                <span class="material-symbols-outlined">location_on</span>
                <div><label>Barangay</label>
                    <p id="udBarangay"></p>
                </div>
            </div>
            <div class="ud-item ud-full">
                <span class="material-symbols-outlined">home</span>
                <div><label>Current Address</label>
                    <p id="udAddress"></p>
                </div>
            </div>
            <div class="ud-item">
                <span class="material-symbols-outlined">calendar_month</span>
                <div><label>Joined</label>
                    <p id="udJoined"></p>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .ud-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ud-box {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 100%;
        max-width: 520px;
        position: relative;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        animation: udFadeIn 0.2s ease;
    }

    @keyframes udFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ud-close {
        position: absolute;
        top: 14px;
        right: 14px;
        background: none;
        border: none;
        cursor: pointer;
        color: #888;
        display: flex;
        align-items: center;
    }

    .ud-close:hover {
        color: #e53935;
    }

    .ud-header {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 24px;
    }

    .ud-avatar-wrap {
        flex-shrink: 0;
        width: 72px;
        height: 72px;
    }

    .ud-avatar-wrap img {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e0e0e0;
    }

    /* Initials fallback circle */
    .ud-initials {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #1565c0;
        color: #fff;
        font-size: 1.4rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: 1px;
        user-select: none;
    }

    .ud-header h2 {
        margin: 0 0 6px;
        font-size: 1.2rem;
        color: #212121;
    }

    .ud-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .ud-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .ud-full {
        grid-column: 1 / -1;
    }

    .ud-item .material-symbols-outlined {
        font-size: 20px;
        color: #1565c0;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .ud-item label {
        display: block;
        font-size: 0.72rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 2px;
    }

    .ud-item p {
        margin: 0;
        font-size: 0.92rem;
        color: #212121;
        word-break: break-word;
    }

    .btn-view-details {
        color: #1565c0;
    }

    .btn-view-details:hover {
        color: #0d47a1;
    }
</style>


<script>
    (function () {
        if (window._udModalInit) return;
        window._udModalInit = true;

        const modal = document.getElementById('userDetailsModal');
        const closeBtn = document.getElementById('closeUserDetailsModal');

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-view-details');
            if (!btn) return;

            const d = btn.dataset;
            const pic = document.getElementById('udProfilePic');
            const initials = document.getElementById('udInitials');

            // --- Profile picture logic ---
            if (d.profile) {
                // Reset visibility first
                pic.style.display = 'none';
                initials.style.display = 'none';

                pic.src = d.profile; // onload/onerror on the <img> handles show/hide
            } else {
                // No picture at all — show initials immediately
                pic.style.display = 'none';
                pic.src = '';
                initials.textContent = d.initials || '?';
                initials.style.display = 'flex';
            }

            document.getElementById('udFullName').textContent = d.fullname || 'N/A';
            document.getElementById('udEmail').textContent = d.email || 'N/A';
            document.getElementById('udPhone').textContent = d.phone || 'N/A';
            document.getElementById('udDOB').textContent = d.dob || 'N/A';
            document.getElementById('udBarangay').textContent = d.barangay || 'N/A';
            document.getElementById('udAddress').textContent = d.address || 'N/A';

            const joined = d.joined ? new Date(d.joined) : null;
            document.getElementById('udJoined').textContent = joined
                ? joined.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
                : 'N/A';

            const badge = document.getElementById('udRoleBadge');
            badge.textContent = d.role || '';
            badge.className = 'badge badge-' + (d.role || '').toLowerCase();

            modal.style.display = 'flex';
        });

        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
    })();
</script>