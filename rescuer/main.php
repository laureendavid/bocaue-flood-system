<?php
/* ================================================================
   main.php — Rescuer Dashboard
   Place this file in: C:\xampp\htdocs\soe\rescuer\main.php
   ================================================================ */
$requiredRole = 'Rescuer';
include('../config/auth.php');

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = [
  'dashboard',
  'flood-monitoring-map',
  'evacuation-center',
  'hotlines',
  'community',
];
if (!in_array($page, $allowedPages)) $page = 'dashboard';

$pageLabels = [
  'dashboard'           => 'Dashboard',
  'flood-monitoring-map'=> 'Flood Monitoring Map',
  'evacuation-center'   => 'Evacuation Center',
  'hotlines'            => 'Hotlines',
  'community'           => 'Community',
];

$pageIcons = [
  'dashboard'           => 'dashboard',
  'flood-monitoring-map'=> 'map',
  'evacuation-center'   => 'home_work',
  'hotlines'            => 'call',
  'community'           => 'groups',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rescuer Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/rescuerStyles.css" />
</head>
<body>
<div class="app-shell">

  <!-- ===== SIDEBAR OVERLAY (mobile/tablet) ===== -->
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
      <h1>Rescuer</h1>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($pageLabels as $key => $label): ?>
      <a class="nav-link<?= $page === $key ? ' active' : '' ?>"
         href="main.php?page=<?= $key ?>"
         data-page="<?= $key ?>"
         data-label="<?= $label ?>">
        <span class="material-symbols-outlined"><?= $pageIcons[$key] ?></span>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="main-content">

    <!-- Top Bar (mobile) -->
    <div class="topbar" role="banner">
      <button id="hamburger-btn" class="hamburger-btn" aria-label="Open navigation" aria-controls="sidebar">
        <span class="material-symbols-outlined">menu</span>
      </button>

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

    <!-- ===== PAGE SECTION ===== -->
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

<script src="assets/js/rescuer.js"></script>
</body>
</html>