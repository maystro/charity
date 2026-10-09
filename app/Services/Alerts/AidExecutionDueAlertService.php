<?php

namespace App\Services\Alerts;

use App\Contracts\Alerts\ScheduledAlertGenerator;
use App\Enums\ExecutionDueState;
use App\Models\AidRequestItem;
use App\Models\Alert;
use App\Services\ExecutionSchedule\Data\ItemExecutionDue;
use App\Services\ExecutionSchedule\ExecutionScheduleResolver;
use App\Services\ExecutionSchedule\UpcomingExecutionQuery;
use Carbon\Carbon;

class AidExecutionDueAlertService implements ScheduledAlertGenerator
{
    public function __construct(
        protected UpcomingExecutionQuery $query = new UpcomingExecutionQuery,
        protected ExecutionScheduleResolver $resolver = new ExecutionScheduleResolver,
    ) {}

    /**
     * @return array{created: int, updated: int, resolved: int}
     */
    public function generate(): array
    {
        $leadDays = $this->query->leadDays();
        $today = now();
        $created = 0;
        $updated = 0;
        $resolved = 0;

        $activeAlerts = Alert::activeGroupedByAlertable(AidRequestItem::class, [
            Alert::TYPE_AID_EXECUTION_DUE,
            Alert::TYPE_AID_EXECUTION_OVERDUE,
        ]);

        foreach ($this->query->eligibleItems() as $item) {
            $reminder = $this->resolver->resolveReminder($item, $leadDays, $today);

            $existing = $activeAlerts->get($item->id)?->first();

            if ($reminder === null) {
                if ($existing) {
                    $existing->resolve();
                    $resolved++;
                }

                continue;
            }

            $type = $reminder->state === ExecutionDueState::Overdue
                ? Alert::TYPE_AID_EXECUTION_OVERDUE
                : Alert::TYPE_AID_EXECUTION_DUE;

            $severity = $reminder->state === ExecutionDueState::Overdue
                ? Alert::SEVERITY_CRITICAL
                : Alert::SEVERITY_WARNING;

            if ($existing && $existing->type !== $type) {
                $existing->resolve();
                $existing = null;
                $resolved++;
            }

            $payload = [
                'type' => $type,
                'title' => $this->titleFor($type),
                'message' => $this->messageFor($reminder),
                'severity' => $severity,
                'due_at' => Carbon::parse($reminder->dueDate)->startOfDay(),
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                Alert::create(array_merge($payload, [
                    'status' => Alert::STATUS_ACTIVE,
                    'alertable_type' => AidRequestItem::class,
                    'alertable_id' => $item->id,
                ]));
                $created++;
            }
        }

        return compact('created', 'updated', 'resolved');
    }

    protected function titleFor(string $type): string
    {
        return match ($type) {
            Alert::TYPE_AID_EXECUTION_OVERDUE => 'تأخر موعد تنفيذ مساعدة',
            default => 'اقتراب موعد تنفيذ مساعدة',
        };
    }

    protected function messageFor(ItemExecutionDue $due): string
    {
        $item = $due->item;
        $request = $item->aidRequest;
        $requestLabel = $request?->title ?? $request?->request_number ?? 'طلب مساعدة';
        $itemLabel = $item->title;
        $date = $due->dueDate->format('Y-m-d');

        return match ($due->state) {
            ExecutionDueState::Overdue => "البند «{$itemLabel}» في طلب «{$requestLabel}» تجاوز موعد التنفيذ ({$date}).",
            default => "البند «{$itemLabel}» في طلب «{$requestLabel}» موعد التنفيذ في {$date}.",
        };
    }
}
