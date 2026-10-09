<?php

namespace App\Services\ExecutionSchedule;

use App\Models\AidRequest;
use App\Models\AidRequestItem;
use Illuminate\Database\Eloquent\Builder;

class ExecutionNextDueDateSync
{
    public function __construct(
        protected ExecutionScheduleResolver $resolver = new ExecutionScheduleResolver,
    ) {}

    public function sync(AidRequestItem $item): void
    {
        $item->loadMissing('aidRequest');

        $due = $this->resolver->nextDueDate($item);
        $dateString = $due?->toDateString();

        $current = $item->next_due_date?->toDateString();

        if ($current === $dateString) {
            return;
        }

        $item->forceFill(['next_due_date' => $dateString])->saveQuietly();
    }

    public function syncForRequest(AidRequest $request): void
    {
        $request->items()->each(fn (AidRequestItem $item) => $this->sync($item));
    }

    public function syncAll(?Builder $query = null): int
    {
        $query ??= AidRequestItem::query()->with('aidRequest');
        $updated = 0;

        $query->orderBy('id')->chunkById(100, function ($items) use (&$updated): void {
            foreach ($items as $item) {
                $before = $item->next_due_date?->toDateString();
                $this->sync($item);
                $item->refresh();
                if ($item->next_due_date?->toDateString() !== $before) {
                    $updated++;
                }
            }
        });

        return $updated;
    }
}
