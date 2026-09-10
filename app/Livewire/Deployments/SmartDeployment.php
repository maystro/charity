<?php

namespace App\Livewire\Deployments;

use App\Models\DeploymentAllowedPath;
use App\Models\SmartDeployment as SmartDeploymentModel;
use App\Services\Deployment\SmartDeploymentService;
use App\Support\Deployment\DeploymentPaths;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app', ['title' => 'النشر الذكي'])]
class SmartDeployment extends Component
{
    #[Url]
    public string $mode = 'local';

    public bool $isScanning = false;

    public bool $isDeploying = false;

    public int $uploadProgress = 0;

    public int $uploadedCount = 0;

    public int $totalFilesToUpload = 0;

    public ?string $currentFile = null;

    public string $currentStatus = 'idle'; // idle | scanning | deploying | success | error

    /** @var array<int, string> */
    public array $addedFiles = [];

    /** @var array<int, string> */
    public array $modifiedFiles = [];

    /** @var array<int, string> */
    public array $deletedFiles = [];

    public int $totalSize = 0;

    public ?string $notes = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public bool $isServerConfigured = false;

    public string $serverUrl = '';

    /** @var array<int, string> Checked allowed paths (root-level entries only). */
    public array $allowedSelected = [];

    public string $allowedSearch = '';

    public bool $allowedSaving = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->refreshSetupState();
        $this->loadAllowedSelection();
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'server' ? 'server' : 'local';
        $this->clearScan();
    }

    #[Computed]
    public function service(): SmartDeploymentService
    {
        return app(SmartDeploymentService::class);
    }

    /**
     * Merge all pending files into one sorted list for the preview table.
     *
     * @return array<int, array{path: string, status: 'added'|'modified'|'deleted'}>
     */
    #[Computed]
    public function sortedFiles(): array
    {
        $files = [];

        foreach ($this->addedFiles as $path) {
            $files[] = ['path' => $path, 'status' => 'added'];
        }

        foreach ($this->modifiedFiles as $path) {
            $files[] = ['path' => $path, 'status' => 'modified'];
        }

        foreach ($this->deletedFiles as $path) {
            $files[] = ['path' => $path, 'status' => 'deleted'];
        }

        // Sort by status: deleted first, then modified, then added.
        $order = ['deleted' => 0, 'modified' => 1, 'added' => 2];

        usort($files, fn (array $a, array $b) => $order[$a['status']] <=> $order[$b['status']]);

        return $files;
    }

    /**
     * Local comparison — diff the working tree against the manifest.
     */
    public function scanChanges(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->refreshSetupState();
        $this->isScanning = true;
        $this->currentStatus = 'scanning';
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->clearScan();

        try {
            $changes = $this->service->getLocalChanges();
            $this->applyChanges($changes);
            $this->currentStatus = 'success';

            $this->dispatch('notify', message: 'تم فحص الملفات المحلية بنجاح.', type: 'success');
        } catch (Throwable $e) {
            $this->currentStatus = 'error';
            $this->errorMessage = $e->getMessage();

            $this->dispatch('notify', message: 'فشل الفحص المحلي: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isScanning = false;
        }
    }

    /**
     * Server comparison — diff the working tree against the actual server files.
     */
    public function scanServerChanges(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->refreshSetupState();
        $this->isScanning = true;
        $this->currentStatus = 'scanning';
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->clearScan();

        try {
            $changes = $this->service->getServerChanges();
            $this->applyChanges($changes);
            $this->currentStatus = 'success';

            $this->dispatch('notify', message: 'تمت مقارنة الملفات مع السيرفر بنجاح.', type: 'success');
        } catch (Throwable $e) {
            $this->currentStatus = 'error';
            $this->errorMessage = $e->getMessage();

            $this->dispatch('notify', message: 'فشلت المقارنة مع السيرفر: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isScanning = false;
        }
    }

    /**
     * Start the deployment with the currently scanned changes.
     */
    public function startDeployment(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->refreshSetupState();

        if (! $this->isServerConfigured) {
            $this->successMessage = 'لم يتم ضبط DEPLOY_SERVER_URL — سيتم تحديث المانيفست محليًا فقط دون رفع إلى سيرفر.';
            $this->dispatch('notify', message: $this->successMessage, type: 'info');
        }

        $pending = array_merge($this->addedFiles, $this->modifiedFiles, $this->deletedFiles);

        if ($pending === []) {
            $this->dispatch('notify', message: 'لا توجد تغييرات لنشرها.', type: 'warning');

            return;
        }

        $this->isDeploying = true;
        $this->currentStatus = 'deploying';
        $this->uploadProgress = 0;
        $this->uploadedCount = 0;
        $this->totalFilesToUpload = count(array_merge($this->addedFiles, $this->modifiedFiles));
        $this->currentFile = null;
        $this->errorMessage = null;
        $this->successMessage = null;

        $record = $this->service->startRecord(auth()->user(), $this->mode);
        $files = array_merge($this->addedFiles, $this->modifiedFiles);
        $total = count($files);

        try {
            $this->service->deploy(
                [
                    'added' => $this->addedFiles,
                    'modified' => $this->modifiedFiles,
                    'removed' => $this->deletedFiles,
                ],
                function (int $done, int $totalCount, ?string $path = null): void {
                    $this->uploadedCount = $done;
                    $this->totalFilesToUpload = $totalCount;
                    $this->currentFile = $path;
                    $this->uploadProgress = $totalCount > 0 ? (int) round($done * 100 / $totalCount) : 100;
                    $this->stream(
                        to: 'progress',
                        content: $this->uploadProgress,
                        replace: true,
                    );
                }
            );

            $this->service->completeRecord(
                $record,
                [
                    'files_count' => $total,
                    'total_size' => $this->totalSize,
                    'files' => $files,
                ],
                notes: $this->notes ?: null,
            );

            $this->uploadProgress = 100;
            $this->currentStatus = 'success';
            $this->successMessage = 'تم نشر '.$total.' ملف بنجاح.';
            $this->dispatch('notify', message: $this->successMessage, type: 'success');
        } catch (Throwable $e) {
            $this->service->failRecord($record, $e->getMessage());
            $this->currentStatus = 'error';
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: 'فشل النشر: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isDeploying = false;
        }
    }

    /**
     * Reset the local manifest so the next scan reports every file as new.
     */
    public function resetManifest(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->service->resetManifest();
        $this->clearScan();
        $this->successMessage = 'تمت إعادة ضبط المانيفست — سيُعتبر كل ملف جديداً في الفحص القادم.';

        $this->dispatch('notify', message: 'تمت إعادة ضبط المانيفست.', type: 'success');
    }

    /**
     * @param  array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>, total_size: int}  $changes
     */
    protected function applyChanges(array $changes): void
    {
        $this->addedFiles = $changes['added'];
        $this->modifiedFiles = $changes['modified'];
        $this->deletedFiles = $changes['removed'];
        $this->totalSize = $changes['total_size'];
    }

    protected function clearScan(): void
    {
        $this->addedFiles = [];
        $this->modifiedFiles = [];
        $this->deletedFiles = [];
        $this->totalSize = 0;
        $this->uploadProgress = 0;
        $this->uploadedCount = 0;
        $this->totalFilesToUpload = 0;
        $this->currentFile = null;
    }

    protected function refreshSetupState(): void
    {
        $this->serverUrl = (string) config('deployment.smart.server_url', '');
        $this->isServerConfigured = $this->service->isServerConfigured();
    }

    #[Computed]
    public function recentDeployments()
    {
        return SmartDeploymentModel::query()
            ->with('user')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    /* ---------------------------------------------------------------------
     | Allowed paths (مسارات النشر)
     | --------------------------------------------------------------------- */

    /**
     * Root-level project entries only: folders and files directly under base_path().
     * Choosing a folder covers everything inside it.
     *
     * @return array<int, array{path: string, label: string, depth: int, type: string}>
     */
    #[Computed]
    public function allowedEntries(): array
    {
        $entries = [];

        foreach (scandir(base_path()) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if ($this->isExcluded($item)) {
                continue;
            }

            $entries[] = [
                'path' => $item,
                'label' => $item,
                'depth' => 0,
                'type' => is_dir(base_path().'/'.$item) ? 'dir' : 'file',
            ];
        }

        return $this->sortEntries($entries);
    }

    /**
     * Root-level entries that exist but are always excluded from deployment.
     * Shown for transparency only — never selectable or saved.
     *
     * @return array<int, array{path: string, label: string, depth: int, type: string}>
     */
    #[Computed]
    public function allowedDisabledEntries(): array
    {
        $entries = [];

        foreach (scandir(base_path()) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (! $this->isExcluded($item)) {
                continue;
            }

            $entries[] = [
                'path' => $item,
                'label' => $item,
                'depth' => 0,
                'type' => is_dir(base_path().'/'.$item) ? 'dir' : 'file',
            ];
        }

        return $this->sortEntries($entries);
    }

    /**
     * Number of checked allowed paths.
     */
    #[Computed]
    public function allowedSelectedCount(): int
    {
        return count($this->allowedSelected);
    }

    public function selectAllPaths(): void
    {
        $this->allowedSelected = array_column($this->allowedEntries(), 'path');
    }

    public function clearAllPaths(): void
    {
        $this->allowedSelected = [];
    }

    public function saveAllowedPaths(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->allowedSaving = true;

        try {
            $validPaths = array_flip(array_column($this->allowedEntries(), 'path'));

            // Reject anything that is not a real project entry.
            $selection = array_values(array_unique(array_filter(
                $this->allowedSelected,
                fn (string $path): bool => isset($validPaths[$path])
            )));

            // A checked folder already covers everything under it — drop children.
            $selection = array_values(array_filter(
                $selection,
                fn (string $path): bool => ! $this->isUnderSelectedDir($path, $selection)
            ));

            sort($selection);

            DeploymentAllowedPath::query()->delete();
            foreach ($selection as $path) {
                DeploymentAllowedPath::create(['path' => $path]);
            }

            $this->dispatch('notify', message: 'تم حفظ '.count($selection).' مسار مسموح.', type: 'success');
        } finally {
            $this->allowedSaving = false;
        }
    }

    /**
     * Load the saved allowed paths into the selection, lifting any deep saved
     * path to its root entry so it appears in the root-level list.
     */
    protected function loadAllowedSelection(): void
    {
        $roots = array_column($this->allowedEntries(), 'path');
        $selected = [];

        foreach (DeploymentPaths::allowed() as $path) {
            $root = explode('/', $path)[0];

            if (in_array($path, $roots, true)) {
                $selected[] = $path;
            } elseif (in_array($root, $roots, true)) {
                $selected[] = $root;
            }
        }

        $this->allowedSelected = array_values(array_unique($selected));
        sort($this->allowedSelected);
    }

    /**
     * Whether the given path sits under another selected directory.
     *
     * @param  array<int, string>  $selection
     */
    protected function isUnderSelectedDir(string $path, array $selection): bool
    {
        foreach ($selection as $candidate) {
            if ($candidate !== $path && Str::startsWith($path, $candidate.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a relative path falls inside any excluded segment.
     */
    protected function isExcluded(string $relative): bool
    {
        foreach (ProjectSnapshot::DEFAULT_EXCLUDES as $exclude) {
            if ($relative === $exclude || Str::startsWith($relative, $exclude.'/')) {
                return true;
            }
        }

        return in_array(basename($relative), ProjectSnapshot::SKIP_FILENAMES, true);
    }

    /**
     * Folders first, then files — both alphabetical.
     *
     * @param  array<int, array{path: string, label: string, depth: int, type: string}>  $entries
     * @return array<int, array{path: string, label: string, depth: int, type: string}>
     */
    protected function sortEntries(array $entries): array
    {
        usort($entries, function (array $a, array $b) {
            if (($a['type'] === 'dir') !== ($b['type'] === 'dir')) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strcmp(mb_strtolower($a['label']), mb_strtolower($b['label']));
        });

        return $entries;
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.smart-deployment', [
            'recentDeployments' => $this->recentDeployments,
            'stats' => $this->computeStats(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeStats(): array
    {
        try {
            return $this->service->getStats();
        } catch (Throwable) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'last_deployment' => null,
                'synced' => false,
                'manifest_exists' => false,
                'manifest_files' => 0,
            ];
        }
    }
}
