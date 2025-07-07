<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->deviceId,
            'model' => $this->model,
            'status' => [
                'is_online' => $this->isOnline(),
                'last_seen' => $this->last_heartbeat?->toISOString(),
                'minutes_offline' => $this->last_heartbeat 
                    ? $this->last_heartbeat->diffInMinutes(now()) 
                    : null,
            ],
            'battery' => [
                'level' => $this->batteryLevel,
                'is_low' => $this->batteryLevel <= 15,
                'last_update' => $this->battery_updated_at?->toISOString(),
            ],
            'location' => $this->when($this->latitude && $this->longitude, [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->location_accuracy,
                'last_update' => $this->location_updated_at?->toISOString(),
            ]),
            'statistics' => [
                'total_apps' => $this->whenLoaded('apps', fn() => $this->apps->count()),
                'blocked_apps' => $this->whenLoaded('apps', fn() => 
                    $this->apps->where('appStatus', 'BLOQUEADA')->count()
                ),
                'limited_apps' => $this->whenLoaded('apps', fn() => 
                    $this->apps->where('appStatus', 'LIMITADA')->count()
                ),
                'active_schedules' => $this->whenLoaded('horarios', fn() => 
                    $this->horarios->where('isActive', true)->count()
                ),
            ],
            'apps' => AppResource::collection($this->whenLoaded('apps')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('horarios')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'links' => [
                'self' => route('api.parent.devices.show', $this->id),
                'location' => route('api.parent.devices.location', $this->id),
                'apps' => route('api.parent.devices.apps', $this->id),
                'schedules' => route('api.parent.devices.schedules.index', $this->id),
            ],
        ];
    }
} 