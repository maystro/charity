<?php

namespace App\Enums;

enum DatabaseBackupStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الإنشاء',
            self::Completed => 'مكتملة',
            self::Failed => 'فاشلة',
        };
    }
}
