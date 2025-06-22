<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\DeviceApp;

class Device extends Model
{
    protected $fillable = [
        'deviceId',
        'model',
        'batteryLevel',
    ];

    protected static function booted()
    {
        static::deleting(function (Device $device) {
            $device->deviceApps()->delete();
            $device->horarios()->delete();
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
     * Offline si no hay actualización en más de 1 minuto
     */
    public function getStatusAttribute(): string
    {
        if (!$this->updated_at) {
            return 'offline';
        }

        $lastUpdate = $this->updated_at;
        $now = now();
        
        // Usar diffInMinutes con absolute = true para evitar valores negativos
        $diffInMinutes = $now->diffInMinutes($lastUpdate, true);

        return $diffInMinutes <= 1 ? 'online' : 'offline';
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
     * Obtener el tiempo transcurrido desde la última actualización
     */
    public function getLastSeenAttribute(): string
    {
        if (!$this->updated_at) {
            return 'Nunca';
        }

        $lastUpdate = $this->updated_at;
        $now = now();
        
        // Usar diffInMinutes con absolute = true para evitar valores negativos
        $diffInMinutes = $now->diffInMinutes($lastUpdate, true);

        if ($diffInMinutes < 1) {
            return 'Ahora mismo';
        } elseif ($diffInMinutes < 60) {
            return "Hace " . round($diffInMinutes) . " minuto" . (round($diffInMinutes) > 1 ? 's' : '');
        } else {
            $diffInHours = $now->diffInHours($lastUpdate, true);
            if ($diffInHours < 24) {
                return "Hace " . round($diffInHours) . " hora" . (round($diffInHours) > 1 ? 's' : '');
            } else {
                $diffInDays = $now->diffInDays($lastUpdate, true);
                return "Hace " . round($diffInDays) . " día" . (round($diffInDays) > 1 ? 's' : '');
            }
        }
    }
}
