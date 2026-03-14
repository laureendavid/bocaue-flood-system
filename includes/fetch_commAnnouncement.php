<?php
require_once '../config/db.php';

$sql = "SELECT a.title, a.message, a.created_at,
               u.full_name AS issued_by,
               COALESCE(b.barangay_name, 'All Barangays') AS target_area
        FROM announcements a
        LEFT JOIN users u ON u.user_id = a.created_by
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        WHERE (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
        ORDER BY a.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        $formatted_date = date('M j, Y · g:i a', strtotime($row['created_at']));
        $is_all = $row['target_area'] === 'All Barangays';
        ?>
        <div
            style="background:#ffffff; border-radius:10px; padding:14px 16px; margin-bottom:10px; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

            <!-- Title Row -->
            <div style="margin-bottom:6px;">
                <span style="font-size:0.88rem; font-weight:700; color:#1e293b; display:block; line-height:1.4;">
                    <?= htmlspecialchars($row['title']) ?>
                </span>
            </div>

            <!-- Message -->
            <p style="font-size:0.81rem; color:#475569; line-height:1.6; margin-bottom:10px;">
                <?= nl2br(htmlspecialchars($row['message'])) ?>
            </p>

            <!-- Footer -->
            <div
                style="display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px solid #f1f5f9;">
                <span
                    style="font-size:0.7rem; background:<?= $is_all ? '#f0fdf4' : '#eff6ff' ?>; color:<?= $is_all ? '#16a34a' : '#1d4ed8' ?>; padding:2px 8px; border-radius:999px; font-weight:600; border:1px solid <?= $is_all ? '#bbf7d0' : '#bfdbfe' ?>;">
                    <?= htmlspecialchars($row['target_area']) ?>
                </span>
                <div style="text-align:right;">
                    <span style="font-size:0.7rem; color:#94a3b8; display:block;"><?= $formatted_date ?></span>
                    <span style="font-size:0.68rem; color:#b0bec5;">by <?= htmlspecialchars($row['issued_by']) ?></span>
                </div>
            </div>
        </div>
        <?php
    endwhile;
else:
    ?>
    <p style="color:#94a3b8; font-style:italic; font-size:0.82rem; text-align:center; padding:24px 0;">
        No announcements to display.
    </p>
<?php endif; ?>