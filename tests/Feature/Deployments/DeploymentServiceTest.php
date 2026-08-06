<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use App\Services\Deployment\DeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeploymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_creates_pending_deployment_for_published_release(): void
    {
        Bus::fake();

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        $deployment = app(DeploymentService::class)->queue($release, DeploymentEnvironment::Staging, $superAdmin);

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Staging->value,
            'status' => DeploymentStatus::Pending->value,
            'created_by' => $superAdmin->id,
        ]);

        $this->assertNull($deployment->started_at);
        $this->assertNull($deployment->completed_at);

        Bus::assertDispatched(RunDeploymentJob::class);
    }

    public function test_queue_rejects_draft_release(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only published releases can be deployed.');

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);

        app(DeploymentService::class)->queue($release, DeploymentEnvironment::Staging, $superAdmin);
    }

    public function test_queue_rejects_disallowed_environment(): void
    {
        $this->expectException(\RuntimeException::class);

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        config(['deployment.environments' => []]);

        app(DeploymentService::class)->queue($release, DeploymentEnvironment::Production, $superAdmin);
    }

    public function test_queue_prevents_concurrent_deployment_for_same_environment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is already an active deployment');

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Staging,
            'status' => DeploymentStatus::InProgress,
            'created_by' => $superAdmin->id,
        ]);

        app(DeploymentService::class)->queue($release, DeploymentEnvironment::Staging, $superAdmin);
    }

    public function test_queue_allows_different_environments_concurrently(): void
    {
        Bus::fake();

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Staging,
            'status' => DeploymentStatus::InProgress,
            'created_by' => $superAdmin->id,
        ]);

        $deployment = app(DeploymentService::class)->queue($release, DeploymentEnvironment::Production, $superAdmin);

        $this->assertSame(DeploymentStatus::Pending, $deployment->status);

        Bus::assertDispatched(RunDeploymentJob::class);
    }

    public function test_mark_as_completed_sets_timestamps_and_revision(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $deployment = Deployment::factory()->create(['created_by' => $superAdmin->id]);

        app(DeploymentService::class)->markAsCompleted($deployment, 'abc123');

        $this->assertSame(DeploymentStatus::Completed, $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertSame('abc123', $deployment->source_revision);
    }

    public function test_mark_as_failed_sets_reason_and_timestamp(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $deployment = Deployment::factory()->create(['created_by' => $superAdmin->id]);

        app(DeploymentService::class)->markAsFailed($deployment, 'Migration failed');

        $this->assertSame(DeploymentStatus::Failed, $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertSame('Migration failed', $deployment->failure_reason);
    }

    public function test_rollback_marks_completed_deployment_as_rolled_back(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $deployment = Deployment::factory()->completed()->create(['created_by' => $superAdmin->id]);

        app(DeploymentService::class)->rollback($deployment);

        $this->assertSame(DeploymentStatus::RolledBack, $deployment->status);
        $this->assertNotNull($deployment->rolled_back_at);
    }

    public function test_rollback_rejects_pending_deployment(): void
    {
        $this->expectException(\RuntimeException::class);

        $superAdmin = User::factory()->superAdmin()->create();
        $deployment = Deployment::factory()->create(['created_by' => $superAdmin->id]);

        app(DeploymentService::class)->rollback($deployment);
    }
}
