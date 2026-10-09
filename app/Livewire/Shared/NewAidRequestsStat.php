<?php

namespace App\Livewire\Shared;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Support\TopBarStatCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Stat tile for the top bar showing the count of new aid requests awaiting review.
 */
class NewAidRequestsStat extends Component
{
    public int $cacheGeneration = 0;

    public function render(): View
    {
        return view('livewire.shared.new-aid-requests-stat');
    }

    #[On('topbar-stats-cache-cleared')]
    public function onTopBarCacheCleared(): void
    {
        unset($this->snapshot, $this->newRequestsCount, $this->topNewRequests);
        $this->cacheGeneration++;
    }

    /**
     * @return array{count: int, top: Collection<int, AidRequest>}
     */
    #[Computed]
    public function snapshot(): array
    {
        return app(TopBarStatCache::class)->remember('new_aid_requests', function (): array {
            $query = $this->newRequestsQuery();

            return [
                'count' => $query->count(),
                'top' => (clone $query)->with('family')->orderBy('created_at')->limit(5)->get(),
            ];
        });
    }

    #[Computed]
    public function newRequestsCount(): int
    {
        return $this->snapshot['count'];
    }

    /**
     * @return Collection<int, AidRequest>
     */
    #[Computed]
    public function topNewRequests(): Collection
    {
        return $this->snapshot['top'];
    }

    protected function newRequestsQuery(): Builder
    {
        $query = AidRequest::query()
            ->whereIn('status', AidRequestStatus::underReviewStatuses());

        $user = auth()->user();

        if ($user && $user->isFieldworker()) {
            $query->where('submitted_by', $user->id);
        }

        return $query;
    }
}
