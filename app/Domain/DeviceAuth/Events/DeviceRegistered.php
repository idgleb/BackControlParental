<?php

namespace App\Domain\DeviceAuth\Events;

use App\Models\Device;
use App\Domain\DeviceAuth\ValueObjects\VerificationCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly Device $device,
        public readonly VerificationCode $verificationCode
    ) {}
    
    /**
     * Obtener información para logging
     */
    public function toLogContext(): array
    {
        return [
            'device_id' => $this->device->deviceId,
            'model' => $this->device->model,
            'verification_code' => $this->verificationCode->format(),
            'expires_at' => $this->verificationCode->expiresAt()->toISOString(),
        ];
    }
} 