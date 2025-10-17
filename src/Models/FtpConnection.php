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
     * Get recursive folder tree structure (includes both folders and files)
     *
     * @param string $path Starting path (default: root)
     * @param int $maxDepth Maximum depth to scan (default: 10)
     * @param int $currentDepth Current depth (used internally)
     * @return array Tree structure with folders and files
     */
    public function getFolderTree(string $path = '/', int $maxDepth = 10, int $currentDepth = 0): array
    {
        if (!$this->isConnected() || $currentDepth >= $maxDepth) {
            $this->logDebug("getFolderTree: Not connected or max depth reached", [
                'connected' => $this->isConnected(),
                'currentDepth' => $currentDepth,
                'maxDepth' => $maxDepth
            ]);
            return [];
        }

        $this->logDebug("getFolderTree: Fetching tree for path", ['path' => $path, 'depth' => $currentDepth]);

        // Save current directory
        $currentDir = @ftp_pwd($this->connection);
        $this->logDebug("getFolderTree: Current directory", ['currentDir' => $currentDir]);

        // Change to the target directory first
        // This is necessary because ftp_rawlist() doesn't support folders with spaces
        if (!@ftp_chdir($this->connection, $path)) {
            $this->logDebug("getFolderTree: Failed to change to directory", ['path' => $path]);
            return [];
        }

        $this->logDebug("getFolderTree: Successfully changed to directory", ['path' => $path]);

        // Use ftp_rawlist to get detailed directory listing of current directory
        $rawList = @ftp_rawlist($this->connection, ".");

        // Change back to original directory
        if ($currentDir) {
            @ftp_chdir($this->connection, $currentDir);
        }

        if ($rawList === false || empty($rawList)) {
            $this->logDebug("getFolderTree: ftp_rawlist returned false or empty", ['path' => $path]);
            return [];
        }

        $this->logDebug("getFolderTree: Got raw list", ['path' => $path, 'count' => count($rawList)]);

        $folders = [];
        $files = [];

        foreach ($rawList as $line) {
            $this->logDebug("getFolderTree: Processing line", ['line' => $line]);

            // Parse the raw FTP list line (Unix format)
            // Example: "drwxr-xr-x 2 user group 4096 Jan 01 12:00 foldername"
            // Example: "-rw-r--r-- 1 user group 1234 Jan 01 12:00 filename.txt"
            $parts = preg_split('/\s+/', $line, 9);

            if (count($parts) < 9) {
                $this->logDebug("getFolderTree: Line has less than 9 parts", ['parts_count' => count($parts)]);
                continue;
            }

            $permissions = $parts[0];
            $name = $parts[8];

            // Parse date/time (parts 5, 6, 7)
            // Format: "Jan 01 12:00" or "Jan 01 2024"
            $month = $parts[5] ?? '';
            $day = $parts[6] ?? '';
            $timeOrYear = $parts[7] ?? '';

            // Format the date
            $modified = $this->formatModifiedDate($month, $day, $timeOrYear);

            $this->logDebug("getFolderTree: Parsed item", ['permissions' => $permissions, 'name' => $name, 'modified' => $modified]);

            // Skip current and parent directory references
            if ($name === '.' || $name === '..') {
                continue;
            }

            // Normalize path
            $fullPath = rtrim($path, '/') . '/' . $name;

            // Check if it's a directory (first character is 'd')
            if ($permissions[0] === 'd') {
                $this->logDebug("getFolderTree: Found directory", ['name' => $name, 'fullPath' => $fullPath]);

                $folders[] = [
                    'name' => $name,
                    'path' => $fullPath,
                    'type' => 'directory',
                    'modified' => $modified,
                    'permissions' => $permissions,
                    'children' => [] // Children loaded on demand
                ];
            } else {
                // It's a file
                $this->logDebug("getFolderTree: Found file", ['name' => $name, 'fullPath' => $fullPath]);

                $files[] = [
                    'name' => $name,
                    'path' => $fullPath,
                    'type' => 'file',
                    'size' => isset($parts[4]) ? (int)$parts[4] : 0,
                    'modified' => $modified,
                    'permissions' => $permissions
                ];
            }
        }

        // Sort folders and files alphabetically
        usort($folders, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        usort($files, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Combine folders first, then files
        $tree = array_merge($folders, $files);

        $this->logDebug("getFolderTree: Returning tree", [
            'path' => $path,
            'folder_count' => count($folders),
            'file_count' => count($files),
            'total_count' => count($tree)
        ]);

        return $tree;
    }

    /**
     * Log debug information
     */
    private function logDebug(string $message, array $context = []): void
    {
        if ($this->config['logging']['enabled']) {
            $logPath = $this->config['logging']['log_path'];
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
            $logMessage = "[{$timestamp}] [FTP_DEBUG] {$message}{$contextStr}\n";
            @error_log($logMessage, 3, $logPath);
        }
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
            $this->logDebug("getDirectoryContents: Not connected");
            return ['folders' => [], 'files' => []];
        }

        $this->logDebug("getDirectoryContents: Fetching contents for directory", ['directory' => $directory]);

        // Save current directory
        $currentDir = @ftp_pwd($this->connection);

        // Change to the target directory first
        if (!@ftp_chdir($this->connection, $directory)) {
            $this->logDebug("getDirectoryContents: Failed to change to directory", ['directory' => $directory]);
            return ['folders' => [], 'files' => []];
        }

        // Use ftp_rawlist to get detailed directory listing
        $rawList = @ftp_rawlist($this->connection, ".");

        // Change back to original directory
        if ($currentDir) {
            @ftp_chdir($this->connection, $currentDir);
        }

        if ($rawList === false || empty($rawList)) {
            $this->logDebug("getDirectoryContents: ftp_rawlist returned false or empty", ['directory' => $directory]);
            return ['folders' => [], 'files' => []];
        }

        $this->logDebug("getDirectoryContents: Got raw list", ['directory' => $directory, 'count' => count($rawList)]);

        $folders = [];
        $files = [];

        foreach ($rawList as $line) {
            // Parse the raw FTP list line (Unix format)
            // Example: "drwxr-xr-x 2 user group 4096 Jan 01 12:00 foldername"
            // Example: "-rw-r--r-- 1 user group 1234 Jan 01 12:00 filename.txt"
            $parts = preg_split('/\s+/', $line, 9);

            if (count($parts) < 9) {
                continue;
            }

            $permissions = $parts[0];
            $name = $parts[8];

            // Parse date/time (parts 5, 6, 7)
            $month = $parts[5] ?? '';
            $day = $parts[6] ?? '';
            $timeOrYear = $parts[7] ?? '';

            // Format the date
            $modified = $this->formatModifiedDate($month, $day, $timeOrYear);

            // Skip current and parent directory references
            if ($name === '.' || $name === '..') {
                continue;
            }

            // Normalize path
            $fullPath = rtrim($directory, '/') . '/' . $name;

            // Check if it's a directory (first character is 'd')
            if ($permissions[0] === 'd') {
                $folders[] = [
                    'name' => $name,
                    'path' => $fullPath,
                    'type' => 'directory',
                    'modified' => $modified,
                    'permissions' => $permissions
                ];
            } else {
                $files[] = [
                    'name' => $name,
                    'path' => $fullPath,
                    'type' => 'file',
                    'size' => (int)$parts[4],
                    'modified' => $modified,
                    'permissions' => $permissions
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

        $this->logDebug("getDirectoryContents: Returning contents", [
            'directory' => $directory,
            'folder_count' => count($folders),
            'file_count' => count($files)
        ]);

        return [
            'folders' => $folders,
            'files' => $files
        ];
    }

    /**
     * Format modified date from FTP rawlist format
     * Simple format: DD/MM/YYYY HH:MM
     *
     * @param string $month Month name (e.g., "Jan", "Feb")
     * @param string $day Day of month (e.g., "01", "15")
     * @param string $timeOrYear Time (e.g., "12:30") or Year (e.g., "2024")
     * @return string Formatted date string (DD/MM/YYYY HH:MM or DD/MM/YYYY)
     */
    private function formatModifiedDate(string $month, string $day, string $timeOrYear): string
    {
        if (empty($month) || empty($day) || empty($timeOrYear)) {
            return '-';
        }

        // Month mapping (English FTP format)
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];

        $monthNum = $months[$month] ?? '01';
        $currentYear = date('Y');
        $dayNum = str_pad($day, 2, '0', STR_PAD_LEFT);

        // Check if it's a time (contains :) or a year
        if (strpos($timeOrYear, ':') !== false) {
            // It's a time, assume current year - format: DD/MM/YYYY HH:MM
            return "{$dayNum}/{$monthNum}/{$currentYear} {$timeOrYear}";
        } else {
            // It's a year (old file) - format: DD/MM/YYYY
            return "{$dayNum}/{$monthNum}/{$timeOrYear}";
        }
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
