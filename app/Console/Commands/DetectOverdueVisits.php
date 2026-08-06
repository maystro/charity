<?php

namespace App\Console\Commands;

use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Console\Command;

class DetectOverdueVisits extends Command
{
    protected $signature = 'app:detect-overdue-visits';

    protected $description = 'كشف الزيارات المتأخرة وتحديث حالتها';

    public function handle(): int
    {
        $overdueVisits = Visit::query()
            ->where('is_overdue', false)
            ->whereIn('status', VisitStatus::pendingStatuses())
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->get();

        $count = 0;

        foreach ($overdueVisits as $visit) {
            $visit->update(['is_overdue' => true]);
            $count++;
        }

        $this->info("تم تحديث {$count} زيارة متأخرة.");

        return self::SUCCESS;
    }
}
