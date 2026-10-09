<?php

namespace App\Support;

use App\Enums\VisitStatus;
use App\Models\AidRequest;
use App\Models\Alert;
use App\Models\Family;
use App\Models\Project;
use App\Models\Visit;
use Illuminate\Support\Facades\Cache;

class SidebarBadgeCounts
{
    /**
     * @return array<string, int>
     */
    public function all(): array
    {
        $userId = auth()->id() ?? 'guest';

        return Cache::remember(
            "sidebar_badges.{$userId}",
            now()->addSeconds(90),
            fn (): array => $this->compute(),
        );
    }

    /**
     * @return array<string, int>
     */
    protected function compute(): array
    {
        return [
            'families_pending' => Family::query()
                ->whereIn('status', ['under_review', 'draft', 'needs_completion'])
                ->count(),
            'reassessment_overdue' => Alert::query()
                ->active()
                ->forType(Alert::TYPE_REASSESSMENT_OVERDUE)
                ->count(),
            'aid_requests_pending' => AidRequest::query()
                ->whereIn('status', ['submitted', 'under_review'])
                ->count(),
            'visits_overdue' => Visit::query()
                ->where('is_overdue', true)
                ->whereIn('status', VisitStatus::pendingStatuses())
                ->count(),
            'projects_active' => Project::query()
                ->where('status', 'active')
                ->count(),
        ];
    }
}
