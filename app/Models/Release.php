<?php

namespace App\Models;

use App\Enums\ReleaseStatus;
use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'description',
        'status',
        'source_revision',
        'file_snapshot',
        'released_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReleaseStatus::class,
            'file_snapshot' => 'array',
            'released_at' => 'datetime',
        ];
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ReleaseChange::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::Draft);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::Published);
    }

    public function scopeRolledBack(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::RolledBack);
    }

    public function isPublished(): bool
    {
        return $this->status === ReleaseStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === ReleaseStatus::Draft;
    }

    public function isRolledBack(): bool
    {
        return $this->status === ReleaseStatus::RolledBack;
    }
}
