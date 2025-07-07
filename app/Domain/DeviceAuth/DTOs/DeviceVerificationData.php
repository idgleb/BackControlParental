<?php

namespace App\Domain\DeviceAuth\DTOs;

final class DeviceVerificationData
{
    public function __construct(
        public readonly string $deviceId,
        public readonly string $verificationCode,
        public readonly ?int $parentUserId = null,
        public readonly ?string $childName = null,
    ) {}
    
    /**
     * Crear desde request HTTP
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            deviceId: $data['device_id'],
            verificationCode: str_replace(['-', ' '], '', $data['verification_code']),
            parentUserId: $userId,
            childName: $data['child_name'] ?? null,
        );
    }
    
    /**
     * Validar los datos
     */
    public function validate(): array
    {
        $errors = [];
        
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $this->deviceId)) {
            $errors[] = 'Invalid device ID format';
        }
        
        if (!preg_match('/^\d{6}$/', $this->verificationCode)) {
            $errors[] = 'Verification code must be 6 digits';
        }
        
        if ($this->childName && strlen($this->childName) > 100) {
            $errors[] = 'Child name is too long';
        }
        
        return $errors;
    }
} 