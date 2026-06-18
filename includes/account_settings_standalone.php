<?php

/**
 * Standalone account settings shell for LGU and Rescuer portals.
 *
 * Required variables before include:
 * - $portalPageTitle
 * - $portalCssPath
 * - $portalEyebrow
 * - $cancelUrl
 * - $formAction
 * - $accountSettingsData (from bfis_load_account_settings_profile)
 */

if (!isset($accountSettingsData) || !is_array($accountSettingsData)) {
    header('Location: ' . ($cancelUrl ?? 'main.php'));
    exit;
}

extract($accountSettingsData, EXTR_SKIP);
$accountSettingsEmbedded = false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($portalPageTitle ?? 'Account Settings') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= htmlspecialchars($portalCssPath) ?>" />
  <link rel="stylesheet" href="../assets/css/account-settings.css" />
</head>
<body class="account-settings-body">
  <div class="as-standalone-shell">
    <header class="as-standalone-topbar">
      <a href="<?= htmlspecialchars($cancelUrl) ?>" class="as-back-link">
        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
        Back to Dashboard
      </a>
    </header>

    <main class="as-standalone-main">
      <?php include __DIR__ . '/account_settings_view.php'; ?>
    </main>
  </div>

  <script src="../assets/js/account-settings.js"></script>
</body>
</html>
