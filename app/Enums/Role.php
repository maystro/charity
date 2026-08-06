<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Fieldworker = 'fieldworker';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير',
            self::Fieldworker => 'مندوب',
            self::User => 'مستخدم',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
