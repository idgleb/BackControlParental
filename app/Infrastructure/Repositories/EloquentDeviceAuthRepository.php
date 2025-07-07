<?php

namespace App\Infrastructure\Repositories;

use App\Models\Device;
use App\Domain\DeviceAuth\ValueObjects\DeviceToken;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentDeviceAuthRepository implements DeviceAuthRepositoryInterface
{
    /**
     * Buscar un dispositivo por ID
     */
    public function findByDeviceId(string $deviceId): ?Device
    {
        return Device::where('deviceId', $deviceId)->first();
    }
    
    /**
     * Buscar un dispositivo por token
     */
    public function findByToken(DeviceToken $token): ?Device
    {
        return Device::where('api_token', $token->hash())->first();
    }
    
    /**
     * Buscar un dispositivo por código de verificación
     */
    public function findByVerificationCode(string $code): ?Device
    {
        return Device::where('verification_code', $code)
            ->where('verification_expires_at', '>', now())
            ->first();
    }
    
    /**
     * Verificar si un dispositivo existe
     */
    public function exists(string $deviceId): bool
    {
        return Device::where('deviceId', $deviceId)->exists();
    }
    
    /**
     * Verificar si un código de verificación está en uso
     */
    public function verificationCodeExists(string $code): bool
    {
        return Device::where('verification_code', $code)
            ->where('verification_expires_at', '>', now())
            ->exists();
    }
    
    /**
     * Crear un nuevo dispositivo
     */
    public function create(array $data): Device
    {
        return Device::create($data);
    }
    
    /**
     * Actualizar un dispositivo
     */
    public function update(Device $device, array $data): bool
    {
        return $device->update($data);
    }
    
    /**
     * Incrementar intentos fallidos
     */
    public function incrementFailedAttempts(string $deviceId): void
    {
        Device::where('deviceId', $deviceId)->increment('failed_attempts');
    }
    
    /**
     * Resetear intentos fallidos
     */
    public function resetFailedAttempts(string $deviceId): void
    {
        Device::where('deviceId', $deviceId)->update(['failed_attempts' => 0]);
    }
    
    /**
     * Bloquear dispositivo temporalmente
     */
    public function blockUntil(string $deviceId, \DateTimeInterface $until): void
    {
        Device::where('deviceId', $deviceId)->update([
            'blocked_until' => $until,
            'is_active' => false,
        ]);
    }
    
    /**
     * Obtener dispositivos por usuario padre
     */
    public function getByParentUser(int $userId): array
    {
        return Device::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('users')->get()->all();
    }
    
    /**
     * Vincular dispositivo con usuario padre
     */
    public function attachToParent(Device $device, int $userId, ?string $childName = null): void
    {
        $device->users()->attach($userId, [
            'child_name' => $childName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Desvincular dispositivo de usuario padre
     */
    public function detachFromParent(Device $device, int $userId): void
    {
        $device->users()->detach($userId);
    }
    
    /**
     * Verificar si un dispositivo pertenece a un usuario
     */
    public function belongsToUser(string $deviceId, int $userId): bool
    {
        return DB::table('device_user')
            ->where('device_id', function ($query) use ($deviceId) {
                $query->select('id')
                    ->from('devices')
                    ->where('deviceId', $deviceId)
                    ->limit(1);
            })
            ->where('user_id', $userId)
            ->exists();
    }
    
    /**
     * Limpiar códigos de verificación expirados
     */
    public function cleanupExpiredCodes(): int
    {
        return Device::where('verification_expires_at', '<', now())
            ->whereNotNull('verification_code')
            ->update([
                'verification_code' => null,
                'verification_expires_at' => null,
            ]);
    }
    
    /**
     * Invalidar token de un dispositivo
     */
    public function invalidateToken(string $deviceId): void
    {
        Device::where('deviceId', $deviceId)->update([
            'api_token' => null,
            'is_active' => false,
        ]);
    }
} 