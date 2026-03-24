<?php

namespace App\Providers;

use App\Models\ServiceLog;
use App\Observers\ServiceLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ServiceLogObserver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ServiceLog::observe(ServiceLogObserver::class);
    }
}
