<?php

namespace App\Services\Alerts;

use App\Contracts\Alerts\ScheduledAlertGenerator;
use App\Enums\FamilyStatus;
use App\Models\Alert;
use App\Models\Family;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReAssessmentAlertService implements ScheduledAlertGenerator
{
    /**
     * Scan approved families and generate re-assessment due/overdue alerts.
     *
     * @return array{created: int, updated: int, resolved: int}
     */
    public function generate(): array
    {
        $intervalMonths = (int) SystemSetting::get('reassessment_interval_months', 3);
        $threshold = now()->subMonths($intervalMonths);

        $created = 0;
        $updated = 0;

        $activeAlerts = Alert::activeGroupedByAlertable(Family::class, [
            Alert::TYPE_REASSESSMENT_DUE,
            Alert::TYPE_REASSESSMENT_OVERDUE,
        ]);

        $families = $this->familiesToProcess($threshold, $activeAlerts);

        foreach ($families as $family) {
            $assessment = $family->currentAssessment;

            if (! $assessment || ! $assessment->approved_at) {
                continue;
            }

            $dueAt = $assessment->approved_at->copy()->addMonths($intervalMonths);
            $familyAlerts = $activeAlerts->get($family->id, collect());

            if ($dueAt->isFuture()) {
                $existing = $this->firstReassessmentAlert($familyAlerts);

                if ($existing) {
                    $existing->resolve();
                    $updated++;
                }

                continue;
            }

            $isOverdue = $dueAt->isPast();

            $type = $isOverdue
                ? Alert::TYPE_REASSESSMENT_OVERDUE
                : Alert::TYPE_REASSESSMENT_DUE;

            $severity = $isOverdue
                ? Alert::SEVERITY_CRITICAL
                : Alert::SEVERITY_WARNING;

            $existing = $familyAlerts->firstWhere('type', $type);

            if ($existing) {
                $existing->update([
                    'severity' => $severity,
                    'due_at' => $dueAt,
                    'title' => $this->titleFor($type),
                    'message' => $this->messageFor($family, $type, $dueAt),
                ]);
                $updated++;
            } else {
                if ($isOverdue) {
                    $familyAlerts
                        ->where('type', Alert::TYPE_REASSESSMENT_DUE)
                        ->each(fn (Alert $alert) => $alert->resolve());
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

        return ['created' => $created, 'updated' => $updated, 'resolved' => 0];
    }

    /**
     * @param  Collection<int, Collection<int, Alert>>  $activeAlerts
     * @return \Illuminate\Database\Eloquent\Collection<int, Family>
     */
    protected function familiesToProcess(Carbon $threshold, Collection $activeAlerts): \Illuminate\Database\Eloquent\Collection
    {
        $familyIdsWithActiveAlerts = $activeAlerts->keys()->all();

        return Family::query()
            ->where('status', FamilyStatus::Approved->value)
            ->whereNotNull('current_assessment_id')
            ->where(function (Builder $query) use ($threshold, $familyIdsWithActiveAlerts): void {
                $query->whereHas('currentAssessment', function (Builder $assessment) use ($threshold): void {
                    $assessment->whereNotNull('approved_at')
                        ->where('approved_at', '<=', $threshold);
                });

                if ($familyIdsWithActiveAlerts !== []) {
                    $query->orWhereIn('id', $familyIdsWithActiveAlerts);
                }
            })
            ->with('currentAssessment')
            ->get();
    }

    /**
     * @param  Collection<int, Alert>  $familyAlerts
     */
    protected function firstReassessmentAlert(Collection $familyAlerts): ?Alert
    {
        return $familyAlerts->first(fn (Alert $alert): bool => in_array($alert->type, [
            Alert::TYPE_REASSESSMENT_DUE,
            Alert::TYPE_REASSESSMENT_OVERDUE,
        ], true));
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
