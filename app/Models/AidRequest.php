<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AidRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'request_number',
        'family_id',
        'branch_id',
        'area_id',
        'representative_id',
        'created_by',
        'submitted_by',
        'source_type',
        'applicant_name',
        'applicant_relation',
        'applicant_phone',
        'request_type',
        'priority',
        'title',
        'description',
        'requested_at',
        'needed_by',
        'campaign_id',
        'status',
        'internal_notes',
        'exception_reason',
        'duplicate_reason',
        'total_estimated_amount',
        'submitted_at',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'needed_by' => 'date',
        'submitted_at' => 'datetime',
        'total_estimated_amount' => 'decimal:2',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AidRequestItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AidRequestAttachment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AidRequestStatusHistory::class);
    }
}
