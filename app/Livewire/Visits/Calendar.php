<?php

namespace App\Livewire\Visits;

use App\Enums\VisitType;
use App\Models\Visit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تقويم الزيارات'])]
class Calendar extends Component
{
    public string $currentMonth;

    public string $visitType = '';

    public ?int $researcher_id = null;

    public function mount(): void
    {
        $this->currentMonth = now()->format('Y-m');
    }

    public function prevMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->format('Y-m');
    }

    #[Computed]
    public function visits(): Collection
    {
        $start = Carbon::parse($this->currentMonth.'-01')->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = Carbon::parse($this->currentMonth.'-01')->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        return $this->scopedVisitQuery()
            ->whereBetween('scheduled_at', [$start, $end])
            ->when($this->visitType, fn (Builder $query) => $query->where('visit_type', $this->visitType))
            ->when($this->researcher_id, fn (Builder $query) => $query->where('researcher_id', $this->researcher_id))
            ->get()
            ->groupBy(fn (Visit $visit) => $visit->scheduled_at?->format('Y-m-d'));
    }

    #[Computed]
    public function days(): Collection
    {
        $start = Carbon::parse($this->currentMonth.'-01')->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = Carbon::parse($this->currentMonth.'-01')->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $days = collect();
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $days->push($date->copy());
        }

        return $days;
    }

    #[Computed]
    public function monthLabel(): string
    {
        $date = Carbon::parse($this->currentMonth.'-01');

        return $date->translatedFormat('F Y');
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
        return view('livewire.pages.visits.calendar', [
            'visitsByDate' => $this->visits,
            'days' => $this->days,
            'monthLabel' => $this->monthLabel,
            'typeOptions' => VisitType::options(),
        ]);
    }
}
