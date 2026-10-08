<?php

namespace App\Console\Commands;

use App\Services\Families\ResearchSheetImporter;
use Illuminate\Console\Command;

class ImportResearchSheet extends Command
{
    protected $signature = 'app:import-research-sheet';

    protected $description = 'Import research sheet from docs/نموذج تسجيل ابحاث.xlsx';

    public function handle(ResearchSheetImporter $importer): int
    {
        $this->info('Starting import...');

        try {
            $result = $importer->import();
        } catch (\Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }

        $this->info("Imported: {$result['imported']}, Skipped: {$result['skipped']}");

        return 0;
    }
}
