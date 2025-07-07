<?php

namespace App\Domain\DeviceAuth\ValueObjects;

use Carbon\Carbon;

final class VerificationCode
{
    private const CODE_LENGTH = 6;
    private const EXPIRATION_MINUTES = 30;
    
    private function __construct(
        private readonly string $code,
        private readonly Carbon $expiresAt
    ) {
        $this->validate();
    }
    
    /**
     * Generar un nuevo código de verificación
     */
    public static function generate(): self
    {
        $code = str_pad(
            (string) random_int(0, 999999), 
            self::CODE_LENGTH, 
            '0', 
            STR_PAD_LEFT
        );
        
        return new self(
            $code,
            now()->addMinutes(self::EXPIRATION_MINUTES)
        );
    }
    
    /**
     * Crear desde valores existentes
     */
    public static function fromDatabase(string $code, Carbon $expiresAt): self
    {
        return new self($code, $expiresAt);
    }
    
    /**
     * Obtener el código
     */
    public function code(): string
    {
        return $this->code;
    }
    
    /**
     * Obtener la fecha de expiración
     */
    public function expiresAt(): Carbon
    {
        return $this->expiresAt;
    }
    
    /**
     * Verificar si el código ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }
    
    /**
     * Verificar si el código coincide
     */
    public function matches(string $inputCode): bool
    {
        return hash_equals($this->code, $inputCode);
    }
    
    /**
     * Obtener minutos restantes hasta expiración
     */
    public function minutesUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInMinutes($this->expiresAt));
    }
    
    /**
     * Formatear para mostrar al usuario
     */
    public function format(): string
    {
        return implode('-', str_split($this->code, 3));
    }
    
    /**
     * Validar el código
     */
    private function validate(): void
    {
        if (strlen($this->code) !== self::CODE_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Verification code must be exactly %d digits', self::CODE_LENGTH)
            );
        }
        
        if (!ctype_digit($this->code)) {
            throw new \InvalidArgumentException('Verification code must contain only digits');
        }
    }
    
    public function __toString(): string
    {
        return $this->code;
    }
} 