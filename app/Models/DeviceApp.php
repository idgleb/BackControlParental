<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceApp extends Model
{
    protected $fillable = [
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
}
