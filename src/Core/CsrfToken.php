<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

/**
 * CSRF Token Manager
 *
 * Generates and validates CSRF tokens to prevent Cross-Site Request Forgery attacks.
 * Uses cryptographically secure random tokens stored in session.
 */
class CsrfToken
{
    private const SESSION_KEY = 'csrf_tokens';

    public function __construct(
        private array $config
    ) {}

    /**
     * Generate a new CSRF token
     *
     * @return string CSRF token
     */
    public function generate(): string
    {
        // Generate cryptographically secure random token
        $tokenLength = $this->config['security']['csrf']['token_length'];
        $token = bin2hex(random_bytes($tokenLength));

        // Store token in session with timestamp
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$token] = time();

        // Clean up old tokens
        $this->cleanupOldTokens();

        return $token;
    }

    /**
     * Validate CSRF token
     *
     * @param string|null $token Token to validate
     * @return bool True if valid
     */
    public function validate(?string $token): bool
    {
        if (!$this->config['security']['csrf']['enabled']) {
            return true;
        }

        if ($token === null || $token === '') {
            return false;
        }

        // Check if token exists in session
        if (!isset($_SESSION[self::SESSION_KEY][$token])) {
            return false;
        }

        $tokenTime = $_SESSION[self::SESSION_KEY][$token];
        $tokenLifetime = $this->config['security']['csrf']['token_lifetime'];

        // Check if token has expired
        if (time() - $tokenTime > $tokenLifetime) {
            unset($_SESSION[self::SESSION_KEY][$token]);
            return false;
        }

        // Token is valid - remove it (one-time use)
        unset($_SESSION[self::SESSION_KEY][$token]);

        return true;
    }

    /**
     * Get current token (or generate new one)
     *
     * @return string CSRF token
     */
    public function getToken(): string
    {
        // Generate new token for each request
        return $this->generate();
    }

    /**
     * Clean up expired tokens from session
     */
    private function cleanupOldTokens(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }

        $tokenLifetime = $this->config['security']['csrf']['token_lifetime'];
        $currentTime = time();

        foreach ($_SESSION[self::SESSION_KEY] as $token => $timestamp) {
            if ($currentTime - $timestamp > $tokenLifetime) {
                unset($_SESSION[self::SESSION_KEY][$token]);
            }
        }
    }

    /**
     * Get HTML hidden input field for CSRF token
     *
     * @return string HTML input element
     */
    public function getHiddenInput(): string
    {
        $tokenName = htmlspecialchars($this->config['security']['csrf']['token_name'], ENT_QUOTES);
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES);

        return "<input type=\"hidden\" name=\"{$tokenName}\" value=\"{$token}\">";
    }
}
