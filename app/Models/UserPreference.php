<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'accent_color',
        'font_size',
        'ui_density',
        'sidebar_state',
        'reduced_motion',
    ];

    protected function casts(): array
    {
        return [
            'reduced_motion' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
