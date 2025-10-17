<?php

declare(strict_types=1);

/**
 * WebFTP - Secure FTP Web Client
 *
 * Modern, secure, fast FTP client built with PHP 8.0+
 * Follows MVC pattern with enterprise-grade security.
 *
 * Entry Point - All requests are routed through this file.
 */

// Ensure PHP 8.0+
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    die('PHP 8.0 or higher is required. Current version: ' . PHP_VERSION);
}

// Start output buffering
ob_start();

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Configure error handling based on environment
if ($config['app']['environment'] === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

ini_set('log_errors', $config['error']['log_errors'] ? '1' : '0');
error_reporting($config['error']['error_reporting']);

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Enable compression if configured
if ($config['performance']['enable_compression'] && extension_loaded('zlib')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', (string)$config['performance']['compression_level']);
}

// Autoloader for classes
spl_autoload_register(function (string $class) use ($config) {
    // Convert namespace to file path
    // WebFTP\Core\SecurityManager -> src/Core/SecurityManager.php
    $prefix = 'WebFTP\\';
    $baseDir = $config['paths']['src'] . '/';

    // Check if class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get relative class name
    $relativeClass = substr($class, $len);

    // Convert to file path
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Load file if exists
    if (file_exists($file)) {
        require $file;
    }
});

// Import required classes
use WebFTP\Core\Router;
use WebFTP\Core\Request;
use WebFTP\Core\Response;
use WebFTP\Core\SecurityManager;
use WebFTP\Core\CsrfToken;
use WebFTP\Core\ConfigValidator;
use WebFTP\Models\Session;
use WebFTP\Controllers\AuthController;
use WebFTP\Controllers\DashboardController;

// Validate FTP configuration
$configValidation = ConfigValidator::validateFtpConfig($config);
if (!$configValidation['valid']) {
    http_response_code(500);
    $errorList = implode(', ', $configValidation['errors']);
    die("Configuration Error: {$errorList}. Please check config/config.php");
}

// Initialize core components
$security = new SecurityManager($config);
$session = new Session($config, $security);
$request = new Request($security);
$response = new Response();
$csrf = new CsrfToken($config);

// Set security headers
$security->setSecurityHeaders();

// Start session
$session->start();

// Initialize router
$router = new Router();

// Define routes
$router->get('/', function () use ($config, $request, $response, $session, $security, $csrf) {
    $controller = new AuthController($config, $request, $response, $session, $security, $csrf);
    $controller->showLogin();
});

$router->post('/login', function () use ($config, $request, $response, $session, $security, $csrf) {
    $controller = new AuthController($config, $request, $response, $session, $security, $csrf);
    $controller->login();
});

$router->get('/logout', function () use ($config, $request, $response, $session, $security, $csrf) {
    $controller = new AuthController($config, $request, $response, $session, $security, $csrf);
    $controller->logout();
});

$router->get('/dashboard', function () use ($config, $request, $response, $session) {
    $controller = new DashboardController($config, $request, $response, $session);
    $controller->index();
});

$router->post('/api/theme', function () use ($config, $request, $response, $session) {
    $controller = new DashboardController($config, $request, $response, $session);
    $controller->updateTheme();
});

$router->post('/api/language', function () use ($config, $request, $response, $session) {
    $controller = new DashboardController($config, $request, $response, $session);
    $controller->updateLanguage();
});

$router->get('/api/folder-tree', function () use ($config, $request, $response, $session) {
    $controller = new DashboardController($config, $request, $response, $session);
    $controller->getFolderTree();
});

$router->get('/api/folder-contents', function () use ($config, $request, $response, $session) {
    $controller = new DashboardController($config, $request, $response, $session);
    $controller->getFolderContents();
});

// Dispatch request
try {
    $method = $request->method();
    $path = $request->uri();
    $router->dispatch($method, $path);
} catch (\Throwable $e) {
    // Log error if logging enabled
    if ($config['logging']['enabled']) {
        error_log("Application error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }

    // Show error based on environment
    if ($config['app']['environment'] === 'production') {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - WebFTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-slate-800 rounded-lg shadow-2xl p-8 border border-slate-700 max-w-md text-center">
        <div class="mb-4">
            <svg class="w-16 h-16 text-red-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">Something went wrong</h1>
        <p class="text-slate-400 mb-6">An error occurred while processing your request.</p>
        <a href="/" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
            Return to Login
        </a>
    </div>
</body>
</html>';
    } else {
        // Development mode - show detailed error
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - WebFTP</title>
    <style>
        body { font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; }
        .error { background: #dc2626; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .trace { background: #334155; padding: 15px; border-radius: 5px; overflow-x: auto; }
        pre { margin: 0; }
    </style>
</head>
<body>
    <div class="error">
        <h1>Error: ' . htmlspecialchars($e->getMessage()) . '</h1>
        <p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>
    </div>
    <div class="trace">
        <h2>Stack Trace:</h2>
        <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
    </div>
</body>
</html>';
    }
}

// Flush output buffer
ob_end_flush();
