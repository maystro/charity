<?php

namespace App\Enums;

enum AidRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case NeedsCompletion = 'needs_completion';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case PartiallyApproved = 'partially_approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case InExecution = 'in_execution';
    case PendingDeliveryReview = 'pending_delivery_review';
    case Delivered = 'delivered';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Submitted => 'مقدمة',
            self::NeedsCompletion => 'تحتاج استكمال',
            self::UnderReview => 'تحت المراجعة',
            self::Approved => 'معتمدة',
            self::PartiallyApproved => 'معتمدة جزئياً',
            self::Rejected => 'مرفوضة',
            self::Cancelled => 'ملغاة',
            self::InExecution => 'قيد التنفيذ',
            self::PendingDeliveryReview => 'بانتظار مراجعة التسليم',
            self::Delivered => 'تم التسليم',
            self::Completed => 'منجزة',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Submitted => 'info',
            self::NeedsCompletion => 'warning',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::PartiallyApproved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'neutral',
            self::InExecution => 'warning',
            self::PendingDeliveryReview => 'warning',
            self::Delivered => 'success',
            self::Completed => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }

    /** الحالات التي تعتبر "تحت المراجعة". */
    public static function underReviewStatuses(): array
    {
        return [
            self::Submitted->value,
            self::NeedsCompletion->value,
            self::UnderReview->value,
        ];
    }

    /** الحالات التي تعتبر "معتمدة" (جاهزة للتنفيذ). */
    public static function approvedStatuses(): array
    {
        return [
            self::Approved->value,
            self::PartiallyApproved->value,
        ];
    }

    /** الحالات التي تعتبر "قيد التنفيذ أو التسليم". */
    public static function executionStatuses(): array
    {
        return [
            self::InExecution->value,
            self::PendingDeliveryReview->value,
            self::Delivered->value,
        ];
    }

    /** الحالات التي تعتبر "منتهية" (مكتملة أو مسلّمة). */
    public static function completedStatuses(): array
    {
        return [
            self::Delivered->value,
            self::Completed->value,
        ];
    }
}
