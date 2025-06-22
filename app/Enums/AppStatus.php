<?php

namespace App\Enums;

enum AppStatus: string
{
    case BLOQUEADA = 'BLOQUEADA';
    case DISPONIBLE = 'DISPONIBLE';
    case HORARIO = 'HORARIO';

    /**
     * Obtener la descripción del estado
     */
    public function getDescription(): string
    {
        return $this->value;
    }

    /**
     * Obtener el enum desde una descripción
     */
    public static function fromDescription(string $description): self
    {
        return match ($description) {
            'BLOQUEADA' => self::BLOQUEADA,
            'DISPONIBLE' => self::DISPONIBLE,
            'HORARIO' => self::HORARIO,
        };
    }

    /**
     * Obtener todas las opciones para formularios
     */
    public static function getOptions(): array
    {
        return [
            self::BLOQUEADA->value => 'Bloqueada',
            self::DISPONIBLE->value => 'Disponible',
            self::HORARIO->value => 'Bajo Horario',
        ];
    }

    /**
     * Verificar si el estado permite uso
     */
    public function allowsUsage(): bool
    {
        return match ($this) {
            self::DISPONIBLE => true,
            self::HORARIO => true, // Depende del horario
            self::BLOQUEADA => false,
        };
    }
}
