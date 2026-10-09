<?php

namespace App\Livewire\Shared;

use App\Enums\ExecutionDueState;
use App\Models\AidRequest;
use App\Services\ExecutionSchedule\UpcomingExecutionQuery;
use App\Support\TopBarStatCache;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Stat tile: aid requests with execution due within the configured lead window or overdue.
 */
class UpcomingExecutionStat extends Component
{
    public int $cacheGeneration = 0;

    public function render(): View
    {
        return view('livewire.shared.upcoming-execution-stat');
    }

    #[On('topbar-stats-cache-cleared')]
    public function onTopBarCacheCleared(): void
    {
        unset($this->dueRequests, $this->requestsCount, $this->overdueCount, $this->topRequests);
        $this->cacheGeneration++;
    }

    /**
     * @return Collection<int, array{request: AidRequest, due_date: CarbonInterface, items: Collection, state: ExecutionDueState}>
     */
    #[Computed]
    public function dueRequests(): Collection
    {
        $user = auth()->user();

        return app(TopBarStatCache::class)->remember('execution_due_requests', function () use ($user): Collection {
            return app(UpcomingExecutionQuery::class)
                ->requestsDueForReminder(leadDays: null, user: $user);
        });
    }

    #[Computed]
    public function requestsCount(): int
    {
        return $this->dueRequests->count();
    }

    #[Computed]
    public function overdueCount(): int
    {
        return $this->dueRequests
            ->filter(fn (array $row): bool => $row['state'] === ExecutionDueState::Overdue)
            ->count();
    }

    /**
     * @return Collection<int, array{request: AidRequest, due_date: CarbonInterface, items: Collection<int, mixed>, state: ExecutionDueState}>
     */
    #[Computed]
    public function topRequests(): Collection
    {
        return $this->dueRequests->take(5);
    }
}
