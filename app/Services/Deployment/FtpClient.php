<?php

namespace App\Services\Deployment;

use RuntimeException;

/**
 * PHP-native FTP client wrapping ftp_* functions.
 *
 * The remote root path is joined with every target so the user can target a
 * subdirectory of the account (e.g. /public_html).
 */
class FtpClient implements FtpClientContract
{
    /** @var resource|\FTP\Connection|null */
    protected $connection = null;

    /**
     * @param  array{host: string, port: int, username: string, password: string, root_path: string}  $credentials
     */
    public function __construct(
        protected array $credentials,
        protected bool $passive = true,
        protected int $timeout = 90,
    ) {
    }

    public function connect(): void
    {
        if ($this->isActiveConnection()) {
            return;
        }

        if (! function_exists('ftp_connect')) {
            throw new RuntimeException('امتداد FTP غير مفعّل على هذا الخادم.');
        }

        $host = trim((string) ($this->credentials['host'] ?? ''));
        $port = (int) ($this->credentials['port'] ?: 21);
        $username = trim((string) ($this->credentials['username'] ?? ''));
        $password = (string) ($this->credentials['password'] ?? '');

        if ($host === '' || $username === '') {
            throw new RuntimeException('إعدادات FTP غير مكتملة — أكملها من صفحة إعدادات النشر أولاً.');
        }

        $connectTimeout = max(3, min(10, $this->timeout > 0 ? $this->timeout : 10));
        $operationTimeout = max(3, min(15, $connectTimeout + 5));

        $connection = @ftp_connect($host, $port, $connectTimeout);

        if ($connection === false) {
            throw new RuntimeException("تعذر الاتصال بالسيرفر [{$host}:{$port}].");
        }

        $this->connection = $connection;

        if (function_exists('ftp_set_option')) {
            @ftp_set_option($connection, FTP_TIMEOUT_SEC, $operationTimeout);
        }

        $login = @ftp_login(
            $connection,
            $username,
            $password
        );

        if ($login === false) {
            $this->disconnect();

            throw new RuntimeException('فشل تسجيل الدخول: اسم المستخدم أو كلمة المرور غير صحيحة.');
        }

        if ($this->passive) {
            @ftp_pasv($connection, true);
        }
    }

    public function ensureDirectory(string $remoteDir): void
    {
        $connection = $this->assertConnected();

        $remoteDir = trim($remoteDir, '/');

        if ($remoteDir === '' || $remoteDir === '.') {
            return;
        }

        $current = rtrim($this->joinRoot(''), '/');
        $segments = array_values(array_filter(explode('/', $remoteDir), static fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return;
        }

        foreach ($segments as $segment) {
            $current .= '/'.$segment;

            $pwd = @ftp_pwd($connection);
            if ($pwd !== false && rtrim($pwd, '/') === rtrim($current, '/')) {
                continue;
            }

            if (@ftp_chdir($connection, $current) === true) {
                continue;
            }

            $made = @ftp_mkdir($connection, $current);

            if ($made === false) {
                throw new RuntimeException("تعذر إنشاء المجلد [{$current}] على السيرفر.");
            }

            if (@ftp_chdir($connection, $current) !== true) {
                throw new RuntimeException("تعذر الانتقال إلى المجلد [{$current}] بعد إنشائه.");
            }
        }
    }

    public function upload(string $localPath, string $remotePath): void
    {
        $connection = $this->assertConnected();

        $remotePath = $this->joinRoot($remotePath);

        $uploaded = @ftp_put($connection, $remotePath, $localPath, FTP_BINARY);

        if ($uploaded === false) {
            throw new RuntimeException("فشل رفع الملف [{$remotePath}].");
        }
    }

    public function delete(string $remotePath): void
    {
        $connection = $this->assertConnected();

        @ftp_delete($connection, $this->joinRoot($remotePath));
    }

    public function listDirectory(string $remoteDir): array
    {
        return $this->listRaw($this->joinRoot($remoteDir));
    }

    public function tree(string $remoteDir = ''): array
    {
        $files = [];

        $this->walkRaw($this->joinRoot($remoteDir), $remoteDir, $files);

        usort($files, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $files;
    }

    public function download(string $remotePath, string $localPath): void
    {
        $connection = $this->assertConnected();

        $downloaded = @ftp_get($connection, $localPath, $this->joinRoot($remotePath), FTP_BINARY);

        if ($downloaded === false) {
            throw new RuntimeException("فشل تنزيل الملف [{$remotePath}] من السيرفر.");
        }
    }

    public function deleteDirectory(string $remoteDir): void
    {
        $this->deleteRaw($this->joinRoot($remoteDir));
    }

    public function disconnect(): void
    {
        if ($this->isActiveConnection()) {
            @ftp_close($this->connection);
        }

        $this->connection = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    protected function joinRoot(string $remotePath): string
    {
        $root = rtrim((string) $this->credentials['root_path'], '/');

        if ($root === '') {
            return '/'.ltrim($remotePath, '/');
        }

        return $root.'/'.ltrim($remotePath, '/');
    }

    /** @return resource|\FTP\Connection */
    protected function assertConnected()
    {
        if (! $this->isActiveConnection()) {
            throw new RuntimeException('لا يوجد اتصال FTP نشط.');
        }

        return $this->connection;
    }

    /**
     * PHP ≥ 8.1 wraps FTP sockets in a FTP\Connection object instead of a resource.
     */
    protected function isActiveConnection(): bool
    {
        return is_resource($this->connection) || $this->connection instanceof \FTP\Connection;
    }

    /**
     * List entries of an already-root-joined remote path (best-effort).
     *
     * @return array<int, array{name: string, type: 'file'|'dir'}>
     */
    protected function listRaw(string $remotePath): array
    {
        $connection = $this->assertConnected();

        if (function_exists('ftp_mlsd')) {
            $listing = @ftp_mlsd($connection, $remotePath);

            if (is_array($listing)) {
                return collect($listing)
                    ->filter(fn (array $item): bool => isset($item['name']) && ! in_array($item['name'], ['.', '..'], true))
                    ->map(fn (array $item): array => [
                        'name' => (string) $item['name'],
                        'type' => ($item['type'] ?? '') === 'dir' ? 'dir' : 'file',
                    ])
                    ->values()
                    ->all();
            }
        }

        $raw = @ftp_rawlist($connection, $remotePath);

        if (! is_array($raw)) {
            return [];
        }

        $entries = [];

        foreach ($raw as $line) {
            $parts = preg_split('/\s+/', trim($line));
            $name = $parts !== [] ? (string) end($parts) : '';

            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $entries[] = [
                'name' => $name,
                'type' => str_starts_with($line, 'd') ? 'dir' : 'file',
            ];
        }

        return $entries;
    }

    /**
     * Recursively collect every entry below an already-root-joined path.
     *
     * @param  array<int, array{path: string, type: 'file'|'dir', size: ?int}>  $files
     */
    protected function walkRaw(string $remotePath, string $relativePrefix, array &$files): void
    {
        $connection = $this->assertConnected();

        foreach ($this->listRawDetailed($remotePath) as $entry) {
            $name = $entry['name'];
            $relative = $relativePrefix === '' ? $name : $relativePrefix.'/'.$name;

            if ($entry['type'] === 'dir') {
                $files[] = ['path' => $relative, 'type' => 'dir', 'size' => null];
                $this->walkRaw(rtrim($remotePath, '/').'/'.$name, $relative, $files);

                continue;
            }

            $size = $entry['size'] ?? null;

            if ($size === null) {
                $size = @ftp_size($connection, rtrim($remotePath, '/').'/'.$name);
            }

            $files[] = [
                'path' => $relative,
                'type' => 'file',
                'size' => $size === false ? null : (int) $size,
            ];
        }
    }

    /**
     * List entries with an optional byte size (best-effort, FTP-specific).
     *
     * @return array<int, array{name: string, type: 'file'|'dir', size: ?int}>
     */
    protected function listRawDetailed(string $remotePath): array
    {
        $connection = $this->assertConnected();

        if (function_exists('ftp_mlsd')) {
            $listing = @ftp_mlsd($connection, $remotePath);

            if (is_array($listing)) {
                return collect($listing)
                    ->filter(fn (array $item): bool => isset($item['name']) && ! in_array($item['name'], ['.', '..'], true))
                    ->map(fn (array $item): array => [
                        'name' => (string) $item['name'],
                        'type' => ($item['type'] ?? '') === 'dir' ? 'dir' : 'file',
                        'size' => isset($item['size']) ? (int) $item['size'] : null,
                    ])
                    ->values()
                    ->all();
            }
        }

        $raw = @ftp_rawlist($connection, $remotePath);

        if (! is_array($raw)) {
            return [];
        }

        $entries = [];

        foreach ($raw as $line) {
            $parts = preg_split('/\s+/', trim($line));
            $name = $parts !== [] ? (string) end($parts) : '';

            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $isDir = str_starts_with($line, 'd');

            // Unix rawlist: perms links owner group size month day [time|year] name
            $size = null;
            $partsCount = count($parts);

            if (! $isDir && $partsCount >= 5 && ctype_digit((string) ($parts[4] ?? ''))) {
                $size = (int) $parts[4];
            }

            $entries[] = [
                'name' => $name,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $size,
            ];
        }

        return $entries;
    }

    /**
     * Recursively delete an already-root-joined remote path (best-effort).
     */
    protected function deleteRaw(string $remotePath): void
    {
        $connection = $this->assertConnected();

        foreach ($this->listRaw($remotePath) as $entry) {
            $child = rtrim($remotePath, '/').'/'.$entry['name'];

            if ($entry['type'] === 'dir') {
                $this->deleteRaw($child);
            } else {
                @ftp_delete($connection, $child);
            }
        }

        @ftp_rmdir($connection, $remotePath);
    }
}
