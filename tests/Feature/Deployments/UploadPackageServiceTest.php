<?php

namespace Tests\Feature\Deployments;

use App\Enums\ReleaseChangeType;
use App\Livewire\Deployments\ShowRelease;
use App\Models\Release;
use App\Models\User;
use App\Services\Deployment\UploadPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UploadPackageServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_build_creates_zip_with_existing_files_and_removed_note(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);

        $release->changes()->createMany([
            ['type' => ReleaseChangeType::Modified, 'file_path' => 'composer.json', 'description' => 'اعتماديات'],
            ['type' => ReleaseChangeType::Added, 'file_path' => 'config/deployment.php', 'description' => 'إعدادات النشر'],
            ['type' => ReleaseChangeType::Removed, 'file_path' => 'app/Deleted.php', 'description' => 'حذف'],
        ]);

        $result = app(UploadPackageService::class)->build($release);

        $this->assertFileExists($result['path']);
        $this->assertSame(2, $result['count']);
        $this->assertSame(['app/Deleted.php'], $result['removed']);

        $this->createdFiles[] = $result['path'];

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($result['path']));
        $this->assertNotFalse($zip->locateName('composer.json'));
        $this->assertNotFalse($zip->locateName('config/deployment.php'));
        $this->assertNotFalse($zip->locateName('REMOVED_FILES.txt'));
        $this->assertStringContainsString('app/Deleted.php', $zip->getFromName('REMOVED_FILES.txt'));
        $zip->close();
    }

    public function test_build_skips_missing_files(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);

        $release->changes()->create([
            'type' => ReleaseChangeType::Modified,
            'file_path' => 'app/DoesNotExist.php',
            'description' => 'غير موجود',
        ]);

        $result = app(UploadPackageService::class)->build($release);

        $this->assertSame(0, $result['count']);
        $this->assertSame(['app/DoesNotExist.php'], $result['missing']);
        $this->assertFileExists($result['path']);

        $this->createdFiles[] = $result['path'];
    }

    public function test_old_packages_for_same_release_are_cleared(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);
        $release->changes()->create([
            'type' => ReleaseChangeType::Modified,
            'file_path' => 'composer.json',
            'description' => 'x',
        ]);

        $service = app(UploadPackageService::class);
        $first = $service->build($release);
        $this->createdFiles[] = $first['path'];

        $second = $service->build($release);
        $this->createdFiles[] = $second['path'];

        $this->assertFileDoesNotExist($first['path']);
        $this->assertFileExists($second['path']);
    }

    public function test_prepare_upload_package_triggers_download(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);
        $release->changes()->create([
            'type' => ReleaseChangeType::Modified,
            'file_path' => 'composer.json',
            'description' => 'x',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->call('prepareUploadPackage')
            ->assertFileDownloaded()
            ->assertSet('preparingPackage', false)
            ->assertSet('lastPackage.count', 1);
    }

    public function test_prepare_upload_package_handles_empty_changes(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->published()->create(['created_by' => $superAdmin->id]);

        Livewire::actingAs($superAdmin)
            ->test(ShowRelease::class, ['release' => $release])
            ->call('prepareUploadPackage')
            ->assertDispatched('notify')
            ->assertSet('preparingPackage', false);
    }
}
