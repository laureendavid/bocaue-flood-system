<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/account_settings_data.php';

$requiredRole = 'LGU';
include __DIR__ . '/../config/auth.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$accountSettingsData = bfis_load_account_settings_profile($conn, $userId);

if (!$accountSettingsData) {
    header('Location: main.php');
    exit;
}

$portalPageTitle = 'Account Settings - LGU Portal';
$portalCssPath = 'assets/css/lguStyles.css';
$portalEyebrow = 'LGU Portal — My Account';
$cancelUrl = 'main.php';
$formAction = '../backend/update_account_settings.php';

include __DIR__ . '/../includes/account_settings_standalone.php';
