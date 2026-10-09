<?php

namespace App\Providers;

use App\Models\AidRequestItem;
use App\Observers\AidRequestItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AidRequestItem::observe(AidRequestItemObserver::class);

        if ($this->app->environment('local', 'testing')) {
            Model::preventLazyLoading(! $this->app->runningInConsole());
        }

        // Custom pagination view uses app design tokens (avoids default dark: classes
        // that render a black bar when OS is in dark mode).
        Paginator::defaultView('components.ui.pagination-links');
    }
}
