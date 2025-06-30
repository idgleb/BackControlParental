<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SyncEvent;
use App\Enums\AppStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
            'deviceId' => 'required|string|exists:devices,deviceId',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            $device = Device::where('deviceId', $validated['deviceId'])->firstOrFail();
            $user->devices()->syncWithoutDetaching([$validated['deviceId']]);
        }

        return redirect()->route('devices.index');
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
            'status' => $status,
            'last_seen' => $device->last_seen,
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
            'last_seen' => $device->updated_at ? $device->updated_at->toISOString() : null,
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
                'last_seen' => $device->updated_at ? $device->updated_at->toISOString() : null,
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
}
