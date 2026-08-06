<?php

namespace App\Enums;

enum DeploymentStepStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::InProgress => 'جارٍ التنفيذ',
            self::Completed => 'مكتملة',
            self::Failed => 'فاشلة',
            self::Skipped => 'تم تجاوزها',
        };
    }
}
