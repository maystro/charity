<?php

namespace App\Livewire\Shared;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Stat tile for the top bar showing the count of new aid requests
 * awaiting review (submitted / needs_completion / under_review).
 *
 * Shows: icon + number + label ("X طلبات مساعدة جديدة").
 * On click: dropdown with the top new requests and a link to the index.
 */
class NewAidRequestsStat extends Component
{
    public function render(): View
    {
        return view('livewire.shared.new-aid-requests-stat');
    }

    /**
     * Count of new aid requests awaiting review.
     */
    #[Computed]
    public function newRequestsCount(): int
    {
        return $this->newRequestsQuery()->count();
    }

    /**
     * Top 5 new requests, ordered by creation date (oldest first).
     *
     * @return Collection<int, AidRequest>
     */
    #[Computed]
    public function topNewRequests()
    {
        return $this->newRequestsQuery()
            ->with('family')
            ->orderBy('created_at')
            ->take(5)
            ->get();
    }

    protected function newRequestsQuery(): Builder
    {
        $query = AidRequest::query()
            ->whereIn('status', AidRequestStatus::underReviewStatuses());

        // المندوب يرى الطلبات التي قدّمها هو فقط، والمشرف يرى الكل.
        $user = auth()->user();

        if ($user && $user->isFieldworker()) {
            $query->where('submitted_by', $user->id);
        }

        return $query;
    }
}
