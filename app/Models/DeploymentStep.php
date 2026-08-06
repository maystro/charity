<?php

namespace App\Models;

use App\Enums\DeploymentStepStatus;
use Database\Factories\DeploymentStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentStep extends Model
{
    /** @use HasFactory<DeploymentStepFactory> */
    use HasFactory;

    protected $fillable = [
        'deployment_id',
        'key',
        'label',
        'status',
        'started_at',
        'completed_at',
        'output',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeploymentStepStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => DeploymentStepStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $output = ''): void
    {
        $this->update([
            'status' => DeploymentStepStatus::Completed,
            'completed_at' => now(),
            'output' => $output,
        ]);
    }

    public function markAsFailed(string $output = ''): void
    {
        $this->update([
            'status' => DeploymentStepStatus::Failed,
            'completed_at' => now(),
            'output' => $output,
        ]);
    }

    public function markAsSkipped(): void
    {
        $this->update([
            'status' => DeploymentStepStatus::Skipped,
            'completed_at' => now(),
        ]);
    }
}
