<?php

namespace App\Enums;

enum ExecutionDueState: string
{
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'قريب',
            self::Overdue => 'متأخر',
        };
    }
}
