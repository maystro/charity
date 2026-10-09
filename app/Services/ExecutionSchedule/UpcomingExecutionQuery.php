<?php

namespace App\Services\ExecutionSchedule;

use App\Enums\ExecutionDueState;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ExecutionSchedule\Data\ItemExecutionDue;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UpcomingExecutionQuery
{
    public function __construct(
        protected ExecutionScheduleResolver $resolver = new ExecutionScheduleResolver,
    ) {}

    public function leadDays(): int
    {
        return max(0, (int) SystemSetting::get('execution_reminder_lead_days', 3));
    }

    /**
     * @return Collection<int, ItemExecutionDue>
     */
    public function itemsDueForReminder(?int $leadDays = null, ?User $user = null, ?CarbonInterface $today = null): Collection
    {
        $leadDays ??= $this->leadDays();
        $today = $today ?? now();

        return $this->reminderWindowQuery($leadDays, $user)
            ->with(['aidRequest.family'])
            ->get()
            ->map(fn (AidRequestItem $item) => $this->resolver->resolveReminder($item, $leadDays, $today))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array{request: AidRequest, due_date: CarbonInterface, items: Collection<int, ItemExecutionDue>, state: ExecutionDueState}>
     */
    public function requestsDueForReminder(?int $leadDays = null, ?User $user = null, ?CarbonInterface $today = null): Collection
    {
        $items = $this->itemsDueForReminder($leadDays, $user, $today);

        return $items
            ->groupBy(fn (ItemExecutionDue $due) => $due->item->aid_request_id)
            ->map(function (Collection $group) {
                /** @var Collection<int, ItemExecutionDue> $group */
                $request = $group->first()->item->aidRequest;
                $earliest = $group->min(fn (ItemExecutionDue $due) => $due->dueDate->getTimestamp());
                $dueDate = $group->first(fn (ItemExecutionDue $due) => $due->dueDate->getTimestamp() === $earliest)->dueDate;
                $hasOverdue = $group->contains(fn (ItemExecutionDue $due) => $due->state === ExecutionDueState::Overdue);

                return [
                    'request' => $request,
                    'due_date' => $dueDate,
                    'items' => $group->values(),
                    'state' => $hasOverdue ? ExecutionDueState::Overdue : ExecutionDueState::Upcoming,
                ];
            })
            ->sortBy(fn (array $row) => $row['due_date']->getTimestamp())
            ->values();
    }

    public function countRequestsDueForReminder(?int $leadDays = null, ?User $user = null, ?CarbonInterface $today = null): int
    {
        return $this->requestsDueForReminder($leadDays, $user, $today)->count();
    }

    /**
     * @return Collection<int, AidRequestItem>
     */
    public function eligibleItems(?User $user = null): Collection
    {
        return $this->baseItemQuery($user)
            ->with(['aidRequest'])
            ->get();
    }

    /**
     * @return Builder<AidRequestItem>
     */
    protected function baseItemQuery(?User $user): Builder
    {
        $query = AidRequestItem::query()
            ->where('approved', true)
            ->whereHas('aidRequest', function (Builder $request) use ($user): void {
                $request->whereIn('status', ExecutionScheduleResolver::eligibleRequestStatuses());

                if ($user?->isFieldworker()) {
                    $request->where('submitted_by', $user->id);
                }
            });

        return $query;
    }

    /**
     * @return Builder<AidRequestItem>
     */
    protected function reminderWindowQuery(?int $leadDays, ?User $user): Builder
    {
        $leadDays ??= $this->leadDays();
        $windowEnd = now()->addDays($leadDays)->toDateString();

        return $this->baseItemQuery($user)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $windowEnd);
    }
}
