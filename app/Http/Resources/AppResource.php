<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->deviceId,
            'package_name' => $this->packageName,
            'app_name' => $this->appName,
            'app_category' => $this->appCategory,
            'content_rating' => $this->contentRating,
            'is_system_app' => (bool) $this->isSystemApp,
            'usage_time_today' => $this->usageTimeToday,
            'app_status' => $this->appStatus,
            'daily_usage_limit_minutes' => $this->dailyUsageLimitMinutes,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
} 