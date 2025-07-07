<?php

namespace App\Domain\DeviceAuth\Actions;

use App\Models\Device;
use App\Models\User;
use App\Domain\DeviceAuth\DTOs\DeviceVerificationData;
use App\Domain\DeviceAuth\ValueObjects\DeviceToken;
use App\Domain\DeviceAuth\ValueObjects\VerificationCode;
use App\Domain\DeviceAuth\Events\DeviceVerified;
use App\Domain\DeviceAuth\Exceptions\DeviceNotFoundException;
use App\Domain\DeviceAuth\Exceptions\InvalidVerificationCodeException;
use App\Domain\DeviceAuth\Exceptions\VerificationCodeExpiredException;
use App\Domain\DeviceAuth\Exceptions\DeviceAlreadyVerifiedException;
use App\Domain\DeviceAuth\Exceptions\TooManyVerificationAttemptsException;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;
use App\Domain\DeviceAuth\Services\DeviceAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyDeviceAction
{
    private const MAX_ATTEMPTS = 5;
    
    public function __construct(
        private DeviceAuthRepositoryInterface $repository,
        private DeviceAuthService $authService
    ) {}
    
    /**
     * Ejecutar la verificación de un dispositivo
     */
    public function execute(DeviceVerificationData $data, ?User $parentUser = null): array
    {
        // Validar datos
        $errors = $data->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        // Buscar dispositivo
        $device = $this->repository->findByDeviceId($data->deviceId);
        if (!$device) {
            throw new DeviceNotFoundException($data->deviceId);
        }
        
        // Verificar intentos fallidos
        if ($device->failed_attempts >= self::MAX_ATTEMPTS) {
            throw new TooManyVerificationAttemptsException(
                $device->failed_attempts,
                self::MAX_ATTEMPTS
            );
        }
        
        // Verificar si ya está verificado
        if ($device->is_verified) {
            throw new DeviceAlreadyVerifiedException($data->deviceId);
        }
        
        // Verificar código
        if (!$device->verification_code || $device->verification_code !== $data->verificationCode) {
            $this->handleFailedAttempt($device);
            throw new InvalidVerificationCodeException();
        }
        
        // Verificar expiración
        $verificationCode = VerificationCode::fromDatabase(
            $device->verification_code,
            $device->verification_expires_at
        );
        
        if ($verificationCode->isExpired()) {
            throw new VerificationCodeExpiredException();
        }
        
        return DB::transaction(function () use ($device, $data, $parentUser) {
            // Generar token
            $token = DeviceToken::generate();
            
            // Actualizar dispositivo
            $this->repository->update($device, [
                'api_token' => $token->hash(),
                'is_verified' => true,
                'is_active' => true,
                'verification_code' => null,
                'verification_expires_at' => null,
                'failed_attempts' => 0,
                'verified_at' => now(),
            ]);
            
            // Si hay usuario padre, vincular
            if ($parentUser || $data->parentUserId) {
                $userId = $parentUser?->id ?? $data->parentUserId;
                $this->repository->attachToParent($device, $userId, $data->childName);
            }
            
            // Recargar dispositivo
            $device->refresh();
            
            // Disparar evento
            event(new DeviceVerified($device, $token, $parentUser, $data->childName));
            
            // Log
            Log::info('Device verified', [
                'device_id' => $device->deviceId,
                'parent_user_id' => $parentUser?->id,
                'child_name' => $data->childName,
            ]);
            
            return [
                'device' => $device,
                'token' => $token,
            ];
        });
    }
    
    /**
     * Manejar intento fallido
     */
    private function handleFailedAttempt(Device $device): void
    {
        $this->authService->handleFailedAttempt($device->deviceId);
        
        Log::warning('Failed device verification attempt', [
            'device_id' => $device->deviceId,
            'failed_attempts' => $device->failed_attempts + 1,
        ]);
    }
} 