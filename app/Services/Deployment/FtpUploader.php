<?php

namespace App\Services\Deployment;

use App\Models\Release;
use RuntimeException;

/**
 * Uploads every changed file of a release to the shared host over FTP.
 *
 * - Files marked as removed are deleted on the server (best-effort).
 * - Remote directories are created on demand.
 * - Output is a ProcessResult so the queue job treats it like a command.
 */
class FtpUploader
{
    /**
     * Server directories that must be emptied after an upload so Laravel
     * picks up the new files. Shared hosting has no exec(), so caches are
     * cleared by deleting the cached files directly.
     */
    public const SERVER_CACHE_PATHS = [
        'bootstrap/cache',
        'storage/framework/views',
    ];

    public function __construct(
        protected DeploymentFtpSettings $settings,
    ) {
    }

    /**
     * @return array{uploaded: int, removed: int, cleared: int, failed: array<int, string>}
     */
    public function upload(Release $release, ?FtpClientContract $client = null): array
    {
        $credentials = $this->settings->all();

        if ($credentials['host'] === null || $credentials['username'] === null) {
            throw new RuntimeException('إعدادات FTP غير مكتملة: حدّد بيانات الاتصال من صفحة إعدادات النشر.');
        }

        $client ??= new FtpClient($credentials);

        $changes = $release->changes;
        $uploaded = 0;
        $removed = 0;
        $cleared = 0;
        $failed = [];

        try {
            $client->connect();

            // Create every needed directory once, before the file loop, so we
            // don't spend hundreds of round-trips verifying them per file.
            $this->ensureDirectories($changes, $client);

            foreach ($changes as $change) {
                $relative = ltrim($change->file_path, '/');

                if ($change->type->value === 'removed') {
                    try {
                        $client->delete($relative);
                        $removed++;
                    } catch (RuntimeException) {
                        // Missing remote file is acceptable during cleanup.
                    }

                    continue;
                }

                $absolute = $this->resolveLocalPath($relative);

                if ($absolute === null) {
                    $failed[] = $relative;

                    continue;
                }

                try {
                    $client->upload($absolute, $relative);
                    $uploaded++;
                } catch (RuntimeException $e) {
                    // Shared hosting occasionally drops the control connection
                    // mid-transfer. Reconnect once and retry before giving up.
                    try {
                        $client->reconnect();
                        $client->upload($absolute, $relative);
                        $uploaded++;
                    } catch (RuntimeException) {
                        $failed[] = "{$relative} ({$e->getMessage()})";
                    }
                }
            }

            // Clear the server caches so the newly uploaded files take effect.
            $cleared = $this->clearServerCaches($client);
        } finally {
            $client->disconnect();
        }

        return [
            'uploaded' => $uploaded,
            'removed' => $removed,
            'cleared' => $cleared,
            'failed' => $failed,
        ];
    }

    /**
     * Ensure every directory referenced by added/modified files exists on the
     * server. One round-trip per unique directory instead of per file.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ReleaseChange>  $changes
     */
    protected function ensureDirectories($changes, FtpClientContract $client): void
    {
        $directories = [];

        foreach ($changes as $change) {
            if ($change->type->value === 'removed') {
                continue;
            }

            $directory = dirname(ltrim($change->file_path, '/'));

            if ($directory !== '' && $directory !== '.') {
                $directories[$directory] = true;
            }
        }

        try {
            foreach (array_keys($directories) as $directory) {
                $client->ensureDirectory($directory);
            }
        } catch (RuntimeException) {
            // A dropped connection can also abort directory creation; retry once.
            $client->reconnect();

            foreach (array_keys($directories) as $directory) {
                $client->ensureDirectory($directory);
            }
        }
    }

    /**
     * Best-effort deletion of Laravel cache files on the server.
     */
    protected function clearServerCaches(FtpClientContract $client): int
    {
        $cleared = 0;

        foreach (self::SERVER_CACHE_PATHS as $cachePath) {
            try {
                $entries = $client->listDirectory($cachePath);

                if ($entries === []) {
                    continue;
                }

                $client->deleteDirectory($cachePath);
                $cleared += count($entries);
            } catch (RuntimeException) {
                // The connection may have dropped during the upload loop;
                // reconnect once and retry before giving up.
                try {
                    $client->reconnect();

                    $entries = $client->listDirectory($cachePath);

                    if ($entries === []) {
                        continue;
                    }

                    $client->deleteDirectory($cachePath);
                    $cleared += count($entries);
                } catch (RuntimeException) {
                    // Best-effort — stale caches must never block a deployment.
                }
            }
        }

        return $cleared;
    }

    /**
     * Build a ProcessResult for the deployment step output.
     */
    public function result(Release $release, ?FtpClientContract $client = null): ProcessResult
    {
        try {
            $stats = $this->upload($release, $client);

            $lines = [
                "تم رفع {$stats['uploaded']} ملف.",
            ];

            if ($stats['removed'] > 0) {
                $lines[] = "تم حذف {$stats['removed']} ملف من السيرفر.";
            }

            if ($stats['cleared'] > 0) {
                $lines[] = "تم مسح كاش السيرفر ({$stats['cleared']} عنصر).";
            }

            if ($stats['failed'] !== []) {
                $lines[] = 'فشل رفع '.count($stats['failed']).' ملف:';
                $lines = array_merge($lines, array_map(
                    fn (string $path) => '- '.$path,
                    array_slice($stats['failed'], 0, 20)
                ));
            }

            return new ProcessResult(
                successful: $stats['failed'] === [],
                output: implode("\n", $lines),
                exitCode: $stats['failed'] === [] ? 0 : 1,
            );
        } catch (RuntimeException $e) {
            return new ProcessResult(
                successful: false,
                output: $e->getMessage(),
                exitCode: 1,
            );
        }
    }

    /**
     * Resolve a relative project path to an absolute local path, but only when
     * it stays inside the project and matches the allowlist.
     */
    protected function resolveLocalPath(string $relative): ?string
    {
        $absolute = base_path($relative);
        $real = realpath($absolute);

        if ($real === false || ! str_starts_with($real, base_path())) {
            return null;
        }

        $allowed = \App\Support\Deployment\DeploymentPaths::allowed();

        if ($allowed !== []) {
            $allowedPrefixes = array_map(
                fn (string $path) => rtrim($path, '/'),
                $allowed
            );

            $matches = array_filter(
                $allowedPrefixes,
                fn (string $prefix) => $relative === $prefix || str_starts_with($relative, $prefix.'/')
            );

            if ($matches === []) {
                return null;
            }
        }

        return $real;
    }
}
