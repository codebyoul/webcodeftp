<?php

declare(strict_types=1);

namespace WebFTP\Core;

/**
 * Logger Class
 *
 * Provides centralized logging functionality for the entire application
 * with support for different log levels and contexts.
 */
class Logger
{
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private array $config;
    private static ?Logger $instance = null;
    private string $logPath;
    private bool $enabled;
    private string $logLevel;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->enabled = $config['logging']['enabled'] ?? false;
        $this->logPath = $config['logging']['log_path'] ?? __DIR__ . '/../../logs/app.log';
        $this->logLevel = strtoupper($config['logging']['level'] ?? 'ERROR');

        // Ensure log directory exists
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Initialize logger with config (call once at app start)
     */
    public static function init(array $config): void
    {
        self::$instance = new self($config);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Main logging method
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $instance = self::$instance;

        // If not initialized, use error_log as fallback
        if (!$instance) {
            error_log("[{$level}] {$message}" . (!empty($context) ? ' | ' . json_encode($context) : ''));
            return;
        }

        // Check if logging is enabled
        if (!$instance->enabled) {
            return;
        }

        // Check log level
        if (!$instance->shouldLog($level)) {
            return;
        }

        // Format the log message
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        // Get caller information for debugging
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = '';
        if (isset($backtrace[2])) {
            $file = basename($backtrace[2]['file'] ?? 'unknown');
            $line = $backtrace[2]['line'] ?? 0;
            $function = $backtrace[2]['function'] ?? 'unknown';
            $class = $backtrace[2]['class'] ?? '';
            $caller = $class ? " [{$class}::{$function} in {$file}:{$line}]" : " [{$function} in {$file}:{$line}]";
        }

        $logMessage = "[{$timestamp}] [{$level}]{$caller} {$message}{$contextStr}\n";

        // Write to log file
        @error_log($logMessage, 3, $instance->logPath);

        // Also output to error_log in development
        if (($instance->config['app']['environment'] ?? 'production') === 'development') {
            error_log($logMessage);
        }
    }

    /**
     * Check if a message should be logged based on level
     */
    private function shouldLog(string $level): bool
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];

        $currentLevel = $levels[$this->logLevel] ?? 3;
        $messageLevel = $levels[$level] ?? 0;

        return $messageLevel >= $currentLevel;
    }

    /**
     * Log authentication attempts
     */
    public static function auth(string $username, string $action, bool $success, string $ip = ''): void
    {
        $instance = self::$instance;

        if (!$instance || !($instance->config['logging']['log_auth_attempts'] ?? false)) {
            return;
        }

        $message = "Auth {$action} for user '{$username}' from IP {$ip}: " . ($success ? 'SUCCESS' : 'FAILED');
        self::log($success ? self::INFO : self::WARNING, $message);
    }

    /**
     * Log FTP operations
     */
    public static function ftp(string $operation, array $details = [], bool $success = true): void
    {
        $instance = self::$instance;

        if (!$instance || !($instance->config['logging']['log_ftp_operations'] ?? false)) {
            return;
        }

        $level = $success ? self::INFO : self::ERROR;
        $status = $success ? 'SUCCESS' : 'FAILED';
        self::log($level, "FTP {$operation} {$status}", $details);
    }

    /**
     * Clear log file
     */
    public static function clear(): bool
    {
        $instance = self::$instance;
        if (!$instance) {
            return false;
        }

        return @file_put_contents($instance->logPath, '') !== false;
    }

    /**
     * Get log file size
     */
    public static function getSize(): int
    {
        $instance = self::$instance;
        if (!$instance || !file_exists($instance->logPath)) {
            return 0;
        }

        return filesize($instance->logPath) ?: 0;
    }

    /**
     * Rotate log file if it exceeds max size
     */
    public static function rotate(int $maxSize = 10485760): bool // 10MB default
    {
        $instance = self::$instance;
        if (!$instance) {
            return false;
        }

        if (self::getSize() > $maxSize) {
            $backupPath = $instance->logPath . '.' . date('Y-m-d-His');
            if (@rename($instance->logPath, $backupPath)) {
                self::info('Log file rotated', ['backup' => $backupPath]);
                return true;
            }
        }

        return false;
    }
}