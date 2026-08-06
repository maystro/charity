<?php

namespace App\Models;

use App\Enums\DatabaseBackupStatus;
use Database\Factories\DatabaseBackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    /** @use HasFactory<DatabaseBackupFactory> */
    use HasFactory;

    protected $fillable = [
        'filename',
        'size_bytes',
        'status',
        'failure_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DatabaseBackupStatus::class,
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Whether this backup was created automatically (scheduler) — no user.
     */
    public function isSystem(): bool
    {
        return $this->created_by === null;
    }
}
