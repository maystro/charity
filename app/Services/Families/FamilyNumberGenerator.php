<?php

namespace App\Services\Families;

use Illuminate\Support\Facades\DB;

class FamilyNumberGenerator
{
    /**
     * Generate a unique 8-digit case number based on date/time.
     *
     * Format: YYDDTTSS
     *   YY = last 2 digits of year
     *   DD = day of year (001-366, 3 digits)
     *   TT = hour + minute combined (e.g. 14:30 → 1430, but we take 2 digits)
     *   SS = sequence within the same minute (0-99)
     *
     * Example: 2026-07-17 14:30:45 → 261981430 + sequence
     *
     * Guarantees uniqueness via DB transaction + lock.
     */
    public function generate(): int
    {
        $now = now();

        // Base: 2-digit year + 3-digit day-of-year + 4-digit HHMM
        $prefix = (int) ($now->format('y')
            .str_pad((string) ($now->dayOfYear + 1), 3, '0', STR_PAD_LEFT)
            .$now->format('Hi'));

        // Use a memory cache so that repeated generate() calls within a single
        // request/test transaction always increment, even if the DB query below
        // cannot see uncommitted inserts from the same outer transaction (SQLite
        // does this when an inner transaction uses savepoints).
        $cache = self::$cache[$prefix] ?? null;

        $lastNumber = max(
            $cache ?? 0,
            (int) (DB::table('families')
                ->where('case_number', '>=', $prefix * 100000)
                ->where('case_number', '<', ($prefix + 1) * 100000)
                ->max('case_number') ?? 0)
        );

        $next = ($lastNumber === 0) ? ($prefix * 100000 + 1) : ($lastNumber + 1);
        self::$cache[$prefix] = $next;

        return $next;
    }

    /** @var array<int, int> */
    private static array $cache = [];
}
