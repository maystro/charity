<?php

namespace App\Enums;

enum ReleaseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case RolledBack = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Published => 'منشور',
            self::RolledBack => 'متراجع عنه',
        };
    }
}
