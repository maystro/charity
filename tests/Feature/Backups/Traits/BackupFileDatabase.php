<?php

namespace Tests\Feature\Backups\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Switches the sqlite connection to a temporary file so the backup service
 * (which cannot operate on the in-memory database) can create real backups,
 * and isolates the backup directory from the app's real storage.
 */
trait BackupFileDatabase
{
    protected string $backupTempDb;

    protected string $backupTempDir;

    protected function setUpBackupFileDatabase(): void
    {
        $this->backupTempDb = sys_get_temp_dir().'/db_backup_test_'.uniqid().'.sqlite';
        $this->backupTempDir = storage_path('app/testing-database-backups');

        config([
            'database.connections.sqlite.database' => $this->backupTempDb,
            'backup.directory' => 'testing-database-backups',
        ]);

        DB::purge('sqlite');

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    protected function tearDownBackupFileDatabase(): void
    {
        if (is_dir($this->backupTempDir)) {
            foreach (glob($this->backupTempDir.'/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($this->backupTempDir);
        }

        DB::disconnect('sqlite');
        @unlink($this->backupTempDb);
    }
}
