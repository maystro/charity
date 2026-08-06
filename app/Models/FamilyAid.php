<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyAid extends Model
{
    protected $fillable = [
        'family_id',
        'aid_type',
        'eligible',
        'reasons',
    ];

    protected $casts = [
        'eligible' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
