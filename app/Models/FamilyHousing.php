<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyHousing extends Model
{
    protected $table = 'family_housing';

    protected $fillable = [
        'family_id',
        'housing_type',
        'housing_type_other',
        'residence_status',
        'floors_count',
        'rooms_count',
        'roof_type',
        'has_water',
        'has_electricity',
        'has_sewage',
        'finishing_description',
        'electrical_appliances',
        'home_furniture',
        'other_equipment',
    ];

    protected $casts = [
        'floors_count' => 'integer',
        'rooms_count' => 'integer',
        'has_water' => 'boolean',
        'has_electricity' => 'boolean',
        'has_sewage' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
