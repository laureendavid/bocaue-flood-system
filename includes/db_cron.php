<?php

/**
 * Lightweight DB maintenance run on each request (poor man's cron).
 */

/**
 * @return void
 */
function bfis_run_verification_db_cron(PDO $pdo, mysqli $conn): void
{
    static $ran = false;

    if ($ran) {
        return;
    }

    $ran = true;

    try {
        $pdo->exec('DELETE FROM email_verifications WHERE expires_at < NOW()');
    } catch (Throwable $throwable) {
        error_log('PDO verification cron failed: ' . $throwable->getMessage());
    }

    try {
        $conn->query('DELETE FROM email_verifications WHERE expires_at < NOW()');
    } catch (Throwable $throwable) {
        error_log('mysqli verification cron failed: ' . $throwable->getMessage());
    }

    try {
        $userColumns = [];
        $columnResult = $conn->query('SHOW COLUMNS FROM users');

        if ($columnResult instanceof mysqli_result) {
            while ($row = $columnResult->fetch_assoc()) {
                $userColumns[] = (string) ($row['Field'] ?? '');
            }
        }

        if (!in_array('is_verified', $userColumns, true)) {
            return;
        }

        $conn->query(
            'DELETE FROM users
             WHERE is_verified = 0
               AND user_id IN (
                   SELECT user_id FROM (
                       SELECT user_id
                       FROM email_verifications
                       WHERE expires_at < NOW() - INTERVAL 7 DAY
                   ) expired_verifications
               )'
        );
    } catch (Throwable $throwable) {
        error_log('mysqli expired unverified user cron failed: ' . $throwable->getMessage());
    }
}
