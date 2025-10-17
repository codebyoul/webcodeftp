<?php

declare(strict_types=1);

namespace WebFTP\Controllers;

use WebFTP\Core\Request;
use WebFTP\Core\Response;
use WebFTP\Models\Session;

/**
 * Dashboard Controller
 *
 * Handles the main FTP client interface for authenticated users.
 */
class DashboardController
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
        $defaultLanguage = $this->config['localization']['default_language'];
        $language = $this->session->get('language', $defaultLanguage);

        // Get FTP connection details from session
        $ftpHost = $this->session->get('ftp_host');
        $ftpUsername = $this->session->get('ftp_username');

        // Render file manager view
        $this->response->view('filemanager', [
            'app_name' => $this->config['app']['name'],
            'ftp_host' => $ftpHost,
            'ftp_username' => $ftpUsername,
            'theme' => $theme,
            'language' => $language,
            'language_cookie_name' => $this->config['localization']['language_cookie_name'],
            'language_cookie_lifetime' => $this->config['localization']['language_cookie_lifetime'],
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

        // Save language preference
        $this->session->set('language', $language);

        $this->response->json(['success' => true, 'language' => $language]);
    }

    /**
     * Get folder tree structure from FTP
     */
    public function getFolderTree(): void
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

            // Get folder tree
            $tree = $ftp->getFolderTree($sanitizedPath);

            // Disconnect
            $ftp->disconnect();

            $this->response->json([
                'success' => true,
                'tree' => $tree,
                'path' => $sanitizedPath
            ]);

        } catch (\Exception $e) {
            $this->response->json([
                'success' => false,
                'message' => 'Error retrieving folder tree'
            ]);
        }
    }
}
