<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

/**
 * Simple Router
 *
 * Routes HTTP requests to appropriate controllers.
 */
class Router
{
    private array $routes = [];

    /**
     * Add GET route
     *
     * @param string $path URL path
     * @param callable $handler Route handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Add POST route
     *
     * @param string $path URL path
     * @param callable $handler Route handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Dispatch request to appropriate handler
     *
     * @param string $method HTTP method
     * @param string $path URL path
     */
    public function dispatch(string $method, string $path): void
    {
        // Normalize path
        $path = $path === '' ? '/' : $path;

        // Check if route exists
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            call_user_func($handler);
            return;
        }

        // 404 Not Found
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
}
