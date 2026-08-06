<?php

namespace App\Services\Deployment;

use App\Models\SmartDeployment;
use App\Models\User;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Smart file deployment: upload only the files that changed since the last
 * deployment, tracked through a local manifest. Supports two comparison
 * modes:
 *
 *  - local:  diff the working tree against storage/app/deployment_manifest.json
 *  - server: diff against the files actually present on the remote host
 *            (deployer.php over HTTP)
 *
 * Deployment sends a ZIP archive to deployer.php via HTTP POST — no FTP needed.
 */
class SmartDeploymentService
{
    public function __construct(
        protected ProjectSnapshot $snapshot,
    ) {
    }

    /* ---------------------------------------------------------------------
     | Scanning
     | --------------------------------------------------------------------- */

    /**
     * Scan the included paths and compute a checksum for every file.
     *
     * @return array<string, string> [relative_path => md5]
     */
    public function scanLocalFiles(): array
    {
        $include = config('deployment.smart.include', []);

        $files = $this->snapshot->scan(
            base_path(),
            ProjectSnapshot::DEFAULT_EXCLUDES,
            $include
        );

        $excludeWithin = config('deployment.smart.exclude_within', []);

        return collect($files)
            ->reject(fn (string $hash, string $path): bool => ! $this->shouldIncludeFile($path, $excludeWithin))
            ->all();
    }

    /**
     * Whether a relative path should be part of the deployment set.
     *
     * @param  array<int, string>|null  $excludeWithin
     */
    public function shouldIncludeFile(string $relativePath, ?array $excludeWithin = null): bool
    {
        if (basename($relativePath) === '.gitignore') {
            return false;
        }

        $excludeWithin ??= config('deployment.smart.exclude_within', []);

        foreach ($excludeWithin as $excluded) {
            if ($relativePath === $excluded || Str::startsWith($relativePath, $excluded.'/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compute the total byte size of a set of relative paths.
     *
     * @param  array<int, string>  $paths
     */
    public function totalSize(array $paths): int
    {
        $size = 0;

        foreach ($paths as $path) {
            $absolute = base_path($path);
            $fileSize = is_file($absolute) ? filesize($absolute) : false;
            $size += $fileSize === false ? 0 : (int) $fileSize;
        }

        return $size;
    }

    /* ---------------------------------------------------------------------
     | Manifest
     | --------------------------------------------------------------------- */

    public function manifestPath(): string
    {
        return (string) config('deployment.smart.manifest_path', storage_path('app/deployment_manifest.json'));
    }

    /**
     * @return array<string, string>
     */
    public function loadManifest(): array
    {
        $path = $this->manifestPath();

        if (! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * @param  array<string, string>  $manifest
     */
    public function saveManifest(array $manifest): void
    {
        $path = $this->manifestPath();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function resetManifest(): void
    {
        $path = $this->manifestPath();

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /* ---------------------------------------------------------------------
     | Change detection
     | --------------------------------------------------------------------- */

    /**
     * Diff the local tree against the manifest.
     *
     * @return array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>, total_size: int}
     */
    public function getLocalChanges(): array
    {
        $current = $this->scanLocalFiles();
        $manifest = $this->loadManifest();

        return $this->diffSnapshots($manifest, $current);
    }

    /**
     * Diff the local tree against the actual server files.
     *
     * @return array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>, total_size: int}
     */
    public function getServerChanges(): array
    {
        $current = $this->scanLocalFiles();
        $server = $this->getServerManifest();

        return $this->diffSnapshots($server, $current);
    }

    /**
     * Fetch the manifest of files present on the server via deployer.php.
     *
     * @return array<string, string> [relative_path => hash]
     */
    public function getServerManifest(): array
    {
        $serverUrl = (string) config('deployment.smart.server_url', '');

        if ($serverUrl === '') {
            throw new RuntimeException('لم يتم ضبط DEPLOY_SERVER_URL — لا يمكن مقارنة السيرفر بدون deployer.php.');
        }

        return $this->fetchHttpManifest($serverUrl);
    }

    /**
     * Call deployer.php?action=get_manifest and return the file map.
     *
     * @return array<string, string>
     *
     * @throws RuntimeException with a descriptive Arabic reason on any failure.
     */
    protected function fetchHttpManifest(string $serverUrl): array
    {
        try {
            $response = Http::asForm()
                ->timeout(60)
                ->post($serverUrl, [
                    'action' => 'get_manifest',
                    'secret' => (string) config('deployment.smart.secret_key', ''),
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('تعذر الاتصال بالسيرفر — تأكد من أن الرابط صحيح وأن السيرفر متاح.');
        }

        if (! $response->successful()) {
            $status = $response->status();

            if ($status === 403) {
                throw new RuntimeException('المفتاح السري غير صحيح — تأكد من DEPLOY_SECRET_KEY في ملف .env.');
            }

            throw new RuntimeException("استجابة السيرفر غير ناجحة (HTTP {$status}) — تأكد من deployer.php.");
        }

        $data = $response->json();

        if (! is_array($data) || ($data['success'] ?? false) !== true || ! isset($data['files'])) {
            throw new RuntimeException('استجابة غير متوقعة من السيرفر — تأكد من وجود deployer.php ويعمل بشكل صحيح.');
        }

        $manifest = [];

        foreach ((array) $data['files'] as $path => $hash) {
            if (is_string($path) && is_string($hash)) {
                $manifest[$path] = $hash;
            }
        }

        return $manifest;
    }

    /**
     * Whether the deployer.php server endpoint is configured.
     */
    public function isServerConfigured(): bool
    {
        return (string) config('deployment.smart.server_url', '') !== '';
    }

    /**
     * @param  array<string, string>  $previous
     * @param  array<string, string>  $current
     * @return array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>, total_size: int}
     */
    protected function diffSnapshots(array $previous, array $current): array
    {
        $added = [];
        $modified = [];
        $removed = [];

        foreach ($current as $path => $hash) {
            if (! array_key_exists($path, $previous)) {
                $added[] = $path;
            } elseif ($previous[$path] !== $hash) {
                $modified[] = $path;
            }
        }

        foreach ($previous as $path => $hash) {
            if (! array_key_exists($path, $current)) {
                $removed[] = $path;
            }
        }

        sort($added);
        sort($modified);
        sort($removed);

        $all = array_merge($added, $modified, $removed);

        return [
            'added' => $added,
            'modified' => $modified,
            'removed' => $removed,
            'total_size' => $this->totalSize($all),
        ];
    }

    /* ---------------------------------------------------------------------
     | Deployment
     | --------------------------------------------------------------------- */

    /**
     * Create a ZIP archive of changed files, upload it to deployer.php,
     * and update the local manifest on success.
     *
     * When DEPLOY_SERVER_URL is empty, operates in local-only mode — just
     * updates the manifest without contacting any server.
     *
     * @param  array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>}  $changes
     * @param  callable(int, int, ?string): void|null  $onProgress  (done, total, currentFile)
     * @return array{files_count: int, total_size: int, files: array<int, string>, deleted: array<int, string>}
     */
    public function deploy(array $changes, ?callable $onProgress = null): array
    {
        $files = array_merge($changes['added'], $changes['modified']);
        $deleted = $changes['removed'];
        $total = count($files);
        $serverUrl = (string) config('deployment.smart.server_url', '');

        // Local-only mode — no server endpoint configured.
        if ($serverUrl === '') {
            $current = $this->scanLocalFiles();
            $manifest = $this->loadManifest();

            foreach ($files as $path) {
                if (isset($current[$path])) {
                    $manifest[$path] = $current[$path];
                }
            }

            foreach ($deleted as $path) {
                unset($manifest[$path]);
            }

            $this->saveManifest($manifest);

            if ($onProgress !== null) {
                $onProgress($total, $total, null);
            }

            return [
                'files_count' => $total,
                'total_size' => $this->totalSize($files),
                'files' => $files,
                'deleted' => $deleted,
            ];
        }

        // --- Remote deployment via deployer.php ---

        // 1. Build ZIP archive.
        $zipPath = storage_path('app/deployment_'.now()->format('YmdHis').'_'.uniqid().'.zip');

        if (! is_dir(dirname($zipPath))) {
            @mkdir(dirname($zipPath), 0775, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذر إنشاء الأرشيف.');
        }

        $done = 0;

        foreach ($files as $path) {
            $absolute = base_path($path);

            if (is_file($absolute)) {
                $zip->addFile($absolute, $path);
            }

            $done++;

            if ($onProgress !== null && $total > 0) {
                // Archiving phase: 0–50 %
                $onProgress($done, $total, $path);
            }
        }

        $zip->close();

        // 2. Upload to deployer.php.
        if ($onProgress !== null) {
            $onProgress($total, $total, 'جارٍ رفع الأرشيف إلى السيرفر...');
        }

        try {
            $archiveContent = (string) file_get_contents($zipPath);

            $response = Http::timeout(300)
                ->attach('archive', $archiveContent, 'deployment.zip')
                ->post($serverUrl, [
                    'action' => 'deploy',
                    'secret' => (string) config('deployment.smart.secret_key', ''),
                ]);

            // Clean up temp ZIP.
            @unlink($zipPath);

            $data = $response->json();

            if (! is_array($data) || ($data['success'] ?? false) !== true) {
                $error = $data['error'] ?? 'استجابة السيرفر غير ناجحة ('.$response->status().').';

                throw new RuntimeException($error);
            }

            // 3. Update local manifest.
            $current = $this->scanLocalFiles();
            $manifest = $this->loadManifest();

            foreach ($files as $path) {
                if (isset($current[$path])) {
                    $manifest[$path] = $current[$path];
                }
            }

            foreach ($deleted as $path) {
                unset($manifest[$path]);
            }

            $this->saveManifest($manifest);

            if ($onProgress !== null) {
                $onProgress($total, $total, null);
            }

            return [
                'files_count' => $total,
                'total_size' => $this->totalSize($files),
                'files' => $files,
                'deleted' => $deleted,
                'server_response' => $data,
            ];
        } catch (Throwable $e) {
            @unlink($zipPath);

            throw new RuntimeException('فشل النشر: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Open a new smart deployment record (status: deploying).
     */
    public function startRecord(?User $user = null, string $mode = 'local'): SmartDeployment
    {
        return SmartDeployment::create([
            'user_id' => $user?->id,
            'mode' => $mode,
            'status' => 'deploying',
            'files_count' => 0,
            'total_size' => 0,
            'files_list' => [],
            'started_at' => now(),
        ]);
    }

    /**
     * Mark a smart deployment record as successful.
     *
     * @param  array{files_count?: int, total_size?: int, files?: array<int, string>}  $result
     */
    public function completeRecord(SmartDeployment $record, array $result = [], ?string $notes = null, ?string $serverResponse = null): SmartDeployment
    {
        $record->update([
            'files_count' => $result['files_count'] ?? 0,
            'total_size' => $result['total_size'] ?? 0,
            'files_list' => $result['files'] ?? [],
            'notes' => $notes,
            'server_response' => $serverResponse,
            'completed_at' => now(),
            'status' => 'success',
        ]);

        return $record;
    }

    /**
     * Mark a smart deployment record as failed.
     */
    public function failRecord(SmartDeployment $record, ?string $serverResponse = null): SmartDeployment
    {
        $record->update([
            'status' => 'failed',
            'server_response' => $serverResponse,
            'completed_at' => now(),
        ]);

        return $record;
    }

    /* ---------------------------------------------------------------------
     | History & stats
     | --------------------------------------------------------------------- */

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SmartDeployment>
     */
    public function getRecentDeployments(int $limit = 10)
    {
        return SmartDeployment::query()
            ->with('user')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{total_files: int, total_size: int, last_deployment: SmartDeployment|null, synced: bool, manifest_exists: bool, manifest_files: int}
     */
    public function getStats(): array
    {
        $current = $this->scanLocalFiles();
        $changes = $this->getLocalChanges();

        return [
            'total_files' => count($current),
            'total_size' => $this->totalSize(array_keys($current)),
            'last_deployment' => SmartDeployment::query()->orderByDesc('id')->first(),
            'synced' => count($changes['added']) + count($changes['modified']) + count($changes['removed']) === 0,
            'manifest_exists' => is_file($this->manifestPath()),
            'manifest_files' => count($this->loadManifest()),
        ];
    }

}
