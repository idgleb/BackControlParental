<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HorarioController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices/link', [DeviceController::class, 'link'])->name('devices.link');
    Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
    Route::post('/devices/{device}/apps', [DeviceController::class, 'updateApps'])->name('devices.apps.update');
    Route::get('/devices/{device}/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::post('/devices/{device}/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::get('/devices/{device}/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');
    Route::put('/devices/{device}/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');
    Route::delete('/devices/{device}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
