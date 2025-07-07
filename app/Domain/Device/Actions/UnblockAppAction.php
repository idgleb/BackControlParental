<?php

namespace App\Domain\Device\Actions;

use App\Models\Device;
use App\Models\DeviceApp;
use App\Domain\Device\Events\AppUnblocked;
use App\Models\SyncEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnblockAppAction
{
    /**
     * Desbloquear una aplicación en un dispositivo
     */
    public function execute(Device $device, string $packageName, ?int $userId = null): DeviceApp
    {
        return DB::transaction(function () use ($device, $packageName, $userId) {
            // Buscar la app
            $app = DeviceApp::where('deviceId', $device->deviceId)
                ->where('packageName', $packageName)
                ->firstOrFail();
            
            // Verificar que esté bloqueada
            if ($app->appStatus !== 'BLOQUEADA') {
                throw new \InvalidArgumentException('La aplicación no está bloqueada');
            }
            
            // Desbloquear la app
            $app->update([
                'appStatus' => 'DISPONIBLE',
                'dailyUsageLimitMinutes' => 0,
            ]);
            
            // Crear evento de sincronización
            SyncEvent::create([
                'deviceId' => $device->deviceId,
                'entity_type' => 'app',
                'entity_id' => $packageName,
                'action' => 'unblock',
                'data' => [
                    'packageName' => $packageName,
                    'appStatus' => 'DISPONIBLE',
                    'unblockedBy' => $userId,
                    'unblockedAt' => now()->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
            
            // Log de la acción
            Log::info('App unblocked', [
                'device_id' => $device->deviceId,
                'package_name' => $packageName,
                'unblocked_by' => $userId,
            ]);
            
            // Disparar evento (opcional, para notificaciones futuras)
            // event(new AppUnblocked($device, $app, $userId));
            
            return $app->fresh();
        });
    }
} 