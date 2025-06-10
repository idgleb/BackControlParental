<?php

namespace App\Http\Controllers;

use App\Models\DeviceApp;
use App\Models\Horario;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function getApps()
    {
        return DeviceApp::all();
    }

    public function postApps(Request $request)
    {
        foreach ($request->all() as $data) {
            DeviceApp::updateOrCreate(
                ['packageName' => $data['packageName']],
                [
                    'appName' => $data['appName'],
                    'appStatus' => $data['appStatus'],
                    'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'],
                    'usageTimeToday' => $data['usageTimeToday'],
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }

    public function getHorarios()
    {
        return Horario::all();
    }

    public function postHorarios(Request $request)
    {
        foreach ($request->all() as $data) {
            Horario::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'nombreDeHorario' => $data['nombreDeHorario'],
                    'diasDeSemana' => $data['diasDeSemana'],
                    'horaInicio' => $data['horaInicio'],
                    'horaFin' => $data['horaFin'],
                    'isActive' => $data['isActive'],
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }
}
