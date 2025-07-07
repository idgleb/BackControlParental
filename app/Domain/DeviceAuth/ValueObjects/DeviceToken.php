<?php

namespace App\Domain\DeviceAuth\ValueObjects;

use Illuminate\Support\Str;

final class DeviceToken
{
    private const TOKEN_LENGTH = 80;
    
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }
    
    /**
     * Crear un nuevo token aleatorio
     */
    public static function generate(): self
    {
        return new self(Str::random(self::TOKEN_LENGTH));
    }
    
    /**
     * Crear desde un string existente
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }
    
    /**
     * Obtener el valor del token
     */
    public function value(): string
    {
        return $this->value;
    }
    
    /**
     * Obtener el hash del token para almacenar en BD
     */
    public function hash(): string
    {
        return hash('sha256', $this->value);
    }
    
    /**
     * Verificar si un token coincide con este hash
     */
    public function verifyAgainstHash(string $hash): bool
    {
        return hash_equals($hash, $this->hash());
    }
    
    /**
     * Obtener representación para headers HTTP
     */
    public function toHeader(): string
    {
        return 'Bearer ' . $this->value;
    }
    
    /**
     * Validar el formato del token
     */
    private function validate(): void
    {
        if (strlen($this->value) !== self::TOKEN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Device token must be exactly %d characters long', self::TOKEN_LENGTH)
            );
        }
        
        if (!ctype_alnum($this->value)) {
            throw new \InvalidArgumentException('Device token must be alphanumeric');
        }
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
} 