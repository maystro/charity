<?php

namespace Tests\Feature\Backups;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Backups\Traits\BackupFileDatabase;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    use BackupFileDatabase;

    protected DatabaseBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackupFileDatabase();
        $this->service = app(DatabaseBackupService::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownBackupFileDatabase();

        parent::tearDown();
    }

    public function test_create_creates_backup_file_and_record(): void
    {
        $user = User::factory()->superAdmin()->create();

        $backup = $this->service->create($user);

        $this->assertTrue($backup->status === DatabaseBackupStatus::Completed);
        $this->assertSame($user->id, $backup->created_by);
        $this->assertFileExists($this->service->directory().DIRECTORY_SEPARATOR.$backup->filename);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertDatabaseHas('database_backups', ['id' => $backup->id]);
    }

    public function test_create_marks_system_backup_without_user(): void
    {
        $backup = $this->service->create();

        $this->assertNull($backup->created_by);
        $this->assertTrue($backup->isSystem());
    }

    public function test_prune_keeps_only_latest_keep_backups(): void
    {
        config(['backup.keep' => 100]);

        foreach (range(1, 7) as $i) {
            $this->service->create();
        }

        $this->assertSame(7, DatabaseBackup::count());

        $this->service->prune(5);

        $this->assertSame(5, DatabaseBackup::count());
        $this->assertSame(5, count(glob($this->service->directory().'/*') ?: []));
    }

    public function test_delete_removes_file_and_record(): void
    {
        $backup = $this->service->create();

        $this->service->delete($backup);

        $this->assertDatabaseMissing('database_backups', ['id' => $backup->id]);
        $this->assertFileDoesNotExist($this->service->directory().DIRECTORY_SEPARATOR.$backup->filename);
    }

    public function test_download_returns_binary_file_response(): void
    {
        $backup = $this->service->create();

        $response = $this->service->download($backup);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($backup->filename, $response->headers->get('content-disposition'));
    }

    public function test_restore_replaces_database_with_backup_snapshot(): void
    {
        $user = User::factory()->superAdmin()->create(['name' => 'المستخدم الأصلي']);
        $backup = $this->service->create($user);

        User::where('id', $user->id)->delete();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->service->restore($backup);

        $this->assertSame('المستخدم الأصلي', DB::table('users')->where('id', $user->id)->value('name'));
    }

    public function test_restore_rejects_missing_file(): void
    {
        $backup = DatabaseBackup::factory()->create(['filename' => 'does-not-exist.sqlite']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ملف النسخة الاحتياطية غير موجود');

        $this->service->restore($backup);
    }
}
