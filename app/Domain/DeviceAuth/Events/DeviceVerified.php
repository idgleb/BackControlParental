<?php

namespace App\Domain\DeviceAuth\Events;

use App\Models\Device;
use App\Models\User;
use App\Domain\DeviceAuth\ValueObjects\DeviceToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly Device $device,
        public readonly DeviceToken $token,
        public readonly ?User $parentUser = null,
        public readonly ?string $childName = null
    ) {}
    
    /**
     * Obtener información para logging
     */
    public function toLogContext(): array
    {
        return [
            'device_id' => $this->device->deviceId,
            'model' => $this->device->model,
            'parent_user_id' => $this->parentUser?->id,
            'parent_email' => $this->parentUser?->email,
            'child_name' => $this->childName,
            'verified_at' => now()->toISOString(),
        ];
    }
} 