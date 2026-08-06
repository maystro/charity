<?php

namespace App\Models;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStepStatus;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    /** @use HasFactory<DeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'release_id',
        'environment',
        'status',
        'started_at',
        'completed_at',
        'source_revision',
        'failure_reason',
        'output_log',
        'created_by',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'environment' => DeploymentEnvironment::class,
            'status' => DeploymentStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DeploymentStep::class)->orderBy('sort_order');
    }

    /**
     * Scope to deployments that are pending or in progress.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [DeploymentStatus::Pending, DeploymentStatus::InProgress]);
    }

    public function markAsCompleted(?string $sourceRevision = null): void
    {
        $this->update([
            'status' => DeploymentStatus::Completed,
            'completed_at' => now(),
            'source_revision' => $sourceRevision ?? $this->source_revision,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => DeploymentStatus::Failed,
            'completed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function markAsRolledBack(): void
    {
        $this->update([
            'status' => DeploymentStatus::RolledBack,
            'rolled_back_at' => now(),
        ]);
    }

    public function duration(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();

        return (int) $this->started_at->diffInSeconds($end);
    }

    /**
     * Progress percentage based on real step states, or null when no steps exist.
     */
    public function progressPercentage(): ?int
    {
        if ($this->steps->isEmpty()) {
            return null;
        }

        $total = $this->steps->count();
        $finished = $this->steps->filter(
            fn (DeploymentStep $step) => in_array($step->status, [DeploymentStepStatus::Completed, DeploymentStepStatus::Skipped], true)
        )->count();

        return (int) round($finished / $total * 100);
    }

    /**
     * The step currently being executed, if any.
     */
    public function currentStep(): ?DeploymentStep
    {
        return $this->steps->firstWhere('status', DeploymentStepStatus::InProgress);
    }
}
