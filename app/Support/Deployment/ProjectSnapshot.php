<?php

namespace App\Support\Deployment;

use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Builds and compares file snapshots of the project.
 *
 * A snapshot maps each tracked file's relative path to its content hash.
 * Comparing two snapshots reveals added, modified, and removed files — which
 * is how the system detects what changed since the last release without
 * requiring a Git repository.
 */
class ProjectSnapshot
{
    /**
     * Paths (relative) that are never included in snapshots or upload packages.
     *
     * @var array<int, string>
     */
    public const DEFAULT_EXCLUDES = [
        '.git',
        '.idea',
        '.vscode',
        'node_modules',
        'vendor',
        'storage',
        'bootstrap/cache',
        '.env',
        '.env.example',
        '.env.testing',
        '.DS_Store',
        'Thumbs.db',
        'database/database.sqlite',
        'database/database.sqlite-journal',
    ];

    /**
     * Files that are always skipped, even inside included directories.
     *
     * @var array<int, string>
     */
    public const SKIP_FILENAMES = [
        '.DS_Store',
        'Thumbs.db',
    ];

    /**
     * Build a snapshot of [relative_path => content_hash] for all project files.
     *
     * @param  array<int, string>  $excludes
     * @return array<string, string>
     */
    public function scan(?string $root = null, array $excludes = self::DEFAULT_EXCLUDES, ?array $allowedPaths = null): array
    {
        $root = rtrim($root ?: base_path(), DIRECTORY_SEPARATOR);
        $allowedPaths ??= DeploymentPaths::allowed();

        $snapshot = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(Str::after($file->getPathname(), $root), DIRECTORY_SEPARATOR);

            if ($this->isExcluded($relative, $excludes) || $this->isSkippedFilename($relative)) {
                continue;
            }

            if (! $this->isAllowed($relative, $allowedPaths)) {
                continue;
            }

            $snapshot[$relative] = md5_file($file->getPathname());
        }

        ksort($snapshot);

        return $snapshot;
    }

    /**
     * Detect files whose modification time is at or after a given point in time.
     *
     * Used as a fallback when no baseline snapshot exists yet (e.g. after a
     * database reset): without a previous snapshot we cannot tell "added" from
     * "modified", so every recently-touched file is reported with $type.
     *
     * @return array<int, array{file_path: string, type: string, hash: string}>
     */
    public function changesByMtimeSince(\DateTimeInterface $since, string $type = 'modified', ?string $root = null, ?array $allowedPaths = null): array
    {
        $root = rtrim($root ?: base_path(), DIRECTORY_SEPARATOR);

        $changes = [];

        foreach ($this->scan($root, self::DEFAULT_EXCLUDES, $allowedPaths) as $path => $hash) {
            $mtime = filemtime($root.DIRECTORY_SEPARATOR.$path);

            if ($mtime !== false && $mtime >= $since->getTimestamp()) {
                $changes[] = ['file_path' => $path, 'type' => $type, 'hash' => $hash];
            }
        }

        usort($changes, fn (array $a, array $b) => strcmp($a['file_path'], $b['file_path']));

        return $changes;
    }

    /**
     * Compare two snapshots and list files that changed between them.
     *
     * @param  array<string, string>  $previous
     * @param  array<string, string>  $current
     * @return array<int, array{file_path: string, type: string, hash: string}>
     */
    public function changesSince(array $previous, array $current): array
    {
        $changes = [];

        foreach ($current as $path => $hash) {
            if (! array_key_exists($path, $previous)) {
                $changes[] = ['file_path' => $path, 'type' => 'added', 'hash' => $hash];
            } elseif ($previous[$path] !== $hash) {
                $changes[] = ['file_path' => $path, 'type' => 'modified', 'hash' => $hash];
            }
        }

        foreach ($previous as $path => $hash) {
            if (! array_key_exists($path, $current)) {
                $changes[] = ['file_path' => $path, 'type' => 'removed', 'hash' => ''];
            }
        }

        usort($changes, fn (array $a, array $b) => strcmp($a['file_path'], $b['file_path']));

        return $changes;
    }

    /**
     * Whether a relative path matches a filename that is always skipped.
     */
    protected function isSkippedFilename(string $relative): bool
    {
        return in_array(basename($relative), self::SKIP_FILENAMES, true);
    }

    /**
     * Whether a relative path falls inside any excluded path/segment.
     *
     * @param  array<int, string>  $excludes
     */
    protected function isExcluded(string $relative, array $excludes): bool
    {
        $relative = str_replace('\\', '/', $relative);

        foreach ($excludes as $exclude) {
            $exclude = str_replace('\\', '/', rtrim($exclude, '/'));

            if ($relative === $exclude || Str::startsWith($relative, $exclude.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a relative path is inside any allowed path/segment.
     *
     * @param  array<int, string>  $allowedPaths
     */
    protected function isAllowed(string $relative, array $allowedPaths): bool
    {
        $relative = str_replace('\\', '/', $relative);

        foreach ($allowedPaths as $allowed) {
            $allowed = str_replace('\\', '/', rtrim($allowed, '/'));

            if ($relative === $allowed || Str::startsWith($relative, $allowed.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter a stored snapshot down to the configured allowed paths.
     *
     * Old baselines may contain files that are no longer allowed (e.g. saved
     * before the allowlist was enforced); ignoring them prevents bogus
     * "removed" rows on the next import.
     *
     * @param  array<string, string>  $snapshot
     * @param  array<int, string>|null  $allowedPaths
     * @return array<string, string>
     */
    public function allowedOnly(array $snapshot, ?array $allowedPaths = null): array
    {
        $allowedPaths ??= DeploymentPaths::allowed();

        return array_filter(
            $snapshot,
            fn (string $path): bool => $this->isAllowed($path, $allowedPaths),
            ARRAY_FILTER_USE_KEY
        );
    }
}
