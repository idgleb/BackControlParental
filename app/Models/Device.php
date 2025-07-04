<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\DeviceApp;
use App\Models\SyncEvent;

class Device extends Model
{
    protected $fillable = [
        'deviceId',
        'model',
        'batteryLevel',
        'latitude',
        'longitude',
        'location_updated_at',
        'last_seen',
        'ping_interval_seconds',
    ];

    protected $casts = [
        'location_updated_at' => 'datetime',
        'last_seen' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function booted()
    {
        static::deleting(function (Device $device) {
            $device->deviceApps()->delete();
            $device->horarios()->delete();
        });

        // Registrar evento cuando se actualiza el dispositivo
        static::updated(function (Device $device) {
            // Solo crear evento si hubo cambios significativos
            $relevantChanges = $device->only(['model', 'batteryLevel', 'latitude', 'longitude']);
            $originalValues = $device->getOriginal();
            
            $hasChanges = false;
            $changedData = [];
            
            foreach ($relevantChanges as $key => $value) {
                if ($device->isDirty($key)) {
                    $hasChanges = true;
                    $changedData[$key] = $value;
                }
            }
            
            if ($hasChanges) {
                SyncEvent::recordUpdate(
                    'device',
                    $device->deviceId,
                    $device->deviceId,
                    $changedData,
                    array_intersect_key($originalValues, $changedData)
                );
            }
        });
    }

    public function deviceApps(): HasMany
    {
        return $this->hasMany(DeviceApp::class, 'deviceId', 'deviceId');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'deviceId', 'deviceId');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'device_user',
            'deviceId',
            'user_id',
            'deviceId',
            'id'
        );
    }

    /**
     * Obtener el estado online/offline del dispositivo
     * Usa last_seen y ping_interval_seconds para determinación más precisa
     */
    public function getStatusAttribute(): string
    {
        if (!$this->last_seen) {
            return 'offline';
        }

        $lastSeen = $this->last_seen;
        $now = now();
        
        // Usar el intervalo de ping configurado + un margen de tolerancia (50%)
        $timeoutSeconds = $this->ping_interval_seconds * 1.5;
        
        $diffInSeconds = $now->diffInSeconds($lastSeen, true);
        return $diffInSeconds <= $timeoutSeconds ? 'online' : 'offline';
    }

    /**
     * Verificar si el dispositivo está online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Verificar si el dispositivo está offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    /**
     * Actualizar heartbeat (última vez visto)
     */
    public function updateHeartbeat(): void
    {
        $this->update(['last_seen' => now()]);
    }

    /**
     * Actualizar ubicación
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }

    /**
     * Verificar si tiene ubicación
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
