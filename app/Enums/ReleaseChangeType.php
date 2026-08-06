<?php

namespace App\Enums;

enum ReleaseChangeType: string
{
    case Added = 'added';
    case Modified = 'modified';
    case Fixed = 'fixed';
    case Updated = 'updated';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Added => 'إضافة',
            self::Modified => 'تعديل',
            self::Fixed => 'إصلاح',
            self::Updated => 'تحديث',
            self::Removed => 'حذف',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Added => 'plus-circle',
            self::Modified => 'pencil-square',
            self::Fixed => 'check-circle',
            self::Updated => 'arrow-path',
            self::Removed => 'trash',
        };
    }
}
