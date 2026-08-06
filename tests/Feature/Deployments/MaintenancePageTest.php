<?php

namespace Tests\Feature\Deployments;

use App\Livewire\Deployments\Maintenance;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_maintenance_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('deployments.maintenance'))
            ->assertOk()
            ->assertSee('الصيانة');
    }

    public function test_non_super_admin_cannot_access_maintenance_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('deployments.maintenance'))
            ->assertForbidden();
    }

    public function test_clear_caches_calls_optimize_clear(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear')
            ->andReturn(0);

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('clearCaches')
            ->assertSet('isClearingCache', false)
            ->assertSet('statusMessage', 'تم حذف كاش Laravel والملفات المؤقتة بنجاح.')
            ->assertDispatched('notify', type: 'success');
    }

    public function test_update_packages_runs_composer_update(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $process = new class
        {
            public function successful(): bool
            {
                return true;
            }

            public function output(): string
            {
                return 'Updated packages.';
            }

            public function errorOutput(): string
            {
                return '';
            }
        };

        Process::shouldReceive('path')
            ->once()
            ->with(base_path())
            ->andReturnSelf();
        Process::shouldReceive('timeout')
            ->once()
            ->with(900)
            ->andReturnSelf();
        Process::shouldReceive('run')
            ->once()
            ->with('composer update --no-interaction --no-progress --prefer-dist')
            ->andReturn($process);

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('updatePackages')
            ->assertSet('isUpdatingPackages', false)
            ->assertDispatched('notify', type: 'success');
    }
}
