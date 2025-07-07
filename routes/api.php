<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Este archivo carga las rutas API versionadas y públicas.
| Las rutas específicas están organizadas en archivos separados.
|
*/

// Rutas públicas globales
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'version' => config('app.version', '1.0.0'),
        'timestamp' => now()->toISOString()
    ]);
});

// Autenticación para app de padres
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');

// API v1 - App de niños
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// API para app de padres
Route::prefix('parent')->group(base_path('routes/api/parent.php'));


