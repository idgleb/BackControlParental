<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\BlockAppRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\DeviceCollection;
use App\Http\Resources\AppResource;
use App\Domain\Device\Actions\BlockAppAction;
use App\Domain\Device\Actions\UnblockAppAction;
use App\Domain\Device\DTOs\AppBlockData;
// use App\Domain\Device\Services\DeviceMonitoringService;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        // private DeviceMonitoringService $monitoringService
    ) {}
    
    /**
     * Listar dispositivos del usuario
     */
    public function index(Request $request): DeviceCollection
    {
        $devices = $request->user()
            ->devices()
            ->with(['apps', 'horarios'])
            ->paginate();
            
        return new DeviceCollection($devices);
    }
    
    /**
     * Ver detalle de un dispositivo
     */
    public function show(Request $request, Device $device): DeviceResource
    {
        $this->authorize('view', $device);
        
        $device->load(['apps', 'horarios']);
        
        return new DeviceResource($device);
    }
    
    /**
     * Obtener estado actual del dispositivo
     */
    public function status(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        // $status = $this->monitoringService->getDeviceStatus($device);
        
        return response()->json([
            'data' => [
                'online' => $device->isOnline(),
                'battery_level' => $device->batteryLevel,
                'last_seen' => $device->last_seen,
            ],
        ]);
    }
    
    /**
     * Obtener ubicación del dispositivo
     */
    public function location(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        return response()->json([
            'data' => [
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
                'accuracy' => $device->location_accuracy,
                'updated_at' => $device->location_updated_at,
                'address' => null, // TODO: Implementar geocoding
            ],
        ]);
    }
    
    /**
     * Listar apps del dispositivo
     */
    public function apps(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $apps = $device->apps()
            ->orderBy('usageTimeToday', 'desc')
            ->get();
            
        return AppResource::collection($apps)->response();
    }
    
    /**
     * Bloquear una app
     */
    public function blockApp(
        BlockAppRequest $request, 
        Device $device, 
        string $packageName,
        BlockAppAction $action
    ): JsonResponse {
        $this->authorize('update', $device);
        
        $blockData = AppBlockData::fromRequest(
            $request->validated(),
            $request->user()->id
        );
        
        $app = $action->execute($device, $packageName, $blockData);
        
        return response()->json([
            'message' => 'App bloqueada exitosamente',
            'data' => new AppResource($app),
        ]);
    }
    
    /**
     * Desbloquear una app
     */
    public function unblockApp(
        Request $request, 
        Device $device, 
        string $packageName,
        UnblockAppAction $action
    ): JsonResponse {
        $this->authorize('update', $device);
        
        $app = $action->execute($device, $packageName, $request->user()->id);
        
        return response()->json([
            'message' => 'App desbloqueada exitosamente',
            'data' => new AppResource($app),
        ]);
    }
    
    /**
     * Dashboard resumen
     */
    public function dashboardSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $devices = $user->devices()->with(['apps', 'horarios'])->get();
        $devicesNeedingAttention = collect();
        
        foreach ($devices as $device) {
            // $status = $this->monitoringService->getDeviceStatus($device);
            if (!$device->isOnline() || $device->batteryLevel < 20) {
                $devicesNeedingAttention->push([
                    'device' => new DeviceResource($device),
                    'status' => [
                        'online' => $device->isOnline(),
                        'battery_level' => $device->batteryLevel,
                        'last_seen' => $device->last_seen,
                    ],
                ]);
            }
        }
        
        return response()->json([
            'data' => [
                'total_devices' => $devices->count(),
                'online_devices' => $devices->filter(fn($d) => $d->isOnline())->count(),
                'total_blocked_apps' => $devices->sum(fn($d) => 
                    $d->apps->where('appStatus', 'BLOQUEADA')->count()
                ),
                'active_schedules' => $devices->sum(fn($d) => 
                    $d->horarios->where('isActive', true)->count()
                ),
                'devices_needing_attention' => $devicesNeedingAttention,
            ],
        ]);
    }
} 