<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;
use App\Infrastructure\Repositories\EloquentDeviceAuthRepository;

class DeviceAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar repositorio
        $this->app->bind(
            DeviceAuthRepositoryInterface::class,
            EloquentDeviceAuthRepository::class
        );
        
        // Registrar servicios como singleton para mejorar rendimiento
        $this->app->singleton(
            \App\Domain\DeviceAuth\Services\VerificationCodeService::class
        );
        
        $this->app->singleton(
            \App\Domain\DeviceAuth\Services\DeviceAuthService::class
        );
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar listeners de eventos si es necesario
        // Event::listen(DeviceRegistered::class, SendVerificationCodeNotification::class);
    }
} 