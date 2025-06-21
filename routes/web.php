<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
Route::post('/devices/link', [DeviceController::class, 'link'])->name('devices.link');
