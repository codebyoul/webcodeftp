<?php

declare(strict_types=1);

namespace WebFTP\Services;

use WebFTP\Core\SecurityManager;

/**
 * SSH Connection Service
 *
 * Handles SSH connection and disconnection ONLY.
 * Will use phpseclib library for SSH operations.
 *
 * IMPORTANT: This is a skeleton - implementation pending.
 */
class SshConnectionService
{
    private $connection = null;

    public function __construct(
        private array $config,
        private SecurityManager $security
    ) {}

    /**
     * Connect to SSH server
     *
     * @param string $host SSH server hostname/IP
     * @param int $port SSH server port
     * @param string $username SSH username
     * @param string $password SSH password
     * @return array{success: bool, message: string}
     *
     * TODO: Implement using phpseclib
     */
    public function connect(
        string $host,
        int $port,
        string $username,
        string $password
    ): array {
        // TODO: Implement SSH connection using phpseclib
        return [
            'success' => false,
            'message' => 'SSH connection not implemented yet (pending phpseclib integration)'
        ];
    }

    /**
     * Disconnect from SSH server
     *
     * @return void
     *
     * TODO: Implement using phpseclib
     */
    public function disconnect(): void
    {
        // TODO: Implement SSH disconnection
    }

    /**
     * Check if SSH connection is active
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        // TODO: Implement connection check
        return false;
    }

    /**
     * Get SSH connection resource
     *
     * @return mixed SSH connection object (phpseclib)
     */
    public function getConnection(): mixed
    {
        return $this->connection;
    }
}
