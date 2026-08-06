<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'قيد التخطيط',
            self::Active => 'نشط',
            self::Completed => 'منجز',
            self::Suspended => 'متوقف',
            self::Cancelled => 'ملغي',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Planning => 'neutral',
            self::Active => 'info',
            self::Completed => 'success',
            self::Suspended => 'warning',
            self::Cancelled => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
