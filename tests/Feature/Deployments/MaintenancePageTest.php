<?php

namespace Tests\Feature\Deployments;

use App\Livewire\Deployments\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Mockery;
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

    private function processResult(bool $success, string $output = ''): object
    {
        return new class($success, $output)
        {
            public function __construct(private readonly bool $success, private readonly string $message) {}

            public function successful(): bool
            {
                return $this->success;
            }

            public function output(): string
            {
                return $this->message;
            }

            public function errorOutput(): string
            {
                return '';
            }
        };
    }

    private function mockIconsSubprocess(bool $success, string $output = ''): void
    {
        Process::shouldReceive('path')
            ->andReturnSelf();
        Process::shouldReceive('timeout')
            ->andReturnSelf();
        Process::shouldReceive('run')
            ->once()
            ->with(Mockery::on(fn (string $command): bool => str_contains($command, 'icons:')))
            ->andReturn($this->processResult($success, $output));
    }

    public function test_clear_caches_clears_optimize_and_icons(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear')
            ->andReturn(0);
        $this->mockIconsSubprocess(true);

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('clearCaches')
            ->assertSet('isClearingCache', false)
            ->assertSet('statusMessage', fn (string $message) => str_contains($message, 'تم حذف الكاش بشكل كامل')
                && str_contains($message, 'كاش الأيقونات'))
            ->assertDispatched('notify', type: 'success');
    }

    public function test_build_caches_runs_all_cache_commands(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->with('config:cache')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('route:cache')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('view:cache')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('event:cache')
            ->andReturn(0);
        $this->mockIconsSubprocess(true);

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('buildCaches')
            ->assertSet('isBuildingCache', false)
            ->assertSet('statusMessage', fn (string $message) => str_contains($message, 'تم بناء الكاش بنجاح (5 من 5)'))
            ->assertDispatched('notify', type: 'success');
    }

    public function test_build_caches_reports_partial_failures(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->with('config:cache')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('route:cache')
            ->once()
            ->andReturn(1);
        Artisan::shouldReceive('call')
            ->with('view:cache')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('event:cache')
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->andReturn('SQLSTATE: unable to prepare route');
        $this->mockIconsSubprocess(true);

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('buildCaches')
            ->assertSet('isBuildingCache', false)
            ->assertSet('statusMessage', fn (string $message) => str_contains($message, 'تم بناء الكاش بنجاح (4 من 5)')
                && str_contains($message, 'كاش المسارات')
                && str_contains($message, 'SQLSTATE: unable to prepare route'))
            ->assertDispatched('notify', type: 'success');
    }

    public function test_build_caches_reports_failure_when_everything_fails(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->with(Mockery::on(fn (string $command): bool => in_array($command, ['config:cache', 'route:cache', 'view:cache', 'event:cache'], true)))
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->andReturn('error');
        $this->mockIconsSubprocess(false, 'icons failed');

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('buildCaches')
            ->assertSet('isBuildingCache', false)
            ->assertSet('errorMessage', fn (string $message) => str_contains($message, 'فشل بناء الكاش'))
            ->assertDispatched('notify', type: 'error');
    }

    public function test_maintenance_page_shows_migrations_section(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('deployments.maintenance'))
            ->assertOk()
            ->assertSee('تشغيل المايجريشن')
            ->assertSee('migrate --force');
    }

    public function test_maintenance_page_shows_composer_alternatives_for_locked_hosting(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('deployments.maintenance'))
            ->assertOk()
            ->assertSee('composer update')
            ->assertSee('public')
            ->assertSee('Cron')
            ->assertSee('vendor');
    }

    public function test_run_migrations_calls_migrate_with_force(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("INFO  Migrating: 2026_10_28_000000_example\n");

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('runMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('migrationOutput', 'INFO  Migrating: 2026_10_28_000000_example')
            ->assertSet('statusMessage', 'INFO  Migrating: 2026_10_28_000000_example')
            ->assertDispatched('notify', type: 'success');
    }

    public function test_run_migrations_reports_failure(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('SQLSTATE: table already exists');

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('runMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('errorMessage', 'SQLSTATE: table already exists')
            ->assertDispatched('notify', type: 'error');
    }

    public function test_run_migrations_executes_in_process_without_mocking(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('runMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('errorMessage', null)
            ->assertDispatched('notify', type: 'success');
    }

    public function test_fresh_mode_asks_for_confirmation_instead_of_running(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')->never();
        Artisan::shouldReceive('output')->never();

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->set('freshMode', true)
            ->call('startMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('migrationOutput', null)
            ->assertDispatched('open-modal', 'confirm-fresh-migrations');
    }

    public function test_run_fresh_migrations_calls_migrate_fresh_with_seed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--seed' => true, '--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("INFO  Dropped all tables successfully.\nINFO  Database seeding completed successfully.");

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->set('freshMode', true)
            ->call('runFreshMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('freshMode', false)
            ->assertSet('errorMessage', null)
            ->assertDispatched('notify', type: 'success');
    }

    public function test_run_fresh_migrations_reports_failure_and_keeps_switch_armed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--seed' => true, '--force' => true])
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('SQLSTATE: seeding failed');

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->set('freshMode', true)
            ->call('runFreshMigrations')
            ->assertSet('isRunningMigrations', false)
            ->assertSet('freshMode', true)
            ->assertSet('errorMessage', 'SQLSTATE: seeding failed')
            ->assertDispatched('notify', type: 'error');
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

        $cleared = new class
        {
            public function successful(): bool
            {
                return true;
            }

            public function output(): string
            {
                return 'INFO Cache cleared successfully.';
            }

            public function errorOutput(): string
            {
                return '';
            }
        };

        Process::shouldReceive('path')
            ->andReturnSelf();
        Process::shouldReceive('env')
            ->andReturnSelf();
        Process::shouldReceive('timeout')
            ->andReturnSelf();
        Process::shouldReceive('run')
            ->andReturnUsing(function (string $command) use ($process, $cleared) {
                return str_contains($command, 'optimize:clear') ? $cleared : $process;
            });

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('updatePackages')
            ->assertSet('isUpdatingPackages', false)
            ->assertSet('statusMessage', fn ($message) => str_starts_with($message, 'تم التنفيذ من: '.base_path())
                && str_contains($message, 'تم تحديث الحزم وتنظيف الكاش بنجاح.'))
            ->assertDispatched('notify', type: 'success');
    }

    public function test_update_packages_runs_composer_from_project_root_without_scripts(): void
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

        $cleared = new class
        {
            public function successful(): bool
            {
                return true;
            }

            public function output(): string
            {
                return 'INFO Cache cleared successfully.';
            }

            public function errorOutput(): string
            {
                return '';
            }
        };

        $commands = [];

        Process::shouldReceive('path')
            ->andReturnSelf();
        Process::shouldReceive('env')
            ->andReturnSelf();
        Process::shouldReceive('timeout')
            ->andReturnSelf();
        Process::shouldReceive('run')
            ->andReturnUsing(function (string $command) use ($process, $cleared, &$commands) {
                $commands[] = $command;

                return str_contains($command, 'optimize:clear') ? $cleared : $process;
            });

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('updatePackages')
            ->assertSet('isUpdatingPackages', false)
            ->assertDispatched('notify', type: 'success');

        $composerCommand = collect($commands)->first(fn (string $command): bool => str_contains($command, 'update'));

        $this->assertNotNull($composerCommand);
        $this->assertStringContainsString('--working-dir='.escapeshellarg(base_path()), $composerCommand);
        $this->assertStringContainsString('--no-scripts', $composerCommand);
        $this->assertStringNotContainsString(escapeshellarg(public_path()), $composerCommand);
        $this->assertTrue(collect($commands)->contains(fn (string $command): bool => str_contains($command, 'optimize:clear')));
    }

    public function test_create_storage_link_creates_symlink_when_missing(): void
    {
        $this->skipWhenSymlinksAreUnavailable();

        $superAdmin = User::factory()->superAdmin()->create();

        $link = public_path('storage');

        // تهيئة الحالة: الرابط غير موجود
        if (is_link($link)) {
            unlink($link);
        }

        try {
            Livewire::actingAs($superAdmin)
                ->test(Maintenance::class)
                ->call('createStorageLink')
                ->assertSet('isLinkingStorage', false)
                ->assertSet('storageLinkExists', true)
                ->assertSet('statusMessage', 'تم إنشاء رابط التخزين بنجاح: public/storage → storage/app/public.')
                ->assertDispatched('notify', type: 'success');

            $this->assertTrue(is_link($link));
        } finally {
            // إبقاء الرابط موجودًا لبيئة التطوير المحلية (وضع طبيعي)
            if (! is_link($link)) {
                @symlink(storage_path('app/public'), $link);
            }
        }
    }

    public function test_create_storage_link_reports_when_link_already_exists(): void
    {
        $this->skipWhenSymlinksAreUnavailable();

        $superAdmin = User::factory()->superAdmin()->create();

        $link = public_path('storage');

        // تهيئة الحالة: الرابط موجود
        if (! is_link($link)) {
            symlink(storage_path('app/public'), $link);
        }

        Livewire::actingAs($superAdmin)
            ->test(Maintenance::class)
            ->call('createStorageLink')
            ->assertSet('storageLinkExists', true)
            ->assertSet('statusMessage', 'رابط التخزين موجود بالفعل: public/storage.')
            ->assertDispatched('notify', type: 'info');
    }

    /**
     * Creating symlinks needs elevated privileges on Windows (Developer Mode or admin).
     * Skip instead of destroying the existing public/storage link when unavailable.
     */
    private function skipWhenSymlinksAreUnavailable(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'symlink-probe');
        $probe = $target.'.link';

        if (! @symlink($target, $probe)) {
            @unlink($target);

            $this->markTestSkipped('This environment does not allow creating symbolic links.');
        }

        @unlink($probe);
        @unlink($target);
    }
}
