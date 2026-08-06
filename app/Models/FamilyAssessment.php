<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FamilyAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'round',
        'status',
        'case_type',
        'case_name',
        'community',
        'detailed_address',
        'phone',
        'family_type',
        'members_count',
        'total_income',
        'average_income_per_person',
        'review_notes',
        'rejection_reason',
        'created_by',
        'updated_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'members_count' => 'integer',
        'total_income' => 'decimal:2',
        'average_income_per_person' => 'decimal:2',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_assessment_id');
    }

    public function incomeSources(): HasMany
    {
        return $this->hasMany(FamilyIncomeSource::class, 'family_assessment_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(FamilyResource::class, 'family_assessment_id');
    }

    public function burdens(): HasMany
    {
        return $this->hasMany(FamilyBurden::class, 'family_assessment_id');
    }

    public function housing(): HasOne
    {
        return $this->hasOne(FamilyHousing::class, 'family_assessment_id');
    }

    public function aids(): HasMany
    {
        return $this->hasMany(FamilyAid::class, 'family_assessment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
