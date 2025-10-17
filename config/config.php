<?php

declare(strict_types=1);

/**
 * WebFTP Configuration File
 *
 * All application configuration must be defined here.
 * PHP 8.0+ required.
 *
 * Security Notice: Keep this file outside the public directory
 * and ensure proper file permissions (600 or 640).
 */

return [

    // ============================================================================
    // APPLICATION SETTINGS
    // ============================================================================

    'app' => [
        'name' => 'WebFTP',
        'version' => '2.0.0',
        'environment' => 'production', // 'development' or 'production'
        'timezone' => 'UTC',
        'charset' => 'UTF-8',
    ],

    // ============================================================================
    // LOCALIZATION / INTERNATIONALIZATION
    // ============================================================================

    'localization' => [
        'default_language' => 'en',
        'available_languages' => ['en', 'fr', 'es', 'de', 'it', 'pt', 'ar'],
        'language_cookie_name' => 'webftp_language',
        'language_cookie_lifetime' => 31536000, // 1 year in seconds
    ],

    // ============================================================================
    // USER INTERFACE / THEMING
    // ============================================================================

    'ui' => [
        'default_theme' => 'dark', // 'light' or 'dark'
        'available_themes' => ['light', 'dark'],
        'theme_cookie_name' => 'webftp_theme',
        'theme_cookie_lifetime' => 31536000, // 1 year in seconds
    ],

    // ============================================================================
    // SECURITY SETTINGS
    // ============================================================================

    'security' => [

        // Session Configuration
        'session' => [
            'name' => 'WEBFTP_SESSION', // Custom session name
            'lifetime' => 3600, // 1 hour in seconds
            'cookie_httponly' => true,
            'cookie_secure' => true, // Set to false if not using HTTPS in development
            'cookie_samesite' => 'Strict', // 'Strict', 'Lax', or 'None'
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'regenerate_on_login' => true, // Prevent session fixation
        ],

        // CSRF Protection
        'csrf' => [
            'enabled' => true,
            'token_name' => '_csrf_token',
            'token_length' => 32, // bytes
            'token_lifetime' => 3600, // 1 hour in seconds
        ],

        // Rate Limiting (Brute Force Protection)
        'rate_limit' => [
            'enabled' => true,
            'max_attempts' => 5, // Maximum failed login attempts
            'lockout_duration' => 900, // 15 minutes in seconds
            'track_by_ip' => true, // Track attempts by IP address
        ],

        // Password Requirements
        'password' => [
            'min_length' => 8,
            'require_uppercase' => false, // FTP passwords may not meet this
            'require_lowercase' => false,
            'require_numbers' => false,
            'require_special' => false,
        ],

        // Security Headers
        'headers' => [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://cdn.tailwindcss.com 'unsafe-inline'; style-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none';",
        ],

        // Input Validation
        'input' => [
            'max_input_length' => 10000, // Maximum input field length
            'allowed_file_extensions' => ['txt', 'html', 'css', 'js', 'php', 'json', 'xml', 'md'],
            'max_upload_size' => 104857600, // 100MB in bytes
        ],
    ],

    // ============================================================================
    // FTP SERVER SETTINGS
    // ============================================================================
    // Users can ONLY connect to this FTP server (security: prevents SSRF attacks)

    'ftp' => [
        // FTP Server Configuration (YOUR hosting FTP server)
        // Users will authenticate with their ISPConfig/cPanel credentials
        'server' => [
            'host' => '192.168.187.139',  // Your FTP server hostname/IP
            'port' => 21,                    // FTP port (21 for FTP, 990 for FTPS)
            'use_ssl' => true,              // Enable FTPS (FTP over SSL/TLS)
            'passive_mode' => true,          // Use passive mode (recommended)
        ],

        // Connection Settings
        'timeout' => 30,                     // Connection timeout in seconds
        'operation_timeout' => 120,          // 2 minutes for file operations
        'max_connections_per_session' => 1,
        'connection_retry_attempts' => 3,
        'connection_retry_delay' => 2,       // seconds between retries
    ],

    // ============================================================================
    // LOGGING & ERROR HANDLING
    // ============================================================================

    'logging' => [
        'enabled' => true,
        'level' => 'error', // 'debug', 'info', 'warning', 'error'
        'log_path' => __DIR__ . '/../logs/app.log',
        'log_auth_attempts' => true, // Log all authentication attempts
        'log_ftp_operations' => false, // Log FTP operations (may be verbose)
    ],

    'error' => [
        'display_errors' => false, // Never display errors in production
        'log_errors' => true,
        'error_reporting' => E_ALL, // Report all errors internally
    ],

    // ============================================================================
    // PATHS
    // ============================================================================

    'paths' => [
        'root' => dirname(__DIR__),
        'src' => dirname(__DIR__) . '/src',
        'views' => dirname(__DIR__) . '/src/Views',
        'public' => dirname(__DIR__) . '/public',
        'logs' => dirname(__DIR__) . '/logs',
        'temp' => sys_get_temp_dir(),
    ],

    // ============================================================================
    // PERFORMANCE SETTINGS
    // ============================================================================

    'performance' => [
        'enable_compression' => true, // Enable gzip compression
        'compression_level' => 6, // 1-9, higher = more compression, more CPU
        'enable_caching' => false, // Cache static resources (future feature)
    ],

];
