<?php

namespace App\Livewire\Research;

use App\Models\SocialResearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'البحوث الميدانية'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function researches(): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->with(['family', 'creator'])
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $search): void {
                    $search->where('research_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('family', fn (Builder $family) => $family
                            ->where('case_name', 'like', '%'.$this->search.'%')
                            ->orWhere('case_number', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn (Builder $query) => $query->where('status', $this->status))
            ->latest('conducted_at')
            ->paginate(10);
    }

    protected function scopedQuery(): Builder
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return SocialResearch::query();
        }

        return SocialResearch::query()->whereHas('family', function (Builder $family) use ($user): void {
            $family->where('submitted_by', $user?->id)
                ->orWhere('created_by', $user?->id)
                ->orWhereHas('fieldworker', fn (Builder $fieldworker) => $fieldworker->where('user_id', $user?->id));
        });
    }

    public function render(): View
    {
        return view('livewire.research.index', ['researches' => $this->researches]);
    }
}
