<?php

namespace App\Domain\Device\Actions;

use App\Models\Device;
use App\Models\DeviceApp;
use App\Domain\Device\DTOs\AppBlockData;
use App\Models\SyncEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlockAppAction
{
    /**
     * Ejecutar la acción de bloquear una app
     * 
     * @param Device $device
     * @param string $packageName
     * @param AppBlockData $data
     * @return DeviceApp
     */
    public function execute(Device $device, string $packageName, AppBlockData $data): DeviceApp
    {
        return DB::transaction(function () use ($device, $packageName, $data) {
            // 1. Encontrar la app
            $app = DeviceApp::where('deviceId', $device->deviceId)
                ->where('packageName', $packageName)
                ->firstOrFail();
            
            // 2. Validar que se puede bloquear
            $this->validateCanBlock($app);
            
            // 3. Actualizar estado
            $app->update([
                'appStatus' => 'BLOQUEADA',
                'dailyUsageLimitMinutes' => $data->dailyLimitMinutes ?? 0,
            ]);
            
            // 4. Crear evento de sincronización
            SyncEvent::create([
                'deviceId' => $device->deviceId,
                'entity_type' => 'app',
                'entity_id' => $packageName,
                'action' => 'block',
                'data' => [
                    'packageName' => $packageName,
                    'appStatus' => 'BLOQUEADA',
                    'reason' => $data->reason,
                    'blockedBy' => $data->blockedBy,
                    'blockedAt' => now()->toIso8601String(),
                    'scheduledUnblockAt' => $data->scheduledUnblockAt?->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
            
            // 5. Log de la acción
            Log::info('App blocked', [
                'device_id' => $device->deviceId,
                'package_name' => $packageName,
                'blocked_by' => $data->blockedBy,
                'reason' => $data->reason,
            ]);
            
            // 6. Disparar evento (opcional, para notificaciones futuras)
            // event(new AppBlocked($device, $app, $data));
            
            return $app->fresh();
        });
    }
    
    /**
     * Validar que la app se puede bloquear
     */
    private function validateCanBlock(DeviceApp $app): void
    {
        // No bloquear apps del sistema críticas
        $criticalApps = [
            'com.android.systemui',
            'com.android.settings',
            'com.android.phone',
            'com.android.launcher',
            'com.google.android.packageinstaller',
            'com.ursolgleb.controlparental', // Nuestra propia app
        ];
        
        if (in_array($app->packageName, $criticalApps)) {
            throw new \InvalidArgumentException("No se puede bloquear una app crítica del sistema");
        }
        
        if ($app->appStatus === 'BLOQUEADA') {
            throw new \InvalidArgumentException("La app ya está bloqueada");
        }
    }
} 