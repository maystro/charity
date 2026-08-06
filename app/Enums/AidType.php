<?php

namespace App\Enums;

enum AidType: string
{
    case Financial = 'financial';
    case Medical = 'medical';
    case Educational = 'educational';
    case Marriage = 'marriage';
    case HousingFurniture = 'housing_furniture';

    public function label(): string
    {
        return match ($this) {
            self::Financial => 'اقتصادية',
            self::Medical => 'صحية',
            self::Educational => 'تعليمية',
            self::Marriage => 'زواج',
            self::HousingFurniture => 'إعمار وأثاث',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Financial => 'banknotes',
            self::Medical => 'heart',
            self::Educational => 'academic-cap',
            self::Marriage => 'sparkles',
            self::HousingFurniture => 'home',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Financial => 'مساعدات نقدية وإعانات مالية',
            self::Medical => 'علاج وأدوية وعمليات جراحية',
            self::Educational => 'رسوم دراسية ومستلزمات تعليمية',
            self::Marriage => 'مساعدة شباب الأسر في الزواج',
            self::HousingFurniture => 'ترميم سكن وتأثيث منزل',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
