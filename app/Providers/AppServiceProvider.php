<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Organization;
use App\Observers\OrganizationObserver;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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

        // 20 citas por minuto por IP
        // 60 por minuto por organización
        RateLimiter::for('booking', function ($request) {

            $organization = $request->route('organization');

            // si aún es slug (string), úsalo directamente
            $orgKey = is_string($organization)
                ? $organization
                : $organization?->id;

            return [
                Limit::perMinute(20)->by($request->ip()),

                // evita spam masivo contra una misma organización
                Limit::perMinute(60)->by($request->ip() . '|' . $orgKey),
            ];
        });
    }
}
