<?php

declare(strict_types=1);

namespace WebFTP\Services;

use FTP\Connection;
use WebFTP\Core\SecurityManager;
use WebFTP\Core\Logger;

/**
 * FTP Operations Service
 *
 * Handles ALL FTP file and directory operations.
 * This service provides a reusable interface for FTP operations
 * that can be used anywhere in the application.
 *
 * Operations include:
 * - Directory listing (tree and flat)
 * - File reading and writing
 * - File and folder creation
 * - Renaming and deletion
 * - Directory navigation
 */
class FtpOperationsService
{
    public function __construct(
        private FtpConnectionService $connectionService,
        private SecurityManager $security,
        private array $config
    ) {
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
        if (!$this->connectionService->isConnected() || $currentDepth >= $maxDepth) {
            Logger::ftp("getFolderTree", [
                'connected' => $this->connectionService->isConnected(),
                'currentDepth' => $currentDepth,
                'maxDepth' => $maxDepth,
                'reason' => 'Not connected or max depth reached'
            ], false);
            return [];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return [];
        }

        Logger::ftp("getFolderTree", ['path' => $path, 'depth' => $currentDepth]);

        // Save current directory
        $currentDir = @ftp_pwd($connection);

        // Change to the target directory first
        if (!@ftp_chdir($connection, $path)) {
            Logger::ftp("getFolderTree", ['action' => 'Failed to change to directory', 'path' => $path], false);
            return [];
        }

        // Use ftp_rawlist to get detailed directory listing
        $rawList = @ftp_rawlist($connection, ".");

        // Change back to original directory
        if ($currentDir) {
            @ftp_chdir($connection, $currentDir);
        }

        if ($rawList === false || empty($rawList)) {
            Logger::ftp("getFolderTree", ['action' => 'ftp_rawlist returned false or empty', 'path' => $path], false);
            return [];
        }

        Logger::ftp("getFolderTree", ['path' => $path, 'count' => count($rawList)]);

        $folders = [];
        $files = [];

        foreach ($rawList as $line) {
            // Parse the raw FTP list line (Unix format)
            $parts = preg_split('/\s+/', $line, 9);

            if (count($parts) < 9) {
                continue;
            }

            $permissions = $parts[0];
            $rawName = $parts[8];

            // Check if it's a symbolic link
            // Method 1: Permissions start with 'l' (lrwxrwxrwx)
            // Method 2: Name format is "link_name -> target_path"
            $isSymlink = false;
            $symlinkTarget = null;
            $name = $rawName;

            // Check permissions field (most reliable for symlinks)
            if (isset($permissions[0]) && $permissions[0] === 'l') {
                $isSymlink = true;
            }

            // Parse symlink target from name (if present)
            if (strpos($rawName, ' -> ') !== false) {
                $symlinkParts = explode(' -> ', $rawName, 2);
                $name = $symlinkParts[0];
                $symlinkTarget = $symlinkParts[1] ?? null;
                $isSymlink = true; // Redundant but explicit
            }

            // Parse date/time
            $month = $parts[5] ?? '';
            $day = $parts[6] ?? '';
            $timeOrYear = $parts[7] ?? '';
            $modified = $this->formatModifiedDate($month, $day, $timeOrYear);

            // Skip current and parent directory references
            if ($name === '.' || $name === '..') {
                continue;
            }

            // Normalize path
            $fullPath = rtrim($path, '/') . '/' . $name;

            // Check if it's a directory (first character is 'd')
            if ($permissions[0] === 'd') {
                $folderData = [
                    'name' => $name,
                    'path' => $fullPath,                    // Symlink path (for navigation)
                    'real_path' => $isSymlink && $symlinkTarget ? $symlinkTarget : $fullPath, // Real path for operations
                    'type' => 'directory',
                    'modified' => $modified,
                    'permissions' => $permissions,
                    'children' => [], // Children loaded on demand
                    'is_symlink' => $isSymlink
                ];

                if ($isSymlink && $symlinkTarget) {
                    $folderData['symlink_target'] = $symlinkTarget;
                }

                $folders[] = $folderData;
            } else {
                $fileData = [
                    'name' => $name,
                    'path' => $fullPath,                    // Symlink path (for navigation)
                    'real_path' => $isSymlink && $symlinkTarget ? $symlinkTarget : $fullPath, // Real path for operations
                    'type' => 'file',
                    'size' => isset($parts[4]) ? (int) $parts[4] : 0,
                    'modified' => $modified,
                    'permissions' => $permissions,
                    'is_symlink' => $isSymlink
                ];

                if ($isSymlink && $symlinkTarget) {
                    $fileData['symlink_target'] = $symlinkTarget;
                }

                $files[] = $fileData;
            }
        }

        // Sort folders and files alphabetically
        usort($folders, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Combine folders first, then files
        $tree = array_merge($folders, $files);

        Logger::ftp("getFolderTree", [
            'path' => $path,
            'folder_count' => count($folders),
            'file_count' => count($files),
            'total_count' => count($tree)
        ]);

        return $tree;
    }

    /**
     * Get files and folders in a directory (non-recursive)
     *
     * @param string $directory Directory path
     * @return array Array with 'success', 'folders' and 'files'
     */
    public function getDirectoryContents(string $directory = '/'): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("getDirectoryContents", ['reason' => 'Not connected'], false);
            return ['success' => false, 'folders' => [], 'files' => []];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'folders' => [], 'files' => []];
        }

        Logger::ftp("getDirectoryContents", ['directory' => $directory]);

        // Save current directory
        $currentDir = @ftp_pwd($connection);

        // Change to the target directory
        $chdirResult = @ftp_chdir($connection, $directory);

        if (!$chdirResult) {
            Logger::ftp("getDirectoryContents: Directory does not exist", ['directory' => $directory], false);
            return ['success' => false, 'folders' => [], 'files' => []];
        }

        // Use ftp_rawlist to get detailed directory listing
        $rawList = @ftp_rawlist($connection, ".");

        // Change back to original directory
        if ($currentDir) {
            @ftp_chdir($connection, $currentDir);
        }

        if ($rawList === false || empty($rawList)) {
            Logger::ftp("getDirectoryContents: Empty directory or read failed", ['directory' => $directory]);
            return ['success' => true, 'folders' => [], 'files' => []];
        }

        Logger::ftp("getDirectoryContents", ['directory' => $directory, 'count' => count($rawList)]);

        $folders = [];
        $files = [];

        foreach ($rawList as $line) {
            // Parse the raw FTP list line
            $parts = preg_split('/\s+/', $line, 9);

            if (count($parts) < 9) {
                continue;
            }

            $permissions = $parts[0];
            $rawName = $parts[8];

            // Check if it's a symbolic link
            // Method 1: Permissions start with 'l' (lrwxrwxrwx)
            // Method 2: Name format is "link_name -> target_path"
            $isSymlink = false;
            $symlinkTarget = null;
            $name = $rawName;

            // Check permissions field (most reliable for symlinks)
            if (isset($permissions[0]) && $permissions[0] === 'l') {
                $isSymlink = true;
            }

            // Parse symlink target from name (if present)
            if (strpos($rawName, ' -> ') !== false) {
                $symlinkParts = explode(' -> ', $rawName, 2);
                $name = $symlinkParts[0];
                $symlinkTarget = $symlinkParts[1] ?? null;
                $isSymlink = true; // Redundant but explicit
            }

            // Parse date/time
            $month = $parts[5] ?? '';
            $day = $parts[6] ?? '';
            $timeOrYear = $parts[7] ?? '';
            $modified = $this->formatModifiedDate($month, $day, $timeOrYear);

            // Skip current and parent directory references
            if ($name === '.' || $name === '..') {
                continue;
            }

            // Normalize path
            $fullPath = rtrim($directory, '/') . '/' . $name;

            // Check if it's a directory
            if ($permissions[0] === 'd') {
                $folderData = [
                    'name' => $name,
                    'path' => $fullPath,                    // Symlink path (for navigation)
                    'real_path' => $isSymlink && $symlinkTarget ? $symlinkTarget : $fullPath, // Real path for operations
                    'type' => 'directory',
                    'modified' => $modified,
                    'permissions' => $permissions,
                    'is_symlink' => $isSymlink
                ];

                if ($isSymlink && $symlinkTarget) {
                    $folderData['symlink_target'] = $symlinkTarget;
                }

                $folders[] = $folderData;
            } else {
                // Get file extension
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $fileData = [
                    'name' => $name,
                    'path' => $fullPath,                    // Symlink path (for navigation)
                    'real_path' => $isSymlink && $symlinkTarget ? $symlinkTarget : $fullPath, // Real path for operations
                    'type' => 'file',
                    'size' => (int) $parts[4],
                    'modified' => $modified,
                    'permissions' => $permissions,
                    'extension' => $extension,
                    'is_symlink' => $isSymlink
                ];

                if ($isSymlink && $symlinkTarget) {
                    $fileData['symlink_target'] = $symlinkTarget;
                }

                $files[] = $fileData;
            }
        }

        // Sort alphabetically
        usort($folders, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        Logger::ftp("getDirectoryContents", [
            'directory' => $directory,
            'folder_count' => count($folders),
            'file_count' => count($files)
        ]);

        return [
            'success' => true,
            'folders' => $folders,
            'files' => $files
        ];
    }

    /**
     * Read file content from FTP server
     *
     * @param string $filePath Path to the file on FTP server
     * @return string|false File content as string or false on failure
     */
    public function readFile(string $filePath): string|false
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("readFile", ['reason' => 'Not connected', 'path' => $filePath], false);
            return false;
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return false;
        }

        Logger::ftp("readFile", ['path' => $filePath]);

        // Create a temporary stream to store file content
        $tempStream = fopen('php://temp', 'r+');

        if ($tempStream === false) {
            Logger::ftp("readFile", ['reason' => 'Failed to create temp stream', 'path' => $filePath], false);
            return false;
        }

        // Download file to temporary stream in binary mode
        $result = @ftp_fget($connection, $tempStream, $filePath, FTP_BINARY);

        if (!$result) {
            fclose($tempStream);
            Logger::ftp("readFile", ['reason' => 'Failed to download file', 'path' => $filePath], false);
            return false;
        }

        // Read content from stream
        rewind($tempStream);
        $content = stream_get_contents($tempStream);
        fclose($tempStream);

        if ($content === false) {
            Logger::ftp("readFile", ['reason' => 'Failed to read stream content', 'path' => $filePath], false);
            return false;
        }

        Logger::ftp("readFile", ['path' => $filePath, 'size' => strlen($content)], true);

        return $content;
    }

    /**
     * Write/Upload file to FTP server
     *
     * @param string $remotePath Remote file path on FTP server
     * @param string $content File content to write
     * @return array ['success' => bool, 'message' => string]
     */
    public function writeFile(string $remotePath, string $content): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("writeFile", ['reason' => 'Not connected', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Not connected to FTP server'];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'message' => 'FTP connection not available'];
        }

        // Create temporary file with content
        $tempFile = tmpfile();
        if (!$tempFile) {
            Logger::ftp("writeFile", ['reason' => 'Failed to create temp file', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Could not create temporary file'];
        }

        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Write content to temp file
        file_put_contents($tempPath, $content);

        // Upload temp file to FTP in binary mode
        $uploadResult = @ftp_put($connection, $remotePath, $tempPath, FTP_BINARY);

        fclose($tempFile);

        if (!$uploadResult) {
            Logger::ftp("writeFile", ['reason' => 'Upload failed', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Could not save file'];
        }

        Logger::ftp("writeFile", ['path' => $remotePath, 'size' => strlen($content)], true);

        return ['success' => true, 'message' => 'File saved successfully'];
    }

    /**
     * Create new empty file on FTP server
     *
     * @param string $remotePath Remote file path
     * @return array ['success' => bool, 'message' => string]
     */
    public function createFile(string $remotePath): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("createFile", ['reason' => 'Not connected', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Not connected to FTP server'];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'message' => 'FTP connection not available'];
        }

        // Check if file already exists
        $size = @ftp_size($connection, $remotePath);
        if ($size >= 0) {
            Logger::ftp("createFile", ['reason' => 'File already exists', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'File already exists'];
        }

        // Create empty file by uploading empty content
        $tempFile = tmpfile();
        if (!$tempFile) {
            return ['success' => false, 'message' => 'Could not create temporary file'];
        }

        $uploadResult = @ftp_fput($connection, $remotePath, $tempFile, FTP_BINARY);

        fclose($tempFile);

        if (!$uploadResult) {
            Logger::ftp("createFile", ['path' => $remotePath], false);
            return ['success' => false, 'message' => 'Failed to create file on FTP server'];
        }

        Logger::ftp("createFile", ['path' => $remotePath], true);

        return ['success' => true, 'message' => 'File created successfully'];
    }

    /**
     * Create new folder on FTP server
     *
     * @param string $remotePath Remote folder path
     * @return array ['success' => bool, 'message' => string]
     */
    public function createFolder(string $remotePath): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("createFolder", ['reason' => 'Not connected', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Not connected to FTP server'];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'message' => 'FTP connection not available'];
        }

        // Create folder
        $result = @ftp_mkdir($connection, $remotePath);

        if (!$result) {
            Logger::ftp("createFolder", ['path' => $remotePath], false);
            return ['success' => false, 'message' => 'Failed to create folder (may already exist)'];
        }

        Logger::ftp("createFolder", ['path' => $remotePath], true);

        return ['success' => true, 'message' => 'Folder created successfully'];
    }

    /**
     * Rename file or folder on FTP server
     *
     * @param string $oldPath Current path
     * @param string $newPath New path
     * @return array ['success' => bool, 'message' => string]
     */
    public function rename(string $oldPath, string $newPath): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("rename", ['reason' => 'Not connected', 'from' => $oldPath, 'to' => $newPath], false);
            return ['success' => false, 'message' => 'Not connected to FTP server'];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'message' => 'FTP connection not available'];
        }

        // Check if new name already exists
        $size = @ftp_size($connection, $newPath);
        $rawList = @ftp_rawlist($connection, $newPath);
        if ($size >= 0 || ($rawList !== false && count($rawList) > 0)) {
            Logger::ftp("rename", ['reason' => 'Target already exists', 'from' => $oldPath, 'to' => $newPath], false);
            return ['success' => false, 'message' => 'A file or folder with this name already exists'];
        }

        // Rename the file/folder
        $result = @ftp_rename($connection, $oldPath, $newPath);

        if (!$result) {
            Logger::ftp("rename", ['from' => $oldPath, 'to' => $newPath], false);
            return ['success' => false, 'message' => 'Failed to rename - please check permissions'];
        }

        Logger::ftp("rename", ['from' => $oldPath, 'to' => $newPath], true);

        return ['success' => true, 'message' => 'Renamed successfully'];
    }

    /**
     * Delete files and/or folders (supports single or batch delete)
     *
     * @param string|array $paths Single path string or array of paths
     * @return array ['success' => bool, 'results' => array, 'successCount' => int, 'failedCount' => int, 'message' => string]
     */
    public function delete(string|array $paths): array
    {
        // Convert single path to array for uniform processing
        if (is_string($paths)) {
            $paths = [$paths];
        }

        if (!$this->connectionService->isConnected()) {
            Logger::ftp("delete", ['reason' => 'Not connected', 'count' => count($paths)], false);
            return [
                'success' => false,
                'message' => 'Not connected to FTP server',
                'results' => [],
                'successCount' => 0,
                'failedCount' => count($paths)
            ];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return [
                'success' => false,
                'message' => 'FTP connection not available',
                'results' => [],
                'successCount' => 0,
                'failedCount' => count($paths)
            ];
        }

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($paths as $path) {
            // Check if path is a directory
            $isDir = $this->isDirectory($path);

            if ($isDir) {
                // Delete directory recursively
                $result = $this->deleteFolderRecursive($connection, $path);
            } else {
                // Delete file
                $result = @ftp_delete($connection, $path);
            }

            if ($result) {
                $successCount++;
                $results[] = [
                    'path' => $path,
                    'success' => true,
                    'type' => $isDir ? 'directory' : 'file'
                ];
                Logger::ftp("delete:item", ['path' => $path, 'type' => $isDir ? 'directory' : 'file'], true);
            } else {
                $failedCount++;
                $results[] = [
                    'path' => $path,
                    'success' => false,
                    'message' => 'Failed to delete ' . ($isDir ? 'directory' : 'file')
                ];
                Logger::ftp("delete:item", ['path' => $path, 'type' => $isDir ? 'directory' : 'file'], false);
            }
        }

        Logger::ftp("delete", [
            'total' => count($paths),
            'success' => $successCount,
            'failed' => $failedCount
        ], $failedCount === 0);

        return [
            'success' => $failedCount === 0,
            'results' => $results,
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'message' => $failedCount === 0
                ? "Successfully deleted {$successCount} item(s)"
                : "Deleted {$successCount} item(s), failed to delete {$failedCount} item(s)"
        ];
    }

    /**
     * Download file from FTP to local temp file
     *
     * @param string $remotePath Remote file path
     * @param string $localPath Local file path to save
     * @return array ['success' => bool, 'message' => string, 'size' => int]
     */
    public function downloadFile(string $remotePath, string $localPath): array
    {
        if (!$this->connectionService->isConnected()) {
            Logger::ftp("downloadFile", ['reason' => 'Not connected', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'Not connected to FTP server', 'size' => 0];
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return ['success' => false, 'message' => 'FTP connection not available', 'size' => 0];
        }

        // Get file size
        $size = @ftp_size($connection, $remotePath);
        if ($size === -1) {
            Logger::ftp("downloadFile", ['reason' => 'File not found', 'path' => $remotePath], false);
            return ['success' => false, 'message' => 'File not found', 'size' => 0];
        }

        // Download file
        $result = @ftp_get($connection, $localPath, $remotePath, FTP_BINARY);

        if (!$result) {
            Logger::ftp("downloadFile", ['path' => $remotePath], false);
            return ['success' => false, 'message' => 'Failed to download file', 'size' => 0];
        }

        Logger::ftp("downloadFile", ['path' => $remotePath, 'size' => $size], true);

        return ['success' => true, 'message' => 'File downloaded successfully', 'size' => $size];
    }

    /**
     * Get file size from FTP server
     *
     * @param string $remotePath Remote file path
     * @return int File size in bytes or -1 on error
     */
    public function getFileSize(string $remotePath): int
    {
        if (!$this->connectionService->isConnected()) {
            return -1;
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return -1;
        }

        return @ftp_size($connection, $remotePath);
    }

    /**
     * Check if path is a directory
     *
     * @param string $path Path to check
     * @return bool True if directory, false otherwise
     */
    public function isDirectory(string $path): bool
    {
        if (!$this->connectionService->isConnected()) {
            return false;
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return false;
        }

        // Try to change to the directory
        $currentDir = @ftp_pwd($connection);
        $result = @ftp_chdir($connection, $path);

        if ($result) {
            // Change back to original directory
            @ftp_chdir($connection, $currentDir);
            return true;
        }

        return false;
    }

    /**
     * Change current directory on FTP server
     *
     * @param string $directory Target directory
     * @return bool Success status
     */
    public function changeDirectory(string $directory): bool
    {
        if (!$this->connectionService->isConnected()) {
            return false;
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return false;
        }

        // Validate path
        if ($this->security->sanitizePath($directory) === null) {
            return false;
        }

        return @ftp_chdir($connection, $directory);
    }

    /**
     * Get current directory on FTP server
     *
     * @return string|null Current directory path or null on error
     */
    public function getCurrentDirectory(): ?string
    {
        if (!$this->connectionService->isConnected()) {
            return null;
        }

        $connection = $this->connectionService->getConnection();
        if (!$connection) {
            return null;
        }

        $dir = @ftp_pwd($connection);
        return $dir !== false ? $dir : null;
    }

    /**
     * Recursively delete FTP directory and all contents
     *
     * @param Connection $connection FTP connection
     * @param string $dir Directory path
     * @return bool Success status
     */
    private function deleteFolderRecursive(Connection $connection, string $dir): bool
    {
        // Get directory contents
        $files = @ftp_nlist($connection, $dir);

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
            $isDir = @ftp_nlist($connection, $file) !== false;

            if ($isDir) {
                // Recursively delete subdirectory
                if (!$this->deleteFolderRecursive($connection, $file)) {
                    return false;
                }
            } else {
                // Delete file
                if (!@ftp_delete($connection, $file)) {
                    return false;
                }
            }
        }

        // Finally, remove the empty directory
        return @ftp_rmdir($connection, $dir);
    }

    /**
     * Format modified date from FTP rawlist format
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

        // Month mapping
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
            // It's a time, assume current year
            return "{$dayNum}/{$monthNum}/{$currentYear} {$timeOrYear}";
        } else {
            // It's a year (old file)
            return "{$dayNum}/{$monthNum}/{$timeOrYear}";
        }
    }
}
