<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Providers\BroadcastServiceProvider;
// use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom providers
        $this->app->register(BroadcastServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paginator::useBootstrap();
    }
}
