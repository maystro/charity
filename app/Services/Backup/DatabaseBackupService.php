<?php

namespace App\Services\Backup;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

/**
 * Creates, downloads, restores, and prunes database backups.
 *
 * SQLite backups use `VACUUM INTO` which produces a consistent snapshot even
 * while the database is in use. MySQL/PostgreSQL backups shell out to
 * mysqldump/pg_dump with credentials passed through environment variables
 * (never on the command line).
 */
class DatabaseBackupService
{
    /**
     * Directory where backups are stored (under storage/app).
     */
    public function directory(): string
    {
        return storage_path('app/'.config('backup.directory', 'database-backups'));
    }

    /**
     * Create a backup of the current database.
     */
    public function create(?User $user = null): DatabaseBackup
    {
        $this->ensureDirectory();

        $filename = 'backup-'.now()->format('Ymd-His-u').'.'.$this->extension();
        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;

        try {
            $this->dump($path);

            $backup = DatabaseBackup::create([
                'filename' => $filename,
                'size_bytes' => filesize($path) ?: 0,
                'status' => DatabaseBackupStatus::Completed,
                'created_by' => $user?->id,
            ]);

            $this->prune((int) config('backup.keep', 5));

            return $backup;
        } catch (\Throwable $e) {
            @unlink($path);

            throw new RuntimeException('تعذر إنشاء النسخة الاحتياطية: '.$e->getMessage());
        }
    }

    /**
     * Restore the database from a backup file.
     */
    public function restore(DatabaseBackup $backup): void
    {
        $source = $this->directory().DIRECTORY_SEPARATOR.$backup->filename;

        if (! is_file($source)) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير موجود.');
        }

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $this->restoreSqlite($source);

            return;
        }

        throw new RuntimeException("استعادة قاعدة البيانات من النوع [{$driver}] غير مدعومة من الواجهة.");
    }

    /**
     * Download a backup file.
     */
    public function download(DatabaseBackup $backup): BinaryFileResponse
    {
        $path = $this->directory().DIRECTORY_SEPARATOR.$backup->filename;

        if (! is_file($path)) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير موجود.');
        }

        return response()->download($path, $backup->filename);
    }

    /**
     * Delete a backup file and its record.
     */
    public function delete(DatabaseBackup $backup): void
    {
        @unlink($this->directory().DIRECTORY_SEPARATOR.$backup->filename);
        $backup->delete();
    }

    /**
     * Keep only the latest $keep backups, deleting older ones.
     *
     * @return int Number of deleted backups.
     */
    public function prune(int $keep = 5): int
    {
        $ids = DatabaseBackup::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->pluck('id')
            ->values();

        $oldIds = $ids->slice($keep);

        if ($oldIds->isEmpty()) {
            return 0;
        }

        $old = DatabaseBackup::query()->whereIn('id', $oldIds)->get();

        foreach ($old as $backup) {
            $this->delete($backup);
        }

        return $old->count();
    }

    /**
     * Whether a backup can be restored (SQLite only for now).
     */
    public function restoreSupported(): bool
    {
        return config('database.default') === 'sqlite';
    }

    protected function ensureDirectory(): void
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function extension(): string
    {
        return match (config('database.default')) {
            'mysql', 'pgsql' => 'sql',
            default => 'sqlite',
        };
    }

    protected function dump(string $path): void
    {
        $driver = config('database.default');

        match ($driver) {
            'sqlite' => $this->dumpSqlite($path),
            'mysql' => $this->dumpMysql($path),
            'pgsql' => $this->dumpPostgres($path),
            default => throw new RuntimeException("محرك قاعدة البيانات [{$driver}] غير مدعوم."),
        };
    }

    protected function dumpSqlite(string $path): void
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('قاعدة البيانات في الذاكرة (memory) — لا يمكن إنشاء نسخة منها.');
        }

        // VACUUM INTO produces a compact, consistent snapshot and works while
        // the database is being used by other connections.
        DB::statement("VACUUM INTO '".addslashes($path)."'");
    }

    protected function dumpMysql(string $path): void
    {
        $config = config('database.connections.mysql');

        $process = new Process([
            $config['dump'] ?? 'mysqldump',
            '--host='.$config['host'],
            '--port='.(string) $config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--routines',
            (string) $config['database'],
        ]);
        $process->setTimeout(600);

        if (! empty($config['password'])) {
            $process->setEnv(['MYSQL_PWD' => (string) $config['password']]);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        file_put_contents($path, $process->getOutput());
    }

    protected function dumpPostgres(string $path): void
    {
        $config = config('database.connections.pgsql');

        $process = new Process([
            $config['dump'] ?? 'pg_dump',
            '--host='.$config['host'],
            '--port='.(string) $config['port'],
            '--username='.$config['username'],
            '--no-password',
            (string) $config['database'],
        ]);
        $process->setTimeout(600);
        $process->setEnv(['PGPASSWORD' => (string) ($config['password'] ?? '')]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        file_put_contents($path, $process->getOutput());
    }

    protected function restoreSqlite(string $source): void
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('قاعدة البيانات في الذاكرة — لا يمكن استعادتها.');
        }

        // Copy to a temp file in the same directory, then rename over the live
        // file — rename is atomic on the same filesystem.
        $temp = dirname($database).DIRECTORY_SEPARATOR.'database.restore-'.now()->format('YmdHisu').'.sqlite';

        try {
            copy($source, $temp);

            DB::disconnect();

            if (! rename($temp, $database)) {
                throw new RuntimeException('تعذر استبدال ملف قاعدة البيانات.');
            }

            DB::reconnect();
        } catch (\Throwable $e) {
            @unlink($temp);

            throw new RuntimeException('فشلت الاستعادة: '.$e->getMessage());
        }
    }
}
