<?php

namespace App\Livewire\Shared;

use App\Support\TopBarStatCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Lightweight poller: invalidates top-bar stat cache periodically (one request, not four).
 */
class TopBarStatsRefresher extends Component
{
    public function refreshStats(): void
    {
        app(TopBarStatCache::class)->forgetAll();

        $userId = auth()->id() ?? 'guest';
        Cache::forget("sidebar_badges.{$userId}");

        $this->dispatch('topbar-stats-cache-cleared');
    }

    public function render(): View
    {
        return view('livewire.shared.top-bar-stats-refresher');
    }
}
