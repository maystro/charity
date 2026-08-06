<?php

namespace App\Models;

use App\Enums\FamilyStatus;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @use HasFactory<FamilyFactory>
 */
class Family extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Use case_number in URLs instead of id.
     * e.g. /families/261981430 instead of /families/2
     */
    public function getRouteKeyName(): string
    {
        return 'case_number';
    }

    protected $fillable = [
        'case_number',
        'case_type',
        'case_name',
        'community',
        'detailed_address',
        'phone',
        'family_type',
        'members_count',
        'total_income',
        'average_income_per_person',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'review_notes',
        'rejection_reason',
        'created_by',
        'updated_by',
        'current_assessment_id',
        'fieldworker_id',
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

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function incomeSources(): HasMany
    {
        return $this->hasMany(FamilyIncomeSource::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(FamilyResource::class);
    }

    public function burdens(): HasMany
    {
        return $this->hasMany(FamilyBurden::class);
    }

    public function housing(): HasOne
    {
        return $this->hasOne(FamilyHousing::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(FamilyStatusHistory::class)->orderByDesc('created_at');
    }

    public function aidRequests(): HasMany
    {
        return $this->hasMany(AidRequest::class);
    }

    public function socialResearches(): HasMany
    {
        return $this->hasMany(SocialResearch::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function aids(): HasMany
    {
        return $this->hasMany(FamilyAid::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(FamilyAssessment::class)->orderByDesc('round');
    }

    public function currentAssessment(): BelongsTo
    {
        return $this->belongsTo(FamilyAssessment::class, 'current_assessment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fieldworker(): BelongsTo
    {
        return $this->belongsTo(Fieldworker::class);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Get aids keyed by aid_type for easy access.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAidsAttribute(): array
    {
        return $this->aids()->get()->keyBy('aid_type')->map(fn ($aid) => [
            'eligible' => $aid->eligible,
            'reasons' => $aid->reasons,
        ])->toArray();
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === FamilyStatus::Approved->value;
    }

    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, [
            FamilyStatus::Draft->value,
            FamilyStatus::NeedsCompletion->value,
        ], true);
    }
}
