<?php

declare(strict_types=1);

namespace WebFTP\Core;

/**
 * Configuration Validator
 *
 * Validates configuration settings to ensure security and correctness.
 */
class ConfigValidator
{
    /**
     * Validate FTP server configuration
     *
     * @param array $config Full configuration array
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateFtpConfig(array $config): array
    {
        $errors = [];

        // Check if FTP config exists
        if (!isset($config['ftp']['server'])) {
            $errors[] = 'FTP server configuration is missing';
            return ['valid' => false, 'errors' => $errors];
        }

        $ftpServer = $config['ftp']['server'];

        // Validate host
        if (empty($ftpServer['host'])) {
            $errors[] = 'FTP host is required in config.php';
        } elseif (!self::isValidHostname($ftpServer['host'])) {
            $errors[] = "Invalid FTP hostname format: {$ftpServer['host']}";
        }

        // Validate port
        if (!isset($ftpServer['port']) || $ftpServer['port'] < 1 || $ftpServer['port'] > 65535) {
            $errors[] = 'FTP port must be between 1 and 65535';
        }

        // Validate SSL setting
        if (!isset($ftpServer['use_ssl']) || !is_bool($ftpServer['use_ssl'])) {
            $errors[] = 'FTP use_ssl must be true or false';
        }

        // Validate passive mode
        if (!isset($ftpServer['passive_mode']) || !is_bool($ftpServer['passive_mode'])) {
            $errors[] = 'FTP passive_mode must be true or false';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate hostname format
     *
     * @param string $host Hostname
     * @return bool
     */
    private static function isValidHostname(string $host): bool
    {
        $host = trim($host);

        if (empty($host)) {
            return false;
        }

        // Allow alphanumeric, dots, hyphens, underscores
        return (bool) preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9._-]*[a-zA-Z0-9])?$/', $host);
    }
}
