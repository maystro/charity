<?php

namespace App\Livewire\Deployments;

use App\Models\SmartDeployment as SmartDeploymentModel;
use App\Services\Deployment\SmartDeploymentService;
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

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->refreshSetupState();
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
                function (int $done, int $totalCount, ?string $path = null) use ($total): void {
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
