<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Scheduled = 'scheduled';
    case Assigned = 'assigned';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NotCompleted = 'not_completed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'مجدولة',
            self::Assigned => 'مُسندة',
            self::Confirmed => 'مؤكدة',
            self::InProgress => 'قيد التنفيذ',
            self::Completed => 'مكتملة',
            self::NotCompleted => 'لم تتم',
            self::Rescheduled => 'أعيد جدولتها',
            self::Cancelled => 'ملغاة',
            self::Overdue => 'متأخرة',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Assigned => 'info',
            self::Confirmed => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::NotCompleted => 'neutral',
            self::Rescheduled => 'warning',
            self::Cancelled => 'danger',
            self::Overdue => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }

    /** الحالات التي تعتبر قيد الانتظار (لم تنته بعد). */
    public static function pendingStatuses(): array
    {
        return [
            self::Scheduled->value,
            self::Assigned->value,
            self::Confirmed->value,
            self::InProgress->value,
        ];
    }

    /** الحالات التي تعتبر منتهية. */
    public static function completedStatuses(): array
    {
        return [
            self::Completed->value,
            self::NotCompleted->value,
        ];
    }

    /** الحالات التي تعتبر ملغاة أو مؤجلة. */
    public static function cancelledStatuses(): array
    {
        return [
            self::Rescheduled->value,
            self::Cancelled->value,
        ];
    }
}
