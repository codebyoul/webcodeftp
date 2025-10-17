<?php

declare(strict_types=1);

namespace WebFTP\Controllers;

use WebFTP\Core\Request;
use WebFTP\Core\Response;
use WebFTP\Core\SecurityManager;
use WebFTP\Core\CsrfToken;
use WebFTP\Core\Language;
use WebFTP\Models\Session;
use WebFTP\Models\FtpConnection;

/**
 * Authentication Controller
 *
 * Handles user authentication via FTP credentials.
 */
class AuthController
{
    public function __construct(
        private array $config,
        private Request $request,
        private Response $response,
        private Session $session,
        private SecurityManager $security,
        private CsrfToken $csrf
    ) {}

    /**
     * Show login page
     */
    public function showLogin(): void
    {
        // Redirect if already authenticated
        if ($this->session->isAuthenticated()) {
            $this->response->redirect('/dashboard');
        }

        // Generate CSRF token
        $csrfToken = $this->csrf->getToken();

        // Get flash messages
        $error = $this->session->getFlash('error');
        $success = $this->session->getFlash('success');

        // Load language: priority order - cookie > session > default
        $cookieName = $this->config['localization']['language_cookie_name'];
        $defaultLanguage = $this->config['localization']['default_language'];
        $language = $_COOKIE[$cookieName] ?? $this->session->get('language', $defaultLanguage);

        // Load translations
        $lang = new Language($language, $this->config);

        // Render login view
        $this->response->view('login', [
            'csrf_token' => $csrfToken,
            'csrf_field_name' => $this->config['security']['csrf']['token_name'],
            'error' => $error,
            'success' => $success,
            'app_name' => $this->config['app']['name'],
            'language' => $language,
            'translations' => $lang->all(),
            'language_cookie_name' => $cookieName,
            'language_cookie_lifetime' => $this->config['localization']['language_cookie_lifetime'],
        ]);
    }

    /**
     * Process login attempt
     */
    public function login(): void
    {
        // Check if already authenticated
        if ($this->session->isAuthenticated()) {
            $this->response->redirect('/dashboard');
        }

        // Validate CSRF token
        $csrfToken = $this->request->post($this->config['security']['csrf']['token_name']);
        if (!$this->csrf->validate($csrfToken)) {
            $this->session->flash('error', 'Invalid security token. Please try again.');
            $this->response->redirect('/');
        }

        // Get client IP for rate limiting
        $clientIp = $this->security->getClientIp();

        // Check rate limiting
        $rateLimit = $this->security->checkRateLimit($clientIp);
        if (!$rateLimit['allowed']) {
            $minutesRemaining = ceil(($rateLimit['reset_time'] - time()) / 60);
            $this->session->flash('error', "Too many failed attempts. Please try again in {$minutesRemaining} minute(s).");
            $this->response->redirect('/');
        }

        // Get FTP server settings from config (trusted, secure)
        $ftpServer = $this->config['ftp']['server'];
        $host = $ftpServer['host'];
        $port = $ftpServer['port'];
        $useSsl = $ftpServer['use_ssl'];
        $passiveMode = $ftpServer['passive_mode'];

        // Get user credentials from form
        $username = $this->request->post('username', '', 255);
        $password = $this->request->post('password', '', 1000);

        // Validate required fields (username and password only)
        if (empty($username) || empty($password)) {
            $this->security->recordFailedAttempt($clientIp);
            $this->session->flash('error', 'Username and password are required.');
            $this->response->redirect('/');
        }

        // Attempt FTP connection to configured server
        $ftp = new FtpConnection($this->config, $this->security);
        $result = $ftp->connect($host, $port, $username, $password, $useSsl, $passiveMode);

        if (!$result['success']) {
            // Record failed attempt
            $this->security->recordFailedAttempt($clientIp);

            // Log failed attempt if logging enabled
            if ($this->config['logging']['log_auth_attempts']) {
                error_log("Failed FTP login attempt from {$clientIp} to {$host}:{$port} as {$username}");
            }

            $this->session->flash('error', $result['message']);
            $this->response->redirect('/');
        }

        // Connection successful - reset rate limit
        $this->security->resetRateLimit($clientIp);

        // Store FTP credentials in session (encrypted by PHP session handler)
        $this->session->set('ftp_host', $host);
        $this->session->set('ftp_port', $port);
        $this->session->set('ftp_username', $username);
        $this->session->set('ftp_password', $password);
        $this->session->set('ftp_use_ssl', $useSsl);

        // Mark as authenticated
        $this->session->authenticate();

        // Log successful authentication
        if ($this->config['logging']['log_auth_attempts']) {
            error_log("Successful FTP login from {$clientIp} to {$host}:{$port} as {$username}");
        }

        // Disconnect FTP (will reconnect when needed)
        $ftp->disconnect();

        // Redirect to dashboard
        $this->response->redirect('/dashboard');
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        // Log logout event
        if ($this->config['logging']['log_auth_attempts'] && $this->session->isAuthenticated()) {
            $clientIp = $this->security->getClientIp();
            $host = $this->session->get('ftp_host', 'unknown');
            error_log("User logged out from {$clientIp} (was connected to {$host})");
        }

        // Destroy session
        $this->session->unauthenticate();

        // Redirect to login with success message
        $this->session->flash('success', 'You have been logged out successfully.');
        $this->response->redirect('/');
    }
}
