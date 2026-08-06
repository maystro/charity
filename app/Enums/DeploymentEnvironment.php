<?php

namespace App\Enums;

enum DeploymentEnvironment: string
{
    case Testing = 'testing';
    case Staging = 'staging';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Testing => 'اختباري',
            self::Staging => 'تجريبي',
            self::Production => 'إنتاجي',
        };
    }
}
