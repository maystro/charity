<?php

namespace App\Models;

use App\Enums\ReleaseChangeType;
use Database\Factories\ReleaseChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseChange extends Model
{
    /** @use HasFactory<ReleaseChangeFactory> */
    use HasFactory;

    protected $fillable = [
        'release_id',
        'type',
        'file_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReleaseChangeType::class,
        ];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
