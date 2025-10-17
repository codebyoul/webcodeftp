<?php

declare(strict_types=1);

namespace WebFTP\Models;

use WebFTP\Core\SecurityManager;

/**
 * Secure Session Manager
 *
 * Handles session initialization, validation, and security.
 * Prevents session hijacking, fixation, and other attacks.
 */
class Session
{
    private bool $started = false;

    public function __construct(
        private array $config,
        private SecurityManager $security
    ) {}

    /**
     * Start secure session with hardened configuration
     */
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionConfig = $this->config['security']['session'];

        // Configure session parameters BEFORE starting session
        ini_set('session.use_strict_mode', $sessionConfig['use_strict_mode'] ? '1' : '0');
        ini_set('session.use_only_cookies', $sessionConfig['use_only_cookies'] ? '1' : '0');
        ini_set('session.cookie_httponly', $sessionConfig['cookie_httponly'] ? '1' : '0');
        ini_set('session.cookie_secure', $sessionConfig['cookie_secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $sessionConfig['cookie_samesite']);
        ini_set('session.cookie_lifetime', (string)$sessionConfig['lifetime']);
        ini_set('session.gc_maxlifetime', (string)$sessionConfig['lifetime']);

        // Use custom session name
        session_name($sessionConfig['name']);

        // Start session
        session_start();

        $this->started = true;

        // Validate session
        $this->validateSession();
    }

    /**
     * Validate session to prevent hijacking
     */
    private function validateSession(): void
    {
        // First-time session initialization
        if (!isset($_SESSION['_initialized'])) {
            $this->initializeSession();
            return;
        }

        // Validate session fingerprint
        if (!$this->validateFingerprint()) {
            $this->destroy();
            $this->initializeSession();
            return;
        }

        // Check session timeout
        if ($this->isExpired()) {
            $this->destroy();
            return;
        }

        // Update last activity time
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Initialize new session with security markers
     */
    private function initializeSession(): void
    {
        $_SESSION['_initialized'] = true;
        $_SESSION['_created'] = time();
        $_SESSION['_last_activity'] = time();
        $_SESSION['_fingerprint'] = $this->generateFingerprint();
        $_SESSION['_ip_address'] = $this->security->getClientIp();
    }

    /**
     * Generate session fingerprint for validation
     *
     * @return string Session fingerprint hash
     */
    private function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

        // Create fingerprint from browser characteristics
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Validate session fingerprint
     *
     * @return bool True if fingerprint matches
     */
    private function validateFingerprint(): bool
    {
        if (!isset($_SESSION['_fingerprint'])) {
            return false;
        }

        $currentFingerprint = $this->generateFingerprint();
        return hash_equals($_SESSION['_fingerprint'], $currentFingerprint);
    }

    /**
     * Check if session has expired
     *
     * @return bool True if expired
     */
    private function isExpired(): bool
    {
        if (!isset($_SESSION['_last_activity'])) {
            return true;
        }

        $lifetime = $this->config['security']['session']['lifetime'];
        return (time() - $_SESSION['_last_activity']) > $lifetime;
    }

    /**
     * Regenerate session ID (prevent fixation)
     */
    public function regenerate(): void
    {
        if ($this->started) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
    }

    /**
     * Destroy session completely
     */
    public function destroy(): void
    {
        if ($this->started) {
            $_SESSION = [];

            // Delete session cookie
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );

            session_destroy();
            $this->started = false;
        }
    }

    /**
     * Set session value
     *
     * @param string $key Session key
     * @param mixed $value Value to store
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     *
     * @param string $key Session key
     * @param mixed $default Default value if not set
     * @return mixed Session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     *
     * @param string $key Session key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     *
     * @param string $key Session key
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->get('authenticated', false) === true;
    }

    /**
     * Mark user as authenticated
     */
    public function authenticate(): void
    {
        // Regenerate session ID on authentication (prevent session fixation)
        if ($this->config['security']['session']['regenerate_on_login']) {
            $this->regenerate();
        }

        $this->set('authenticated', true);
        $this->set('auth_time', time());
    }

    /**
     * Mark user as unauthenticated
     */
    public function unauthenticate(): void
    {
        $this->destroy();
    }

    /**
     * Get session ID
     *
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Flash message - store for one request
     *
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get and remove flash message
     *
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash message exists
     *
     * @param string $key Flash key
     * @return bool
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }
}
