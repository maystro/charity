<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'visit_number',
        'visit_type',
        'priority',
        'purpose',
        'family_id',
        'research_id',
        'aid_request_id',
        'branch_id',
        'area_id',
        'representative_id',
        'researcher_id',
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_minutes',
        'contacted_person',
        'contacted_person_relation',
        'address_snapshot',
        'latitude',
        'longitude',
        'location_verified',
        'outcome_summary',
        'recommendations',
        'next_follow_up_at',
        'not_completed_reason',
        'created_by',
        'completed_by',
        'notes',
        'status',
        'is_overdue',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'location_verified' => 'boolean',
        'is_overdue' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function research(): BelongsTo
    {
        return $this->belongsTo(SocialResearch::class, 'research_id');
    }

    public function aidRequest(): BelongsTo
    {
        return $this->belongsTo(AidRequest::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Fieldworker::class, 'representative_id');
    }

    public function researcher(): BelongsTo
    {
        return $this->belongsTo(Fieldworker::class, 'researcher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(VisitStatusHistory::class);
    }

    /** Scope: visits that are overdue (past scheduled_at, still pending). */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_overdue', true)
            ->whereIn('status', VisitStatus::pendingStatuses());
    }

    /** Scope: visits scheduled for today. */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', today())
            ->whereIn('status', VisitStatus::pendingStatuses());
    }

    /** Scope: upcoming visits (future scheduled_at, still pending). */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>', now())
            ->whereIn('status', VisitStatus::pendingStatuses());
    }

    /** Scope: completed visits. */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', VisitStatus::completedStatuses());
    }
}
