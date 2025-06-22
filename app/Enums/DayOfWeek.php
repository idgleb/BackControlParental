<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;

    /**
     * Obtener el nombre del día
     */
    public function getName(): string
    {
        return match ($this) {
            self::SUNDAY => 'Domingo',
            self::MONDAY => 'Lunes',
            self::TUESDAY => 'Martes',
            self::WEDNESDAY => 'Miércoles',
            self::THURSDAY => 'Jueves',
            self::FRIDAY => 'Viernes',
            self::SATURDAY => 'Sábado',
        };
    }

    /**
     * Obtener el nombre corto del día
     */
    public function getShortName(): string
    {
        return match ($this) {
            self::SUNDAY => 'Dom',
            self::MONDAY => 'Lun',
            self::TUESDAY => 'Mar',
            self::WEDNESDAY => 'Mié',
            self::THURSDAY => 'Jue',
            self::FRIDAY => 'Vie',
            self::SATURDAY => 'Sáb',
        };
    }

    /**
     * Obtener el enum desde un número
     */
    public static function fromNumber(int $number): self
    {
        return match ($number) {
            0 => self::SUNDAY,
            1 => self::MONDAY,
            2 => self::TUESDAY,
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,
            5 => self::FRIDAY,
            6 => self::SATURDAY,
            default => throw new \InvalidArgumentException("Número de día inválido: {$number}"),
        };
    }

    /**
     * Obtener el enum desde un nombre
     */
    public static function fromName(string $name): self
    {
        return match (strtolower($name)) {
            'domingo', 'sunday', 'dom' => self::SUNDAY,
            'lunes', 'monday', 'lun' => self::MONDAY,
            'martes', 'tuesday', 'mar' => self::TUESDAY,
            'miércoles', 'wednesday', 'mié' => self::WEDNESDAY,
            'jueves', 'thursday', 'jue' => self::THURSDAY,
            'viernes', 'friday', 'vie' => self::FRIDAY,
            'sábado', 'saturday', 'sáb' => self::SATURDAY,
            default => throw new \InvalidArgumentException("Nombre de día inválido: {$name}"),
        };
    }

    /**
     * Obtener todas las opciones para formularios
     */
    public static function getOptions(): array
    {
        return [
            self::MONDAY->value => self::MONDAY->getName(),
            self::TUESDAY->value => self::TUESDAY->getName(),
            self::WEDNESDAY->value => self::WEDNESDAY->getName(),
            self::THURSDAY->value => self::THURSDAY->getName(),
            self::FRIDAY->value => self::FRIDAY->getName(),
            self::SATURDAY->value => self::SATURDAY->getName(),
            self::SUNDAY->value => self::SUNDAY->getName(),
        ];
    }

    /**
     * Obtener días de semana (lunes a viernes)
     */
    public static function getWeekdays(): array
    {
        return [
            self::MONDAY->value,
            self::TUESDAY->value,
            self::WEDNESDAY->value,
            self::THURSDAY->value,
            self::FRIDAY->value,
        ];
    }

    /**
     * Obtener fines de semana (sábado y domingo)
     */
    public static function getWeekends(): array
    {
        return [
            self::SATURDAY->value,
            self::SUNDAY->value,
        ];
    }

    /**
     * Convertir array de números a array de enums
     */
    public static function fromNumbers(array $numbers): array
    {
        return array_map(fn($number) => self::fromNumber((int) $number), $numbers);
    }

    /**
     * Convertir array de enums a array de números
     */
    public static function toNumbers(array $days): array
    {
        return array_map(fn($day) => $day->value, $days);
    }

    /**
     * Verificar si es día de semana
     */
    public function isWeekday(): bool
    {
        return in_array($this, [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
        ]);
    }

    /**
     * Verificar si es fin de semana
     */
    public function isWeekend(): bool
    {
        return in_array($this, [
            self::SATURDAY,
            self::SUNDAY,
        ]);
    }
} 