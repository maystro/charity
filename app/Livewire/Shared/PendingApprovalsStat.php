<?php

namespace App\Livewire\Shared;

use App\Enums\FamilyStatus;
use App\Models\Family;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Stat tile for the top bar showing the count of families/cases
 * awaiting approval (under_review + needs_completion).
 *
 * Shows: icon + number + label ("مطلوب اعتماد X حالات").
 * On click: dropdown with the top pending families and a link to the index.
 */
class PendingApprovalsStat extends Component
{
    public function render(): View
    {
        return view('livewire.shared.pending-approvals-stat');
    }

    /**
     * Count of families awaiting approval.
     */
    #[Computed]
    public function pendingCount(): int
    {
        return $this->pendingFamiliesQuery()->count();
    }

    /**
     * Top 5 pending families, ordered by creation date (oldest first).
     *
     * @return Collection<int, Family>
     */
    #[Computed]
    public function topPendingFamilies()
    {
        return $this->pendingFamiliesQuery()
            ->orderBy('created_at')
            ->take(5)
            ->get();
    }

    protected function pendingFamiliesQuery(): Builder
    {
        return Family::query()
            ->whereIn('status', [
                FamilyStatus::UnderReview->value,
                FamilyStatus::NeedsCompletion->value,
            ]);
    }
}
