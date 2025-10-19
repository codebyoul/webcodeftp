<?php

declare(strict_types=1);

namespace WebFTP\Controllers;

use WebFTP\Core\Request;
use WebFTP\Core\Response;
use WebFTP\Core\Logger;
use WebFTP\Models\Session;

/**
 * File Manager Controller
 *
 * Handles the main FTP file manager interface for authenticated users.
 */
class FileManagerController
{
    public function __construct(
        private array $config,
        private Request $request,
        private Response $response,
        private Session $session
    ) {}

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
        $lang = new \WebFTP\Core\Language($language, $this->config);
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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);
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

        // Get FTP credentials from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        Logger::debug("getFolderTree API: Starting", ['ftpHost' => $ftpHost, 'ftpUsername' => $ftpUsername]);

        if (!$ftpHost || !$ftpUsername || !$ftpPassword) {
            Logger::debug("getFolderTree API: FTP credentials not found in session");
            $this->response->json(['success' => false, 'message' => 'FTP credentials not found']);
        }

        try {
            // Initialize FTP connection
            $security = new \WebFTP\Core\SecurityManager($this->config);
            $ftp = new \WebFTP\Models\FtpConnection($this->config, $security);

            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            Logger::debug("getFolderTree API: Connecting to FTP", [
                'host' => $ftpConfig['host'],
                'port' => $ftpConfig['port'],
                'use_ssl' => $ftpConfig['use_ssl']
            ]);

            $connectionResult = $ftp->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                Logger::debug("getFolderTree API: Connection failed", ['message' => $connectionResult['message']]);
                $this->response->json([
                    'success' => false,
                    'message' => $connectionResult['message']
                ]);
            }

            Logger::debug("getFolderTree API: Connected successfully");

            // Get path from request (default to root)
            $path = $this->request->get('path', '/');
            Logger::debug("getFolderTree API: Requested path", ['path' => $path]);

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                Logger::debug("getFolderTree API: Invalid path", ['path' => $path]);
                $this->response->json([
                    'success' => false,
                    'message' => 'Invalid path'
                ]);
            }

            Logger::debug("getFolderTree API: Sanitized path", ['sanitizedPath' => $sanitizedPath]);

            // Get folder tree
            $tree = $ftp->getFolderTree($sanitizedPath);

            Logger::debug("getFolderTree API: Got tree", ['folder_count' => count($tree)]);

            // Disconnect
            $ftp->disconnect();

            Logger::debug("getFolderTree API: Disconnected, returning response");

            $this->response->json([
                'success' => true,
                'tree' => $tree,
                'path' => $sanitizedPath
            ]);

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

        // Get FTP credentials from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        if (!$ftpHost || !$ftpUsername || !$ftpPassword) {
            $this->response->json(['success' => false, 'message' => 'FTP credentials not found']);
        }

        try {
            // Initialize FTP connection
            $security = new \WebFTP\Core\SecurityManager($this->config);
            $ftp = new \WebFTP\Models\FtpConnection($this->config, $security);

            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            $connectionResult = $ftp->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                $this->response->json([
                    'success' => false,
                    'message' => $connectionResult['message']
                ]);
            }

            // Get path from request (default to root)
            $path = $this->request->get('path', '/');

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json([
                    'success' => false,
                    'message' => 'Invalid path'
                ]);
            }

            // Get directory contents
            $contents = $ftp->getDirectoryContents($sanitizedPath);

            // Disconnect
            $ftp->disconnect();

            // Check if the folder exists (success flag from FTP model)
            if (!isset($contents['success']) || !$contents['success']) {
                Logger::debug("getFolderContents: Folder does not exist", [
                    'path' => $sanitizedPath,
                    'contents' => $contents
                ]);
                $this->response->json([
                    'success' => false,
                    'message' => 'Folder not found',
                    'path' => $sanitizedPath
                ]);
            }

            Logger::debug("getFolderContents: Success", [
                'path' => $sanitizedPath,
                'folders' => count($contents['folders']),
                'files' => count($contents['files'])
            ]);

            $this->response->json([
                'success' => true,
                'path' => $sanitizedPath,
                'folders' => $contents['folders'],
                'files' => $contents['files']
            ]);

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

        // Get FTP credentials from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        if (!$ftpHost || !$ftpUsername || !$ftpPassword) {
            $this->response->json(['success' => false, 'message' => 'FTP credentials not found']);
        }

        try {
            // Initialize FTP connection
            $security = new \WebFTP\Core\SecurityManager($this->config);
            $ftp = new \WebFTP\Models\FtpConnection($this->config, $security);

            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            $connectionResult = $ftp->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                $this->response->json([
                    'success' => false,
                    'message' => $connectionResult['message']
                ]);
            }

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
            }

            // Get file size first
            $fileSize = @ftp_size($ftp->getConnection(), $sanitizedPath);
            if ($fileSize === -1) {
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'File not found']);
            }

            // Check file size limit
            $maxSize = $this->config['file_editor']['max_file_size'];
            if ($fileSize > $maxSize) {
                $ftp->disconnect();
                $this->response->json([
                    'success' => false,
                    'message' => 'File too large (' . round($fileSize / 1024 / 1024, 2) . ' MB). Maximum: ' . round($maxSize / 1024 / 1024) . ' MB'
                ]);
            }

            // Create temporary file to store content
            $tempFile = tmpfile();
            if (!$tempFile) {
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'Could not create temporary file']);
            }

            $tempPath = stream_get_meta_data($tempFile)['uri'];

            // Download file from FTP to temp
            $downloadResult = @ftp_get($ftp->getConnection(), $tempPath, $sanitizedPath, FTP_BINARY);

            if (!$downloadResult) {
                fclose($tempFile);
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'Could not read file']);
            }

            // Read content from temp file
            $content = file_get_contents($tempPath);
            fclose($tempFile);

            // Disconnect
            $ftp->disconnect();

            // Get file extension and determine if it's editable
            $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));
            $isEditable = in_array($extension, $this->config['file_editor']['editable_extensions']);
            $isPreviewable = in_array($extension, $this->config['file_editor']['preview_extensions']);

            // For image files, base64 encode the content
            if ($isPreviewable) {
                $content = base64_encode($content);
            }

            // Return file data
            $this->response->json([
                'success' => true,
                'content' => $content,
                'size' => $fileSize,
                'extension' => $extension,
                'isEditable' => $isEditable,
                'isPreviewable' => $isPreviewable,
                'path' => $sanitizedPath
            ]);

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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        // Get file path and content from request
        $path = $this->request->post('path', '');

        // Get content directly from POST without sanitization (we need raw content for files)
        $content = $_POST['content'] ?? '';

        // Still validate length for security
        $maxSize = $this->config['file_editor']['max_file_size'] ?? 10485760; // 10MB default
        if (strlen($content) > $maxSize) {
            $this->response->json([
                'success' => false,
                'message' => 'Content too large. Maximum: ' . round($maxSize / 1024 / 1024) . ' MB'
            ]);
        }

        if (empty($path)) {
            $this->response->json(['success' => false, 'message' => 'File path required']);
        }

        // Get FTP credentials from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        if (!$ftpHost || !$ftpUsername || !$ftpPassword) {
            $this->response->json(['success' => false, 'message' => 'FTP credentials not found']);
        }

        try {
            // Initialize FTP connection
            $security = new \WebFTP\Core\SecurityManager($this->config);
            $ftp = new \WebFTP\Models\FtpConnection($this->config, $security);

            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            $connectionResult = $ftp->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                $this->response->json([
                    'success' => false,
                    'message' => $connectionResult['message']
                ]);
            }

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                $this->response->json(['success' => false, 'message' => 'Invalid path']);
            }


            // Check if file extension is editable
            $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['file_editor']['editable_extensions'])) {
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'File type not editable']);
            }

            // Create temporary file with new content
            $tempFile = tmpfile();
            if (!$tempFile) {
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'Could not create temporary file']);
            }

            $tempPath = stream_get_meta_data($tempFile)['uri'];

            // Write content to temp file exactly as received
            file_put_contents($tempPath, $content);

            // Upload temp file to FTP in binary mode (no transformation)
            $uploadResult = @ftp_put($ftp->getConnection(), $sanitizedPath, $tempPath, FTP_BINARY);

            fclose($tempFile);

            if (!$uploadResult) {
                $ftp->disconnect();
                $this->response->json(['success' => false, 'message' => 'Could not save file']);
            }

            // Disconnect
            $ftp->disconnect();

            // Return success
            $this->response->json([
                'success' => true,
                'message' => 'File saved successfully',
                'path' => $sanitizedPath
            ]);

        } catch (\Exception $e) {
            $this->response->json([
                'success' => false,
                'message' => 'Error saving file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get fresh CSRF token via AJAX
     *
     * Used when the editor needs a fresh token for saving
     * after the page has been open for a while
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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);
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

        // Get FTP credentials from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');
        $ftpPassword = $this->session->get('ftp_password');

        if (!$ftpHost || !$ftpUsername || !$ftpPassword) {
            http_response_code(401);
            echo 'FTP credentials not found';
            exit;
        }

        try {
            // Initialize FTP connection
            $security = new \WebFTP\Core\SecurityManager($this->config);
            $ftp = new \WebFTP\Models\FtpConnection($this->config, $security);

            // Connect to FTP server
            $ftpConfig = $this->config['ftp']['server'];
            $connectionResult = $ftp->connect(
                $ftpConfig['host'],
                $ftpConfig['port'],
                $ftpUsername,
                $ftpPassword,
                $ftpConfig['use_ssl'],
                $ftpConfig['passive_mode']
            );

            if (!$connectionResult['success']) {
                http_response_code(500);
                echo 'FTP connection failed';
                exit;
            }

            // Sanitize path
            $sanitizedPath = $security->sanitizePath($path);
            if ($sanitizedPath === null) {
                http_response_code(400);
                echo 'Invalid path';
                exit;
            }

            // Read file from FTP
            $fileContent = $ftp->readFile($sanitizedPath);

            // Disconnect
            $ftp->disconnect();

            if ($fileContent === false) {
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
            header('Content-Length: ' . strlen($fileContent));
            header('Cache-Control: public, max-age=3600');
            echo $fileContent;
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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);

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

        // Sanitize filename - remove any path traversal attempts
        $filename = basename($filename);
        if (empty($filename) || $filename === '.' || $filename === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid filename']);
            return;
        }

        // Build full path
        $fullPath = rtrim($path, '/') . '/' . $filename;

        try {
            // Get FTP connection details from session
            $ftpHost = $this->config['ftp']['server']['host'];
            $ftpPort = $this->config['ftp']['server']['port'];
            $ftpUser = $this->session->get('ftp_username');
            $ftpPass = $this->session->get('ftp_password');
            $useSsl = $this->config['ftp']['server']['use_ssl'];
            $timeout = $this->config['ftp']['timeout'];

            // Connect to FTP
            $conn = $useSsl ? @ftp_ssl_connect($ftpHost, $ftpPort, $timeout) : @ftp_connect($ftpHost, $ftpPort, $timeout);

            if (!$conn) {
                throw new \Exception('Could not connect to FTP server');
            }

            // Login
            if (!@ftp_login($conn, $ftpUser, $ftpPass)) {
                ftp_close($conn);
                throw new \Exception('FTP login failed');
            }

            // Set passive mode
            if ($this->config['ftp']['server']['passive_mode']) {
                ftp_pasv($conn, true);
            }

            // Check if file already exists
            $size = @ftp_size($conn, $fullPath);
            if ($size >= 0) {
                ftp_close($conn);
                $this->response->json(['success' => false, 'message' => 'File already exists']);
                return;
            }

            // Create empty file by uploading empty content
            $tempFile = tmpfile();
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            if (!@ftp_fput($conn, $fullPath, $tempFile, FTP_BINARY)) {
                fclose($tempFile);
                ftp_close($conn);
                throw new \Exception('Failed to create file on FTP server');
            }

            fclose($tempFile);
            ftp_close($conn);

            Logger::ftp('create_file', ['path' => $fullPath], true);

            $this->response->json([
                'success' => true,
                'message' => 'File created successfully',
                'path' => $fullPath
            ]);

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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);

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

        // Sanitize foldername - remove any path traversal attempts
        $foldername = basename($foldername);
        if (empty($foldername) || $foldername === '.' || $foldername === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid folder name']);
            return;
        }

        // Build full path
        $fullPath = rtrim($path, '/') . '/' . $foldername;

        try {
            // Get FTP connection details from session
            $ftpHost = $this->config['ftp']['server']['host'];
            $ftpPort = $this->config['ftp']['server']['port'];
            $ftpUser = $this->session->get('ftp_username');
            $ftpPass = $this->session->get('ftp_password');
            $useSsl = $this->config['ftp']['server']['use_ssl'];
            $timeout = $this->config['ftp']['timeout'];

            // Connect to FTP
            $conn = $useSsl ? @ftp_ssl_connect($ftpHost, $ftpPort, $timeout) : @ftp_connect($ftpHost, $ftpPort, $timeout);

            if (!$conn) {
                throw new \Exception('Could not connect to FTP server');
            }

            // Login
            if (!@ftp_login($conn, $ftpUser, $ftpPass)) {
                ftp_close($conn);
                throw new \Exception('FTP login failed');
            }

            // Set passive mode
            if ($this->config['ftp']['server']['passive_mode']) {
                ftp_pasv($conn, true);
            }

            // Create folder
            if (!@ftp_mkdir($conn, $fullPath)) {
                ftp_close($conn);
                throw new \Exception('Failed to create folder on FTP server (may already exist)');
            }

            ftp_close($conn);

            Logger::ftp('create_folder', ['path' => $fullPath], true);

            $this->response->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'path' => $fullPath
            ]);

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
        $csrf = new \WebFTP\Core\CsrfToken($this->config);

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

        // Sanitize new name - remove any path traversal attempts
        $newName = basename($newName);
        if (empty($newName) || $newName === '.' || $newName === '..') {
            $this->response->json(['success' => false, 'message' => 'Invalid name']);
            return;
        }

        // Build new path (same directory, different name)
        $parentPath = dirname($oldPath);
        $newPath = ($parentPath === '/' || $parentPath === '.')
            ? '/' . $newName
            : $parentPath . '/' . $newName;

        try {
            // Get FTP connection details from session
            $ftpHost = $this->config['ftp']['server']['host'];
            $ftpPort = $this->config['ftp']['server']['port'];
            $ftpUser = $this->session->get('ftp_username');
            $ftpPass = $this->session->get('ftp_password');
            $useSsl = $this->config['ftp']['server']['use_ssl'];
            $timeout = $this->config['ftp']['timeout'];

            // Connect to FTP
            $conn = $useSsl ? @ftp_ssl_connect($ftpHost, $ftpPort, $timeout) : @ftp_connect($ftpHost, $ftpPort, $timeout);

            if (!$conn) {
                throw new \Exception('Could not connect to FTP server');
            }

            // Login
            if (!@ftp_login($conn, $ftpUser, $ftpPass)) {
                ftp_close($conn);
                throw new \Exception('FTP login failed');
            }

            // Set passive mode
            if ($this->config['ftp']['server']['passive_mode']) {
                ftp_pasv($conn, true);
            }

            // Check if new name already exists
            $size = @ftp_size($conn, $newPath);
            $rawList = @ftp_rawlist($conn, $newPath);
            if ($size >= 0 || ($rawList !== false && count($rawList) > 0)) {
                ftp_close($conn);
                $this->response->json(['success' => false, 'message' => 'A file or folder with this name already exists']);
                return;
            }

            // Rename the file/folder
            if (!@ftp_rename($conn, $oldPath, $newPath)) {
                ftp_close($conn);
                throw new \Exception('Failed to rename - please check permissions');
            }

            ftp_close($conn);

            Logger::ftp('rename', ['from' => $oldPath, 'to' => $newPath], true);

            $this->response->json([
                'success' => true,
                'message' => 'Renamed successfully',
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'parent_path' => $parentPath === '.' ? '/' : $parentPath
            ]);

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
        if (!$this->session->validateCsrfToken($csrfToken)) {
            Logger::warning('CSRF validation failed for delete', [
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
            $path = $this->sanitizePath($path);

            // Get FTP connection from session
            $ftpConnection = $this->session->get('ftp_connection');
            if (!$ftpConnection || !is_resource($ftpConnection)) {
                $this->response->json(['success' => false, 'message' => 'FTP connection lost. Please login again.']);
                return;
            }

            // Check if path exists and determine if it's a file or directory
            $isDir = @ftp_nlist($ftpConnection, $path) !== false;

            if ($isDir) {
                // It's a directory - delete recursively
                $deleted = $this->deleteFtpDirectory($ftpConnection, $path);
            } else {
                // It's a file - delete directly
                $deleted = @ftp_delete($ftpConnection, $path);
            }

            if ($deleted) {
                Logger::info('Item deleted successfully', [
                    'user' => $this->session->get('ftp_username'),
                    'path' => $path,
                    'type' => $isDir ? 'directory' : 'file'
                ]);
                $this->response->json([
                    'success' => true,
                    'message' => $isDir ? 'Folder deleted successfully' : 'File deleted successfully'
                ]);
            } else {
                $this->response->json([
                    'success' => false,
                    'message' => 'Failed to delete item. It may not exist or you may not have permission.'
                ]);
            }

        } catch (\Exception $e) {
            Logger::error('Delete error', [
                'path' => $path ?? 'unknown',
                'message' => $e->getMessage()
            ]);
            $this->response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Recursively delete FTP directory
     */
    private function deleteFtpDirectory($ftpConnection, string $dir): bool
    {
        // Get directory contents
        $files = @ftp_nlist($ftpConnection, $dir);

        if ($files === false) {
            return false;
        }

        // Filter out . and ..
        $files = array_filter($files, function($file) use ($dir) {
            $basename = basename($file);
            return $basename !== '.' && $basename !== '..';
        });

        // Delete each item
        foreach ($files as $file) {
            // Check if it's a directory
            $isDir = @ftp_nlist($ftpConnection, $file) !== false;

            if ($isDir) {
                // Recursively delete subdirectory
                if (!$this->deleteFtpDirectory($ftpConnection, $file)) {
                    return false;
                }
            } else {
                // Delete file
                if (!@ftp_delete($ftpConnection, $file)) {
                    return false;
                }
            }
        }

        // Finally, remove the empty directory
        return @ftp_rmdir($ftpConnection, $dir);
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
            $path = $this->sanitizePath($path);

            // Get FTP connection from session
            $ftpConnection = $this->session->get('ftp_connection');
            if (!$ftpConnection || !is_resource($ftpConnection)) {
                http_response_code(500);
                echo 'FTP connection lost. Please login again.';
                return;
            }

            // Get file size
            $size = @ftp_size($ftpConnection, $path);
            if ($size === -1) {
                http_response_code(404);
                echo 'File not found';
                return;
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'webftp_');

            // Download file from FTP to temp location
            $downloaded = @ftp_get($ftpConnection, $tempFile, $path, FTP_BINARY);

            if (!$downloaded) {
                @unlink($tempFile);
                http_response_code(500);
                echo 'Failed to download file from FTP server';
                return;
            }

            // Get filename
            $filename = basename($path);

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
                'path' => $path,
                'size' => $size
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

}
