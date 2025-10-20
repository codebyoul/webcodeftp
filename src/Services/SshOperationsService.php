<?php

declare(strict_types=1);

namespace WebCodeFTP\Services;

use WebCodeFTP\Core\SecurityManager;

/**
 * SSH Operations Service
 *
 * Handles SSH-based file operations: zip, unzip, delete, move.
 * Will use phpseclib library for SSH command execution.
 *
 * IMPORTANT: This is a skeleton - implementation pending.
 */
class SshOperationsService
{
    public function __construct(
        private SshConnectionService $sshConnection,
        private SecurityManager $security,
        private array $config
    ) {}

    /**
     * Compress files/folders into a zip archive
     *
     * @param string $sourcePath Path to file or folder to compress
     * @param string $archivePath Path where the archive will be created
     * @return array{success: bool, message: string}
     *
     * TODO: Implement using SSH command execution (zip, tar, etc.)
     */
    public function zipFile(string $sourcePath, string $archivePath): array
    {
        // TODO: Implement zip operation via SSH
        // Example commands:
        // - zip: zip -r archive.zip folder/
        // - tar: tar -czf archive.tar.gz folder/
        return [
            'success' => false,
            'message' => 'SSH zip operation not implemented yet'
        ];
    }

    /**
     * Extract/decompress archive file
     *
     * @param string $archivePath Path to archive file
     * @param string $destinationPath Directory where files will be extracted
     * @return array{success: bool, message: string}
     *
     * TODO: Implement using SSH command execution
     */
    public function unzipFile(string $archivePath, string $destinationPath): array
    {
        // TODO: Implement unzip operation via SSH
        // Support multiple formats: zip, tar.gz, tar.bz2, 7z, rar, etc.
        // Example commands:
        // - unzip: unzip archive.zip -d destination/
        // - tar: tar -xzf archive.tar.gz -C destination/
        return [
            'success' => false,
            'message' => 'SSH unzip operation not implemented yet'
        ];
    }

    /**
     * Delete file or folder recursively
     *
     * @param string $path Path to file or folder to delete
     * @return array{success: bool, message: string}
     *
     * TODO: Implement using SSH command execution
     */
    public function delete(string $path): array
    {
        // TODO: Implement delete operation via SSH
        // Example commands:
        // - rm file.txt
        // - rm -rf folder/
        return [
            'success' => false,
            'message' => 'SSH delete operation not implemented yet'
        ];
    }

    /**
     * Move/rename file or folder
     *
     * @param string $sourcePath Current path
     * @param string $destinationPath New path
     * @return array{success: bool, message: string}
     *
     * TODO: Implement using SSH command execution
     */
    public function move(string $sourcePath, string $destinationPath): array
    {
        // TODO: Implement move operation via SSH
        // Example command:
        // - mv source destination
        return [
            'success' => false,
            'message' => 'SSH move operation not implemented yet'
        ];
    }
}
