<?php

namespace App\Domain\DeviceAuth\Repositories;

use App\Models\Device;
use App\Domain\DeviceAuth\ValueObjects\DeviceToken;
use App\Domain\DeviceAuth\ValueObjects\VerificationCode;

interface DeviceAuthRepositoryInterface
{
    /**
     * Buscar un dispositivo por ID
     */
    public function findByDeviceId(string $deviceId): ?Device;
    
    /**
     * Buscar un dispositivo por token
     */
    public function findByToken(DeviceToken $token): ?Device;
    
    /**
     * Buscar un dispositivo por código de verificación
     */
    public function findByVerificationCode(string $code): ?Device;
    
    /**
     * Verificar si un dispositivo existe
     */
    public function exists(string $deviceId): bool;
    
    /**
     * Verificar si un código de verificación está en uso
     */
    public function verificationCodeExists(string $code): bool;
    
    /**
     * Crear un nuevo dispositivo
     */
    public function create(array $data): Device;
    
    /**
     * Actualizar un dispositivo
     */
    public function update(Device $device, array $data): bool;
    
    /**
     * Incrementar intentos fallidos
     */
    public function incrementFailedAttempts(string $deviceId): void;
    
    /**
     * Resetear intentos fallidos
     */
    public function resetFailedAttempts(string $deviceId): void;
    
    /**
     * Bloquear dispositivo temporalmente
     */
    public function blockUntil(string $deviceId, \DateTimeInterface $until): void;
    
    /**
     * Obtener dispositivos por usuario padre
     */
    public function getByParentUser(int $userId): array;
    
    /**
     * Vincular dispositivo con usuario padre
     */
    public function attachToParent(Device $device, int $userId, ?string $childName = null): void;
    
    /**
     * Desvincular dispositivo de usuario padre
     */
    public function detachFromParent(Device $device, int $userId): void;
    
    /**
     * Verificar si un dispositivo pertenece a un usuario
     */
    public function belongsToUser(string $deviceId, int $userId): bool;
    
    /**
     * Limpiar códigos de verificación expirados
     */
    public function cleanupExpiredCodes(): int;
    
    /**
     * Invalidar token de un dispositivo
     */
    public function invalidateToken(string $deviceId): void;
} 