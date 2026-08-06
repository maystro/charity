<?php

namespace App\Jobs;

use App\Models\SmartDeployment;
use App\Services\Deployment\SmartDeploymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SmartDeploymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * @param  array{added: array<int, string>, modified: array<int, string>, removed: array<int, string>, total_size: int, notes?: string|null}  $changes
     */
    public function __construct(
        public SmartDeployment $record,
        public array $changes,
    ) {
    }

    public function handle(SmartDeploymentService $service): void
    {
        $files = array_merge($this->changes['added'] ?? [], $this->changes['modified'] ?? []);
        $total = count($files);

        try {
            $service->deploy(
                [
                    'added' => $this->changes['added'] ?? [],
                    'modified' => $this->changes['modified'] ?? [],
                    'removed' => $this->changes['removed'] ?? [],
                ]
            );

            $service->completeRecord(
                $this->record,
                [
                    'files_count' => $total,
                    'total_size' => $this->changes['total_size'] ?? 0,
                    'files' => $files,
                ],
                notes: $this->changes['notes'] ?? null,
            );
        } catch (Throwable $e) {
            $service->failRecord($this->record, $e->getMessage());
        }
    }
}
