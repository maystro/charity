<?php

namespace App\Services\Deployment;

/**
 * Minimal FTP abstraction so uploads can be tested without a live server.
 */
interface FtpClientContract
{
    /**
     * Connect, authenticate and enter passive mode.
     *
     * @throws \RuntimeException when connection or login fails.
     */
    public function connect(): void;

    /**
     * Ensure a remote directory exists, creating parents as needed.
     *
     * @throws \RuntimeException when a directory cannot be created.
     */
    public function ensureDirectory(string $remoteDir): void;

    /**
     * Upload a local file to a remote path (binary mode).
     *
     * @throws \RuntimeException when the transfer fails.
     */
    public function upload(string $localPath, string $remotePath): void;

    /**
     * Delete a remote file (best-effort; missing files are ignored).
     */
    public function delete(string $remotePath): void;

    /**
     * List a remote directory.
     *
     * @return array<int, array{name: string, type: 'file'|'dir'}>
     */
    public function listDirectory(string $remoteDir): array;

    /**
     * Recursively walk a remote directory and return every file below it.
     *
     * @param  string  $remoteDir  directory relative to the configured root ('' = root)
     * @return array<int, array{path: string, type: 'file'|'dir', size: ?int}>
     */
    public function tree(string $remoteDir = ''): array;

    /**
     * Download a remote file into a local path (binary mode).
     *
     * @throws \RuntimeException when the transfer fails.
     */
    public function download(string $remotePath, string $localPath): void;

    /**
     * Delete a remote directory recursively (best-effort; missing ignored).
     */
    public function deleteDirectory(string $remoteDir): void;

    /**
     * Close the connection.
     */
    public function disconnect(): void;

    /**
     * Close and re-establish the connection. Used to recover from transient
     * drops during long upload loops on shared hosting.
     *
     * @throws \RuntimeException when reconnecting fails.
     */
    public function reconnect(): void;
}
