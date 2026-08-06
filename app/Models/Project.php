<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'governorate',
        'status',
        'total_budget',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'total_budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProjectPhase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * إجمالي التبرعات الواردة للمشروع.
     */
    protected function totalDonations(): Attribute
    {
        return Attribute::get(fn () => (float) $this->donations()->sum('amount'));
    }

    /**
     * إجمالي تكلفة مراحل المشروع — مصدره المراحل الفرعية.
     */
    protected function phasesCost(): Attribute
    {
        return Attribute::get(fn () => (float) $this->phases()->sum('cost'));
    }
}
