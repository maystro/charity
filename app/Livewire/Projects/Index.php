<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'المشروعات'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public string $search = '';

    public string $sort = 'newest';

    public function delete(int $id): void
    {
        /** @var Project $project */
        $project = Project::findOrFail($id);
        $project->delete();

        $this->dispatch('notify', message: 'تم حذف المشروع بنجاح', type: 'success');
    }

    #[Computed]
    public function statuses(): array
    {
        return ProjectStatus::options();
    }

    protected function activeStatus(): ?ProjectStatus
    {
        return ProjectStatus::tryFrom($this->statusFilter);
    }

    public function render(): View
    {
        $projects = Project::query()
            ->with(['phases' => fn ($q) => $q->orderBy('sort_order')])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('governorate', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->activeStatus(), fn ($q, $s) => $q->where('status', $s->value))
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('created_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sort === 'budget_desc', fn ($q) => $q->orderByDesc('total_budget'))
            ->when($this->sort === 'budget_asc', fn ($q) => $q->orderBy('total_budget'))
            ->paginate(10);

        return view('livewire.pages.projects.index', [
            'projects' => $projects,
            'statuses' => $this->statuses,
        ]);
    }
}
