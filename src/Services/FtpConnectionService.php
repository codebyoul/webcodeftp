<?php

declare(strict_types=1);

namespace WebFTP\Services;

use FTP\Connection;
use WebFTP\Core\SecurityManager;
use WebFTP\Core\Logger;

/**
 * FTP Connection Service
 *
 * Handles ONLY FTP connection and disconnection logic.
 * Isolated service for establishing and closing FTP connections.
 *
 * Supports both FTP and FTPS (FTP over SSL/TLS).
 */
class FtpConnectionService
{
    private ?Connection $connection = null;
    private bool $connected = false;

    public function __construct(
        private array $config,
        private SecurityManager $security
    ) {
    }

    /**
     * Connect to FTP server
     *
     * @param string $host FTP server hostname
     * @param int $port FTP server port
     * @param string $username FTP username
     * @param string $password FTP password
     * @param bool $useSsl Use FTPS (FTP over SSL)
     * @param bool $passiveMode Use passive mode
     * @return array ['success' => bool, 'message' => string]
     */
    public function connect(
        string $host,
        int $port,
        string $username,
        string $password,
        bool $useSsl = false,
        bool $passiveMode = true
    ): array {
        // Validate host
        if (!$this->security->validateHost($host)) {
            Logger::error('FTP connection failed: Invalid host', ['host' => $host]);
            return [
                'success' => false,
                'message' => 'Invalid or blocked FTP host'
            ];
        }

        // Validate port
        if (!$this->security->validatePort($port)) {
            Logger::error('FTP connection failed: Invalid port', ['port' => $port]);
            return [
                'success' => false,
                'message' => 'Invalid port number'
            ];
        }

        // Validate credentials are not empty
        if (empty($username) || empty($password)) {
            Logger::error('FTP connection failed: Empty credentials');
            return [
                'success' => false,
                'message' => 'Username and password are required'
            ];
        }

        try {
            // Attempt connection
            $timeout = $this->config['ftp']['timeout'];

            if ($useSsl) {
                // FTPS connection
                $this->connection = @ftp_ssl_connect($host, $port, $timeout);
                Logger::info('Attempting FTPS connection', [
                    'host' => $host,
                    'port' => $port,
                    'timeout' => $timeout
                ]);
            } else {
                // Regular FTP connection
                $this->connection = @ftp_connect($host, $port, $timeout);
                Logger::info('Attempting FTP connection', [
                    'host' => $host,
                    'port' => $port,
                    'timeout' => $timeout
                ]);
            }

            if (!$this->connection) {
                Logger::error('FTP connection failed: Could not establish connection', [
                    'host' => $host,
                    'port' => $port,
                    'ssl' => $useSsl
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to connect to FTP server'
                ];
            }

            // Attempt login
            $loginSuccess = @ftp_login($this->connection, $username, $password);

            if (!$loginSuccess) {
                Logger::warning('FTP authentication failed', [
                    'host' => $host,
                    'username' => $username
                ]);
                $this->disconnect();
                return [
                    'success' => false,
                    'message' => 'Authentication failed - invalid username or password'
                ];
            }

            // Set passive mode
            @ftp_pasv($this->connection, $passiveMode);
            Logger::debug('FTP passive mode set', ['passive' => $passiveMode]);

            // Set timeout for operations
            $operationTimeout = $this->config['ftp']['operation_timeout'];
            @ftp_set_option($this->connection, FTP_TIMEOUT_SEC, $operationTimeout);
            Logger::debug('FTP operation timeout set', ['timeout' => $operationTimeout]);

            $this->connected = true;

            Logger::info('FTP connection established successfully', [
                'host' => $host,
                'username' => $username,
                'ssl' => $useSsl,
                'passive' => $passiveMode
            ]);

            return [
                'success' => true,
                'message' => 'Connected successfully'
            ];

        } catch (\Exception $e) {
            Logger::error('FTP connection exception', [
                'host' => $host,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->disconnect();
            return [
                'success' => false,
                'message' => 'Connection error occurred'
            ];
        }
    }

    /**
     * Disconnect from FTP server
     *
     * Safely closes the FTP connection and resets connection state.
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            @ftp_close($this->connection);
            Logger::debug('FTP connection closed');
            $this->connection = null;
            $this->connected = false;
        }
    }

    /**
     * Check if connected
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->connection !== null;
    }

    /**
     * Get FTP connection resource
     *
     * WARNING: Only use this for low-level FTP operations.
     * Prefer using FtpOperationsService for common operations.
     *
     * @return Connection|null FTP connection resource or null if not connected
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * Destructor - ensure connection is closed when object is destroyed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
