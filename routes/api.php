<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HorarioController;
use Illuminate\Http\Request;

// Rutas públicas (no requieren autenticación)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Servidor funcionando correctamente',
        'timestamp' => now()->toISOString()
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas de sincronización (para la app móvil)
Route::get('/sync/apps', [SyncController::class, 'getApps']);
Route::post('/sync/apps', [SyncController::class, 'postApps']);
Route::delete('/sync/apps', [SyncController::class, 'deleteApps']);

Route::get('/sync/horarios', [SyncController::class, 'getHorarios']);
Route::post('/sync/horarios', [SyncController::class, 'postHorarios']);
Route::delete('/sync/horarios', [SyncController::class, 'deleteHorarios']);

Route::get('/sync/devices', [SyncController::class, 'getDevices']);
Route::post('/sync/devices', [SyncController::class, 'postDevices']);

// Nueva API de sincronización basada en eventos
Route::get('/sync/events', [SyncController::class, 'getEvents']);
Route::post('/sync/events', [SyncController::class, 'postEvents']);
Route::get('/sync/status', [SyncController::class, 'getSyncStatus']);
Route::get('/sync/debug', [SyncController::class, 'debugEvents']); // Temporal para debugging
Route::get('/sync/test-events', [SyncController::class, 'testEvents']); // Test directo

// Rutas para actualización automática (verifican sesión web)
Route::middleware('web')->group(function () {
    // Rutas para dispositivos
    Route::get('/devices', [DeviceController::class, 'getDevices']);
    Route::get('/devices/{device}', [DeviceController::class, 'show']);
    Route::get('/devices/{device}/status', [DeviceController::class, 'getStatus']);

    // Rutas para horarios
    Route::get('/devices/{device}/horarios', [HorarioController::class, 'getHorariosForDevice']);
    Route::get('/devices/{device}/horarios/by-id/{idHorario}', [HorarioController::class, 'getHorarioByDeviceAndIdHorario']);

    // Rutas para notificaciones
    Route::get('/notifications/count', [NotificationController::class, 'getCount']);

    // Guardado instantáneo de campos de apps
    Route::post('/devices/{device}/apps/{app}/update-field', [DeviceController::class, 'updateAppField']);
});

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Rutas para notificaciones completas
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});


