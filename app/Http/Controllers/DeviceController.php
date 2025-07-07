<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SyncEvent;
use App\Enums\AppStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $devices = $user ? $user->devices()->get() : collect();
        return view('devices', ['devices' => $devices]);
    }

    public function link(Request $request)
    {
        $validated = $request->validate([
            'verification_code' => 'required|string|size:7', // Formato XXX-XXX
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('devices.index')->with('error', 'Usuario no autenticado');
        }

        // Limpiar código de verificación (remover guiones)
        $code = str_replace('-', '', $validated['verification_code']);
        
        // Buscar dispositivo con código válido
        $device = Device::where('verification_code', $code)
            ->where('verification_expires_at', '>', now())
            ->where('is_verified', false)
            ->first();
        
        if (!$device) {
            return redirect()->route('devices.index')
                ->with('error', 'Código de verificación inválido o expirado. Por favor, solicita un nuevo código desde la app.');
        }
        
        // Vincular dispositivo al usuario
        $user->devices()->syncWithoutDetaching([$device->deviceId]);
        
        // Marcar dispositivo como verificado (NO generar token aquí)
        // El token se generará cuando Android haga check-status
        $device->update([
            'is_verified' => true,
            'verification_code' => null,
            'verification_expires_at' => null,
        ]);
        
        \Illuminate\Support\Facades\Log::info('Device linked from web', [
            'device_id' => $device->deviceId,
            'user_id' => $user->id,
            'is_verified' => true,
            'note' => 'Token will be generated when Android checks status'
        ]);

        return redirect()->route('devices.index')
            ->with('success', "Dispositivo '{$device->model}' vinculado exitosamente.");
    }

    public function show(Request $request, Device $device)
    {
        // Verificar que el usuario tenga acceso al dispositivo
        if (!$request->user() || !$request->user()->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $status = $device->status;
        $deviceData = [
            'id' => $device->id,
            'deviceId' => $device->deviceId,
            'model' => $device->model,
            'batteryLevel' => $device->batteryLevel,
            'status' => $status,
            'last_seen' => $device->last_seen,
            'latitude' => $device->latitude,
            'longitude' => $device->longitude,
            'location_updated_at' => $device->location_updated_at,
            'ping_interval_seconds' => $device->ping_interval_seconds,
            'apps' => $device->deviceApps->map(fn($app) => $this->serializeDeviceApp($app)),
            'schedules' => $device->horarios->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'is_active' => $schedule->is_active,
                    'created_at' => $schedule->created_at->toISOString()
                ];
            }),
            'created_at' => $device->created_at->toISOString()
        ];
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'device' => $deviceData
            ]);
        } else {
            return view('devices.show', [
                'device' => $device,
                'appStatusOptions' => \App\Enums\AppStatus::getOptions()
            ]);
        }
    }

    private function serializeDeviceApp($app)
    {
        return [
            'id' => $app->id,
            'package_name' => $app->packageName ?? $app->package_name ?? null,
            'app_name' => $app->appName ?? $app->app_name ?? null,
            'app_icon_base64' => $app->app_icon_base64 ?? ($app->appIcon ? base64_encode($app->appIcon) : null),
            'app_status' => $app->appStatus instanceof \App\Enums\AppStatus ? $app->appStatus->value : ($app->app_status ?? null),
            'daily_usage_limit_minutes' => $app->dailyUsageLimitMinutes ?? $app->daily_usage_limit_minutes ?? null,
            'created_at' => $app->created_at ? $app->created_at->toISOString() : null,
        ];
    }

    public function updateApps(Request $request, Device $device)
    {
        // Autorizar que el usuario es propietario del dispositivo
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        $validated = $request->validate([
            'apps' => 'required|array',
            'apps.*.appStatus' => 'required|string|in:' . implode(',', array_column(AppStatus::cases(), 'value')),
            'apps.*.dailyUsageLimitMinutes' => 'required|integer|min:0|max:1440',
        ]);

        foreach ($validated['apps'] as $deviceAppId => $data) {
            $app = $device->deviceApps()->where('id', $deviceAppId)->first();
            if ($app) {
                $oldData = $app->toArray();
                
                $app->update([
                    'appStatus' => $data['appStatus'],
                    'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'],
                ]);
                
                // Registrar evento de sincronización
                SyncEvent::recordUpdate(
                    $device->deviceId,
                    'app',
                    $app->packageName,
                    $app->toArray(),
                    $oldData
                );
            }
        }

        // Refrescar el modelo y relaciones
        $device->refresh();
        $device->load(['deviceApps']);

        // Si es una petición AJAX, devolver JSON con datos actualizados
        if ($request->ajax()) {
            $deviceData = [
                'id' => $device->id,
                'deviceId' => $device->deviceId,
                'model' => $device->model,
                'status' => $device->status,
                'last_seen' => $device->last_seen,
                'apps' => $device->deviceApps->map(fn($app) => $this->serializeDeviceApp($app)),
            ];
            return response()->json([
                'success' => true,
                'message' => 'La configuración de las aplicaciones ha sido actualizada exitosamente.',
                'device' => $deviceData
            ]);
        }

        // Si es una petición normal, redirigir con mensaje
        return back()->with('success', 'La configuración de las aplicaciones ha sido actualizada.');
    }

    public function getStatus(Request $request, Device $device)
    {
        // Verificar que el usuario tenga acceso al dispositivo
        if (!$request->user() || !$request->user()->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $status = $device->status;
        return response()->json([
            'status' => $status,
            'last_seen' => $device->last_seen ? $device->last_seen->toISOString() : null,
            'updated_at' => $device->updated_at ? $device->updated_at->toISOString() : null
        ]);
    }
    
    public function getDevices(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $devices = $request->user()->devices()->with(['deviceApps', 'horarios'])->get()->map(function ($device) {
            $status = $device->status;
            return [
                'id' => $device->id,
                'deviceId' => $device->deviceId,
                'model' => $device->model,
                'batteryLevel' => $device->batteryLevel,
                'status' => $status,
                'last_seen' => $device->last_seen ? $device->last_seen->toISOString() : null,
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
                'location_updated_at' => $device->location_updated_at ? $device->location_updated_at->toISOString() : null,
                'apps_count' => $device->deviceApps->count(),
                'schedules_count' => $device->horarios->count(),
                'created_at' => $device->created_at->toISOString()
            ];
        });
        return response()->json([
            'success' => true,
            'devices' => $devices
        ]);
    }

    /**
     * Show the device location in real-time.
     */
    public function location(Request $request, Device $device)
    {
        // Verificar que el usuario tenga acceso al dispositivo
        if (!$request->user() || !$request->user()->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            abort(403);
        }
        
        return view('devices.location', compact('device'));
    }

    public function destroy(Request $request, Device $device)
    {
        // Verificar que el usuario tenga acceso al dispositivo
        if (!$request->user() || !$request->user()->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Desvincular el dispositivo del usuario actual
            $request->user()->devices()->detach($device->deviceId);
            
            // Si no hay más usuarios vinculados al dispositivo, eliminarlo completamente
            if (!$device->users()->exists()) {
                $device->delete();
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo eliminado exitosamente'
                ]);
            }

            return redirect()->route('devices.index')->with('success', 'Dispositivo eliminado exitosamente');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el dispositivo: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar el dispositivo');
        }
    }

    /**
     * Endpoint de heartbeat para mantener el dispositivo online
     */
    public function heartbeat(Request $request, $deviceId)
    {
        $device = Device::where('deviceId', $deviceId)->first();
        
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }
        
        // Actualizar heartbeat
        $device->updateHeartbeat();
        
        // Si se envía ubicación, actualizarla
        if ($request->has('latitude') && $request->has('longitude')) {
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);
            
            $device->updateLocation($validated['latitude'], $validated['longitude']);
        }
        
        return response()->json([
            'success' => true,
            'status' => $device->status,
            'server_time' => now()->toISOString(),
            'next_ping_seconds' => $device->ping_interval_seconds
        ]);
    }

    /**
     * Actualiza un campo individual de una app del dispositivo (AJAX)
     */
    public function updateAppField(Request $request, Device $device, $appId)
    {
        // Autorizar que el usuario es propietario del dispositivo
        if ($request->user()->cannot('update', $device)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $deviceApp = $device->deviceApps()->where('id', $appId)->first();
        if (!$deviceApp) {
            return response()->json(['success' => false, 'message' => 'App no encontrada'], 404);
        }

        $field = null;
        $rules = [];
        if ($request->has('app_status')) {
            $field = 'appStatus';
            $rules['app_status'] = 'required|string|in:' . implode(',', array_column(\App\Enums\AppStatus::cases(), 'value'));
        } elseif ($request->has('daily_usage_limit_minutes')) {
            $field = 'dailyUsageLimitMinutes';
            $rules['daily_usage_limit_minutes'] = 'required|integer|min:0|max:1440';
        } else {
            return response()->json(['success' => false, 'message' => 'Campo no soportado'], 422);
        }

        $validated = $request->validate($rules);
        
        // Guardar datos anteriores para el evento
        $oldData = $deviceApp->toArray();
        
        $deviceApp->$field = $validated[array_key_first($validated)];
        $deviceApp->save();
        
        // Registrar evento de sincronización
        SyncEvent::recordUpdate(
            $device->deviceId,
            'app',
            $deviceApp->packageName,
            $deviceApp->toArray(),
            $oldData
        );

        return response()->json([
            'success' => true,
            'app' => $this->serializeDeviceApp($deviceApp),
            'message' => 'Campo actualizado correctamente'
        ]);
    }

    /**
     * API para App de Padres
     * Obtener todos los dispositivos de los hijos del padre autenticado
     */
    public function getChildrenDevices(Request $request)
    {
        $user = $request->user();
        
        // Obtener dispositivos asociados al usuario padre
        $devices = $user->devices()
            ->with(['apps' => function($query) {
                $query->where('appStatus', 'BLOQUEADA')
                    ->orWhere('appStatus', 'LIMITADA');
            }])
            ->get()
            ->map(function ($device) {
                return [
                    'deviceId' => $device->deviceId,
                    'model' => $device->model,
                    'isOnline' => $device->isOnline(),
                    'lastSeen' => $device->last_heartbeat,
                    'batteryLevel' => $device->batteryLevel,
                    'latitude' => $device->latitude,
                    'longitude' => $device->longitude,
                    'blockedAppsCount' => $device->apps->where('appStatus', 'BLOQUEADA')->count(),
                    'limitedAppsCount' => $device->apps->where('appStatus', 'LIMITADA')->count(),
                    'activeHorariosCount' => $device->horarios()->where('isActive', true)->count(),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $devices,
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Obtener información detallada de un dispositivo específico
     */
    public function getChildDevice(Request $request, Device $device)
    {
        // Verificar que el dispositivo pertenece al usuario
        $this->authorize('view', $device);
        
        return response()->json([
            'success' => true,
            'data' => [
                'deviceId' => $device->deviceId,
                'model' => $device->model,
                'isOnline' => $device->isOnline(),
                'lastSeen' => $device->last_heartbeat,
                'batteryLevel' => $device->batteryLevel,
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
                'totalApps' => $device->apps()->count(),
                'blockedApps' => $device->apps()->where('appStatus', 'BLOQUEADA')->count(),
                'limitedApps' => $device->apps()->where('appStatus', 'LIMITADA')->count(),
                'activeHorarios' => $device->horarios()->where('isActive', true)->count(),
                'createdAt' => $device->created_at,
                'updatedAt' => $device->updated_at,
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Obtener ubicación actual del dispositivo
     */
    public function getDeviceLocation(Request $request, Device $device)
    {
        $this->authorize('view', $device);
        
        return response()->json([
            'success' => true,
            'data' => [
                'deviceId' => $device->deviceId,
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
                'lastUpdate' => $device->location_updated_at ?? $device->updated_at,
                'accuracy' => $device->location_accuracy ?? null,
                'address' => null, // Podrías usar un servicio de geocoding aquí
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Obtener todas las apps del dispositivo para la app de padres
     */
    public function getDeviceApps(Request $request, Device $device)
    {
        $this->authorize('view', $device);
        
        $apps = $device->apps()
            ->orderBy('usageTimeToday', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'packageName' => $app->packageName,
                    'appName' => $app->appName,
                    'appCategory' => $app->appCategory,
                    'appStatus' => $app->appStatus,
                    'usageTimeToday' => $app->usageTimeToday,
                    'dailyUsageLimitMinutes' => $app->dailyUsageLimitMinutes,
                    'isSystemApp' => $app->isSystemApp,
                    'lastUsed' => Carbon::createFromTimestamp($app->timeStempUsageTimeToday / 1000)->toISOString(),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $apps,
            'summary' => [
                'totalApps' => $apps->count(),
                'blockedApps' => $apps->where('appStatus', 'BLOQUEADA')->count(),
                'limitedApps' => $apps->where('appStatus', 'LIMITADA')->count(),
                'totalUsageMinutes' => round($apps->sum('usageTimeToday') / 60000),
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Bloquear una app
     */
    public function blockApp(Request $request, Device $device, $packageName)
    {
        $this->authorize('update', $device);
        
        $app = $device->apps()->where('packageName', $packageName)->firstOrFail();
        $app->update(['appStatus' => 'BLOQUEADA']);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'app',
            'entity_id' => $packageName,
            'action' => 'update',
            'data' => ['appStatus' => 'BLOQUEADA'],
            'created_at' => now(),
        ]);
        
        // Crear notificación
        Notification::create([
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'type' => 'app_blocked',
            'title' => 'App bloqueada',
            'message' => "La app {$app->appName} ha sido bloqueada en {$device->model}",
            'data' => [
                'packageName' => $packageName,
                'appName' => $app->appName,
            ],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'App bloqueada exitosamente',
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Desbloquear una app
     */
    public function unblockApp(Request $request, Device $device, $packageName)
    {
        $this->authorize('update', $device);
        
        $app = $device->apps()->where('packageName', $packageName)->firstOrFail();
        $app->update(['appStatus' => 'DISPONIBLE']);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'app',
            'entity_id' => $packageName,
            'action' => 'update',
            'data' => ['appStatus' => 'DISPONIBLE'],
            'created_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'App desbloqueada exitosamente',
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Establecer límite de tiempo para una app
     */
    public function setAppTimeLimit(Request $request, Device $device, $packageName)
    {
        $this->authorize('update', $device);
        
        $validated = $request->validate([
            'dailyLimitMinutes' => 'required|integer|min:0|max:1440'
        ]);
        
        $app = $device->apps()->where('packageName', $packageName)->firstOrFail();
        $app->update([
            'appStatus' => $validated['dailyLimitMinutes'] > 0 ? 'LIMITADA' : 'DISPONIBLE',
            'dailyUsageLimitMinutes' => $validated['dailyLimitMinutes']
        ]);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'app',
            'entity_id' => $packageName,
            'action' => 'update',
            'data' => [
                'appStatus' => $app->appStatus,
                'dailyUsageLimitMinutes' => $validated['dailyLimitMinutes']
            ],
            'created_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Límite de tiempo establecido exitosamente',
            'data' => [
                'packageName' => $packageName,
                'dailyLimitMinutes' => $validated['dailyLimitMinutes'],
                'appStatus' => $app->appStatus
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Obtener estadísticas de uso del dispositivo
     */
    public function getUsageStats(Request $request, Device $device)
    {
        $this->authorize('view', $device);
        
        $period = $request->query('period', 'today'); // today, week, month
        
        // Aquí implementarías la lógica para obtener estadísticas
        // Por ahora un ejemplo simple
        $stats = [
            'deviceId' => $device->deviceId,
            'period' => $period,
            'totalScreenTime' => 0, // Implementar cálculo real
            'totalAppsUsed' => $device->apps()->where('usageTimeToday', '>', 0)->count(),
            'mostUsedApps' => $device->apps()
                ->where('usageTimeToday', '>', 0)
                ->orderBy('usageTimeToday', 'desc')
                ->take(5)
                ->get(['appName', 'packageName', 'usageTimeToday'])
                ->map(function ($app) {
                    return [
                        'appName' => $app->appName,
                        'packageName' => $app->packageName,
                        'usageMinutes' => round($app->usageTimeToday / 60000)
                    ];
                }),
            'blockedAttempts' => 0, // Implementar contador real
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
            'timestamp' => now()->toISOString()
        ]);
    }
}
