<?php

/**
 * sections/account-settings.php
 * Resident account settings section.
 */

require_once __DIR__ . '/../../includes/account_settings_data.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$accountSettingsData = bfis_load_account_settings_profile($conn, $userId);

if (!$accountSettingsData) {
    echo '<section class="page active"><div class="as-empty">Unable to load your account settings.</div></section>';
    return;
}

extract($accountSettingsData, EXTR_SKIP);

$portalEyebrow = 'Resident Portal — My Account';
$cancelUrl = 'main.php?page=dashboard';
$formAction = '../backend/update_account_settings.php';
$accountSettingsEmbedded = true;

include __DIR__ . '/../../includes/account_settings_view.php';
