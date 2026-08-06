<?php

namespace App\Support\Deployment;

use App\Models\DeploymentAllowedPath;

/**
 * Resolves the effective allowlist of deployable paths.
 *
 * Paths saved through the super-admin UI take precedence; when none have been
 * saved yet the system falls back to the defaults in config/deployment.php.
 */
class DeploymentPaths
{
    /**
     * The current allowlist of allowed paths, sorted by name.
     *
     * @return array<int, string>
     */
    public static function allowed(): array
    {
        try {
            $paths = DeploymentAllowedPath::query()
                ->orderBy('path')
                ->pluck('path')
                ->all();
        } catch (\Throwable) {
            // Table missing / no connection (e.g. unit tests) — use defaults.
            $paths = [];
        }

        return $paths !== [] ? $paths : (array) config('deployment.allowed_paths', []);
    }
}
