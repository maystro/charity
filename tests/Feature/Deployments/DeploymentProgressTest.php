<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use App\Livewire\Deployments\ShowRelease;
use App\Models\Deployment;
use App\Models\DeploymentStep;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentProgressTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function releaseWithSteps(User $superAdmin, DeploymentStatus $status, array $stepStates): array
    {
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Testing,
            'status' => $status,
            'created_by' => $superAdmin->id,
        ]);

        foreach ($stepStates as $index => $stepState) {
            DeploymentStep::create([
                'deployment_id' => $deployment->id,
                'key' => 'step-'.$index,
                'label' => 'خطوة '.($index + 1),
                'status' => $stepState,
                'started_at' => $stepState === DeploymentStepStatus::Pending ? null : now()->subMinute(),
                'completed_at' => in_array($stepState, [DeploymentStepStatus::Completed, DeploymentStepStatus::Failed, DeploymentStepStatus::Skipped], true) ? now() : null,
                'output' => $stepState === DeploymentStepStatus::Failed ? 'Migration failed: duplicate column' : null,
                'sort_order' => $index,
            ]);
        }

        return [$release, $deployment];
    }

    public function test_polling_starts_when_deployment_is_in_progress(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::InProgress, [
            DeploymentStepStatus::Completed,
            DeploymentStepStatus::InProgress,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSeeHtml('wire:poll.2.5s="refreshDeployments"')
            ->assertSet('hasActiveDeployment', true);
    }

    public function test_polling_starts_when_deployment_is_pending(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::Pending, [
            DeploymentStepStatus::Pending,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSeeHtml('wire:poll.2.5s="refreshDeployments"');
    }

    public function test_polling_stops_at_terminal_state(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::Completed, [
            DeploymentStepStatus::Completed,
            DeploymentStepStatus::Completed,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertDontSeeHtml('wire:poll.2.5s="refreshDeployments"')
            ->assertSet('hasActiveDeployment', false);
    }

    public function test_polling_stops_at_failed_state(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::Failed, [
            DeploymentStepStatus::Failed,
            DeploymentStepStatus::Skipped,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertDontSeeHtml('wire:poll.2.5s="refreshDeployments"');
    }

    public function test_progress_percentage_is_computed_from_real_step_states(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::InProgress, [
            DeploymentStepStatus::Completed,
            DeploymentStepStatus::InProgress,
            DeploymentStepStatus::Pending,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSee('33%');
    }

    public function test_progress_shows_current_step_label(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::InProgress, [
            DeploymentStepStatus::Completed,
            DeploymentStepStatus::InProgress,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSee('الخطوة الحالية')
            ->assertSee('خطوة 2');
    }

    public function test_failed_step_output_is_visible(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::Failed, [
            DeploymentStepStatus::Failed,
            DeploymentStepStatus::Skipped,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSee('Migration failed: duplicate column')
            ->assertSee('فشل');
    }

    public function test_refresh_deployments_reloads_latest_step_state(): void
    {
        $superAdmin = $this->superAdmin();
        [$release, $deployment] = $this->releaseWithSteps($superAdmin, DeploymentStatus::InProgress, [
            DeploymentStepStatus::InProgress,
            DeploymentStepStatus::Pending,
        ]);

        // Simulate the job completing one step while the user watches.
        $deployment->steps[0]->markAsCompleted('Done');
        $deployment->steps[1]->markAsInProgress();
        $deployment->refresh();

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->call('refreshDeployments')
            ->assertSee('الخطوة الحالية')
            ->assertSee('خطوة 2')
            ->assertSee('50%');
    }

    public function test_reopening_page_after_completion_shows_correct_state(): void
    {
        $superAdmin = $this->superAdmin();
        [$release] = $this->releaseWithSteps($superAdmin, DeploymentStatus::Completed, [
            DeploymentStepStatus::Completed,
            DeploymentStepStatus::Completed,
        ]);

        // Fresh mount — simulates closing and reopening the browser tab.
        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->assertSee('اكتمل النشر بنجاح')
            ->assertSee('100%')
            ->assertDontSeeHtml('wire:poll.2.5s="refreshDeployments"');
    }
}
