<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\AuthController;

Route::get('/sync/apps', [SyncController::class, 'getApps']);
Route::post('/sync/apps', [SyncController::class, 'postApps']);
Route::delete('/sync/apps', [SyncController::class, 'deleteApps']);

Route::get('/sync/horarios', [SyncController::class, 'getHorarios']);
Route::post('/sync/horarios', [SyncController::class, 'postHorarios']);
Route::delete('/sync/horarios', [SyncController::class, 'deleteHorarios']);

Route::get('/sync/devices', [SyncController::class, 'getDevices']);
Route::post('/sync/devices', [SyncController::class, 'postDevices']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ruta para obtener el estado actualizado de un dispositivo
Route::get('/devices/{device}/status', function (App\Models\Device $device) {
    return response()->json([
        'status' => $device->status,
        'last_seen' => $device->last_seen,
        'updated_at' => $device->updated_at
    ]);
});


