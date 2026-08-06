<?php

namespace App\Livewire\Visits;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'الزيارات والمتابعة'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $visitType = '';

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingVisitType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function todayCount(): int
    {
        return $this->scopedVisitQuery()
            ->today()
            ->count();
    }

    #[Computed]
    public function upcomingCount(): int
    {
        return $this->scopedVisitQuery()
            ->upcoming()
            ->count();
    }

    #[Computed]
    public function overdueCount(): int
    {
        return $this->scopedVisitQuery()
            ->overdue()
            ->count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return $this->scopedVisitQuery()
            ->completed()
            ->count();
    }

    #[Computed]
    public function visits(): LengthAwarePaginator
    {
        return $this->scopedVisitQuery()
            ->with(['family', 'research', 'aidRequest', 'researcher', 'representative'])
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $search): void {
                    $search->whereHas('family', fn (Builder $family) => $family
                        ->where('case_name', 'like', '%'.$this->search.'%')
                        ->orWhere('case_number', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('research', fn (Builder $research) => $research
                            ->where('research_number', 'like', '%'.$this->search.'%'))
                        ->orWhere('visit_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->visitType, fn (Builder $query) => $query->where('visit_type', $this->visitType))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('scheduled_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('scheduled_at', '<=', $this->dateTo))
            ->latest('scheduled_at')
            ->paginate(10);
    }

    protected function scopedVisitQuery(): Builder
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return Visit::query();
        }

        return Visit::query()->where(function (Builder $query) use ($user): void {
            $query->where('created_by', $user?->id)
                ->orWhereHas('family', function (Builder $family) use ($user): void {
                    $family->where('submitted_by', $user?->id)
                        ->orWhere('created_by', $user?->id)
                        ->orWhereHas('fieldworker', fn (Builder $fieldworker) => $fieldworker->where('user_id', $user?->id));
                });
        });
    }

    public function render()
    {
        return view('livewire.pages.visits.index', [
            'visits' => $this->visits,
            'todayCount' => $this->todayCount,
            'upcomingCount' => $this->upcomingCount,
            'overdueCount' => $this->overdueCount,
            'completedCount' => $this->completedCount,
            'statusOptions' => VisitStatus::options(),
            'typeOptions' => VisitType::options(),
        ]);
    }
}
