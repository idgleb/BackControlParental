<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
        
        // Configurar rate limiting
        $this->configureRateLimiting();
    }
    
    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiting para API general
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        
        // Rate limiting para registro/verificación de dispositivos (muy restrictivo)
        RateLimiter::for('device-auth', function ($request) {
            $identifier = $request->input('device_id') ?? $request->ip();
            return Limit::perMinute(10)
                ->by('device-auth:' . $identifier)
                ->response(function ($request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Demasiados intentos. Por favor, espere antes de intentar nuevamente.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });
        
        // Rate limiting para estado del dispositivo (moderado)
        RateLimiter::for('device-status', function ($request) {
            $token = $request->header('X-Device-Token') ?? $request->bearerToken() ?? $request->ip();
            return Limit::perMinute(60) // Aumentado para permitir verificación cada 3 segundos
                ->by('device-status:' . substr(hash('sha256', $token), 0, 16));
        });
        
        // Rate limiting para sincronización (permisivo para dispositivos autenticados)
        RateLimiter::for('device-sync', function ($request) {
            $token = $request->header('X-Device-Token') ?? $request->bearerToken();
            if ($token) {
                // Dispositivos autenticados: más permisivo
                return Limit::perMinute(120)
                    ->by('device-sync:' . substr(hash('sha256', $token), 0, 16));
            }
            // No autenticados: temporal mientras se implementa auth
            // TODO: Reducir a 5/min cuando la app implemente autenticación
            return Limit::perMinute(30)->by('device-sync:' . $request->ip())
                ->response(function ($request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Dispositivo no autenticado. Por favor registre y verifique el dispositivo.',
                        'code' => 'UNAUTHENTICATED',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });
        
        // Rate limiting para heartbeat y reportes (muy permisivo)
        RateLimiter::for('device-heartbeat', function ($request) {
            $deviceId = $request->route('deviceId') ?? $request->input('deviceId');
            $token = $request->header('X-Device-Token') ?? $request->bearerToken();
            
            if ($token && $deviceId) {
                // Por dispositivo específico
                return Limit::perMinute(60)
                    ->by('heartbeat:' . $deviceId);
            }
            
            // Fallback a IP
            return Limit::perMinute(20)->by('heartbeat:' . $request->ip());
        });
        
        // Rate limiting para operaciones críticas (bloqueo de apps desde padres)
        RateLimiter::for('parent-critical', function ($request) {
            return Limit::perMinute(10)
                ->by('parent:' . ($request->user()?->id ?? $request->ip()))
                ->response(function ($request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Por seguridad, esta operación tiene un límite de intentos.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });
    }
}
