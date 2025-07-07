<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| AJAX Routes
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas que son llamadas por JavaScript desde el
| frontend web. Estas rutas siempre devuelven JSON y requieren
| autenticación por sesión web.
|
*/

Route::middleware(['web', 'auth'])->prefix('ajax')->group(function () {
    
    // Dispositivos
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'getDevices']);
        Route::get('/{device}', [DeviceController::class, 'show']);
        Route::get('/{device}/status', [DeviceController::class, 'getStatus']);
        Route::delete('/{device}', [DeviceController::class, 'destroy']);
    });
    
    // Apps de dispositivos
    Route::prefix('devices/{device}/apps')->group(function () {
        Route::post('/{app}/update-field', [DeviceController::class, 'updateAppField']);
        Route::put('/batch-update', [DeviceController::class, 'updateApps']);
    });
    
    // Horarios
    Route::prefix('devices/{device}/horarios')->group(function () {
        Route::get('/', [HorarioController::class, 'getHorariosForDevice']);
        Route::get('/by-id/{idHorario}', [HorarioController::class, 'getHorarioByDeviceAndIdHorario']);
        Route::post('/', [HorarioController::class, 'store']);
        Route::put('/{horario}', [HorarioController::class, 'update']);
        Route::delete('/{horario}', [HorarioController::class, 'destroy']);
    });
    
    // Notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/count', [NotificationController::class, 'getCount']);
        Route::get('/recent', [NotificationController::class, 'getRecent']);
        Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    });
    
    // Dashboard stats
    Route::get('/dashboard/stats', [DeviceController::class, 'getDashboardStats']);
}); 