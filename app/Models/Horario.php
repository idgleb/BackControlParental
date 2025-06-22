<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DayOfWeek;

class Horario extends Model
{
    protected $fillable = [
        'deviceId',
        'idHorario',
        'nombreDeHorario',
        'diasDeSemana',
        'horaInicio',
        'horaFin',
        'isActive',
    ];

    protected $casts = [
        'diasDeSemana' => 'array',
        'isActive' => 'boolean',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'deviceId', 'deviceId');
    }

    /**
     * Mutator para asegurar que diasDeSemana se guarde como array de números enteros
     */
    public function setDiasDeSemanaAttribute($value)
    {
        if (is_array($value)) {
            // Convertir todos los valores a enteros
            $this->attributes['diasDeSemana'] = json_encode(array_map('intval', $value));
        } else {
            $this->attributes['diasDeSemana'] = json_encode([]);
        }
    }

    /**
     * Accessor para obtener los días como array de números enteros
     */
    public function getDiasDeSemanaAttribute($value)
    {
        $days = json_decode($value, true) ?? [];
        return array_map('intval', $days);
    }

    /**
     * Obtener los días como array de enums DayOfWeek
     */
    public function getDaysAsEnums(): array
    {
        return DayOfWeek::fromNumbers($this->diasDeSemana);
    }

    /**
     * Obtener los nombres de los días
     */
    public function getDayNames(): array
    {
        return array_map(fn($day) => $day->getName(), $this->getDaysAsEnums());
    }

    /**
     * Obtener los nombres cortos de los días
     */
    public function getShortDayNames(): array
    {
        return array_map(fn($day) => $day->getShortName(), $this->getDaysAsEnums());
    }

    /**
     * Verificar si el horario incluye un día específico
     */
    public function includesDay(int $dayNumber): bool
    {
        return in_array($dayNumber, $this->diasDeSemana);
    }

    /**
     * Verificar si el horario incluye días de semana
     */
    public function includesWeekdays(): bool
    {
        return !empty(array_intersect($this->diasDeSemana, DayOfWeek::getWeekdays()));
    }

    /**
     * Verificar si el horario incluye fines de semana
     */
    public function includesWeekends(): bool
    {
        return !empty(array_intersect($this->diasDeSemana, DayOfWeek::getWeekends()));
    }

    /**
     * Obtener una descripción legible de los días
     */
    public function getDaysDescription(): string
    {
        if (empty($this->diasDeSemana)) {
            return 'Sin días seleccionados';
        }

        $dayNames = $this->getDayNames();
        
        if (count($dayNames) === 1) {
            return $dayNames[0];
        }

        if (count($dayNames) === 7) {
            return 'Todos los días';
        }

        // Verificar si son solo días de semana
        if ($this->includesWeekdays() && !$this->includesWeekends()) {
            return 'Días de semana';
        }

        // Verificar si son solo fines de semana
        if ($this->includesWeekends() && !$this->includesWeekdays()) {
            return 'Fines de semana';
        }

        // Lista personalizada
        return implode(', ', $dayNames);
    }

    /**
     * Obtener una descripción corta de los días
     */
    public function getShortDaysDescription(): string
    {
        if (empty($this->diasDeSemana)) {
            return 'Sin días';
        }

        $shortNames = $this->getShortDayNames();
        
        if (count($shortNames) === 1) {
            return $shortNames[0];
        }

        if (count($shortNames) === 7) {
            return 'Todos';
        }

        return implode(', ', $shortNames);
    }

    /**
     * Establecer días de semana (lunes a viernes)
     */
    public function setWeekdays(): void
    {
        $this->diasDeSemana = DayOfWeek::getWeekdays();
    }

    /**
     * Establecer fines de semana (sábado y domingo)
     */
    public function setWeekends(): void
    {
        $this->diasDeSemana = DayOfWeek::getWeekends();
    }

    /**
     * Establecer todos los días
     */
    public function setAllDays(): void
    {
        $this->diasDeSemana = array_values(DayOfWeek::getOptions());
    }
}
