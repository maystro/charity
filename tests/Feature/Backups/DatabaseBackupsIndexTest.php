<?php

namespace Tests\Feature\Backups;

use App\Enums\DatabaseBackupStatus;
use App\Livewire\Backups\Index;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Backups\Traits\BackupFileDatabase;
use Tests\TestCase;

class DatabaseBackupsIndexTest extends TestCase
{
    use BackupFileDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackupFileDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownBackupFileDatabase();

        parent::tearDown();
    }

    public function test_component_renders_for_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->assertOk()
            ->assertSee('النسخ الاحتياطية');
    }

    public function test_component_aborts_for_non_super_admin(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_create_action_creates_backup_and_dispatches_notify(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->call('create')
            ->assertDispatched('notify', type: 'success');

        $backup = DatabaseBackup::query()->firstOrFail();

        $this->assertSame(DatabaseBackupStatus::Completed, $backup->status);
        $this->assertSame($superAdmin->id, $backup->created_by);
        $this->assertFileExists(app(DatabaseBackupService::class)->directory().DIRECTORY_SEPARATOR.$backup->filename);
    }

    public function test_delete_action_removes_backup(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $backup = app(DatabaseBackupService::class)->create($superAdmin);

        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->call('delete', $backup->id);

        $this->assertDatabaseMissing('database_backups', ['id' => $backup->id]);
    }

    public function test_restore_requires_exact_filename_confirmation(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'المستخدم الأصلي']);
        $backup = app(DatabaseBackupService::class)->create($superAdmin);

        User::where('id', $superAdmin->id)->delete();

        // Wrong confirmation — must fail with validation error.
        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->set('restoringId', $backup->id)
            ->set('confirmFilename', 'خطأ')
            ->call('restore')
            ->assertHasErrors('confirmFilename');

        $this->assertDatabaseMissing('users', ['id' => $superAdmin->id]);

        // Correct confirmation — restores the snapshot.
        Livewire::actingAs($superAdmin)
            ->test(Index::class)
            ->set('restoringId', $backup->id)
            ->set('confirmFilename', $backup->filename)
            ->call('restore')
            ->assertDispatched('notify', type: 'success');

        $this->assertSame('المستخدم الأصلي', DB::table('users')->where('id', $superAdmin->id)->value('name'));
    }
}
