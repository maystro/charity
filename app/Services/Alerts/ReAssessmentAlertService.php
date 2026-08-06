<?php

namespace App\Services\Alerts;

use App\Enums\FamilyStatus;
use App\Models\Alert;
use App\Models\Family;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;

class ReAssessmentAlertService
{
    /**
     * Scan approved families and generate re-assessment due/overdue alerts.
     *
     * @return array{created: int, updated: int}
     */
    public function generate(): array
    {
        $intervalMonths = (int) SystemSetting::get('reassessment_interval_months', 3);
        $threshold = now()->subMonths($intervalMonths);

        $created = 0;
        $updated = 0;

        // Approved families with a current assessment that has approved_at
        $families = Family::where('status', FamilyStatus::Approved->value)
            ->whereNotNull('current_assessment_id')
            ->with('currentAssessment')
            ->get();

        foreach ($families as $family) {
            $assessment = $family->currentAssessment;

            if (! $assessment || ! $assessment->approved_at) {
                continue;
            }

            $dueAt = $assessment->approved_at->copy()->addMonths($intervalMonths);

            // Skip if not yet due
            if ($dueAt->isFuture()) {
                // If there's an existing active alert that is now not yet due (e.g. interval changed), resolve it
                $existing = Alert::active()
                    ->forType(Alert::TYPE_REASSESSMENT_DUE)
                    ->forAlertable($family)
                    ->first();

                if (! $existing) {
                    $existing = Alert::active()
                        ->forType(Alert::TYPE_REASSESSMENT_OVERDUE)
                        ->forAlertable($family)
                        ->first();
                }

                if ($existing) {
                    $existing->resolve();
                    $updated++;
                }

                continue;
            }

            // Determine if overdue (due date passed)
            $isOverdue = $dueAt->isPast();

            $type = $isOverdue
                ? Alert::TYPE_REASSESSMENT_OVERDUE
                : Alert::TYPE_REASSESSMENT_DUE;

            $severity = $isOverdue
                ? Alert::SEVERITY_CRITICAL
                : Alert::SEVERITY_WARNING;

            // Check for existing active alert of this type for this family
            $existing = Alert::active()
                ->forType($type)
                ->forAlertable($family)
                ->first();

            if ($existing) {
                // Update due_at if needed and ensure severity/title/message are current
                $existing->update([
                    'severity' => $severity,
                    'due_at' => $dueAt,
                    'title' => $this->titleFor($type),
                    'message' => $this->messageFor($family, $type, $dueAt),
                ]);
                $updated++;
            } else {
                // If upgrading from due to overdue, resolve the old due alert
                if ($isOverdue) {
                    Alert::active()
                        ->forType(Alert::TYPE_REASSESSMENT_DUE)
                        ->forAlertable($family)
                        ->get()
                        ->each(fn ($a) => $a->resolve());
                }

                Alert::create([
                    'type' => $type,
                    'title' => $this->titleFor($type),
                    'message' => $this->messageFor($family, $type, $dueAt),
                    'severity' => $severity,
                    'status' => Alert::STATUS_ACTIVE,
                    'alertable_type' => Family::class,
                    'alertable_id' => $family->id,
                    'due_at' => $dueAt,
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    protected function titleFor(string $type): string
    {
        return match ($type) {
            Alert::TYPE_REASSESSMENT_OVERDUE => 'تأخر إعادة التقييم',
            default => 'حان موعد إعادة التقييم',
        };
    }

    protected function messageFor(Family $family, string $type, Carbon $dueAt): string
    {
        $caseName = $family->case_name ?? $family->case_number;
        $dueDate = $dueAt->format('Y-m-d');

        return match ($type) {
            Alert::TYPE_REASSESSMENT_OVERDUE => "الأسرة \"{$caseName}\" تجاوزت موعد إعادة التقييم ({$dueDate}). يرجى البدء بإعادة التقييم فورًا.",
            default => "الأسرة \"{$caseName}\" يحين موعد إعادة تقييمها في {$dueDate}.",
        };
    }
}
