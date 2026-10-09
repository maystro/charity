<?php

namespace App\Observers;

use App\Models\AidRequestItem;
use App\Services\ExecutionSchedule\ExecutionNextDueDateSync;

class AidRequestItemObserver
{
    /**
     * @var array<int, string>
     */
    protected const SCHEDULE_ATTRIBUTES = [
        'approved',
        'delivered',
        'delivery_date',
        'execution_start_date',
        'recurrence_start',
        'recurrence_end',
        'recurrence_interval_days',
        'execution_type',
        'recurrence_type',
    ];

    public function saved(AidRequestItem $item): void
    {
        if (! $item->wasRecentlyCreated && ! $item->wasChanged(self::SCHEDULE_ATTRIBUTES)) {
            return;
        }

        app(ExecutionNextDueDateSync::class)->sync($item);
    }
}
