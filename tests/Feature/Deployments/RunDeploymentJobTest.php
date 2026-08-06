<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use App\Services\Deployment\DeploymentProcessRunner;
use App\Services\Deployment\FtpUploader;
use App\Services\Deployment\ProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/**
 * Fake runner — no real commands are ever executed during tests.
 */
class FakeDeploymentProcessRunner extends DeploymentProcessRunner
{
    /** @var array<int, string> */
    public array $calls = [];

    /** @var array<string, ProcessResult> */
    public array $results = [];

    public function __construct()
    {
    }

    public function run(string $commandKey, ?string $path = null, int $timeout = 120): ProcessResult
    {
        $this->calls[] = $commandKey;

        return $this->results[$commandKey]
            ?? new ProcessResult(true, "Output of {$commandKey}", 0);
    }
}

/**
 * Fake uploader — no real FTP connections are ever made during tests.
 */
class FakeFtpUploader extends FtpUploader
{
    /** @var array<int, string> */
    public array $calls = [];

    public bool $fail = false;

    public function __construct()
    {
    }

    public function result(\App\Models\Release $release, ?\App\Services\Deployment\FtpClientContract $client = null): ProcessResult
    {
        $this->calls[] = $release->id;

        return $this->fail
            ? new ProcessResult(false, 'FTP connection refused', 1)
            : new ProcessResult(true, "Uploaded {$release->changes->count()} files", 0);
    }
}

class RunDeploymentJobTest extends TestCase
{
    use RefreshDatabase;

    private function deployment(): Deployment
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        return Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Testing,
            'created_by' => $superAdmin->id,
        ]);
    }

    private function runner(): FakeDeploymentProcessRunner
    {
        $runner = new FakeDeploymentProcessRunner();
        $this->app->instance(DeploymentProcessRunner::class, $runner);

        return $runner;
    }

    private function uploader(bool $fail = false): FakeFtpUploader
    {
        $uploader = new FakeFtpUploader();
        $uploader->fail = $fail;
        $this->app->instance(FtpUploader::class, $uploader);

        return $uploader;
    }

    public function test_job_runs_steps_in_order_and_marks_deployment_completed(): void
    {
        $deployment = $this->deployment();
        $runner = $this->runner();
        $uploader = $this->uploader();

        (new RunDeploymentJob($deployment))->handle($runner);

        $deployment->refresh();

        $this->assertSame(DeploymentStatus::Completed, $deployment->status);
        $this->assertNotNull($deployment->started_at);
        $this->assertNotNull($deployment->completed_at);
        $this->assertSame(['migrate', 'cache'], $runner->calls);
        $this->assertSame([$deployment->release_id], $uploader->calls);

        $steps = $deployment->steps;
        $this->assertCount(3, $steps);
        $this->assertSame(['upload', 'migrate', 'cache'], $steps->pluck('key')->all());
        $this->assertTrue($steps->every(fn ($step) => $step->status === DeploymentStepStatus::Completed));
        $this->assertSame([0, 1, 2], $steps->pluck('sort_order')->all());
    }

    public function test_upload_failure_marks_deployment_failed_and_skips_remaining(): void
    {
        $deployment = $this->deployment();
        $runner = $this->runner();
        $this->uploader(fail: true);

        (new RunDeploymentJob($deployment))->handle($runner);

        $deployment->refresh();

        $this->assertSame(DeploymentStatus::Failed, $deployment->status);
        $this->assertSame([], $runner->calls);

        $steps = $deployment->steps;
        $this->assertSame(DeploymentStepStatus::Failed, $steps[0]->status);
        $this->assertSame(DeploymentStepStatus::Skipped, $steps[1]->status);
        $this->assertSame(DeploymentStepStatus::Skipped, $steps[2]->status);
        $this->assertStringContainsString('FTP connection refused', $deployment->failure_reason);
    }

    public function test_failed_step_marks_deployment_failed_and_skips_remaining(): void
    {
        $deployment = $this->deployment();
        $runner = $this->runner();
        $this->uploader();
        $runner->results['migrate'] = new ProcessResult(false, 'Migration failed: duplicate column', 1);

        (new RunDeploymentJob($deployment))->handle($runner);

        $deployment->refresh();

        $this->assertSame(DeploymentStatus::Failed, $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertStringContainsString('فشلت الخطوة', $deployment->failure_reason);
        $this->assertStringContainsString('Migration failed: duplicate column', $deployment->failure_reason);

        $steps = $deployment->steps;
        $this->assertSame(DeploymentStepStatus::Completed, $steps[0]->status);
        $this->assertSame(DeploymentStepStatus::Failed, $steps[1]->status);
        $this->assertSame(DeploymentStepStatus::Skipped, $steps[2]->status);
    }

    public function test_unexpected_exception_marks_deployment_failed(): void
    {
        $deployment = $this->deployment();
        $this->uploader();

        $runner = new class extends FakeDeploymentProcessRunner
        {
            public function run(string $commandKey, ?string $path = null, int $timeout = 120): ProcessResult
            {
                throw new RuntimeException('Process timed out.');
            }
        };
        $this->app->instance(DeploymentProcessRunner::class, $runner);

        (new RunDeploymentJob($deployment))->handle($runner);

        $deployment->refresh();

        $this->assertSame(DeploymentStatus::Failed, $deployment->status);
        $this->assertStringContainsString('خطأ غير متوقع', $deployment->failure_reason);

        $steps = $deployment->steps;
        $this->assertSame(DeploymentStepStatus::Completed, $steps[0]->status);
        $this->assertSame(DeploymentStepStatus::Failed, $steps[1]->status);
        $this->assertSame(DeploymentStepStatus::Skipped, $steps[2]->status);
    }

    public function test_job_does_nothing_for_terminal_deployment(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);
        $deployment = Deployment::factory()->completed()->create([
            'release_id' => $release->id,
            'created_by' => $superAdmin->id,
        ]);
        $runner = $this->runner();

        (new RunDeploymentJob($deployment))->handle($runner);

        $this->assertSame([], $runner->calls);
        $this->assertDatabaseCount('deployment_steps', 0);
    }

    public function test_queue_dispatches_job_with_expected_tuning(): void
    {
        Queue::fake();

        $deployment = $this->deployment();

        RunDeploymentJob::dispatch($deployment);

        Queue::assertPushed(RunDeploymentJob::class, function (RunDeploymentJob $job) use ($deployment) {
            return $job->deployment->is($deployment)
                && $job->tries === (int) config('deployment.job.tries')
                && $job->timeout === (int) config('deployment.job.timeout')
                && $job->backoff === (int) config('deployment.job.backoff');
        });
    }

    public function test_service_dispatches_job_after_queuing(): void
    {
        Bus::fake();

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        app(\App\Services\Deployment\DeploymentService::class)
            ->queue($release, DeploymentEnvironment::Testing, $superAdmin);

        Bus::assertDispatched(RunDeploymentJob::class);
    }
}
