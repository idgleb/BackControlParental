<?php

namespace App\Domain\DeviceAuth\Services;

use App\Domain\DeviceAuth\ValueObjects\VerificationCode;
use App\Domain\DeviceAuth\Repositories\DeviceAuthRepositoryInterface;

class VerificationCodeService
{
    private const MAX_GENERATION_ATTEMPTS = 10;
    
    public function __construct(
        private DeviceAuthRepositoryInterface $repository
    ) {}
    
    /**
     * Generar un código de verificación único
     */
    public function generateUniqueCode(): VerificationCode
    {
        $attempts = 0;
        
        do {
            $code = VerificationCode::generate();
            $exists = $this->repository->verificationCodeExists($code->code());
            $attempts++;
            
            if ($attempts >= self::MAX_GENERATION_ATTEMPTS) {
                throw new \RuntimeException('Could not generate unique verification code');
            }
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Validar un código de verificación
     */
    public function validate(VerificationCode $code): bool
    {
        if ($code->isExpired()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Limpiar códigos expirados (para ejecutar en un comando programado)
     */
    public function cleanupExpiredCodes(): int
    {
        return $this->repository->cleanupExpiredCodes();
    }
} 