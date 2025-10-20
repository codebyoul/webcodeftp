<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

/**
 * Security Manager
 *
 * Handles all security-related operations:
 * - Security headers
 * - Input sanitization
 * - Path traversal protection
 * - Rate limiting
 */
class SecurityManager
{
    public function __construct(
        private array $config
    ) {}

    /**
     * Set security headers to prevent common attacks
     */
    public function setSecurityHeaders(): void
    {
        // Prevent caching of sensitive pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Security headers from config
        $headers = $this->config['security']['headers'];

        foreach ($headers as $header => $value) {
            header("{$header}: {$value}");
        }
    }

    /**
     * Sanitize input string - prevent XSS
     *
     * @param string $input Raw input string
     * @param int $maxLength Maximum allowed length
     * @return string Sanitized string
     */
    public function sanitizeInput(string $input, int $maxLength = 1000): string
    {
        // Trim whitespace
        $input = trim($input);

        // Enforce max length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }

        // Convert special characters to HTML entities
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize HTML output - prevent XSS
     *
     * @param string $output Raw output
     * @return string Safe HTML output
     */
    public function escapeHtml(string $output): string
    {
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validate and sanitize file path to prevent directory traversal
     *
     * @param string $path User-provided path
     * @param string $basePath Base directory that path must be within
     * @return string|null Sanitized path or null if invalid
     */
    public function sanitizePath(string $path, string $basePath = '/'): ?string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);

        // Normalize path separators
        $path = str_replace('\\', '/', $path);

        // Remove multiple consecutive slashes
        $path = preg_replace('#/+#', '/', $path);

        // Check for directory traversal patterns
        if (str_contains($path, '..')) {
            return null;
        }

        // For FTP paths, we just validate the pattern
        // Real validation happens at FTP level
        if (!preg_match('#^[a-zA-Z0-9/_.\-\s]+$#', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * Validate hostname format (basic validation)
     *
     * Note: In this application, FTP host is configured in config.php (trusted source),
     * not from user input. This validation is kept for future extensibility.
     *
     * @param string $host Hostname or IP address
     * @return bool True if format is valid
     */
    public function validateHost(string $host): bool
    {
        // Remove whitespace
        $host = trim($host);

        // Empty host is invalid
        if (empty($host)) {
            return false;
        }

        // Validate hostname format - allow alphanumeric, dots, hyphens, underscores
        // Must not start/end with dot or hyphen
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9._-]*[a-zA-Z0-9])?$/', $host)) {
            return false;
        }

        return true;
    }

    /**
     * Validate port number
     *
     * @param int $port Port number
     * @return bool True if valid
     */
    public function validatePort(int $port): bool
    {
        return $port >= 1 && $port <= 65535;
    }

    /**
     * Get client IP address (respects proxies)
     *
     * @return string Client IP address
     */
    public function getClientIp(): string
    {
        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check rate limiting for login attempts
     *
     * @param string $identifier Identifier (IP address or username)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function checkRateLimit(string $identifier): array
    {
        if (!$this->config['security']['rate_limit']['enabled']) {
            return ['allowed' => true, 'remaining' => 999, 'reset_time' => 0];
        }

        $sessionKey = "rate_limit_{$identifier}";
        $maxAttempts = $this->config['security']['rate_limit']['max_attempts'];
        $lockoutDuration = $this->config['security']['rate_limit']['lockout_duration'];

        // Get current attempt data
        $data = $_SESSION[$sessionKey] ?? [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => 0,
        ];

        // Check if currently locked out
        if ($data['locked_until'] > time()) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $data['locked_until'],
            ];
        }

        // Reset if lockout period has expired
        if ($data['locked_until'] > 0 && $data['locked_until'] <= time()) {
            $data = [
                'attempts' => 0,
                'first_attempt' => time(),
                'locked_until' => 0,
            ];
        }

        return [
            'allowed' => $data['attempts'] < $maxAttempts,
            'remaining' => max(0, $maxAttempts - $data['attempts']),
            'reset_time' => $data['locked_until'],
        ];
    }

    /**
     * Record failed login attempt
     *
     * @param string $identifier Identifier (IP address or username)
     */
    public function recordFailedAttempt(string $identifier): void
    {
        if (!$this->config['security']['rate_limit']['enabled']) {
            return;
        }

        $sessionKey = "rate_limit_{$identifier}";
        $maxAttempts = $this->config['security']['rate_limit']['max_attempts'];
        $lockoutDuration = $this->config['security']['rate_limit']['lockout_duration'];

        // Get or initialize attempt data
        $data = $_SESSION[$sessionKey] ?? [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => 0,
        ];

        $data['attempts']++;

        // Lock account if max attempts reached
        if ($data['attempts'] >= $maxAttempts) {
            $data['locked_until'] = time() + $lockoutDuration;
        }

        $_SESSION[$sessionKey] = $data;
    }

    /**
     * Reset rate limit counter (on successful login)
     *
     * @param string $identifier Identifier (IP address or username)
     */
    public function resetRateLimit(string $identifier): void
    {
        $sessionKey = "rate_limit_{$identifier}";
        unset($_SESSION[$sessionKey]);
    }

    /**
     * Constant-time string comparison to prevent timing attacks
     *
     * @param string $known Known string
     * @param string $user User-provided string
     * @return bool True if strings match
     */
    public function timingSafeCompare(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }
}
