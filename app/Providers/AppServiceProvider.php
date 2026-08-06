<?php

namespace App\Providers;

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
        // Custom pagination view uses app design tokens (avoids default dark: classes
        // that render a black bar when OS is in dark mode).
        Paginator::defaultView('components.ui.pagination-links');
    }
}
