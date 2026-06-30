<?php

/**
 * Cloudinary credentials and lazy SDK bootstrap.
 */

/**
 * @return array{cloud_name: string, api_key: string, api_secret: string}
 */
function bfis_cloudinary_credentials(): array
{
    static $cached = null;

    if (is_array($cached)) {
        return $cached;
    }

    $url = getenv('CLOUDINARY_URL') ?: ($_ENV['CLOUDINARY_URL'] ?? '');

    if (
        is_string($url)
        && $url !== ''
        && preg_match('#^cloudinary://([^:]+):([^@]+)@([^/?]+)#', $url, $matches) === 1
    ) {
        $cached = [
            'api_key' => rawurldecode($matches[1]),
            'api_secret' => rawurldecode($matches[2]),
            'cloud_name' => rawurldecode($matches[3]),
        ];

        return $cached;
    }

    $cached = [
        'cloud_name' => 'duenryw6r',
        'api_key' => '412112979956133',
        'api_secret' => 'gX-koBZkHOOh26DGJyhy19jWkYs',
    ];

    return $cached;
}

/**
 * @return bool
 */
function bfis_cloudinary_configure(): bool
{
    static $configured = false;
    static $failed = false;

    if ($configured) {
        return true;
    }

    if ($failed) {
        return false;
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!is_file($autoload)) {
        $failed = true;

        return false;
    }

    try {
        require_once $autoload;

        if (!class_exists(\Cloudinary\Configuration\Configuration::class)) {
            $failed = true;

            return false;
        }

        \Cloudinary\Configuration\Configuration::instance([
            'cloud' => bfis_cloudinary_credentials(),
            'url' => [
                'secure' => true,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('Cloudinary SDK bootstrap failed: ' . $e->getMessage());
        $failed = true;

        return false;
    }

    $configured = true;

    return true;
}

/**
 * @return bool
 */
function bfis_cloudinary_is_available(): bool
{
    return bfis_cloudinary_configure() || function_exists('curl_init');
}
