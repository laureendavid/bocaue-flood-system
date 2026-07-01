<?php

define('BFIS_REGISTRATION_RESIDENT_ROLE_ID', 3);

/**
 * @return bool
 */
function bfis_registration_debug_enabled(): bool
{
    $value = getenv('BFIS_DEBUG');

    if ($value === false && isset($_ENV['BFIS_DEBUG'])) {
        $value = $_ENV['BFIS_DEBUG'];
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
}
