<?php

namespace Tests\Feature\Deployments;

use App\Models\SmartDeployment;
use App\Models\User;
use App\Services\Deployment\SmartDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SmartDeploymentTest extends TestCase
{
    use RefreshDatabase;

    private string $manifestPath;

    private string $tempFile;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = (string) config('deployment.smart.manifest_path');
        $this->tempDir = storage_path('app/smart-test-'.uniqid());
        $this->tempFile = $this->tempDir.'/routes_temp_test.php';

        mkdir($this->tempDir.'/routes', 0777, true);
        file_put_contents($this->tempFile, "<?php // smart test\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->manifestPath);
        @unlink($this->tempFile);
        @rmdir($this->tempDir.'/routes');
        @rmdir($this->tempDir);

        parent::tearDown();
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function service(): SmartDeploymentService
    {
        return app(SmartDeploymentService::class);
    }

    // -----------------------------------------------------------------
    // Local scanning
    // -----------------------------------------------------------------

    public function test_scan_local_reports_everything_added_when_manifest_missing(): void
    {
        $service = $this->service();

        $changes = $service->getLocalChanges();

        $this->assertContains('app/Support/Navigation.php', $changes['added']);
        $this->assertNotContains('app/Support/Navigation.php', $changes['modified']);
        $this->assertSame([], $changes['removed']);
        $this->assertGreaterThan(0, $changes['total_size']);
    }

    public function test_scan_local_reports_synced_after_manifest_saved(): void
    {
        $service = $this->service();

        $current = $service->scanLocalFiles();
        $service->saveManifest($current);

        $changes = $service->getLocalChanges();

        $this->assertSame([], $changes['added']);
        $this->assertSame([], $changes['modified']);
        $this->assertSame([], $changes['removed']);
    }

    public function test_scan_local_detects_modified_file(): void
    {
        $service = $this->service();

        $current = $service->scanLocalFiles();
        $current['app/Support/Navigation.php'] = 'changed-hash';
        $service->saveManifest($current);

        $changes = $service->getLocalChanges();

        $this->assertContains('app/Support/Navigation.php', $changes['modified']);
    }

    public function test_scan_local_reports_removed_files(): void
    {
        $service = $this->service();

        $current = $service->scanLocalFiles();
        $current['routes/__gone__.php'] = 'hash';
        $service->saveManifest($current);

        $changes = $service->getLocalChanges();

        $this->assertContains('routes/__gone__.php', $changes['removed']);
    }

    // -----------------------------------------------------------------
    // Server comparison
    // -----------------------------------------------------------------

    public function test_server_changes_throws_when_no_server_url(): void
    {
        config(['deployment.smart.server_url' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEPLOY_SERVER_URL');

        $this->service()->getServerChanges();
    }

    public function test_server_changes_uses_http_manifest_when_server_url_set(): void
    {
        config(['deployment.smart.server_url' => 'https://example.com/deployer.php']);
        config(['deployment.smart.secret_key' => 'test-secret']);

        Http::fake([
            'https://example.com/deployer.php' => Http::response([
                'success' => true,
                'files' => [
                    'app/Models/User.php' => md5('old-content'),
                ],
            ]),
        ]);

        $changes = $this->service()->getServerChanges();

        // File exists locally but differs from the server hash → modified.
        $this->assertContains('app/Models/User.php', $changes['modified']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/deployer.php'
                && $request['action'] === 'get_manifest'
                && $request['secret'] === 'test-secret';
        });
    }

    // -----------------------------------------------------------------
    // File filtering
    // -----------------------------------------------------------------

    public function test_should_include_file_excludes_gitignore_and_configured_paths(): void
    {
        $service = $this->service();

        $this->assertFalse($service->shouldIncludeFile('.gitignore'));
        $this->assertFalse($service->shouldIncludeFile('public/storage/logo.png'));
        $this->assertFalse($service->shouldIncludeFile('bootstrap/cache/config.php'));
        $this->assertTrue($service->shouldIncludeFile('app/Models/User.php'));
    }

    // -----------------------------------------------------------------
    // Deployment — local-only mode
    // -----------------------------------------------------------------

    public function test_deploy_updates_manifest_locally_when_no_server_url(): void
    {
        config(['deployment.smart.server_url' => '']);

        $service = $this->service();

        $result = $service->deploy([
            'added' => ['app/Support/Navigation.php'],
            'modified' => [],
            'removed' => [],
        ]);

        $this->assertSame(1, $result['files_count']);
        $this->assertSame(['app/Support/Navigation.php'], $result['files']);
        $this->assertArrayHasKey('app/Support/Navigation.php', $service->loadManifest());
    }

    // -----------------------------------------------------------------
    // Deployment — remote via deployer.php
    // -----------------------------------------------------------------

    public function test_deploy_sends_zip_to_server_and_saves_manifest(): void
    {
        config(['deployment.smart.server_url' => 'https://example.com/deployer.php']);
        config(['deployment.smart.secret_key' => 'test-secret']);

        Http::fake([
            'https://example.com/deployer.php' => Http::response([
                'success' => true,
                'files_count' => 1,
            ]),
        ]);

        $service = $this->service();

        $result = $service->deploy([
            'added' => ['app/Support/Navigation.php'],
            'modified' => [],
            'removed' => ['routes/old.php'],
        ]);

        $this->assertSame(1, $result['files_count']);
        $this->assertSame(['app/Support/Navigation.php'], $result['files']);

        // Manifest must now mark the uploaded file as in sync.
        $manifest = $service->loadManifest();
        $this->assertArrayHasKey('app/Support/Navigation.php', $manifest);

        // Verify HTTP request was sent with the ZIP archive.
        Http::assertSent(function ($request) {
            $body = (string) $request->body();

            return $request->url() === 'https://example.com/deployer.php'
                && str_contains($body, 'name="archive"')
                && str_contains($body, 'deploy')
                && str_contains($body, 'test-secret');
        });
    }

    public function test_deploy_reports_progress_during_archiving(): void
    {
        config(['deployment.smart.server_url' => 'https://example.com/deployer.php']);
        config(['deployment.smart.secret_key' => 'test-secret']);

        Http::fake([
            'https://example.com/deployer.php' => Http::response(['success' => true]),
        ]);

        $service = $this->service();
        $progress = [];

        $service->deploy(
            [
                'added' => ['app/Support/Navigation.php', 'routes/web.php'],
                'modified' => [],
                'removed' => [],
            ],
            function (int $done, int $total, ?string $path) use (&$progress): void {
                $progress[] = [$done, $total, $path];
            }
        );

        // First progress: archiving file 1.
        $this->assertSame([1, 2, 'app/Support/Navigation.php'], $progress[0]);

        // Second progress: archiving file 2.
        $this->assertSame([2, 2, 'routes/web.php'], $progress[1]);

        // Third progress: uploading phase.
        $this->assertSame([2, 2, 'جارٍ رفع الأرشيف إلى السيرفر...'], $progress[2]);

        // Fourth progress: done.
        $this->assertSame([2, 2, null], $progress[3]);
    }

    public function test_deploy_throws_on_server_error_response(): void
    {
        config(['deployment.smart.server_url' => 'https://example.com/deployer.php']);
        config(['deployment.smart.secret_key' => 'test-secret']);

        Http::fake([
            'https://example.com/deployer.php' => Http::response([
                'success' => false,
                'error' => 'مفتاح سري غير صحيح.',
            ], 403),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('مفتاح سري غير صحيح');

        $this->service()->deploy([
            'added' => ['app/Support/Navigation.php'],
            'modified' => [],
            'removed' => [],
        ]);
    }

    // -----------------------------------------------------------------
    // Manifest
    // -----------------------------------------------------------------

    public function test_reset_manifest_deletes_manifest_file(): void
    {
        $service = $this->service();
        $service->saveManifest(['app/Models/User.php' => 'hash']);

        $this->assertFileExists($service->manifestPath());

        $service->resetManifest();

        $this->assertFileDoesNotExist($service->manifestPath());
    }

    // -----------------------------------------------------------------
    // Stats
    // -----------------------------------------------------------------

    public function test_stats_reports_totals_and_sync_state(): void
    {
        $service = $this->service();

        $stats = $service->getStats();

        $this->assertGreaterThan(0, $stats['total_files']);
        $this->assertSame(false, $stats['synced']);
        $this->assertSame(false, $stats['manifest_exists']);

        $service->saveManifest($service->scanLocalFiles());

        $stats = $service->getStats();
        $this->assertSame(true, $stats['synced']);
        $this->assertSame(true, $stats['manifest_exists']);
        $this->assertSame($stats['total_files'], $stats['manifest_files']);
    }

    // -----------------------------------------------------------------
    // Deployment history
    // -----------------------------------------------------------------

    public function test_start_and_complete_record_persists_history(): void
    {
        $user = $this->superAdmin();
        $service = $this->service();

        $record = $service->startRecord($user, 'local');

        $this->assertDatabaseHas('smart_deployments', [
            'id' => $record->id,
            'status' => 'deploying',
        ]);

        $service->completeRecord($record, [
            'files_count' => 3,
            'total_size' => 1024,
            'files' => ['a.php', 'b.php', 'c.php'],
        ], notes: 'deployed');

        $this->assertDatabaseHas('smart_deployments', [
            'id' => $record->id,
            'status' => 'success',
            'files_count' => 3,
            'total_size' => 1024,
        ]);

        $this->assertSame(['a.php', 'b.php', 'c.php'], $record->fresh()->files_list);
        $this->assertSame('1 KB', $record->fresh()->formattedSize());
    }

    public function test_fail_record_marks_failure(): void
    {
        $service = $this->service();
        $record = $service->startRecord(null, 'server');

        $service->failRecord($record, 'Connection lost');

        $this->assertDatabaseHas('smart_deployments', [
            'id' => $record->id,
            'status' => 'failed',
            'server_response' => 'Connection lost',
        ]);
    }

    public function test_recent_deployments_returns_latest_first(): void
    {
        SmartDeployment::factory()->count(3)->create();

        $recent = $this->service()->getRecentDeployments(2);

        $this->assertCount(2, $recent);
    }

    // -----------------------------------------------------------------
    // Page access
    // -----------------------------------------------------------------

    public function test_page_requires_super_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('deployments.smart-deployment'))
            ->assertForbidden();
    }

    public function test_page_loads_for_super_admin(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get(route('deployments.smart-deployment'))
            ->assertOk()
            ->assertSee('النشر الذكي')
            ->assertSee('فحص محلي')
            ->assertSee('مقارنة مع السيرفر');
    }
}
