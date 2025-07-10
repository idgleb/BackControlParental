<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // Rutas web estándar
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Rutas API estándar
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // Rutas AJAX personalizadas
            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/ajax.php'));
        });
    }
}
