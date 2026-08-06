<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case NeedsCompletion = 'needs_completion';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::UnderReview => 'تحت المراجعة',
            self::NeedsCompletion => 'تحتاج استكمال',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::UnderReview => 'warning',
            self::NeedsCompletion => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
