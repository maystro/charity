<?php

namespace App\Services\Visits;

use App\Enums\VisitStatus;
use App\Models\Visit;
use App\Models\VisitStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VisitService
{
    public function __construct(
        private readonly VisitNumberGenerator $numberGenerator
    ) {}

    /**
     * Create a new visit.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Visit
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['visit_number'])) {
                $data['visit_number'] = $this->numberGenerator->generate();
            }

            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? VisitStatus::Scheduled->value;
            $data['is_overdue'] = false;

            $visit = Visit::create($data);

            VisitStatusHistory::create([
                'visit_id' => $visit->id,
                'from_status' => null,
                'to_status' => $visit->status,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Update an existing visit.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Visit $visit, array $data): Visit
    {
        return DB::transaction(function () use ($visit, $data) {
            $fromStatus = $visit->status;

            $visit->fill($data);

            // Re-check overdue status on update
            if ($this->shouldBeOverdue($visit)) {
                $visit->is_overdue = true;
            }

            $visit->save();

            // Record status history if status changed
            if ($data['status'] ?? false) {
                $newStatus = is_string($data['status']) ? $data['status'] : $data['status']->value;

                if ($fromStatus !== $newStatus) {
                    VisitStatusHistory::create([
                        'visit_id' => $visit->id,
                        'from_status' => $fromStatus,
                        'to_status' => $newStatus,
                        'changed_by' => Auth::id(),
                        'notes' => $data['status_notes'] ?? null,
                        'created_at' => now(),
                    ]);
                }
            }

            return $visit->fresh();
        });
    }

    /**
     * Execute/complete a visit.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Visit $visit, array $data): Visit
    {
        return DB::transaction(function () use ($visit, $data) {
            $fromStatus = $visit->status;

            $data['status'] = $data['completed'] ?? true
                ? VisitStatus::Completed->value
                : VisitStatus::NotCompleted->value;
            $data['completed_at'] = $data['completed_at'] ?? now();
            $data['completed_by'] = $data['completed_by'] ?? Auth::id();
            $data['is_overdue'] = false;

            $visit->fill($data);
            $visit->save();

            VisitStatusHistory::create([
                'visit_id' => $visit->id,
                'from_status' => $fromStatus,
                'to_status' => $data['status'],
                'changed_by' => Auth::id(),
                'notes' => $data['status_notes'] ?? null,
                'created_at' => now(),
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Cancel a visit.
     */
    public function cancel(Visit $visit, ?string $reason = null): Visit
    {
        return DB::transaction(function () use ($visit, $reason) {
            $fromStatus = $visit->status;

            $visit->update([
                'status' => VisitStatus::Cancelled->value,
                'not_completed_reason' => $reason,
                'is_overdue' => false,
            ]);

            VisitStatusHistory::create([
                'visit_id' => $visit->id,
                'from_status' => $fromStatus,
                'to_status' => VisitStatus::Cancelled->value,
                'changed_by' => Auth::id(),
                'notes' => $reason,
                'created_at' => now(),
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Reschedule a visit to a new date.
     */
    public function reschedule(Visit $visit, string $newScheduledAt): Visit
    {
        return DB::transaction(function () use ($visit, $newScheduledAt) {
            $fromStatus = $visit->status;

            $visit->update([
                'status' => VisitStatus::Scheduled->value,
                'scheduled_at' => $newScheduledAt,
                'is_overdue' => false,
            ]);

            VisitStatusHistory::create([
                'visit_id' => $visit->id,
                'from_status' => $fromStatus,
                'to_status' => VisitStatus::Rescheduled->value,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Determine if a visit should be marked as overdue.
     */
    private function shouldBeOverdue(Visit $visit): bool
    {
        if ($visit->is_overdue) {
            return true;
        }

        if (! in_array($visit->status, VisitStatus::pendingStatuses(), true)) {
            return false;
        }

        if ($visit->scheduled_at === null) {
            return false;
        }

        return $visit->scheduled_at->isPast();
    }
}
