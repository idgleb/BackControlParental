<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceApp extends Model
{
    protected $fillable = [
        'package_name',
        'app_name',
        'app_status',
        'daily_usage_limit_minutes',
        'usage_time_today',
    ];
}
