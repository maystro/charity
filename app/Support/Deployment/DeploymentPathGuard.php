<?php

namespace App\Support\Deployment;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guards every path the deployment process may touch.
 *
 * Rejects absolute paths, parent traversal (".."), and symlinks that resolve
 * outside the project directory. Only paths whose first segment matches an
 * entry in the configured allowlist are accepted. User-supplied paths never
 * reach the Process layer — this guard is defence in depth.
 */
class DeploymentPathGuard
{
    /**
     * Validate a relative path and return it normalized to forward slashes.
     *
     * @param  array<int, string>|null  $allowedPaths
     *
     * @throws RuntimeException
     */
    public function validate(string $path, ?array $allowedPaths = null): string
    {
        $allowedPaths ??= DeploymentPaths::allowed();

        $path = trim($path);

        if ($path === '' || $path === '.') {
            throw new RuntimeException('Deployment path cannot be empty.');
        }

        if ($this->isAbsolute($path)) {
            throw new RuntimeException("Absolute paths are not allowed: [{$path}].");
        }

        $normalized = $this->normalize($path);

        if (Str::contains($normalized, ['..', './']) || $normalized === '..') {
            throw new RuntimeException("Path traversal is not allowed: [{$path}].");
        }

        if (! $this->isInsideAllowedScope($normalized, $allowedPaths)) {
            throw new RuntimeException("Path [{$path}] is outside the allowed deployment scope.");
        }

        $this->assertResolvesInsideProject($normalized, $path);

        return $normalized;
    }

    /**
     * Whether the normalized path starts with an entry from the allowlist.
     *
     * @param  array<int, string>  $allowedPaths
     */
    protected function isInsideAllowedScope(string $path, array $allowedPaths): bool
    {
        return collect($allowedPaths)
            ->map(fn (string $entry) => $this->normalize($entry))
            ->filter(fn (string $entry) => ! Str::contains($entry, ['..', './']) && $entry !== '')
            ->contains(
                fn (string $entry) => $path === $entry || Str::startsWith($path, $entry.'/')
            );
    }

    /**
     * Resolve symlinks and reject anything that lands outside the project root.
     */
    protected function assertResolvesInsideProject(string $normalized, string $original): void
    {
        $projectRoot = realpath(base_path());
        $absolute = realpath(base_path($normalized));

        if ($projectRoot === false || $absolute === false) {
            return;
        }

        $inside = $absolute === $projectRoot
            || Str::startsWith($absolute, $projectRoot.DIRECTORY_SEPARATOR);

        if (! $inside) {
            throw new RuntimeException("Path [{$original}] resolves outside the project directory.");
        }
    }

    protected function isAbsolute(string $path): bool
    {
        return Str::startsWith($path, ['/', '\\'])
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }

    protected function normalize(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }
}
