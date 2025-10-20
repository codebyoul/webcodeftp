<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

/**
 * HTTP Request Handler
 *
 * Safely handles HTTP request data with security in mind.
 */
class Request
{
    public function __construct(
        private SecurityManager $security
    ) {}

    /**
     * Get request method
     *
     * @return string HTTP method (GET, POST, etc.)
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if request is GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Get POST parameter with sanitization
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not set
     * @param int $maxLength Maximum allowed length
     * @return mixed Sanitized value
     */
    public function post(string $key, mixed $default = null, int $maxLength = 1000): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = $_POST[$key];

        if (is_string($value)) {
            return $this->security->sanitizeInput($value, $maxLength);
        }

        return $value;
    }

    /**
     * Get GET parameter with sanitization
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not set
     * @param int $maxLength Maximum allowed length
     * @return mixed Sanitized value
     */
    public function get(string $key, mixed $default = null, int $maxLength = 1000): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = $_GET[$key];

        if (is_string($value)) {
            return $this->security->sanitizeInput($value, $maxLength);
        }

        return $value;
    }

    /**
     * Get parameter from GET or POST
     *
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @param int $maxLength Maximum allowed length
     * @return mixed Sanitized value
     */
    public function input(string $key, mixed $default = null, int $maxLength = 1000): mixed
    {
        return $this->post($key) ?? $this->get($key, $default, $maxLength);
    }

    /**
     * Get raw POST parameter without sanitization
     * WARNING: Use only for JSON data that will be decoded. Never output directly to HTML.
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not set
     * @return mixed Raw value
     */
    public function rawPost(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get all POST data
     *
     * @return array
     */
    public function all(): array
    {
        return $_POST;
    }

    /**
     * Check if parameter exists in POST
     *
     * @param string $key Parameter name
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    /**
     * Get query string
     *
     * @return string
     */
    public function queryString(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is HTTPS
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    /**
     * Get user agent
     *
     * @return string
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
