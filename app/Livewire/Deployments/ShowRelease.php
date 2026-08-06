<?php

namespace App\Livewire\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Models\Release;
use App\Services\Deployment\DeploymentFtpSettings;
use App\Services\Deployment\DeploymentService;
use App\Services\Deployment\ReleaseService;
use App\Services\Deployment\UploadPackageService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تفاصيل الإصدار'])]
class ShowRelease extends Component
{
    public Release $release;

    public bool $showDeployModal = false;

    public string $deployEnvironment = '';

    public bool $preparingPackage = false;

    /** @var array{filename: string, count: int, missing: array<int, string>, removed: array<int, string>}|null */
    public ?array $lastPackage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->loadDeployments();
    }

    /**
     * Whether any deployment is still pending or in progress.
     */
    #[Computed]
    public function hasActiveDeployment(): bool
    {
        return $this->release->deployments
            ->contains(
                fn ($deployment) => in_array($deployment->status, [DeploymentStatus::Pending, DeploymentStatus::InProgress], true)
            );
    }

    /**
     * Whether FTP credentials exist so the upload step can run.
     */
    #[Computed]
    public function isFtpConfigured(): bool
    {
        return app(DeploymentFtpSettings::class)->isConfigured();
    }

    /**
     * Polling target: refreshes deployments and their step progress only.
     */
    public function refreshDeployments(): void
    {
        $this->loadDeployments();
    }

    protected function loadDeployments(): void
    {
        $this->release->load('changes', 'deployments.creator', 'deployments.steps', 'creator');
    }

    public function publish(): void
    {
        try {
            app(ReleaseService::class)->publish($this->release);
            $this->release->refresh();
            $this->dispatch('notify', message: 'تم اعتماد الإصدار ونشره.', type: 'success');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function rollBack(): void
    {
        try {
            app(ReleaseService::class)->rollBack($this->release);
            $this->release->refresh();
            $this->dispatch('notify', message: 'تم التراجع عن الإصدار.', type: 'success');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function openDeployModal(): void
    {
        $this->deployEnvironment = '';
        $this->showDeployModal = true;
        $this->dispatch('open-modal', 'deploy-modal');
    }

    public function closeDeployModal(): void
    {
        $this->showDeployModal = false;
        $this->deployEnvironment = '';
        $this->dispatch('close-modal', 'deploy-modal');
    }

    public function deploy(): void
    {
        $this->validate([
            'deployEnvironment' => ['required', Rule::enum(DeploymentEnvironment::class)],
        ]);

        try {
            app(DeploymentService::class)->queue(
                $this->release,
                DeploymentEnvironment::from($this->deployEnvironment),
                auth()->user()
            );

            $this->closeDeployModal();
            $this->loadDeployments();
            $this->dispatch('notify', message: 'تمت جدولة عملية النشر بنجاح.', type: 'success');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Build a single ZIP with every changed file of this release and download it.
     */
    public function prepareUploadPackage(): mixed
    {
        $this->preparingPackage = true;

        try {
            $result = app(UploadPackageService::class)->build($this->release);

            $message = 'تم تجهيز حزمة الرفع ('.$result['count'].' ملف).';

            if ($result['removed'] !== []) {
                $message .= ' توجد '.count($result['removed']).' ملفات محذوفة مذكورة داخل الحزمة.';
            }

            if ($result['missing'] !== []) {
                $message .= ' تحذير: '.count($result['missing']).' ملف غير موجود في المشروع ولن يُضمّن.';
            }

            $this->lastPackage = $result;
            $this->dispatch('notify', message: $message, type: 'success');

            return response()->download($result['path'], $result['filename']);
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        } finally {
            $this->preparingPackage = false;
        }
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.show', [
            'environments' => DeploymentEnvironment::cases(),
        ]);
    }
}