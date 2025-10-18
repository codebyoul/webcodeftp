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
        // Asset version for cache busting (increment when JS/CSS files change)
        // Format: major.minor.patch (e.g., 1.0.0, 1.0.1, 1.1.0, 2.0.0)
        'asset_version' => '1.2.0',
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
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://esm.sh 'unsafe-inline'; style-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none';",
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
        'level' => 'info', // 'debug', 'info', 'warning', 'error'
        'log_path' => __DIR__ . '/../logs/app.log',
        'log_auth_attempts' => true, // Log all authentication attempts
        'log_ftp_operations' => true, // Log FTP operations (may be verbose)
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

    // ============================================================================
    // FILE ICON MAPPING (Font Awesome Icons)
    // ============================================================================
    // Configure file type icons for the sidebar tree view.
    // Each category has extensions and a Font Awesome icon class.
    // You can add new categories or modify existing ones.
    //
    // Icon format: "fas fa-icon-name text-color-shade dark:text-color-shade"
    // Available colors: blue, green, red, purple, yellow, orange, gray, etc.
    // Available shades: 400, 500, 600, etc.

    'file_icons' => [
        // Programming & Script Files
        'code' => [
            'extensions' => ['php', 'js', 'ts', 'jsx', 'tsx', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go', 'rb', 'swift', 'kt', 'rs', 'scala', 'r', 'pl', 'lua'],
            'icon' => 'fas fa-file-code text-blue-500 dark:text-blue-400',
        ],

        // Markup & Style Files
        'web' => [
            'extensions' => ['html', 'htm', 'css', 'scss', 'sass', 'less'],
            'icon' => 'fas fa-file-code text-green-500 dark:text-green-400',
        ],

        // Configuration & Text Files
        'text' => [
            'extensions' => ['txt', 'md', 'json', 'xml', 'yml', 'yaml', 'toml', 'ini', 'conf', 'config', 'env', 'properties'],
            'icon' => 'fas fa-file-lines text-gray-500 dark:text-gray-400',
        ],

        // Image Files
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp', 'tiff', 'psd', 'ai'],
            'icon' => 'fas fa-file-image text-purple-500 dark:text-purple-400',
        ],

        // Archive Files
        'archive' => [
            'extensions' => ['zip', 'rar', 'tar', 'gz', 'bz2', '7z', 'tgz', 'xz', 'iso'],
            'icon' => 'fas fa-file-zipper text-orange-500 dark:text-orange-400',
        ],

        // Document Files
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'],
            'icon' => 'fas fa-file-pdf text-red-500 dark:text-red-400',
        ],

        // Video Files
        'video' => [
            'extensions' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'm4v', 'mpeg', 'mpg'],
            'icon' => 'fas fa-file-video text-pink-500 dark:text-pink-400',
        ],

        // Audio Files
        'audio' => [
            'extensions' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'wma', 'opus'],
            'icon' => 'fas fa-file-audio text-indigo-500 dark:text-indigo-400',
        ],

        // Database Files
        'database' => [
            'extensions' => ['sql', 'db', 'sqlite', 'mdb', 'accdb'],
            'icon' => 'fas fa-database text-cyan-500 dark:text-cyan-400',
        ],

        // Log Files
        'log' => [
            'extensions' => ['log'],
            'icon' => 'fas fa-file-lines text-yellow-600 dark:text-yellow-500',
        ],

        // Certificate & Key Files
        'certificate' => [
            'extensions' => ['pem', 'crt', 'cer', 'key', 'p12', 'pfx', 'csr'],
            'icon' => 'fas fa-certificate text-amber-600 dark:text-amber-500',
        ],

        // Font Files
        'font' => [
            'extensions' => ['ttf', 'otf', 'woff', 'woff2', 'eot'],
            'icon' => 'fas fa-font text-slate-500 dark:text-slate-400',
        ],

        // Default fallback icon
        'default' => [
            'extensions' => [],
            'icon' => 'fas fa-file text-gray-400 dark:text-gray-500',
        ],
    ],

    // ============================================================================
    // FILE EDITOR & PREVIEW
    // ============================================================================

    'file_editor' => [
        'enabled' => true,
        'max_file_size' => 5 * 1024 * 1024, // 5MB maximum file size for editing

        // Editable file extensions (text-based files)
        // Extended to support ALL major programming languages and frameworks
        'editable_extensions' => [
            // PHP (Laravel, Symfony, etc.)
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps',

            // JavaScript / TypeScript / Node.js
            'js', 'mjs', 'cjs', 'jsx', 'ts', 'tsx',

            // Python
            'py', 'pyw', 'pyi', 'pyx', 'wsgi',

            // Java
            'java', 'jar',

            // C / C++
            'c', 'cpp', 'cc', 'cxx', 'h', 'hpp', 'hh', 'hxx',

            // Other compiled languages
            'go', 'rs', 'swift', 'kt', 'cs', 'scala',

            // Scripting languages
            'rb', 'r', 'pl', 'lua', 'sh', 'bash', 'zsh', 'fish', 'ksh',

            // Web files & Markup
            'html', 'htm', 'xhtml', 'shtml',

            // CSS / Preprocessors
            'css', 'scss', 'sass', 'less', 'styl', 'stylus',

            // Frontend frameworks / components
            'vue', 'svelte',

            // Templates (Laravel, Symfony, Python, Node.js)
            'blade', 'twig', 'jinja', 'jinja2', 'ejs', 'hbs', 'mustache',

            // Data / Config files
            'json', 'jsonc', 'json5', 'xml', 'yaml', 'yml', 'toml', 'ini', 'conf', 'config',
            'env', 'properties', 'htaccess',

            // Markdown / Documentation
            'md', 'markdown', 'mdown', 'mkd', 'mdx', 'rst',

            // Database
            'sql', 'mysql', 'pgsql', 'sqlite', 'psql',

            // WebAssembly
            'wast', 'wat',

            // Text / Log files
            'txt', 'log', 'csv', 'tsv',

            // Development config files
            'gitignore', 'dockerignore', 'editorconfig', 'npmrc', 'eslintrc', 'prettierrc', 'lock',
        ],

        // Preview file extensions (images)
        'preview_extensions' => [
            'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp', 'ico',
        ],

        // CodeMirror themes
        'codemirror_theme_light' => 'light',
        'codemirror_theme_dark' => 'oneDark',
    ],

];
