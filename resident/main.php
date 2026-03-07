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
];
if (!in_array($page, $allowedPages)) $page = 'dashboard';

$pageLabels = [
  'dashboard'      => 'Dashboard',
  'flood-map'      => 'Map',
  'report-flood'   => 'Report Flood',
  'safety-centers' => 'Safety Centers',
  'hotlines'       => 'Hotlines',
];

$pageIcons = [
  'dashboard'      => 'breaking_news',
  'flood-map'      => 'map_search',
  'report-flood'   => 'report',
  'safety-centers' => 'guardian',
  'hotlines'       => 'call_log',
];
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
</head>
<body>
<div class="app-shell">

  <!-- ===== SIDEBAR OVERLAY ===== -->
  <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

  <!-- ===== SIDEBAR ===== -->
  <aside id="sidebar" class="sidebar" aria-label="Main navigation">
    <button id="sidebar-close-btn" class="sidebar-close-btn" aria-label="Close navigation">
      <span class="material-symbols-outlined">close</span>
    </button>
    <div class="sidebar-brand">
      <div class="brand-icon">
        <span class="material-symbols-outlined">person</span>
      </div>
      <h1>Resident</h1>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($pageLabels as $key => $label): ?>
      <a class="nav-link<?= $page === $key ? ' active' : '' ?>"
         href="main.php?page=<?= $key ?>">
        <span class="material-symbols-outlined"><?= $pageIcons[$key] ?></span>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <button id="theme-toggle" class="theme-toggle">
        <span class="material-symbols-outlined">dark_mode</span>
        Toggle Theme
      </button>
      <button id="logout-trigger-btn" class="logout-btn-sidebar">
        <span class="material-symbols-outlined">logout</span>
        Logout
      </button>
    </div>
  </aside>

  <!-- ===== LOGOUT MODAL ===== -->
  <div id="logout-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true">
      <div class="modal-icon-wrap">
        <span class="material-symbols-outlined modal-icon">logout</span>
      </div>
      <h3 class="modal-title">Log out?</h3>
      <p class="modal-body">Are you sure you want to log out of your account?</p>
      <div class="modal-actions">
        <button id="logout-cancel-btn" class="modal-btn modal-btn--cancel">Cancel</button>
        <button class="modal-btn modal-btn--confirm"
                onclick="window.location.href='../main/logout.php'">Yes, Logout</button>
      </div>
    </div>
  </div>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="main-content">

    <!-- Top Bar -->
    <div class="topbar" role="banner">
      <button id="hamburger-btn" class="hamburger-btn" aria-label="Open navigation" aria-controls="sidebar">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <div class="profile-wrapper">
        <button class="profile-avatar" id="profile-btn" aria-label="Profile menu">
          <span class="material-symbols-outlined">person</span>
        </button>
        <div class="profile-dropdown" id="profile-dropdown">
          <a href="account-settings.php" class="dropdown-item">
            <span class="material-symbols-outlined">manage_accounts</span>
            Account Settings
          </a>
          <button class="dropdown-item logout-btn-dropdown" id="logout-trigger-topbar">
            <span class="material-symbols-outlined">logout</span>
            Logout
          </button>
        </div>
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

<script src="assets/js/resident.js"></script>
</body>
</html>