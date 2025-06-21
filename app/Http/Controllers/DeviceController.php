<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $user = User::first();
        $devices = $user ? $user->devices()->get() : collect();
        return view('devices', ['devices' => $devices]);
    }

    public function link(Request $request)
    {
        $validated = $request->validate([
            'deviceId' => 'required|string',
        ]);

        $user = User::first();
        if ($user) {
            $device = Device::firstOrCreate(['deviceId' => $validated['deviceId']]);
            $user->devices()->syncWithoutDetaching([$device->deviceId]);
        }

        return redirect()->route('devices.index');
    }
}
