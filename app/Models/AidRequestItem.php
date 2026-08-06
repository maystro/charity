<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AidRequestItem extends Model
{
    protected $fillable = [
        'aid_request_id',
        'aid_type',
        'category_id',
        'subcategory_id',
        'title',
        'description',
        'execution_type',
        'quantity',
        'unit_id',
        'unit_cost',
        'estimated_total',
        'recurrence_type',
        'frequency',
        'recurrence_interval_days',
        'recurrence_start',
        'recurrence_end',
        'execution_start_date',
        'installments_count',
        'preferred_due_day',
        'stop_when_research_expires',
        'reminder_enabled',
        'reminder_days',
        'priority',
        'payee_type',
        'payee_name',
        'payee_phone',
        'notes',
        'sort_order',
        'approved',
        'reviewed_at',
        'reviewer_id',
        'delivered',
        'delivery_date',
        'delivery_notes',
        'delivered_by',
        'actual_cost',
        'purchase_date',
        'purchase_notes',
        'purchased_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'stop_when_research_expires' => 'boolean',
        'reminder_enabled' => 'boolean',
        'recurrence_start' => 'date',
        'recurrence_end' => 'date',
        'execution_start_date' => 'date',
        'purchase_date' => 'date',
        'approved' => 'boolean',
        'reviewed_at' => 'datetime',
        'delivered' => 'boolean',
        'delivery_date' => 'date',
    ];

    public function aidRequest(): BelongsTo
    {
        return $this->belongsTo(AidRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AidRequestAttachment::class, 'aid_request_item_id');
    }

    /** هل المساعدة دورية؟ */
    public function isRecurring(): bool
    {
        return $this->execution_type === 'دورية' || $this->recurrence_type === 'دورية';
    }
}
