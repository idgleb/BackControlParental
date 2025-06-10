<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Device;

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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'deviceId', 'deviceId');
    }

}
