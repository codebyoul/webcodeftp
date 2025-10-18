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
        $csrf = new \WebFTP\Core\CsrfToken($this->session);

        if (!$csrf->validate($csrfToken)) {
            $this->response->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        // Get file path and content from request
        $path = $this->request->post('path', '');
        $content = $this->request->post('content', '');

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

            // Check file size limit
            $contentSize = strlen($content);
            $maxSize = $this->config['file_editor']['max_file_size'];
            if ($contentSize > $maxSize) {
                $ftp->disconnect();
                $this->response->json([
                    'success' => false,
                    'message' => 'Content too large (' . round($contentSize / 1024 / 1024, 2) . ' MB). Maximum: ' . round($maxSize / 1024 / 1024) . ' MB'
                ]);
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

            // Write content to temp file
            file_put_contents($tempPath, $content);

            // Upload temp file to FTP
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

}
