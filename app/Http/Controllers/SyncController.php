<?php

namespace App\Http\Controllers;

use App\Models\DeviceApp;
use App\Models\Horario;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function getApps()
    {
        return DeviceApp::all();
    }

    public function postApps(Request $request)
    {

/*        // Registrar el contenido completo del request
        Log::debug('Request recibido en postApps: ' . json_encode($request->all(), JSON_PRETTY_PRINT));

        // Opcional: Registrar más detalles específicos
        Log::info('Datos de entrada en postApps:', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);*/

        DB::transaction(function () use ($request) {

            // Delete only the apps belonging to the deviceIds present in the request
            $deviceIds = collect($request->all())
                ->pluck('deviceId')
                ->filter()
                ->unique();

            if ($deviceIds->isNotEmpty()) {
                DB::table('device_apps')
                    ->whereIn('deviceId', $deviceIds->all())
                    ->delete();
            }


            foreach ($request->all() as $data) {
                $icon = $data['appIcon'] ?? null;
                if (is_array($icon)) {
                    $icon = base64_encode(pack('C*', ...$icon));
                }
                DeviceApp::updateOrCreate(
                    ['deviceId' => $data['deviceId'], 'packageName' => $data['packageName']],
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


    public function getDevices()
    {
        return Device::all();
    }

    public function postDevices(Request $request)
    {

        Log::debug('Request recibido en postDevices: ' . json_encode($request->all(), JSON_PRETTY_PRINT));


        Log::info('Datos de entrada:', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);


        $data = $request->all();
        Device::updateOrCreate(
            ['deviceId' => $data['deviceId'] ?? null],
            [
                'model' => $data['model'] ?? null,
                'batteryLevel' => $data['batteryLevel'] ?? null,
            ]
        );


        return response()->json(['status' => 'ok']);
    }


}
