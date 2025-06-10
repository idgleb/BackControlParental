<?php

namespace App\Http\Controllers;

use App\Models\DeviceApp;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function getApps()
    {
        return DeviceApp::all();
    }

    public function postApps(Request $request)
    {
        DB::transaction(function () use ($request) {
            DB::table('device_apps')->delete();
            foreach ($request->all() as $data) {
                $icon = $data['appIcon'] ?? null;
                if (is_array($icon)) {
                    $icon = base64_encode(pack('C*', ...$icon));
                }
                DeviceApp::updateOrCreate(
                    ['packageName' => $data['packageName']],
                    [
                        'appName' => $data['appName'],
                        'appIcon' => $icon,
                        'appCategory' => $this->stringify($data['appCategory']),
                        'contentRating' => $this->stringify($data['contentRating']),
                        'isSystemApp' => $data['isSystemApp'],
                        'usageTimeToday' => $data['usageTimeToday'],
                        'timeStempUsageTimeToday' => $data['timeStempUsageTimeToday'],
                        'appStatus' => $data['appStatus'],
                        'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'],
                    ]
                );
            }
        });
        return response()->json(['status' => 'ok']);
    }

    private function stringify(mixed $value): string|null
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }


    public function getHorarios()
    {
        return Horario::all();
    }

    public function postHorarios(Request $request)
    {
        DB::transaction(function () use ($request) {
            // Clear existing records without leaving the transaction
            DB::table('horarios')->delete();

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
        });

        return response()->json(['status' => 'ok']);
    }
}
