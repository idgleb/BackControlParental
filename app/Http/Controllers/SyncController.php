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
                ['package_name' => $data['packageName']],
                [
                    'app_name' => $data['appName'],
                    'app_status' => $data['appStatus'],
                    'daily_usage_limit_minutes' => $data['dailyUsageLimitMinutes'],
                    'usage_time_today' => $data['usageTimeToday'],
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
                    'diasDeSemana' => json_encode($data['diasDeSemana']),
                    'horaInicio' => $data['horaInicio'],
                    'horaFin' => $data['horaFin'],
                    'isActive' => $data['isActive'],
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }
}
