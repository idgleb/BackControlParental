<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        Blade::componentNamespace('App\\View\\Components\\Layout', 'layout');

        // Forzar HTTPS cuando se accede a través de ngrok
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || 
            request()->server('HTTP_HOST') && str_contains(request()->server('HTTP_HOST'), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
