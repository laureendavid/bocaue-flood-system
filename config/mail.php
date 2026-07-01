<?php

/**
 * SMTP and application URL configuration for outbound mail.
 *
 * Secrets belong in config/mail.local.php (gitignored) or BFIS_MAIL_* environment variables.
 * Copy config/mail.local.php.example to config/mail.local.php and fill in your Brevo credentials.
 */

/**
 * @param string $key
 * @param string $default
 * @return string
 */
function bfis_mail_env(string $key, string $default = ''): string
{
    $value = getenv($key);

    if (is_string($value) && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }

    return $default;
}

/**
 * Production hostname (InfinityFree).
 */
const BFIS_PRODUCTION_HOST = 'bocauefloodinformation.infinityfreeapp.com';

/**
 * Optional subfolder if the app is not at domain root (e.g. '/soe').
 * Leave empty when main/ is directly under htdocs.
 */
const BFIS_PRODUCTION_BASE_PATH = '/soe';

/**
 * @return bool
 */
function bfis_request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    return false;
}

/**
 * @return array{
 *     host: string,
 *     port: int,
 *     encryption: string,
 *     username: string,
 *     password: string,
 *     from_email: string,
 *     from_name: string
 * }
 */
function bfis_mail_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $config = [
        'host' => bfis_mail_env('BFIS_MAIL_HOST', 'smtp-relay.brevo.com'),
        'port' => (int) bfis_mail_env('BFIS_MAIL_PORT', '587'),
        'encryption' => bfis_mail_env('BFIS_MAIL_ENCRYPTION', 'tls'),
        'username' => bfis_mail_env('BFIS_MAIL_USERNAME', ''),
        'password' => bfis_mail_env('BFIS_MAIL_PASSWORD', ''),
        'from_email' => bfis_mail_env('BFIS_MAIL_FROM', ''),
        'from_name' => bfis_mail_env('BFIS_MAIL_FROM_NAME', 'Bocaue Flood Info System'),
    ];

    $localFile = __DIR__ . '/mail.local.php';

    if (is_file($localFile)) {
        $local = require $localFile;

        if (is_array($local)) {
            foreach ($local as $key => $value) {
                if (!is_string($key) || !array_key_exists($key, $config)) {
                    continue;
                }

                if (is_string($value) && $value !== '') {
                    $config[$key] = $value;
                } elseif (is_int($value) && $key === 'port') {
                    $config[$key] = $value;
                }
            }
        }
    }

    return $config;
}

/**
 * @return string
 */
function bfis_app_detect_base_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($script === '') {
        return '';
    }

    $dir = dirname(dirname($script));

    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }

    return str_replace('\\', '/', $dir);
}

/**
 * @return string
 */
function bfis_app_base_url(): string
{
    $configured = bfis_mail_env('BFIS_APP_BASE_URL', '');

    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $productionHost = strtolower(BFIS_PRODUCTION_HOST);

    if (
        $host === $productionHost
        || ($productionHost !== '' && substr($host, -strlen('.' . $productionHost)) === '.' . $productionHost)
    ) {
        $basePath = BFIS_PRODUCTION_BASE_PATH !== ''
            ? '/' . trim(BFIS_PRODUCTION_BASE_PATH, '/')
            : bfis_app_detect_base_path();

        return 'https://' . BFIS_PRODUCTION_HOST . $basePath;
    }

    $scheme = bfis_request_is_https() ? 'https' : 'http';
    $basePath = bfis_app_detect_base_path();

    return $scheme . '://' . ($host !== '' ? $host : 'localhost') . $basePath;
}
