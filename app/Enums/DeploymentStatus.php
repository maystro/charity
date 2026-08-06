<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::InProgress => 'جارٍ التنفيذ',
            self::Completed => 'مكتمل',
            self::Failed => 'فشل',
            self::RolledBack => 'تم التراجع',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::RolledBack], true);
    }
}
