<?php

namespace App\Services\AidRequests;

use Illuminate\Support\Facades\DB;

class AidRequestNumberGenerator
{
    /**
     * Generate a unique, sequential request number like AR-2026-000001.
     * Uses a DB lock to prevent duplicates under concurrent requests.
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "AR-{$year}-";

            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            // جلب آخر رقم للسنة الحالية بطريقة متوافقة مع SQLite و MySQL
            $lastNumber = DB::table('aid_requests')
                ->where('request_number', 'like', "{$prefix}%")
                ->when($isSqlite, fn ($q) => $q->lockForUpdate())
                ->pluck('request_number')
                ->map(fn ($n) => (int) substr((string) $n, strlen($prefix)))
                ->max();

            $next = ($lastNumber ?? 0) + 1;

            return $prefix.str_pad((int) $next, 6, '0', STR_PAD_LEFT);
        });
    }
}
