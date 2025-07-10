<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API V1 Routes - Kids App
|--------------------------------------------------------------------------
|
| Rutas API versión 1 para la aplicación móvil de control parental
| instalada en los dispositivos de los niños.
|
*/

// Rutas de autenticación (públicas)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'registerDevice'])
        ->middleware('throttle:device-auth');
    Route::post('/verify', [AuthController::class, 'verifyDevice'])
        ->middleware('throttle:device-auth');
    Route::get('/check-status', [AuthController::class, 'checkDeviceStatus'])
        ->middleware('throttle:device-status');
    Route::get('/status', [AuthController::class, 'deviceStatus'])
        ->middleware(['device.auth', 'throttle:device-status']);
});

// TEMPORAL: Rutas sin autenticación para testing
// TODO: ELIMINAR cuando la app implemente autenticación
if (config('app.env') !== 'production') {
    Route::prefix('sync')->middleware('throttle:device-sync')->group(function () {
        Route::get('/events', [SyncController::class, 'getEvents']);
    })->withoutMiddleware('device.auth');
}

// Rutas protegidas con device token
Route::middleware(['device.auth'])->group(function () {
    
    // Sincronización
    Route::prefix('sync')->middleware('throttle:device-sync')->group(function () {
        // Apps
        Route::get('/apps', [SyncController::class, 'getApps']);
        Route::post('/apps', [SyncController::class, 'postApps']);
        Route::delete('/apps', [SyncController::class, 'deleteApps']);
        
        // Horarios
        Route::get('/horarios', [SyncController::class, 'getHorarios']);
        Route::post('/horarios', [SyncController::class, 'postHorarios']);
        Route::delete('/horarios', [SyncController::class, 'deleteHorarios']);
        
        // Dispositivos
        Route::get('/devices', [SyncController::class, 'getDevices']);
        Route::post('/devices', [SyncController::class, 'postDevices']);
        
        // Sincronización incremental
        Route::get('/events', [SyncController::class, 'getEvents']);
        Route::post('/events', [SyncController::class, 'postEvents']);
        Route::get('/status', [SyncController::class, 'getSyncStatus']);
    });
    
    // Heartbeat y reportes
    Route::prefix('devices/{deviceId}')->middleware('throttle:device-heartbeat')->group(function () {
        Route::post('/heartbeat', [DeviceController::class, 'heartbeat']);
        Route::post('/usage', [DeviceController::class, 'reportUsage']);
        Route::post('/location', [DeviceController::class, 'updateLocation']);
        Route::post('/battery', [DeviceController::class, 'updateBattery']);
        Route::post('/blocked-attempts', [DeviceController::class, 'reportBlockedAttempt']);
    });
}); 