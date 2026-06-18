<?php
/* ================================================================
   main.php — Resident Dashboard
   Place in: C:\xampp\htdocs\soe\resident\main.php
   ================================================================ */
require_once('../config/db.php');
$requiredRole = 'Resident';
include('../config/auth.php');

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = [
  'dashboard',
  'flood-map',
  'report-flood',
  'safety-centers',
  'hotlines',
  'account-settings',
];
if (!in_array($page, $allowedPages)) $page = 'dashboard';

$pageLabels = [
  'dashboard'      => 'Dashboard',
  'flood-map'      => 'Flood Map',
  'report-flood'   => 'Flood Reports',
  'safety-centers' => 'Safety Centers',
  'hotlines'       => 'Emergency Hotlines',
  'account-settings' => 'Account Settings',
];

$pageIcons = [
  'dashboard'      => 'space_dashboard',
  'flood-map'      => 'map',
  'report-flood'   => 'flood',
  'safety-centers' => 'home_work',
  'hotlines'       => 'support_agent',
  'account-settings' => 'manage_accounts',
];

$residentName = trim((string) ($_SESSION['full_name'] ?? 'Resident User'));
$residentRole = trim((string) ($_SESSION['role'] ?? 'Resident'));
$currentPageTitle = $pageLabels[$page] ?? 'Dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resident Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/residentStyles.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<div class="app-shell">

  <!-- ===== SIDEBAR ===== -->
  <aside id="sidebar" class="sidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
      <div class="brand-icon">
        <span class="material-symbols-outlined">water_drop</span>
      </div>
      <div>
        <p class="brand-subtitle">BFIS</p>
        <h1>Resident Portal</h1>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($pageLabels as $key => $label): ?>
      <?php if ($key === 'account-settings') continue; ?>
      <a class="nav-link<?= $page === $key ? ' active' : '' ?>"
         href="main.php?page=<?= $key ?>">
        <span class="material-symbols-outlined"><?= $pageIcons[$key] ?></span>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <button id="logout-trigger-btn" class="logout-btn-sidebar" type="button">
        <span class="material-symbols-outlined">logout</span>
        Logout
      </button>
    </div>
  </aside>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="main-content">

    <!-- Top Bar -->
    <div class="topbar" role="banner">
      <h2 class="system-name">Bocaue Flood Information System</h2>

      <div class="topbar-actions">
        <div class="notification-wrapper" id="notification-wrapper">
          <button
            type="button"
            class="topbar-action-btn notification-btn"
            id="notification-btn"
            aria-label="Notifications"
            title="Notifications"
          >
            <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
            <span class="notification-badge" id="notification-badge" style="display:none;">0</span>
          </button>
          <div class="notification-dropdown" id="notification-dropdown">
            <div class="notification-header">
              <h4>Notifications</h4>
              <button type="button" class="mark-read-btn" id="mark-read-btn">Mark all as read</button>
            </div>
            <div class="notification-list" id="notification-list">
              <div class="notification-empty">No notifications yet.</div>
            </div>
          </div>
        </div>

        <a
          href="main.php?page=account-settings"
          class="topbar-action-btn<?= $page === 'account-settings' ? ' is-active' : '' ?>"
          aria-label="Account Settings"
          title="Account Settings"
        >
          <span class="material-symbols-outlined" aria-hidden="true">manage_accounts</span>
        </a>
      </div>
    </div>

    <!-- ===== PAGE SECTION ===== -->
    <?php
      $sectionFile = "sections/{$page}.php";
      if (file_exists($sectionFile)) {
        include $sectionFile;
      } else {
        echo '<div class="placeholder-text">This page is coming soon.</div>';
      }
    ?>

  </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/resident.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/resident.js') ?>"></script>
<script src="../assets/js/account-settings.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/account-settings.js') ?>"></script>
</body>
</html>