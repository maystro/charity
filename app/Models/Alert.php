<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'severity',
        'status',
        'alertable_type',
        'alertable_id',
        'created_by',
        'notified_user_id',
        'due_at',
        'dismissed_at',
        'read_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // --- Constants ---

    public const TYPE_REASSESSMENT_DUE = 'reassessment_due';

    public const TYPE_REASSESSMENT_OVERDUE = 'reassessment_overdue';

    public const TYPE_FAMILY_APPROVED = 'family_approved';

    public const TYPE_FAMILY_REJECTED = 'family_rejected';

    public const TYPE_AID_REQUEST_APPROVED = 'aid_request_approved';

    public const TYPE_AID_REQUEST_PARTIALLY_APPROVED = 'aid_request_partially_approved';

    public const TYPE_AID_REQUEST_REJECTED = 'aid_request_rejected';

    public const TYPE_AID_REQUEST_IN_EXECUTION = 'aid_request_in_execution';

    public const TYPE_AID_REQUEST_DELIVERED = 'aid_request_delivered';

    public const TYPE_AID_REQUEST_COMPLETED = 'aid_request_completed';

    public const TYPE_AID_REQUEST_OVERDUE = 'aid_request_overdue';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_RESOLVED = 'resolved';

    // --- Relationships ---

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function notifiedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Alerts addressed to a specific user (their notifications).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('notified_user_id', $user->id);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForAlertable(Builder $query, Model $model): Builder
    {
        return $query->where('alertable_type', $model::class)
            ->where('alertable_id', $model->id);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    // --- Helpers ---

    public function dismiss(): bool
    {
        return $this->update([
            'status' => self::STATUS_DISMISSED,
            'dismissed_at' => now(),
        ]);
    }

    public function resolve(): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}
