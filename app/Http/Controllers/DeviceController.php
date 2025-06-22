<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Enums\AppStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        // Autorizar que el usuario es propietario del dispositivo
        if ($request->user()->cannot('view', $device)) {
            abort(403);
        }

        $device->load('deviceApps');

        return view('devices.show', [
            'device' => $device,
            'appStatusOptions' => AppStatus::getOptions()
        ]);
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
            $device->deviceApps()->where('id', $deviceAppId)->update([
                'appStatus' => $data['appStatus'],
                'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'],
            ]);
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'La configuración de las aplicaciones ha sido actualizada exitosamente.'
            ]);
        }

        // Si es una petición normal, redirigir con mensaje
        return back()->with('success', 'La configuración de las aplicaciones ha sido actualizada.');
    }
}
