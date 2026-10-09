<?php

namespace App\Console\Commands;

use App\Services\ExecutionSchedule\ExecutionNextDueDateSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('execution:sync-next-due-dates')]
#[Description('إعادة حساب عمود next_due_date لبنود طلبات المساعدة')]
class SyncExecutionNextDueDates extends Command
{
    public function handle(ExecutionNextDueDateSync $sync): int
    {
        $this->info('جاري مزامنة مواعيد التنفيذ القادمة...');

        $updated = $sync->syncAll();

        $this->info("تم تحديث {$updated} بنداً.");

        return self::SUCCESS;
    }
}
