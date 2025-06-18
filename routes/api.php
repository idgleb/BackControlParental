<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;

Route::get('/sync/apps', [SyncController::class, 'getApps']);
Route::post('/sync/apps', [SyncController::class, 'postApps']);
Route::delete('/sync/apps', [SyncController::class, 'deleteApps']);

Route::get('/sync/horarios', [SyncController::class, 'getHorarios']);
Route::post('/sync/horarios', [SyncController::class, 'postHorarios']);
Route::delete('/sync/horarios', [SyncController::class, 'deleteHorarios']);

Route::get('/sync/devices', [SyncController::class, 'getDevices']);
Route::post('/sync/devices', [SyncController::class, 'postDevices']);

