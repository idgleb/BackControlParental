<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Parent\DeviceController;
use App\Http\Controllers\Api\Parent\ScheduleController;
use App\Http\Controllers\Api\Parent\ReportController;
use App\Http\Controllers\Api\Parent\NotificationController;

/*
|--------------------------------------------------------------------------
| Parent App API Routes
|--------------------------------------------------------------------------
|
| Rutas API para la aplicación móvil de padres. Todas requieren
| autenticación mediante Sanctum token.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Dispositivos
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/{device}', [DeviceController::class, 'show']);
    Route::prefix('devices/{device}')->group(function () {
        Route::get('/location', [DeviceController::class, 'location']);
        Route::get('/status', [DeviceController::class, 'status']);
        // Route::post('/lock', [DeviceController::class, 'lock']);
        // Route::post('/unlock', [DeviceController::class, 'unlock']);
        // Route::post('/message', [DeviceController::class, 'sendMessage']);
        
        // Apps
        Route::get('/apps', [DeviceController::class, 'apps']);
        Route::post('/apps/{packageName}/block', [DeviceController::class, 'blockApp'])
            ->middleware('throttle:parent-critical');
        Route::post('/apps/{packageName}/unblock', [DeviceController::class, 'unblockApp'])
            ->middleware('throttle:parent-critical');
        // Route::put('/apps/{app}/limit', [DeviceController::class, 'setAppLimit']);
        
        // Horarios
        Route::apiResource('schedules', ScheduleController::class);
        Route::post('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle']);
        
        // Reportes
        Route::prefix('reports')->group(function () {
            Route::get('/usage/today', [ReportController::class, 'todayUsage']);
            Route::get('/usage/week', [ReportController::class, 'weekUsage']);
            Route::get('/usage/month', [ReportController::class, 'monthUsage']);
            Route::get('/apps', [ReportController::class, 'appsUsage']);
            Route::get('/blocked-attempts', [ReportController::class, 'blockedAttempts']);
        });
    });
    
    // Notificaciones/Alertas
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        
        // Solo para desarrollo
        if (!app()->environment('production')) {
            Route::post('/test', [NotificationController::class, 'createTestNotification']);
        }
    });
    
    // Dashboard
    Route::get('/dashboard/summary', [DeviceController::class, 'dashboardSummary']);
}); 