<?php

namespace App\Domain\DeviceAuth\Actions;

use App\Models\Device;
use App\Domain\DeviceAuth\DTOs\DeviceRegistrationData;
use App\Domain\DeviceAuth\ValueObjects\VerificationCode;
use App\Domain\DeviceAuth\Events\DeviceRegistered;
use App\Domain\DeviceAuth\Exceptions\DeviceAlreadyExistsException;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;
use App\Domain\DeviceAuth\Services\VerificationCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterDeviceAction
{
    public function __construct(
        private DeviceAuthRepositoryInterface $repository,
        private VerificationCodeService $codeService
    ) {}
    
    /**
     * Ejecutar el registro de un dispositivo
     */
    public function execute(DeviceRegistrationData $data): array
    {
        // Validar datos
        $errors = $data->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        // Verificar que no existe
        if ($this->repository->exists($data->deviceId)) {
            throw new DeviceAlreadyExistsException($data->deviceId);
        }
        
        return DB::transaction(function () use ($data) {
            // Generar código de verificación único
            $verificationCode = $this->codeService->generateUniqueCode();
            
            // Crear dispositivo
            $device = $this->repository->create([
                'deviceId' => $data->deviceId,
                'model' => $data->model,
                'android_version' => $data->androidVersion,
                'app_version' => $data->appVersion,
                'manufacturer' => $data->manufacturer,
                'device_fingerprint' => $data->fingerprint,
                'verification_code' => $verificationCode->code(),
                'verification_expires_at' => $verificationCode->expiresAt(),
                'is_verified' => false,
                'is_active' => false,
                'api_token' => null,
            ]);
            
            // Disparar evento
            event(new DeviceRegistered($device, $verificationCode));
            
            // Log
            Log::info('Device registered', [
                'device_id' => $device->deviceId,
                'model' => $device->model,
                'verification_code' => $verificationCode->format(),
            ]);
            
            return [
                'device' => $device,
                'verification_code' => $verificationCode,
            ];
        });
    }
} 