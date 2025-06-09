<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;

Route::get('/sync/apps', [SyncController::class, 'getApps']);
Route::post('/sync/apps', [SyncController::class, 'postApps']);

Route::get('/sync/horarios', [SyncController::class, 'getHorarios']);
Route::post('/sync/horarios', [SyncController::class, 'postHorarios']);
