<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            $device->apps()->delete();
        });
    }

    public function apps(): HasMany
    {
        return $this->hasMany(DeviceApp::class, 'deviceId', 'deviceId');
    }
}
