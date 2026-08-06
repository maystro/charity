<?php

namespace App\Enums;

enum VisitType: string
{
    case InitialAssessment = 'initial_assessment';
    case Verification = 'verification';
    case ResearchFollowUp = 'research_follow_up';
    case AidFollowUp = 'aid_follow_up';
    case PostDelivery = 'post_delivery';
    case Reevaluation = 'reevaluation';
    case Urgent = 'urgent';
    case ComplaintInvestigation = 'complaint_investigation';
    case ProjectFollowUp = 'project_follow_up';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InitialAssessment => 'بحث أولي',
            self::Verification => 'تحقق',
            self::ResearchFollowUp => 'متابعة بحث',
            self::AidFollowUp => 'متابعة مساعدة',
            self::PostDelivery => 'ما بعد التسليم',
            self::Reevaluation => 'إعادة تقييم',
            self::Urgent => 'عاجلة',
            self::ComplaintInvestigation => 'فحص شكوى',
            self::ProjectFollowUp => 'متابعة مشروع',
            self::Other => 'أخرى',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::InitialAssessment => 'info',
            self::Verification => 'info',
            self::ResearchFollowUp => 'neutral',
            self::AidFollowUp => 'neutral',
            self::PostDelivery => 'success',
            self::Reevaluation => 'warning',
            self::Urgent => 'danger',
            self::ComplaintInvestigation => 'warning',
            self::ProjectFollowUp => 'neutral',
            self::Other => 'neutral',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
