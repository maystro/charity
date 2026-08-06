<?php

namespace App\Console\Commands;

use App\Services\Alerts\ReAssessmentAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-alerts')]
#[Description('فحص الأسر المعتمدة وتوليد تنبيهات إعادة التقييم المستحقة والمتأخرة')]
class GenerateAlerts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReAssessmentAlertService $service): int
    {
        $this->info('بدء فحص تنبيهات إعادة التقييم...');

        $result = $service->generate();

        $this->info("تم إنشاء {$result['created']} تنبيه جديد وتحديث {$result['updated']} تنبيه موجود.");

        return self::SUCCESS;
    }
}
