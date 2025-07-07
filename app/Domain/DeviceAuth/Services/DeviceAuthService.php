<?php

namespace App\Domain\DeviceAuth\Services;

use App\Models\Device;
use App\Domain\DeviceAuth\ValueObjects\DeviceToken;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;
use App\Domain\DeviceAuth\Exceptions\DeviceBlockedException;
use App\Domain\DeviceAuth\Exceptions\DeviceNotVerifiedException;
use App\Domain\DeviceAuth\Exceptions\InvalidDeviceTokenException;
use Illuminate\Support\Facades\Cache;

class DeviceAuthService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutos
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_DURATION_MINUTES = 30;
    
    public function __construct(
        private DeviceAuthRepositoryInterface $repository
    ) {}
    
    /**
     * Autenticar un dispositivo por token
     */
    public function authenticateByToken(string $tokenString): Device
    {
        $token = DeviceToken::fromString($tokenString);
        
        // Intentar obtener de cache primero
        $cacheKey = 'device_token:' . $token->hash();
        $device = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($token) {
            return $this->repository->findByToken($token);
        });
        
        if (!$device) {
            throw new InvalidDeviceTokenException();
        }
        
        $this->validateDevice($device);
        
        return $device;
    }
    
    /**
     * Validar que un dispositivo puede autenticarse
     */
    public function validateDevice(Device $device): void
    {
        // Verificar si está bloqueado
        if ($device->blocked_until && $device->blocked_until->isFuture()) {
            throw new DeviceBlockedException(
                $device->deviceId,
                "Blocked until {$device->blocked_until->format('Y-m-d H:i:s')}"
            );
        }
        
        // Verificar si está verificado
        if (!$device->is_verified) {
            throw new DeviceNotVerifiedException($device->deviceId);
        }
        
        // Verificar si está activo
        if (!$device->is_active) {
            throw new DeviceBlockedException($device->deviceId, "Device is inactive");
        }
    }
    
    /**
     * Manejar intento fallido de autenticación
     */
    public function handleFailedAttempt(string $deviceId): void
    {
        $this->repository->incrementFailedAttempts($deviceId);
        
        $device = $this->repository->findByDeviceId($deviceId);
        if ($device && $device->failed_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->repository->blockUntil(
                $deviceId,
                now()->addMinutes(self::BLOCK_DURATION_MINUTES)
            );
        }
    }
    
    /**
     * Invalidar cache de un dispositivo
     */
    public function invalidateCache(Device $device): void
    {
        if ($device->api_token) {
            $token = DeviceToken::fromString($device->api_token);
            Cache::forget('device_token:' . $token->hash());
        }
    }
    
    /**
     * Verificar si un dispositivo pertenece a un usuario
     */
    public function belongsToUser(string $deviceId, int $userId): bool
    {
        return $this->repository->belongsToUser($deviceId, $userId);
    }
} 