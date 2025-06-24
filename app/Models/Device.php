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
     * Offline si no hay actualización en más de 10 segundos
     */
    public function getStatusAttribute(): string
    {
        if (!$this->updated_at) {
            return 'offline';
        }

        $lastUpdate = $this->updated_at;
        $now = now();
        $diffInSeconds = $now->diffInSeconds($lastUpdate, true);
        return $diffInSeconds < 10 ? 'online' : 'offline';
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
}
