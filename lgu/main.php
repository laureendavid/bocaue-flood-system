<?php
$requiredRole = 'LGU';
include('../config/auth.php');
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'user-management', 'report-verification', 'data-monitoring', 'data-management', 'community'];
if (!in_array($page, $allowedPages))
  $page = 'dashboard';

$pageLabels = [
  'dashboard' => 'Dashboard',
  'user-management' => 'User Management',
  'report-verification' => 'Report Verification',
  'data-monitoring' => 'Data Monitoring',
  'data-management' => 'Data Management',
  'community' => 'Community',
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LGU Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="assets/css/lguStyles.css" />
  <link rel="stylesheet" href="assets/css/modals/datamanagement_modals.css" />
  <link rel="stylesheet" href="assets/css/data-monitoring.css" />
  <link rel="stylesheet" href="assets/css/user_management.css" />
  <link rel="stylesheet" href="assets/css/modals/hotline_table.css" />
  <link rel="stylesheet" href="assets/css/modals/announcement_table.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="assets/css/modals/evac_center.css" />

</head>

<body>
  <div class="app-shell">

    <!-- Sidebar Overlay (mobile/tablet) -->
    <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar" aria-label="Main navigation">
      <button id="sidebar-close-btn" class="sidebar-close-btn" aria-label="Close navigation">
        <span class="material-symbols-outlined">close</span>
      </button>

      <div class="sidebar-brand">
        <div class="brand-icon">
          <span class="material-symbols-outlined">person</span>
        </div>
        <h1>LGU</h1>
      </div>

      <nav class="sidebar-nav">
        <?php foreach ($pageLabels as $key => $label):
          $icon = [
            'dashboard' => 'home',
            'user-management' => 'group',
            'report-verification' => 'verified_user',
            'data-monitoring' => 'monitoring',
            'data-management' => 'database',
            'community' => 'groups',
          ][$key]; ?>
          <a class="nav-link<?php if ($page === $key)
            echo ' active'; ?>" href="main.php?page=<?= $key ?>" data-page="<?= $key ?>" data-label="<?= $label ?>">
            <span class="material-symbols-outlined"><?= $icon ?></span>
            <?= $label ?>
          </a>
        <?php endforeach; ?>
      </nav>

    </aside>

    <!-- Main Content -->
    <main class="main-content">

      <!-- Top Bar (mobile) -->
      <div class="topbar" role="banner">
        <button id="hamburger-btn" class="hamburger-btn" aria-label="Open navigation" aria-controls="sidebar">
          <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- notification -->
        <div id="toast-container"></div>

        <!-- Profile Avatar + Dropdown -->
        <div class="profile-wrapper" id="profile-wrapper">
          <button class="profile-avatar" id="profile-btn" aria-label="Profile menu">
            <span class="material-symbols-outlined">person</span>
          </button>
          <div class="profile-dropdown" id="profile-dropdown">
            <a href="account-settings.php" class="dropdown-item">
              <span class="material-symbols-outlined">manage_accounts</span>
              Account Settings
            </a>
            <button class="dropdown-item logout-btn" onclick="window.location.href='../main/logout.php'">
              <span class="material-symbols-outlined">logout</span>
              Logout
            </button>
          </div>
        </div>
      </div>

      <?php
      $sectionFile = "sections/{$page}.php";
      if (file_exists($sectionFile)) {
        include $sectionFile;
      } else {
        echo '<div class="placeholder-text">Page not found.</div>';
      }
      ?>

    </main>
  </div>

  <?php include 'sections/modals/datamanagement_modals.php'; ?>
  <?php include 'sections/modals/user_modals.php'; ?>
  <?php include 'sections/modals/hotline_table_modal.php'; ?>
  <?php include 'sections/modals/announcement_table_modals.php'; ?>
  <?php include 'sections/modals/evac_center_modals.php'; ?>
  <?php include 'sections/modals/archive_announcement_modal.php'; ?>


  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script src="assets/js/lgu.js"></script>
  <script src="assets/js/modals/datamanagement_modals.js"></script>
  <script src="assets/js/flood-map.js"></script>
  <script src="assets/js/data-monitoring.js"></script>
  <script src="assets/js/user_management.js"></script>
  <script src="assets/js/modals/hotline_table.js"></script>
  <script src="assets/js/modals/add_announcement.js"></script>
  <script src="assets/js/modals/announcement_table.js"></script>
  <script src="assets/js/save_center.js"></script>
  <script src="assets/js/modals/evac_center.js"></script>
  <script src="assets/js/modals/evac-map-modal.js"></script>
  <script src="assets/js/modals/archive_announcement.js"></script>
</body>

</html>