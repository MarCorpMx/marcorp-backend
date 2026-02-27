<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Organization;
use App\Observers\OrganizationObserver;

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
        Organization::observe(OrganizationObserver::class);
    }
}
