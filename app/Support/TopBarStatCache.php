<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class TopBarStatCache
{
    public function remember(string $key, Closure $callback): mixed
    {
        $userId = auth()->id() ?? 'guest';

        return Cache::remember(
            "topbar_stats.{$userId}.{$key}",
            now()->addSeconds(90),
            $callback
        );
    }

    public function forget(string $key): void
    {
        $userId = auth()->id() ?? 'guest';

        Cache::forget("topbar_stats.{$userId}.{$key}");
    }

    public function forgetAll(): void
    {
        foreach ([
            'pending_approvals',
            'new_aid_requests',
            'reassessment_due_families',
            'execution_due_requests',
        ] as $key) {
            $this->forget($key);
        }
    }
}
