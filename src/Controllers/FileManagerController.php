<?php

declare(strict_types=1);

namespace WebCodeFTP\Controllers;

use WebCodeFTP\Core\Request;
use WebCodeFTP\Core\Response;
use WebCodeFTP\Core\Logger;
use WebCodeFTP\Models\Session;
use WebCodeFTP\Services\FtpConnectionService;
use WebCodeFTP\Services\FtpOperationsService;

/**
 * File Manager Controller
 *
 * Handles the main FTP file manager interface for authenticated users.
 * Uses FtpConnectionService and FtpOperationsService for all FTP operations.
 */
class FileManagerController
{
    public function __construct(
        private array $config,
        private Request $request,
        private Response $response,
        private Session $session
    ) {
    }

    /**
     * Show file manager
     */
    public function index(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->redirect('/');
        }

        // Get user preferences from session or use defaults from config
        $theme = $this->session->get('theme', 'dark');

        // Check for language in cookie first, then session, then use default
        $cookieName = $this->config['localization']['language_cookie_name'] ?? 'webftp_language';
        $defaultLanguage = $this->config['localization']['default_language'];
        $language = $_COOKIE[$cookieName] ?? $this->session->get('language', $defaultLanguage);

        // Debug language detection
        Logger::debug('Language detection', [
            'cookie_name' => $cookieName,
            'cookie_value' => $_COOKIE[$cookieName] ?? 'not set',
            'session_language' => $this->session->get('language', 'not set'),
            'selected_language' => $language
        ]);

        // Load translations
        $lang = new \WebCodeFTP\Core\Language($language, $this->config);
        $translations = $lang->all();

        // Debug translations
        Logger::debug('Translations loaded', [
            'language' => $language,
            'total_keys' => count($translations),
            'has_select_folder' => isset($translations['select_folder']),
            'select_folder_value' => $translations['select_folder'] ?? 'NOT FOUND'
        ]);

        // Get FTP connection details from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');

        // Generate CSRF token for file operations
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);
        $csrfToken = $csrf->getToken();

        // Render file manager view
        $this->response->view('filemanager', [
            'app_name' => $this->config['app']['name'],
            'asset_version' => $this->config['app']['asset_version'] ?? '1.0.0',
            'ftp_host' => $ftpHost,
            'ftp_username' => $ftpUsername,
            'theme' => $theme,
            'language' => $language,
            'translations' => $translations,
            'language_cookie_name' => $this->config['localization']['language_cookie_name'],
            'language_cookie_lifetime' => $this->config['localization']['language_cookie_lifetime'],
            'file_icons' => $this->config['file_icons'],
            'csrf_token' => $csrfToken,
            'ssh_enabled' => $this->config['ssh']['enabled'] ?? false,
        ]);
    }

    /**
     * Update user theme preference
     */
    public function updateTheme(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $theme = $this->request->post('theme', 'dark');

        // Validate theme
        if (!in_array($theme, ['light', 'dark'], true)) {
            $this->response->json(['success' => false, 'message' => 'Invalid theme']);
        }

        // Save theme preference
        $this->session->set('theme', $theme);

        $this->response->json(['success' => true, 'theme' => $theme]);
    }

    /**
     * Update user language preference
     */
    public function updateLanguage(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $defaultLanguage = $this->config['localization']['default_language'];
        $language = $this->request->post('language', $defaultLanguage);

        // Get available languages from config
        $availableLanguages = $this->config['localization']['available_languages'];

        // Validate language
        if (!in_array($language, $availableLanguages, true)) {
            $this->response->json(['success' => false, 'message' => 'Invalid language']);
        }

        // Save language preference to both session and cookie
        $this->session->set('language', $language);

        // Set language cookie
        $cookieName = $this->config['localization']['language_cookie_name'] ?? 'webftp_language';
        $cookieLifetime = $this->config['localization']['language_cookie_lifetime'] ?? 31536000;
        setcookie($cookieName, $language, time() + $cookieLifetime, '/', '', true, true);

        $this->response->json(['success' => true, 'language' => $language]);
    }

    /**
     * Get folder tree structure from FTP
     */
    public function getFolderTree(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            Logger::debug("getFolderTree API: Unauthorized");
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get path from request (default to root)
        $path = $this->request->get('path', '/');

        try {
            // Initialize services
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                Logger::debug("getFolderTree API: Invalid path", ['path' => $path]);
                $this->response->json([
                    'success' => false,
                    'message' => 'Invalid path'
                ]);
            }

            // Connect to FTP and get folder tree
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPath) {
                $tree = $ftpOperations->getFolderTree($sanitizedPath);

                return [
                    'success' => true,
                    'tree' => $tree,
                    'path' => $sanitizedPath
                ];
            });

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::debug("getFolderTree API: Exception", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->response->json([
                'success' => false,
                'message' => 'Error retrieving folder tree'
            ]);
        }
    }

    /**
     * Get folder contents (folders and files)
     */
    public function getFolderContents(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get path from request (default to root)
        $path = $this->request->get('path', '/');

        try {
            // Initialize security manager
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json([
                    'success' => false,
                    'message' => 'Invalid path'
                ]);
            }

            // Connect to FTP and get directory contents
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPath) {
                $contents = $ftpOperations->getDirectoryContents($sanitizedPath);

                // Check if the folder exists
                if (!isset($contents['success']) || !$contents['success']) {
                    Logger::debug("getFolderContents: Folder does not exist", [
                        'path' => $sanitizedPath,
                        'contents' => $contents
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Folder not found',
                        'path' => $sanitizedPath
                    ];
                }

                Logger::debug("getFolderContents: Success", [
                    'path' => $sanitizedPath,
                    'folders' => count($contents['folders']),
                    'files' => count($contents['files'])
                ]);

                return [
                    'success' => true,
                    'path' => $sanitizedPath,
                    'folders' => $contents['folders'],
                    'files' => $contents['files']
                ];
            });

            $this->response->json($result);

        } catch (\Exception $e) {
            $this->response->json([
                'success' => false,
                'message' => 'Error retrieving folder contents'
            ]);
        }
    }

    /**
     * Read file contents for editing/preview
     */
    public function readFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get file path from request
        $path = $this->request->get('path', '');

        if (empty($path)) {
            $this->response->json(['success' => false, 'message' => 'File path required']);
        }

        try {
            // Initialize security manager
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
            }

            // Connect to FTP and read file
            $result = $this->withFtpConnection(function ($ftpOperations, $ftpConnection) use ($sanitizedPath) {
                // Get file size first
                $fileSize = $ftpOperations->getFileSize($sanitizedPath);
                if ($fileSize === -1) {
                    return ['success' => false, 'message' => 'File not found'];
                }

                // Check file size limit
                $maxSize = $this->config['file_editor']['max_file_size'];
                if ($fileSize > $maxSize) {
                    return [
                        'success' => false,
                        'message' => 'File too large (' . round($fileSize / 1024 / 1024, 2) . ' MB). Maximum: ' . round($maxSize / 1024 / 1024) . ' MB'
                    ];
                }

                // Read file content
                $content = $ftpOperations->readFile($sanitizedPath);

                if ($content === false) {
                    return ['success' => false, 'message' => 'Could not read file'];
                }

                // Get file extension and determine if it's editable
                $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));
                $isEditable = in_array($extension, $this->config['file_editor']['editable_extensions']);
                $isPreviewable = in_array($extension, $this->config['file_editor']['preview_extensions']);

                // For image files, base64 encode the content
                if ($isPreviewable) {
                    $content = base64_encode($content);
                }

                // Return file data
                return [
                    'success' => true,
                    'content' => $content,
                    'size' => $fileSize,
                    'extension' => $extension,
                    'isEditable' => $isEditable,
                    'isPreviewable' => $isPreviewable,
                    'path' => $sanitizedPath
                ];
            });

            $this->response->json($result);

        } catch (\Exception $e) {
            $this->response->json([
                'success' => false,
                'message' => 'Error reading file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Write file contents back to FTP
     */
    public function writeFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token', '');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        // Get file path and content from request
        $path = $this->request->post('path', '');

        // Get content directly from POST without sanitization
        $content = $_POST['content'] ?? '';

        // Validate length for security
        $maxSize = $this->config['file_editor']['max_file_size'] ?? 10485760;
        if (strlen($content) > $maxSize) {
            $this->response->json([
                'success' => false,
                'message' => 'Content too large. Maximum: ' . round($maxSize / 1024 / 1024) . ' MB'
            ]);
        }

        if (empty($path)) {
            $this->response->json(['success' => false, 'message' => 'File path required']);
        }

        try {
            // Initialize security manager
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
            }

            // Check if file extension is editable
            $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['file_editor']['editable_extensions'])) {
                $this->response->json(['success' => false, 'message' => 'File type not editable']);
            }

            // Connect to FTP and write file
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPath, $content) {
                $writeResult = $ftpOperations->writeFile($sanitizedPath, $content);

                if (!$writeResult['success']) {
                    return $writeResult;
                }

                return [
                    'success' => true,
                    'message' => 'File saved successfully',
                    'path' => $sanitizedPath
                ];
            });

            $this->response->json($result);

        } catch (\Exception $e) {
            $this->response->json([
                'success' => false,
                'message' => 'Error saving file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get fresh CSRF token via AJAX
     */
    public function getCsrfToken()
    {
        if (!$this->session->isAuthenticated()) {
            $this->response->json([
                'success' => false,
                'message' => 'Not authenticated'
            ]);
            return;
        }

        // Generate new CSRF token
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);
        $csrfToken = $csrf->getToken();

        $this->response->json([
            'success' => true,
            'csrf_token' => $csrfToken
        ]);
    }

    /**
     * Serve image file from FTP
     */
    public function getImage(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            http_response_code(401);
            echo 'Unauthorized';
            exit;
        }

        // Get file path from request
        $path = $this->request->get('path', '');

        if (empty($path)) {
            http_response_code(400);
            echo 'File path required';
            exit;
        }

        try {
            // Initialize security manager
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                http_response_code(400);
                echo 'Invalid path';
                exit;
            }

            // Connect to FTP and read image file
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPath) {
                // Read file from FTP
                $fileContent = $ftpOperations->readFile($sanitizedPath);

                if ($fileContent === false) {
                    return ['success' => false];
                }

                return ['success' => true, 'content' => $fileContent];
            });

            if (!$result['success']) {
                http_response_code(404);
                echo 'File not found';
                exit;
            }

            // Determine MIME type from extension
            $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'ico' => 'image/x-icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff'
            ];

            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Set headers and output image
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . strlen($result['content']));
            header('Cache-Control: public, max-age=3600');
            echo $result['content'];
            exit;

        } catch (\Exception $e) {
            Logger::error('Image preview error', [
                'path' => $path,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            http_response_code(500);
            echo 'Error retrieving image: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Create new file via FTP
     */
    public function createFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token', '');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid security token'], 403);
            return;
        }

        // Get parameters
        $path = $this->request->post('path', '');
        $filename = $this->request->post('filename', '');

        if (empty($path) || empty($filename)) {
            $this->response->json(['success' => false, 'message' => 'Path and filename are required']);
            return;
        }

        // Sanitize filename
        $filename = basename($filename);
        if (empty($filename) || $filename === '.' || $filename === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid filename']);
            return;
        }

        // Build full path
        $fullPath = rtrim($path, '/') . '/' . $filename;

        try {
            // Connect to FTP and create file
            $result = $this->withFtpConnection(function ($ftpOperations) use ($fullPath) {
                return $ftpOperations->createFile($fullPath);
            });

            if ($result['success']) {
                $result['path'] = $fullPath;
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Create file error', [
                'path' => $fullPath,
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create new folder via FTP
     */
    public function createFolder(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token', '');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid security token'], 403);
            return;
        }

        // Get parameters
        $path = $this->request->post('path', '');
        $foldername = $this->request->post('foldername', '');

        if (empty($path) || empty($foldername)) {
            $this->response->json(['success' => false, 'message' => 'Path and folder name are required']);
            return;
        }

        // Sanitize foldername
        $foldername = basename($foldername);
        if (empty($foldername) || $foldername === '.' || $foldername === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid folder name']);
            return;
        }

        // Build full path
        $fullPath = rtrim($path, '/') . '/' . $foldername;

        try {
            // Connect to FTP and create folder
            $result = $this->withFtpConnection(function ($ftpOperations) use ($fullPath) {
                return $ftpOperations->createFolder($fullPath);
            });

            if ($result['success']) {
                $result['path'] = $fullPath;
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Create folder error', [
                'path' => $fullPath,
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Rename file or folder via FTP
     */
    public function rename(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token', '');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid security token'], 403);
            return;
        }

        // Get parameters
        $oldPath = $this->request->post('old_path', '');
        $newName = $this->request->post('new_name', '');

        if (empty($oldPath) || empty($newName)) {
            $this->response->json(['success' => false, 'message' => 'Old path and new name are required']);
            return;
        }

        // Sanitize new name
        $newName = basename($newName);
        if (empty($newName) || $newName === '.' || $newName === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid name']);
            return;
        }

        // Build new path
        $parentPath = dirname($oldPath);
        $newPath = ($parentPath === '/' || $parentPath === '.')
            ? '/' . $newName
            : $parentPath . '/' . $newName;

        try {
            // Connect to FTP and rename
            $result = $this->withFtpConnection(function ($ftpOperations) use ($oldPath, $newPath) {
                return $ftpOperations->rename($oldPath, $newPath);
            });

            if ($result['success']) {
                $result['old_path'] = $oldPath;
                $result['new_path'] = $newPath;
                $result['parent_path'] = $parentPath === '.' ? '/' : $parentPath;
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Rename error', [
                'old_path' => $oldPath,
                'new_name' => $newName,
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete file or folder
     */
    public function delete(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            Logger::warning('CSRF validation failed for delete', [
                'user' => $this->session->get('ftp_username'),
                'ip' => $this->request->ip()
            ]);
            $this->response->json(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        try {
            // Check if paths is an array (batch delete) or single path
            // Note: Use rawPost() for JSON data to avoid HTML entity encoding
            $paths = $this->request->rawPost('paths');
            $path = $this->request->post('path');

            // Collect paths (support both batch and single delete)
            $pathsToDelete = [];
            if (!empty($paths)) {
                // Batch delete mode - paths should be JSON encoded array
                if (is_string($paths)) {
                    // Security: Limit JSON string length (supports ~100 paths)
                    if (strlen($paths) > 10000) {
                        $this->response->json(['success' => false, 'message' => 'Paths data too large']);
                        return;
                    }

                    $decodedPaths = json_decode($paths, true);

                    if ($decodedPaths === null && json_last_error() !== JSON_ERROR_NONE) {
                        $this->response->json(['success' => false, 'message' => 'Invalid JSON in paths']);
                        return;
                    }

                    $paths = $decodedPaths;
                }

                if (!is_array($paths) || empty($paths)) {
                    $this->response->json(['success' => false, 'message' => 'Invalid paths array']);
                    return;
                }
                $pathsToDelete = $paths;
            } elseif (!empty($path)) {
                // Single delete mode (backward compatibility)
                $pathsToDelete = [$path];
            } else {
                $this->response->json(['success' => false, 'message' => 'Path or paths required']);
                return;
            }

            // Sanitize all paths
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);
            $sanitizedPaths = [];
            foreach ($pathsToDelete as $p) {
                $sanitized = $security->sanitizePath($p);
                if ($sanitized === null) {
                    $this->response->json(['success' => false, 'message' => 'Invalid path: ' . $p]);
                    return;
                }
                $sanitizedPaths[] = $sanitized;
            }

            // Perform delete using unified delete() method
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPaths) {
                return $ftpOperations->delete($sanitizedPaths);
            });

            Logger::info('Delete completed', [
                'user' => $this->session->get('ftp_username'),
                'count' => count($sanitizedPaths),
                'success' => $result['successCount'],
                'failed' => $result['failedCount']
            ]);

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Delete error', [
                'paths' => $paths ?? null,
                'path' => $path ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Download file
     */
    public function downloadFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            http_response_code(403);
            echo 'Not authenticated';
            return;
        }

        try {
            $path = $this->request->get('path');

            // Validate path
            if (empty($path)) {
                http_response_code(400);
                echo 'Path is required';
                return;
            }

            // Sanitize path
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);
            $sanitizedPath = $security->sanitizePath($path);

            if ($sanitizedPath === null) {
                http_response_code(400);
                echo 'Invalid path';
                return;
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'webftp_');

            // Connect to FTP and download file
            $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedPath, $tempFile) {
                return $ftpOperations->downloadFile($sanitizedPath, $tempFile);
            });

            if (!$result['success']) {
                @unlink($tempFile);
                http_response_code(500);
                echo $result['message'];
                return;
            }

            // Get filename
            $filename = basename($sanitizedPath);

            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tempFile));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');

            // Output file
            readfile($tempFile);

            // Clean up temp file
            @unlink($tempFile);

            Logger::info('File downloaded', [
                'user' => $this->session->get('ftp_username'),
                'path' => $sanitizedPath,
                'size' => $result['size']
            ]);

        } catch (\Exception $e) {
            Logger::error('Download error', [
                'path' => $path ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            http_response_code(500);
            echo 'Download failed: ' . $e->getMessage();
        }
    }

    /**
     * Unzip archive file via SSH
     */
    public function unzipFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        // Check if SSH is enabled
        if (!$this->config['ssh']['enabled']) {
            $this->response->json(['success' => false, 'message' => 'SSH operations are not enabled']);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            Logger::warning('CSRF validation failed for unzip', [
                'user' => $this->session->get('ftp_username'),
                'ip' => $this->request->ip()
            ]);
            $this->response->json(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        try {
            $path = $this->request->post('path');

            // Validate path
            if (empty($path)) {
                $this->response->json(['success' => false, 'message' => 'Path is required']);
                return;
            }

            // Sanitize path
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);
            $sanitizedPath = $security->sanitizePath($path);

            if ($sanitizedPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
                return;
            }

            // Determine extraction directory (same directory as archive)
            $destinationPath = dirname($sanitizedPath);

            // Use SSH for unzip operation
            $result = $this->withSshConnection(function ($sshOperations) use ($sanitizedPath, $destinationPath) {
                return $sshOperations->unzipFile($sanitizedPath, $destinationPath);
            });

            if ($result['success']) {
                Logger::info('Archive extracted successfully', [
                    'user' => $this->session->get('ftp_username'),
                    'path' => $sanitizedPath
                ]);
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Unzip error', [
                'path' => $path ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Compress files/folders into archive
     */
    public function zipFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        // Check if SSH is enabled (zip requires SSH)
        if (!$this->config['ssh']['enabled']) {
            $this->response->json(['success' => false, 'message' => 'SSH operations are not enabled']);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            Logger::warning('CSRF validation failed for zip', [
                'user' => $this->session->get('ftp_username'),
                'ip' => $this->request->ip()
            ]);
            $this->response->json(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        try {
            $sourcePath = $this->request->post('source_path');
            $archiveName = $this->request->post('archive_name');

            // Validate paths
            if (empty($sourcePath) || empty($archiveName)) {
                $this->response->json(['success' => false, 'message' => 'Source path and archive name are required']);
                return;
            }

            // Sanitize paths
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);
            $sanitizedSourcePath = $security->sanitizePath($sourcePath);
            $sanitizedArchiveName = $security->sanitizePath($archiveName);

            if ($sanitizedSourcePath === null || $sanitizedArchiveName === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
                return;
            }

            // Use SSH for zip operation
            $result = $this->withSshConnection(function ($sshOperations) use ($sanitizedSourcePath, $sanitizedArchiveName) {
                return $sshOperations->zipFile($sanitizedSourcePath, $sanitizedArchiveName);
            });

            if ($result['success']) {
                Logger::info('Archive created successfully', [
                    'user' => $this->session->get('ftp_username'),
                    'source' => $sanitizedSourcePath,
                    'archive' => $sanitizedArchiveName
                ]);
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Zip error', [
                'source' => $sourcePath ?? 'unknown',
                'archive' => $archiveName ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Move/rename file or folder
     */
    public function moveFile(): void
    {
        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->response->json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        // Validate CSRF token
        $csrfToken = $this->request->post('_csrf_token');
        $csrf = new \WebCodeFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            Logger::warning('CSRF validation failed for move', [
                'user' => $this->session->get('ftp_username'),
                'ip' => $this->request->ip()
            ]);
            $this->response->json(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        try {
            $sourcePath = $this->request->post('source_path');
            $destinationPath = $this->request->post('destination_path');

            // Validate paths
            if (empty($sourcePath) || empty($destinationPath)) {
                $this->response->json(['success' => false, 'message' => 'Source and destination paths are required']);
                return;
            }

            // Sanitize paths
            $security = new \WebCodeFTP\Core\SecurityManager($this->config);
            $sanitizedSourcePath = $security->sanitizePath($sourcePath);
            $sanitizedDestinationPath = $security->sanitizePath($destinationPath);

            if ($sanitizedSourcePath === null || $sanitizedDestinationPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
                return;
            }

            // Check if SSH is enabled - use SSH, otherwise use FTP
            if ($this->config['ssh']['enabled']) {
                // Use SSH for move operation
                $result = $this->withSshConnection(function ($sshOperations) use ($sanitizedSourcePath, $sanitizedDestinationPath) {
                    return $sshOperations->move($sanitizedSourcePath, $sanitizedDestinationPath);
                });
            } else {
                // Use FTP for move operation (rename)
                $result = $this->withFtpConnection(function ($ftpOperations) use ($sanitizedSourcePath, $sanitizedDestinationPath) {
                    return $ftpOperations->renameFile($sanitizedSourcePath, $sanitizedDestinationPath);
                });
            }

            if ($result['success']) {
                Logger::info('File/folder moved successfully', [
                    'user' => $this->session->get('ftp_username'),
                    'source' => $sanitizedSourcePath,
                    'destination' => $sanitizedDestinationPath,
                    'method' => $this->config['ssh']['enabled'] ? 'SSH' : 'FTP'
                ]);
            }

            $this->response->json($result);

        } catch (\Exception $e) {
            Logger::error('Move error', [
                'source' => $sourcePath ?? 'unknown',
                'destination' => $destinationPath ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Helper method to execute FTP operations with automatic connection management
     *
     * @param callable $callback Callback function that receives FtpOperationsService
     * @return mixed Result from callback
     * @throws \Exception If connection fails
     */
    private function withFtpConnection(callable $callback): mixed
    {
        // Get FTP credentials from session
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        if (!$ftpUsername || !$ftpPassword) {
            return ['success' => false, 'message' => 'FTP credentials not found'];
        }

        // Initialize services
        $security = new \WebCodeFTP\Core\SecurityManager($this->config);
        $ftpConnectionService = new FtpConnectionService($this->config, $security);
        $ftpOperationsService = new FtpOperationsService($ftpConnectionService, $security, $this->config);

        try {
            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            $connectionResult = $ftpConnectionService->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                return [
                    'success' => false,
                    'message' => $connectionResult['message']
                ];
            }

            // Execute callback with FTP operations service
            $result = $callback($ftpOperationsService, $ftpConnectionService);

            // Disconnect
            $ftpConnectionService->disconnect();

            return $result;

        } catch (\Exception $e) {
            // Ensure disconnection on error
            if (isset($ftpConnectionService)) {
                $ftpConnectionService->disconnect();
            }
            throw $e;
        }
    }

    /**
     * Helper method to execute SSH operations with automatic connection management
     *
     * @param callable $callback Callback function that receives SshOperationsService
     * @return mixed Result from callback
     * @throws \Exception If connection fails
     */
    private function withSshConnection(callable $callback): mixed
    {
        // Get SSH credentials from config (separate from FTP credentials)
        $sshCredentials = $this->config['ssh']['credentials'] ?? [];
        $username = $sshCredentials['username'] ?? '';
        $password = $sshCredentials['password'] ?? '';

        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'SSH credentials not configured in config.php'
            ];
        }

        // Initialize services
        $security = new \WebCodeFTP\Core\SecurityManager($this->config);
        $sshConnectionService = new \WebCodeFTP\Services\SshConnectionService($this->config, $security);
        $sshOperationsService = new \WebCodeFTP\Services\SshOperationsService($sshConnectionService, $security, $this->config);

        try {
            // Connect to SSH server
            $sshConfig = $this->config['ssh']['server'];
            $connectionResult = $sshConnectionService->connect(
                $sshConfig['host'],
                $sshConfig['port'],
                $username,
                $password
            );

            if (!$connectionResult['success']) {
                return [
                    'success' => false,
                    'message' => $connectionResult['message']
                ];
            }

            // Execute callback with SSH operations service
            $result = $callback($sshOperationsService, $sshConnectionService);

            // Disconnect
            $sshConnectionService->disconnect();

            return $result;

        } catch (\Exception $e) {
            // Ensure disconnection on error
            if (isset($sshConnectionService)) {
                $sshConnectionService->disconnect();
            }
            throw $e;
        }
    }
}
