<?php

namespace App\Livewire\Deployments;

use App\Models\Release;
use App\Services\Deployment\ReleaseService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'الإصدارات والنشر'])]
class Index extends Component
{
    use WithPagination;

    public string $filter = '';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function deleteRelease(int $id): void
    {
        $release = Release::findOrFail($id);

        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        try {
            app(ReleaseService::class)->delete($release);
            $this->dispatch('notify', message: 'تم حذف الإصدار.', type: 'success');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    #[Computed]
    public function releases()
    {
        return Release::query()
            ->when($this->filter !== '', fn ($q) => $q->where('status', $this->filter))
            ->withCount('changes', 'deployments')
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.index', [
            'releases' => $this->releases,
        ]);
    }
}
