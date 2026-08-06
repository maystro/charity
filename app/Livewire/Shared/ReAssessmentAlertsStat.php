<?php

namespace App\Livewire\Shared;

use App\Enums\FamilyStatus;
use App\Models\Alert;
use App\Models\Family;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Stat tile for the top bar showing the count of approved families
 * whose re-assessment is due or overdue.
 *
 * Shows: icon + number + label.
 * On hover/click: dropdown with the top due/overdue families.
 */
class ReAssessmentAlertsStat extends Component
{
    public function render(): View
    {
        return view('livewire.shared.reassessment-alerts-stat');
    }

    /**
     * Count of approved families whose re-assessment is due or overdue.
     */
    #[Computed]
    public function dueCount(): int
    {
        return $this->dueFamiliesQuery()
            ->count();
    }

    /**
     * Count of overdue approved families.
     */
    #[Computed]
    public function overdueCount(): int
    {
        $intervalMonths = $this->reassessmentIntervalMonths();

        return $this->dueFamiliesQuery()
            ->get()
            ->filter(function (Family $family) use ($intervalMonths): bool {
                return $family->currentAssessment?->approved_at?->copy()
                    ->addMonths($intervalMonths)
                    ?->isPast() ?? false;
            })
            ->count();
    }

    /**
     * Top 5 families that are due/overdue, ordered by approval date (oldest first).
     *
     * @return Collection<int, Family>
     */
    #[Computed]
    public function topDueFamilies()
    {
        return $this->dueFamiliesQuery()
            ->get()
            ->sortBy(fn (Family $f) => $f->currentAssessment?->approved_at)
            ->take(5)
            ->values();
    }

    /**
     * Active alerts count (cross-check with the alerts table).
     */
    #[Computed]
    public function activeAlertsCount(): int
    {
        return Alert::active()
            ->whereIn('type', [
                Alert::TYPE_REASSESSMENT_DUE,
                Alert::TYPE_REASSESSMENT_OVERDUE,
            ])
            ->count();
    }

    public function viewAll(): void
    {
        $this->redirectRoute('alerts.index', navigate: true);
    }

    protected function dueFamiliesQuery(): Builder
    {
        $threshold = now()->subMonths($this->reassessmentIntervalMonths());

        return Family::with('currentAssessment')
            ->where('status', FamilyStatus::Approved->value)
            ->whereNotNull('current_assessment_id')
            ->whereHas('currentAssessment', function (Builder $query) use ($threshold): void {
                $query->whereNotNull('approved_at')
                    ->where('approved_at', '<=', $threshold);
            });
    }

    protected function reassessmentIntervalMonths(): int
    {
        return (int) SystemSetting::get('reassessment_interval_months', 3);
    }
}
