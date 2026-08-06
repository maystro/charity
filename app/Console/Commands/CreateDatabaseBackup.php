<?php

namespace App\Console\Commands;

use App\Services\Backup\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:database-backup')]
#[Description('إنشاء نسخة احتياطية من قاعدة البيانات')]
class CreateDatabaseBackup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        try {
            $backup = $service->create();

            $this->info("تم إنشاء النسخة الاحتياطية: {$backup->filename}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
