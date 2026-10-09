<?php

namespace App\Services\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AidRequestStatusCounts
{
    /**
     * @param  Builder<AidRequest>|null  $query
     * @return Collection<string, int>
     */
    public function countsByStatus(?Builder $query = null): Collection
    {
        $query ??= AidRequest::query();

        return $query
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);
    }

    /**
     * @param  Collection<string, int>  $byStatus
     * @param  array<int, string>  $statuses
     */
    public function sumForStatuses(Collection $byStatus, array $statuses): int
    {
        $total = 0;

        foreach ($statuses as $status) {
            $total += (int) ($byStatus[$status] ?? 0);
        }

        return $total;
    }

    /**
     * @param  Builder<AidRequest>|null  $query
     * @return array{under_review: int, approved: int}
     */
    public function reviewTabCounts(?Builder $query = null): array
    {
        $byStatus = $this->countsByStatus($query);

        return [
            'under_review' => $this->sumForStatuses($byStatus, AidRequestStatus::underReviewStatuses()),
            'approved' => $this->sumForStatuses($byStatus, AidRequestStatus::approvedStatuses()),
        ];
    }

    /**
     * @param  Builder<AidRequest>|null  $query
     * @return array{ready: int, in_execution: int, pending_review: int, delivered: int, overdue: int}
     */
    public function deliveryTabCounts(?Builder $query = null): array
    {
        $base = $query ?? AidRequest::query();

        $trackedStatuses = array_unique([
            ...AidRequestStatus::approvedStatuses(),
            AidRequestStatus::InExecution->value,
            AidRequestStatus::PendingDeliveryReview->value,
            AidRequestStatus::Delivered->value,
        ]);

        $byStatus = $this->countsByStatus(
            (clone $base)->whereIn('status', $trackedStatuses)
        );

        $overdue = (clone $base)
            ->where('status', AidRequestStatus::InExecution->value)
            ->whereNotNull('needed_by')
            ->where('needed_by', '<', now()->toDateString())
            ->count();

        return [
            'ready' => $this->sumForStatuses($byStatus, AidRequestStatus::approvedStatuses()),
            'in_execution' => (int) ($byStatus[AidRequestStatus::InExecution->value] ?? 0),
            'pending_review' => (int) ($byStatus[AidRequestStatus::PendingDeliveryReview->value] ?? 0),
            'delivered' => (int) ($byStatus[AidRequestStatus::Delivered->value] ?? 0),
            'overdue' => $overdue,
        ];
    }
}
