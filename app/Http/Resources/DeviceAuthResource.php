<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceAuthResource extends JsonResource
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
            'model' => $this->model,
            'android_version' => $this->android_version,
            'app_version' => $this->app_version,
            'manufacturer' => $this->manufacturer,
            'is_verified' => (bool) $this->is_verified,
            'is_active' => (bool) $this->is_active,
            'is_blocked' => $this->blocked_until && $this->blocked_until->isFuture(),
            'blocked_until' => $this->blocked_until?->toISOString(),
            'last_heartbeat' => $this->last_heartbeat?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            'parents' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'child_name' => $user->pivot->child_name,
                    ];
                });
            }),
        ];
    }
} 