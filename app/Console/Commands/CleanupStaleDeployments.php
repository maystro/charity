<?php

namespace App\Console\Commands;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use App\Models\Deployment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cleanup-stale-deployments')]
#[Description('تحويل عمليات النشر العالقة (قيد الانتظار أو قيد التنفيذ) إلى فاشلة بعد المهلة المحددة')]
class CleanupStaleDeployments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) config('deployment.stale_after_minutes', 30);
        $threshold = now()->subMinutes($minutes);

        $deployments = Deployment::query()
            ->whereIn('status', [DeploymentStatus::Pending, DeploymentStatus::InProgress])
            ->where('created_at', '<', $threshold)
            ->with('steps')
            ->get();

        foreach ($deployments as $deployment) {
            $deployment->steps
                ->where('status', DeploymentStepStatus::Pending)
                ->each->markAsSkipped();

            $deployment->markAsFailed("انتهت مهلة الانتظار: لم يكتمل النشر خلال {$minutes} دقيقة.");
        }

        $this->info("تم إنهاء {$deployments->count()} عملية نشر عالقة.");

        return self::SUCCESS;
    }
}
