<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

/**
 * HTTP Response Handler
 *
 * Manages HTTP responses and output.
 */
class Response
{
    /**
     * Send JSON response
     *
     * @param array $data Data to encode
     * @param int $statusCode HTTP status code
     */
    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        // JSON_UNESCAPED_SLASHES: Don't escape forward slashes
        // JSON_UNESCAPED_UNICODE: Don't escape unicode characters
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirect to URL
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (301, 302, etc.)
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Send HTML response
     *
     * @param string $html HTML content
     * @param int $statusCode HTTP status code
     */
    public function html(string $html, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    public function error(string $message, int $statusCode = 500): void
    {
        $this->json([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }

    /**
     * Send success response
     *
     * @param array $data Response data
     */
    public function success(array $data = []): void
    {
        $this->json([
            'success' => true,
            ...$data
        ]);
    }

    /**
     * Render a view
     *
     * @param string $view View file name (without .php)
     * @param array $data Data to pass to view
     * @param int $statusCode HTTP status code
     */
    public function view(string $view, array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');

        // Extract data for use in view
        extract($data, EXTR_SKIP);

        // Make translations available globally for helper functions
        if (isset($translations)) {
            $GLOBALS['translations'] = $translations;
        }

        // Include view helpers for all views
        require_once __DIR__ . '/ViewHelpers.php';

        // Include view file
        $viewPath = __DIR__ . "/../Views/{$view}.php";

        if (!file_exists($viewPath)) {
            $this->error("View not found: {$view}", 500);
        }

        require $viewPath;
        exit;
    }
}
