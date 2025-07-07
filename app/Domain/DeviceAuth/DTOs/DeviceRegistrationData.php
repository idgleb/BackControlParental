<?php

namespace App\Domain\DeviceAuth\DTOs;

class DeviceRegistrationData
{
    public function __construct(
        public readonly string $deviceId,
        public readonly string $model,
        public readonly string $androidVersion,
        public readonly string $appVersion,
        public readonly ?string $manufacturer = null,
        public readonly ?string $fingerprint = null,
    ) {}
    
    public static function fromRequest(array $data): self
    {
        return new self(
            deviceId: $data['device_id'],
            model: $data['model'],
            androidVersion: $data['android_version'],
            appVersion: $data['app_version'],
            manufacturer: $data['manufacturer'] ?? null,
            fingerprint: $data['fingerprint'] ?? null,
        );
    }
}
