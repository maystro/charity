<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartDeployment extends Model
{
    /** @use HasFactory<\Database\Factories\SmartDeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mode',
        'status',
        'files_count',
        'total_size',
        'files_list',
        'notes',
        'server_response',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'files_list' => 'array',
            'total_size' => 'integer',
            'files_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'deploying';
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->total_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function duration(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return max(0, (int) $this->completed_at->diffInSeconds($this->started_at));
    }
}
