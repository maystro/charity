<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class TopBarStatCache
{
    public function remember(string $key, Closure $callback): mixed
    {
        $userId = auth()->id() ?? 'guest';
        $cacheKey = "topbar_stats.{$userId}.{$key}";

        $value = Cache::remember(
            $cacheKey,
            now()->addSeconds(90),
            $callback
        );

        if ($this->containsIncompleteClass($value)) {
            Cache::forget($cacheKey);

            return $callback();
        }

        return $value;
    }

    protected function containsIncompleteClass(mixed $value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return true;
        }

        if (is_iterable($value)) {
            foreach ($value as $item) {
                if ($this->containsIncompleteClass($item)) {
                    return true;
                }
            }
        }

        return false;
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
