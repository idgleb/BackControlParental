<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Device;
use App\Enums\AppStatus;

class DeviceApp extends Model
{
    protected $fillable = [
        'deviceId',
        'packageName',
        'appName',
        'appIcon',
        'appCategory',
        'contentRating',
        'isSystemApp',
        'usageTimeToday',
        'timeStempUsageTimeToday',
        'appStatus',
        'dailyUsageLimitMinutes',
    ];

    protected $casts = [
        'appStatus' => AppStatus::class,
        'isSystemApp' => 'boolean',
        'usageTimeToday' => 'integer',
        'timeStempUsageTimeToday' => 'integer',
        'dailyUsageLimitMinutes' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'deviceId', 'deviceId');
    }

    /**
     * Verificar si la aplicación está bloqueada
     */
    public function isBlocked(): bool
    {
        return $this->appStatus === AppStatus::BLOQUEADA;
    }

    /**
     * Verificar si la aplicación está disponible
     */
    public function isAvailable(): bool
    {
        return $this->appStatus === AppStatus::DISPONIBLE;
    }

    /**
     * Verificar si la aplicación está bajo horario
     */
    public function isUnderSchedule(): bool
    {
        return $this->appStatus === AppStatus::HORARIO;
    }

    /**
     * Verificar si la aplicación permite uso
     */
    public function allowsUsage(): bool
    {
        return $this->appStatus->allowsUsage();
    }

    /**
     * Obtener el tiempo de uso restante hoy
     */
    public function getRemainingTimeToday(): int
    {
        $usedTime = $this->usageTimeToday ?? 0;
        $limit = $this->dailyUsageLimitMinutes ?? 0;
        
        return max(0, $limit - $usedTime);
    }

    /**
     * Verificar si se ha excedido el límite diario
     */
    public function hasExceededDailyLimit(): bool
    {
        return $this->getRemainingTimeToday() <= 0;
    }

    /**
     * Obtener el porcentaje de uso diario
     */
    public function getDailyUsagePercentage(): float
    {
        $usedTime = $this->usageTimeToday ?? 0;
        $limit = $this->dailyUsageLimitMinutes ?? 0;
        
        if ($limit === 0) {
            return 0.0;
        }
        
        return min(100.0, ($usedTime / $limit) * 100);
    }

    /**
     * Obtener la fecha de uso de tiempo como Carbon
     */
    public function getUsageTimeDate(): \Carbon\Carbon
    {
        $timestamp = $this->timeStempUsageTimeToday ?? 0;
        
        // Si el timestamp es muy grande (> 9999999999), asumimos que está en milisegundos
        // Si es más pequeño, asumimos que está en segundos
        if ($timestamp > 9999999999) {
            return \Carbon\Carbon::createFromTimestampMs($timestamp);
        } else {
            return \Carbon\Carbon::createFromTimestamp($timestamp);
        }
    }

    /**
     * Obtener la fecha de uso de tiempo como string legible
     */
    public function getUsageTimeDateString(): string
    {
        return $this->getUsageTimeDate()->format('Y-m-d H:i:s');
    }

    /**
     * Verificar si el tiempo de uso es de hoy
     */
    public function isUsageTimeToday(): bool
    {
        $usageDate = $this->getUsageTimeDate();
        $today = \Carbon\Carbon::today();
        
        return $usageDate->isSameDay($today);
    }

    /**
     * Obtener el tiempo transcurrido desde el último uso
     */
    public function getTimeSinceLastUsage(): \Carbon\CarbonInterval
    {
        $usageDate = $this->getUsageTimeDate();
        return $usageDate->diff(\Carbon\Carbon::now());
    }

    /**
     * Obtener el tiempo transcurrido como string legible
     */
    public function getTimeSinceLastUsageString(): string
    {
        $interval = $this->getTimeSinceLastUsage();
        
        if ($interval->days > 0) {
            return $interval->format('%d días, %h horas');
        } elseif ($interval->h > 0) {
            return $interval->format('%h horas, %i minutos');
        } else {
            return $interval->format('%i minutos');
        }
    }

    /**
     * Accessor para obtener el icono en base64
     */
    public function getAppIconBase64Attribute()
    {
        return $this->appIcon ? base64_encode($this->appIcon) : null;
    }
}
