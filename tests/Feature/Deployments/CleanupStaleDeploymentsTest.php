<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CleanupStaleDeploymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_old_active_deployments_as_failed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        $stale = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Testing,
            'status' => DeploymentStatus::InProgress,
            'created_by' => $superAdmin->id,
            'started_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
        ]);

        $stale->steps()->create([
            'key' => 'migrate',
            'label' => 'تشغيل الهجرات',
            'status' => DeploymentStepStatus::Pending,
            'sort_order' => 0,
        ]);

        Artisan::call('app:cleanup-stale-deployments');

        $stale->refresh();

        $this->assertSame(DeploymentStatus::Failed, $stale->status);
        $this->assertNotNull($stale->completed_at);
        $this->assertStringContainsString('انتهت مهلة الانتظار', $stale->failure_reason);
        $this->assertSame(DeploymentStepStatus::Skipped, $stale->steps->first()->status);
    }

    public function test_leaves_recent_deployments_untouched(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        $fresh = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Testing,
            'status' => DeploymentStatus::InProgress,
            'created_by' => $superAdmin->id,
        ]);

        Artisan::call('app:cleanup-stale-deployments');

        $this->assertSame(DeploymentStatus::InProgress, $fresh->fresh()->status);
    }

    public function test_leaves_completed_deployments_untouched(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        $completed = Deployment::factory()->completed()->create([
            'release_id' => $release->id,
            'created_by' => $superAdmin->id,
            'created_at' => now()->subHours(3),
        ]);

        Artisan::call('app:cleanup-stale-deployments');

        $this->assertSame(DeploymentStatus::Completed, $completed->fresh()->status);
    }
}
