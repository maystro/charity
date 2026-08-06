<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialResearch extends Model
{
    use HasFactory;

    protected $table = 'social_researches';

    protected $fillable = [
        'family_id',
        'research_number',
        'research_type',
        'conducted_at',
        'approved_at',
        'expiry_date',
        'eligibility_degree',
        'average_income',
        'net_income',
        'recommendation',
        'committee_decision',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'conducted_at' => 'date',
        'approved_at' => 'date',
        'expiry_date' => 'date',
        'average_income' => 'decimal:2',
        'net_income' => 'decimal:2',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'research_id');
    }
}
