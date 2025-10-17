<?php

declare(strict_types=1);

namespace WebFTP\Models;

use FTP\Connection;
use WebFTP\Core\SecurityManager;

/**
 * FTP Connection Manager
 *
 * Handles secure FTP connections with validation and error handling.
 * Supports both FTP and FTPS (FTP over SSL).
 */
class FtpConnection
{
    private ?Connection $connection = null;
    private bool $connected = false;

    public function __construct(
        private array $config,
        private SecurityManager $security
    ) {}

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
            return [
                'success' => false,
                'message' => 'Invalid or blocked FTP host'
            ];
        }

        // Validate port
        if (!$this->security->validatePort($port)) {
            return [
                'success' => false,
                'message' => 'Invalid port number'
            ];
        }

        // Validate credentials are not empty
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username and password are required'
            ];
        }

        try {
            // Attempt connection
            $timeout = $this->config['ftp']['timeout'];

            if ($useSsl && $this->config['ftp']['ssl_enabled']) {
                // FTPS connection
                $this->connection = @ftp_ssl_connect($host, $port, $timeout);
            } else {
                // Regular FTP connection
                $this->connection = @ftp_connect($host, $port, $timeout);
            }

            if (!$this->connection) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to FTP server'
                ];
            }

            // Attempt login
            $loginSuccess = @ftp_login($this->connection, $username, $password);

            if (!$loginSuccess) {
                $this->disconnect();
                return [
                    'success' => false,
                    'message' => 'Authentication failed - invalid username or password'
                ];
            }

            // Set passive mode
            @ftp_pasv($this->connection, $passiveMode);

            // Set timeout for operations
            @ftp_set_option($this->connection, FTP_TIMEOUT_SEC, $this->config['ftp']['operation_timeout']);

            $this->connected = true;

            return [
                'success' => true,
                'message' => 'Connected successfully'
            ];

        } catch (\Exception $e) {
            $this->disconnect();
            return [
                'success' => false,
                'message' => 'Connection error occurred'
            ];
        }
    }

    /**
     * Disconnect from FTP server
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            @ftp_close($this->connection);
            $this->connection = null;
            $this->connected = false;
        }
    }

    /**
     * Check if connected
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->connection !== null;
    }

    /**
     * Get current directory
     *
     * @return string|null Current directory path or null on error
     */
    public function getCurrentDirectory(): ?string
    {
        if (!$this->isConnected()) {
            return null;
        }

        $dir = @ftp_pwd($this->connection);
        return $dir !== false ? $dir : null;
    }

    /**
     * List files in directory
     *
     * @param string $directory Directory path
     * @return array|null Array of files or null on error
     */
    public function listFiles(string $directory = '.'): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }

        $files = @ftp_nlist($this->connection, $directory);
        return $files !== false ? $files : null;
    }

    /**
     * Get detailed file list
     *
     * @param string $directory Directory path
     * @return array|null Array of file details or null on error
     */
    public function listFilesDetailed(string $directory = '.'): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }

        $files = @ftp_rawlist($this->connection, $directory);
        return $files !== false ? $files : null;
    }

    /**
     * Change directory
     *
     * @param string $directory Target directory
     * @return bool Success status
     */
    public function changeDirectory(string $directory): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        // Validate path
        if ($this->security->sanitizePath($directory) === null) {
            return false;
        }

        return @ftp_chdir($this->connection, $directory);
    }

    /**
     * Check if path is a directory
     *
     * @param string $path Path to check
     * @return bool True if directory, false otherwise
     */
    public function isDirectory(string $path): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        // Try to change to the directory
        $currentDir = @ftp_pwd($this->connection);
        $result = @ftp_chdir($this->connection, $path);

        if ($result) {
            // Change back to original directory
            @ftp_chdir($this->connection, $currentDir);
            return true;
        }

        return false;
    }

    /**
     * Get recursive folder tree structure
     *
     * @param string $path Starting path (default: root)
     * @param int $maxDepth Maximum depth to scan (default: 10)
     * @param int $currentDepth Current depth (used internally)
     * @return array Tree structure with folders and files
     */
    public function getFolderTree(string $path = '/', int $maxDepth = 10, int $currentDepth = 0): array
    {
        if (!$this->isConnected() || $currentDepth >= $maxDepth) {
            return [];
        }

        // Get list of items in directory
        $items = @ftp_nlist($this->connection, $path);

        if ($items === false) {
            return [];
        }

        $tree = [];

        foreach ($items as $item) {
            // Skip current and parent directory references
            $basename = basename($item);
            if ($basename === '.' || $basename === '..') {
                continue;
            }

            // Get full path
            $fullPath = $path === '/' ? '/' . $basename : $path . '/' . $basename;

            // Check if it's a directory
            if ($this->isDirectory($fullPath)) {
                $tree[] = [
                    'name' => $basename,
                    'path' => $fullPath,
                    'type' => 'directory',
                    'children' => [] // Children loaded on demand
                ];
            }
        }

        // Sort folders alphabetically
        usort($tree, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $tree;
    }

    /**
     * Get files and folders in a directory (non-recursive)
     *
     * @param string $directory Directory path
     * @return array Array with 'folders' and 'files'
     */
    public function getDirectoryContents(string $directory = '/'): array
    {
        if (!$this->isConnected()) {
            return ['folders' => [], 'files' => []];
        }

        $items = @ftp_nlist($this->connection, $directory);

        if ($items === false) {
            return ['folders' => [], 'files' => []];
        }

        $folders = [];
        $files = [];

        foreach ($items as $item) {
            $basename = basename($item);

            // Skip current and parent directory references
            if ($basename === '.' || $basename === '..') {
                continue;
            }

            $fullPath = $directory === '/' ? '/' . $basename : $directory . '/' . $basename;

            if ($this->isDirectory($fullPath)) {
                $folders[] = [
                    'name' => $basename,
                    'path' => $fullPath,
                    'type' => 'directory'
                ];
            } else {
                $files[] = [
                    'name' => $basename,
                    'path' => $fullPath,
                    'type' => 'file'
                ];
            }
        }

        // Sort alphabetically
        usort($folders, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        usort($files, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return [
            'folders' => $folders,
            'files' => $files
        ];
    }

    /**
     * Get FTP connection resource (for advanced operations)
     *
     * @return Connection|null
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
