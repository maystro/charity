<?php

namespace App\Services\ExecutionSchedule;

use App\Enums\AidRequestStatus;
use App\Enums\ExecutionDueState;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Services\ExecutionSchedule\Data\ItemExecutionDue;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ExecutionScheduleResolver
{
    /**
     * @return array<int, string>
     */
    public static function eligibleRequestStatuses(): array
    {
        return [
            AidRequestStatus::Approved->value,
            AidRequestStatus::PartiallyApproved->value,
            AidRequestStatus::InExecution->value,
            AidRequestStatus::PendingDeliveryReview->value,
        ];
    }

    public function nextDueDate(AidRequestItem $item, ?CarbonInterface $today = null): ?CarbonInterface
    {
        $today = $this->normalizeDay($today ?? now());

        if (! $item->approved) {
            return null;
        }

        $request = $item->relationLoaded('aidRequest') ? $item->aidRequest : $item->aidRequest()->first();

        if (! $request instanceof AidRequest || ! $this->requestIsEligible($request)) {
            return null;
        }

        if (! $item->isRecurring()) {
            if ($item->delivered) {
                return null;
            }

            $due = $this->resolveOneTimeDueDate($item, $request);

            return $due ? $this->normalizeDay($due) : null;
        }

        return $this->resolveRecurringDueDate($item, $request);
    }

    public function resolveReminder(
        AidRequestItem $item,
        int $leadDays,
        ?CarbonInterface $today = null,
    ): ?ItemExecutionDue {
        $today = $this->normalizeDay($today ?? now());
        $due = $this->nextDueDate($item, $today);

        if ($due === null) {
            return null;
        }

        $state = $this->resolveState($due, $leadDays, $today);

        if ($state === null) {
            return null;
        }

        return new ItemExecutionDue($item, $due, $state);
    }

    public function isInReminderWindow(
        AidRequestItem $item,
        int $leadDays,
        ?CarbonInterface $today = null,
    ): bool {
        return $this->resolveReminder($item, $leadDays, $today) !== null;
    }

    protected function requestIsEligible(AidRequest $request): bool
    {
        return in_array($request->status, self::eligibleRequestStatuses(), true);
    }

    protected function resolveOneTimeDueDate(AidRequestItem $item, AidRequest $request): ?CarbonInterface
    {
        $base = $item->execution_start_date
            ?? $item->recurrence_start
            ?? $request->needed_by;

        return $base ? Carbon::parse($base) : null;
    }

    protected function resolveRecurringDueDate(AidRequestItem $item, AidRequest $request): ?CarbonInterface
    {
        $start = $item->execution_start_date
            ?? $item->recurrence_start
            ?? $request->needed_by;

        if ($start === null) {
            return null;
        }

        $intervalDays = max(1, (int) ($item->recurrence_interval_days ?? 30));

        if ($item->delivery_date !== null) {
            $due = Carbon::parse($item->delivery_date)->addDays($intervalDays);
        } else {
            $due = Carbon::parse($start);
        }

        $due = $this->normalizeDay($due);

        if ($item->recurrence_end !== null) {
            $end = $this->normalizeDay(Carbon::parse($item->recurrence_end));
            if ($due->greaterThan($end)) {
                return null;
            }
        }

        return $due;
    }

    protected function resolveState(
        CarbonInterface $due,
        int $leadDays,
        CarbonInterface $today,
    ): ?ExecutionDueState {
        $dueDay = $this->normalizeDay($due);
        $windowStart = $dueDay->copy()->subDays(max(0, $leadDays));

        if ($today->greaterThan($dueDay)) {
            return ExecutionDueState::Overdue;
        }

        if ($today->greaterThanOrEqualTo($windowStart) && $today->lessThanOrEqualTo($dueDay)) {
            return ExecutionDueState::Upcoming;
        }

        return null;
    }

    protected function normalizeDay(CarbonInterface $date): CarbonInterface
    {
        return Carbon::parse($date)->startOfDay();
    }
}
