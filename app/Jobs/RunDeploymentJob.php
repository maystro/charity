<?php

namespace App\Jobs;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use App\Models\Deployment;
use App\Models\DeploymentStep;
use App\Services\Deployment\DeploymentProcessRunner;
use App\Services\Deployment\FtpUploader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Executes a queued deployment outside the HTTP request cycle.
 *
 * Only whitelisted command keys from config('deployment.commands') are run.
 * Progress is recorded in deployment_steps so the UI reflects real state.
 */
class RunDeploymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    public int $backoff;

    public function __construct(public Deployment $deployment)
    {
        $this->tries = (int) config('deployment.job.tries', 1);
        $this->timeout = (int) config('deployment.job.timeout', 600);
        $this->backoff = (int) config('deployment.job.backoff', 30);
    }

    /**
     * Prevent the same environment from being executed twice concurrently.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("deployment:{$this->deployment->environment->value}"))->dontRelease(),
        ];
    }

    public function handle(DeploymentProcessRunner $runner): void
    {
        $deployment = $this->deployment;

        if ($deployment->status->isTerminal()) {
            return;
        }

        $deployment->update([
            'status' => DeploymentStatus::InProgress,
            'started_at' => now(),
        ]);

        $commandKeys = (array) config("deployment.environments.{$deployment->environment->value}.commands", []);
        $steps = $this->createSteps($deployment, $commandKeys);

        try {
            foreach ($steps as $step) {
                $step->markAsInProgress();

                $result = $step->key === 'upload'
                    ? app(FtpUploader::class)->result($deployment->release)
                    : $runner->run(
                        $step->key,
                        timeout: (int) config('deployment.job.step_timeout', 120),
                    );

                if (! $result->successful) {
                    $step->markAsFailed($result->output);
                    $this->skipRemainingSteps($steps, $step);
                    $deployment->markAsFailed($this->failureReason($step, $result->output));

                    return;
                }

                $step->markAsCompleted($result->output);
            }
        } catch (\Throwable $e) {
            $message = Str::limit($e->getMessage(), 500);

            $steps->each(function (DeploymentStep $step) use ($message): void {
                if ($step->status === DeploymentStepStatus::InProgress) {
                    $step->markAsFailed($message);
                } elseif ($step->status === DeploymentStepStatus::Pending) {
                    $step->markAsSkipped();
                }
            });

            $deployment->markAsFailed('خطأ غير متوقع أثناء النشر: '.$message);

            return;
        }

        $deployment->markAsCompleted();
    }

    /**
     * Insert a row for every command key in execution order.
     *
     * @param  array<int, string>  $commandKeys
     */
    protected function createSteps(Deployment $deployment, array $commandKeys): Collection
    {
        $labels = (array) config('deployment.step_labels', []);

        return collect($commandKeys)
            ->values()
            ->map(function (string $key, int $index) use ($deployment, $labels): DeploymentStep {
                return DeploymentStep::create([
                    'deployment_id' => $deployment->id,
                    'key' => $key,
                    'label' => $labels[$key] ?? $key,
                    'status' => DeploymentStepStatus::Pending,
                    'sort_order' => $index,
                ]);
            });
    }

    /**
     * Mark untouched steps as skipped after a failure.
     *
     * @param  Collection<int, DeploymentStep>  $steps
     */
    protected function skipRemainingSteps(Collection $steps, ?DeploymentStep $current = null): void
    {
        $steps->each(function (DeploymentStep $step) use ($current): void {
            if ($step->is($current)) {
                return;
            }

            if ($step->status === DeploymentStepStatus::Pending) {
                $step->markAsSkipped();
            }
        });
    }

    protected function failureReason(DeploymentStep $step, string $output): string
    {
        $message = "فشلت الخطوة [{$step->label}].";

        if ($output !== '') {
            $message .= ' '.Str::limit($output, 500);
        }

        return $message;
    }
}
