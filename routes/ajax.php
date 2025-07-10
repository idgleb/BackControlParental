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
        Route::get('/', [DeviceController::class, 'getDevices'])->name('ajax.devices.index');
        Route::get('/{device}', [DeviceController::class, 'show'])->name('ajax.devices.show');
        Route::get('/{device}/status', [DeviceController::class, 'getStatus'])->name('ajax.devices.status');
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('ajax.devices.destroy');
    });
    
    // Apps de dispositivos
    Route::prefix('devices/{device}/apps')->group(function () {
        Route::post('/{app}/update-field', [DeviceController::class, 'updateAppField'])->name('ajax.devices.apps.updateField');
        Route::put('/batch-update', [DeviceController::class, 'updateApps'])->name('ajax.devices.apps.update');
    });
    
    // Horarios
    Route::prefix('devices/{device}/horarios')->group(function () {
        Route::get('/', [HorarioController::class, 'getHorariosForDevice'])->name('ajax.devices.horarios.index');
        Route::get('/by-id/{idHorario}', [HorarioController::class, 'getHorarioByDeviceAndIdHorario'])->name('ajax.devices.horarios.showById');
        Route::post('/', [HorarioController::class, 'store'])->name('ajax.devices.horarios.store');
        Route::put('/{horario}', [HorarioController::class, 'update'])->name('ajax.devices.horarios.update');
        Route::delete('/{horario}', [HorarioController::class, 'destroy'])->name('ajax.devices.horarios.destroy');
    });
    
    // Notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/count', [NotificationController::class, 'getCount'])->name('ajax.notifications.count');
        Route::get('/recent', [NotificationController::class, 'getRecent'])->name('ajax.notifications.recent');
        Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('ajax.notifications.markRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('ajax.notifications.markAllRead');
    });
    
    // Dashboard stats
    Route::get('/dashboard/stats', [DeviceController::class, 'getDashboardStats'])->name('ajax.dashboard.stats');
}); 