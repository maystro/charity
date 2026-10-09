<?php

namespace App\Services\ExecutionSchedule;

use App\Models\AidRequestItem;

class RecurringExecutionCycle
{
    public function __construct(
        protected ExecutionScheduleResolver $resolver = new ExecutionScheduleResolver,
    ) {}

    /**
     * هل يتبقى للبند الدوري دورة تنفيذ قادمة بعد آخر تسليم؟
     */
    public function hasAnotherCycle(AidRequestItem $item): bool
    {
        if (! $item->isRecurring()) {
            return false;
        }

        return $this->resolver->nextDueDate($item) !== null;
    }
}
