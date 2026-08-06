<?php

namespace App\Enums;

enum DonationType: string
{
    case Cash = 'cash';
    case InKind = 'in_kind';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::InKind => 'عيني',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::InKind => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
