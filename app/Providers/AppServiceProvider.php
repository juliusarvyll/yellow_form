<?php

namespace App\Providers;

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
        // Register both admin and dean panels
        $this->app->resolveProvider(\App\Providers\Filament\AdminPanelProvider::class);
        $this->app->resolveProvider(\App\Providers\Filament\DeanPanelProvider::class);
    }
}
