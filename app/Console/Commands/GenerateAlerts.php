<?php

namespace App\Console\Commands;

use App\Contracts\Alerts\ScheduledAlertGenerator;
use App\Services\Alerts\AidExecutionDueAlertService;
use App\Services\Alerts\ReAssessmentAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-alerts')]
#[Description('توليد تنبيهات النظام المجدولة (إعادة التقييم، مواعيد التنفيذ، وغيرها)')]
class GenerateAlerts extends Command
{
    /**
     * @return array<int, class-string<ScheduledAlertGenerator>>
     */
    protected function generators(): array
    {
        return [
            ReAssessmentAlertService::class,
            AidExecutionDueAlertService::class,
        ];
    }

    public function handle(): int
    {
        $this->info('بدء توليد التنبيهات المجدولة...');

        foreach ($this->generators() as $generatorClass) {
            $generator = app($generatorClass);
            $label = class_basename($generatorClass);
            $result = $generator->generate();

            $this->line(sprintf(
                '  %s: أُنشئ %d، حُدّث %d، أُغلق %d.',
                $label,
                $result['created'] ?? 0,
                $result['updated'] ?? 0,
                $result['resolved'] ?? 0,
            ));
        }

        return self::SUCCESS;
    }
}
