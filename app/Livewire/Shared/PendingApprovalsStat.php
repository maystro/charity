<?php

namespace App\Livewire\Shared;

use App\Enums\FamilyStatus;
use App\Models\Family;
use App\Support\TopBarStatCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Stat tile for the top bar showing the count of families/cases
 * awaiting approval (under_review + needs_completion).
 */
class PendingApprovalsStat extends Component
{
    public int $cacheGeneration = 0;

    public function render(): View
    {
        return view('livewire.shared.pending-approvals-stat');
    }

    #[On('topbar-stats-cache-cleared')]
    public function onTopBarCacheCleared(): void
    {
        unset($this->snapshot, $this->pendingCount, $this->topPendingFamilies);
        $this->cacheGeneration++;
    }

    /**
     * @return array{count: int, top: Collection<int, Family>}
     */
    #[Computed]
    public function snapshot(): array
    {
        return app(TopBarStatCache::class)->remember('pending_approvals', function (): array {
            $query = $this->pendingFamiliesQuery();

            return [
                'count' => $query->count(),
                'top' => (clone $query)->orderBy('created_at')->limit(5)->get(),
            ];
        });
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->snapshot['count'];
    }

    /**
     * @return Collection<int, Family>
     */
    #[Computed]
    public function topPendingFamilies(): Collection
    {
        return $this->snapshot['top'];
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
