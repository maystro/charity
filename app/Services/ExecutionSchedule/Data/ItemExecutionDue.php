<?php

namespace App\Services\ExecutionSchedule\Data;

use App\Enums\ExecutionDueState;
use App\Models\AidRequestItem;
use Carbon\CarbonInterface;

readonly class ItemExecutionDue
{
    public function __construct(
        public AidRequestItem $item,
        public CarbonInterface $dueDate,
        public ExecutionDueState $state,
    ) {}

    /**
     * أيام حتى الموعد (0 في يوم الموعد، سالب = متأخر).
     */
    public function daysUntilDue(CarbonInterface $today): int
    {
        return (int) $today->copy()->startOfDay()->diffInDays($this->dueDate->copy()->startOfDay(), false);
    }

    public function overdueDays(CarbonInterface $today): int
    {
        if ($this->state !== ExecutionDueState::Overdue) {
            return 0;
        }

        return (int) $this->dueDate->copy()->startOfDay()->diffInDays($today->copy()->startOfDay(), false);
    }
}
