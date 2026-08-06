<?php

namespace App\Livewire\Deployments;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app', ['title' => 'الصيانة'])]
class Maintenance extends Component
{
    public bool $isClearingCache = false;

    public bool $isUpdatingPackages = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function clearCaches(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isClearingCache = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            Artisan::call('optimize:clear');

            $this->statusMessage = 'تم حذف كاش Laravel والملفات المؤقتة بنجاح.';
            $this->dispatch('notify', message: $this->statusMessage, type: 'success');
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isClearingCache = false;
        }
    }

    public function updatePackages(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isUpdatingPackages = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            $result = Process::path(base_path())
                ->timeout(900)
                ->run('composer update --no-interaction --no-progress --prefer-dist');

            if (! $result->successful()) {
                throw new \RuntimeException(trim($result->errorOutput()) !== '' ? trim($result->errorOutput()) : 'فشل تحديث الحزم.');
            }

            $this->statusMessage = trim($result->output()) !== ''
                ? trim($result->output())
                : 'تم تحديث الحزم بنجاح.';

            $this->dispatch('notify', message: 'تم تحديث الحزم بنجاح.', type: 'success');
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isUpdatingPackages = false;
        }
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.maintenance');
    }
}
