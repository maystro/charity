<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Livewire\Deployments\CreateRelease;
use App\Livewire\Deployments\Index;
use App\Livewire\Deployments\ShowRelease;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_release_with_changes(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->set('version', 'v1.4.0')
            ->set('title', 'تحديث نظام التقارير')
            ->set('description', 'إضافة تصدير PDF')
            ->set('changes', [
                ['type' => 'updated', 'file_path' => 'app/Services/ReportExportService.php', 'description' => 'إنشاء خدمة تصدير التقارير'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('deployments.index'));

        $this->assertDatabaseHas('releases', [
            'version' => 'v1.4.0',
            'title' => 'تحديث نظام التقارير',
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);

        $this->assertDatabaseHas('release_changes', [
            'type' => 'updated',
            'file_path' => 'app/Services/ReportExportService.php',
        ]);
    }

    public function test_create_release_validates_duplicate_version(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Release::factory()->create([
            'version' => 'v1.0.0',
            'created_by' => $superAdmin->id,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->set('version', 'v1.0.0')
            ->set('title', 'إصدار مكرر')
            ->call('save')
            ->assertHasErrors(['version' => 'unique']);
    }

    public function test_create_release_requires_at_least_one_change(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->set('version', 'v1.5.0')
            ->set('title', 'إصدار بدون تغييرات')
            ->call('save')
            ->assertHasErrors(['changes']);
    }

    public function test_draft_cannot_be_deployed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->set('deployEnvironment', DeploymentEnvironment::Staging->value)
            ->call('deploy')
            ->assertDispatched('notify');

        $this->assertDatabaseCount('deployments', 0);
    }

    public function test_super_admin_can_deploy_published_release(): void
    {
        Bus::fake();

        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->set('deployEnvironment', DeploymentEnvironment::Staging->value)
            ->call('deploy')
            ->assertDispatched('notify')
            ->assertSet('showDeployModal', false);

        $this->assertDatabaseHas('deployments', [
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Staging->value,
            'status' => DeploymentStatus::Pending->value,
            'created_by' => $superAdmin->id,
        ]);
    }

    public function test_cannot_deploy_concurrently_to_same_environment(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        Deployment::factory()->create([
            'release_id' => $release->id,
            'environment' => DeploymentEnvironment::Staging,
            'status' => DeploymentStatus::InProgress,
            'created_by' => $superAdmin->id,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->set('deployEnvironment', DeploymentEnvironment::Staging->value)
            ->call('deploy')
            ->assertDispatched('notify');

        $this->assertDatabaseCount('deployments', 1);
    }

    public function test_index_lists_releases_with_filter(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Release::factory()->published()->create([
            'version' => 'v2.0.0',
            'created_by' => $superAdmin->id,
        ]);
        Release::factory()->create([
            'version' => 'v2.1.0',
            'created_by' => $superAdmin->id,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->assertSee('v2.0.0')
            ->assertSee('v2.1.0')
            ->call('setFilter', 'draft')
            ->assertSee('v2.1.0')
            ->assertDontSee('v2.0.0');
    }
}
