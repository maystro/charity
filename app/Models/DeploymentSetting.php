<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single deployment setting value.
 *
 * Sensitive values (passwords/tokens) are encrypted at rest via the
 * `encrypted` cast. The `key` is unique so upserts are safe.
 */
class DeploymentSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'value' => 'encrypted',
        ];
    }
}

