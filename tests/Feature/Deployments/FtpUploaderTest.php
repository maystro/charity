<?php

namespace Tests\Feature\Deployments;

use App\Enums\ReleaseChangeType;
use App\Models\Release;
use App\Models\ReleaseChange;
use App\Models\User;
use App\Services\Deployment\DeploymentFtpSettings;
use App\Services\Deployment\FtpClientContract;
use App\Services\Deployment\FtpUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Fake FTP client — no network calls are ever made during tests.
 */
class FakeFtpClient implements FtpClientContract
{
    public bool $failConnect = false;

    /** Number of times connect() has been called (including reconnects). */
    public int $connectCount = 0;

    /** Number of uploads that should throw before succeeding again. */
    public int $uploadFailuresLeft = 0;

    /** @var array<int, array{0: string, 1: string}> */
    public array $uploaded = [];

    /** @var array<int, string> */
    public array $deleted = [];

    /** @var array<int, string> */
    public array $dirs = [];

    /**
     * Files present in fake remote cache dirs, keyed by directory.
     *
     * @var array<string, array<int, string>>
     */
    public array $cacheFiles = [];

    /** @var array<int, string> */
    public array $clearedDirs = [];

    public function connect(): void
    {
        $this->connectCount++;

        if ($this->failConnect) {
            throw new RuntimeException('Connection refused');
        }
    }

    public function reconnect(): void
    {
        $this->connect();
    }

    public function ensureDirectory(string $remoteDir): void
    {
        $this->dirs[] = $remoteDir;
    }

    public function upload(string $localPath, string $remotePath): void
    {
        if ($this->uploadFailuresLeft > 0) {
            $this->uploadFailuresLeft--;

            throw new RuntimeException('فشل رفع الملف');
        }

        $this->uploaded[] = [$localPath, $remotePath];
    }

    public function delete(string $remotePath): void
    {
        $this->deleted[] = $remotePath;
    }

    public function listDirectory(string $remoteDir): array
    {
        $files = $this->cacheFiles[$remoteDir] ?? [];

        return array_map(
            fn (string $name): array => ['name' => $name, 'type' => 'file'],
            $files
        );
    }

    public function tree(string $remoteDir = ''): array
    {
        $files = $this->cacheFiles[$remoteDir] ?? [];

        return array_map(
            fn (string $name): array => [
                'path' => $remoteDir === '' ? $name : $remoteDir.'/'.$name,
                'type' => 'file',
                'size' => strlen($name),
            ],
            $files
        );
    }

    public function download(string $remotePath, string $localPath): void
    {
        file_put_contents($localPath, "remote:{$remotePath}");
    }

    public function deleteDirectory(string $remoteDir): void
    {
        $this->clearedDirs[] = $remoteDir;
    }

    public function disconnect(): void
    {
    }
}

class FtpUploaderTest extends TestCase
{
    use RefreshDatabase;

    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = base_path('routes/__uploader_test_tmp__.php');
        file_put_contents($this->tempFile, "<?php // test\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->tempFile);

        parent::tearDown();
    }

    private function release(): Release
    {
        $superAdmin = User::factory()->superAdmin()->create();

        return Release::factory()->create(['created_by' => $superAdmin->id]);
    }

    private function settings(array $overrides = []): DeploymentFtpSettings
    {
        $settings = app(DeploymentFtpSettings::class);
        $settings->set('ftp_host', $overrides['host'] ?? 'ftp.example.com');
        $settings->set('ftp_username', $overrides['username'] ?? 'deploy_user');
        $settings->set('ftp_password', $overrides['password'] ?? 'secret');
        $settings->set('ftp_root_path', $overrides['root'] ?? '/public_html');

        return $settings;
    }

    public function test_uploads_added_files_and_deletes_removed_ones(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Removed,
            'file_path' => 'app/Models/DeletedModel.php',
            'description' => 'ملف محذوف',
        ]);

        $this->settings();
        $client = new FakeFtpClient();

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(1, $stats['uploaded']);
        $this->assertSame(1, $stats['removed']);
        $this->assertSame([], $stats['failed']);
        $this->assertSame(['routes'], $client->dirs);
        $this->assertSame([[$this->tempFile, 'routes/__uploader_test_tmp__.php']], $client->uploaded);
        $this->assertSame(['app/Models/DeletedModel.php'], $client->deleted);
    }

    public function test_reports_missing_local_files_as_failed(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/does-not-exist.php',
            'description' => 'ملف غير موجود',
        ]);

        $this->settings();
        $client = new FakeFtpClient();

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(0, $stats['uploaded']);
        $this->assertCount(1, $stats['failed']);
        $this->assertSame([], $client->uploaded);
    }

    public function test_rejects_paths_outside_allowlist(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'vendor/package/file.php',
            'description' => 'خارج القائمة',
        ]);

        $this->settings();
        $client = new FakeFtpClient();

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(0, $stats['uploaded']);
        $this->assertCount(1, $stats['failed']);
        $this->assertSame([], $client->uploaded);
    }

    public function test_throws_when_ftp_not_configured(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('إعدادات FTP غير مكتملة');

        app(FtpUploader::class)->upload($release, new FakeFtpClient());
    }

    public function test_connection_failure_produces_failed_process_result(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();
        $client = new FakeFtpClient();
        $client->failConnect = true;

        $result = app(FtpUploader::class)->result($release, $client);

        $this->assertFalse($result->successful);
        $this->assertSame(1, $result->exitCode);
        $this->assertStringContainsString('Connection refused', $result->output);
    }

    public function test_success_produces_successful_process_result(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();

        $result = app(FtpUploader::class)->result($release, new FakeFtpClient());

        $this->assertTrue($result->successful);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('تم رفع 1 ملف', $result->output);
    }

    public function test_uploads_root_level_file_without_directory_creation(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'composer.json',
            'description' => 'ملف جذر',
        ]);

        $this->settings();
        $client = new FakeFtpClient();

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(1, $stats['uploaded']);
        $this->assertSame([], $stats['failed']);
        $this->assertSame([], $client->dirs);
        $this->assertSame([[base_path('composer.json'), 'composer.json']], $client->uploaded);
    }

    public function test_reconnects_and_retries_file_after_transient_drop(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();
        $client = new FakeFtpClient();
        $client->uploadFailuresLeft = 1;

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(1, $stats['uploaded']);
        $this->assertSame([], $stats['failed']);
        $this->assertSame(2, $client->connectCount);
        $this->assertSame([[$this->tempFile, 'routes/__uploader_test_tmp__.php']], $client->uploaded);
    }

    public function test_reports_failure_when_retry_also_fails(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();
        $client = new FakeFtpClient();
        $client->uploadFailuresLeft = 5;

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(0, $stats['uploaded']);
        $this->assertCount(1, $stats['failed']);
        $this->assertStringContainsString('فشل رفع الملف', $stats['failed'][0]);
        $this->assertSame(2, $client->connectCount);
    }

    public function test_clears_server_caches_after_upload(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();
        $client = new FakeFtpClient();
        $client->cacheFiles = [
            'bootstrap/cache' => ['config.php', 'routes-v7.php'],
            'storage/framework/views' => ['abc123.php', 'def456.php'],
        ];

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(4, $stats['cleared']);
        $this->assertContains('bootstrap/cache', $client->clearedDirs);
        $this->assertContains('storage/framework/views', $client->clearedDirs);

        $result = app(FtpUploader::class)->result($release, $client);
        $this->assertTrue($result->successful);
        $this->assertStringContainsString('تم مسح كاش السيرفر (4 عنصر).', $result->output);
    }

    public function test_skips_cache_dirs_that_do_not_exist(): void
    {
        $release = $this->release();
        ReleaseChange::create([
            'release_id' => $release->id,
            'type' => ReleaseChangeType::Added,
            'file_path' => 'routes/__uploader_test_tmp__.php',
            'description' => 'ملف اختبار',
        ]);

        $this->settings();
        $client = new FakeFtpClient();

        $stats = app(FtpUploader::class)->upload($release, $client);

        $this->assertSame(0, $stats['cleared']);
        $this->assertSame([], $client->clearedDirs);
    }
}
