<?php

namespace App\Enums;

enum DonorType: string
{
    case Individual = 'individual';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'فرد',
            self::Organization => 'جهة',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Individual => 'info',
            self::Organization => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
