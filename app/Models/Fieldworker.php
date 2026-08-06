<?php

namespace App\Models;

use Database\Factories\FieldworkerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<FieldworkerFactory>
 */
class Fieldworker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'governorate',
        'area',
        'status',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * الحساب المرتبط بالمندوب (للدخول إلى النظام).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الأسر التي قام المندوب بإجراء بحوثها/تقييمها.
     *
     * @return HasMany<Family, $this>
     */
    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->user_id) {
            return null;
        }

        return $this->user?->photo;
    }
}
