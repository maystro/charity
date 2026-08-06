<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'national_id',
        'relationship',
        'occupation',
        'income',
        'sort_order',
    ];

    protected $casts = [
        'income' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
