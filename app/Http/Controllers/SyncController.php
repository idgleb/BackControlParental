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
    public function getApps(Request $request)
    {
        $deviceId = $request->query('deviceId');
        if ($deviceId) {
            return DeviceApp::where('deviceId', $deviceId)->get();
        }
        return DeviceApp::all();
    }

    /**
     * @throws \Throwable
     */
    public function postApps(Request $request)
    {

        DB::transaction(function () use ($request) {

            // Delete only the apps belonging to the deviceIds present in the request
            $deviceIds = collect($request->all())
                ->pluck('deviceId')
                ->filter()
                ->unique();
            Log::debug("deviceIds", $deviceIds->all());

            if ($deviceIds->isNotEmpty()) {
                DB::table('device_apps')->whereIn('deviceId', $deviceIds->all())->delete();
            }

            foreach ($request->all() as $data) {
                $icon = $data['appIcon'] ?? null;
                if (is_array($icon)) {
                    $binaryData = pack('C*', ...$icon);
                    Log::debug('appIcon size: ' . strlen($binaryData));
                    $icon = $binaryData;
                    //$icon = base64_encode(pack('C*', ...$icon));
                }

                // Validar que appName no sea null
                $appName = $data['appName'] ?? 'Sin nombre'; // Valor por defecto si es null
                if (empty($appName)) {
                    Log::warning("appName is empty or null for deviceId: {$data['deviceId']}, packageName: {$data['packageName']}");
                }

                DeviceApp::updateOrCreate(
                    ['deviceId' => $data['deviceId'], 'packageName' => $data['packageName']],
                    [
                        'appName' => $appName,
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

    /**
     * @throws \Throwable
     */
    public function deleteApps(Request $request)
    {
        DB::transaction(function () use ($request) {
            $deviceIds = $request->input('deviceId');

            // Si es un string con un solo ID, pasa a ser un array con ese solo elemento
            if (is_string($deviceIds)) $deviceIds = [$deviceIds];

            if ($deviceIds && is_array($deviceIds)) {
                DB::table('device_apps')->whereIn('deviceId', $deviceIds)->delete();
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


    public function getHorarios(Request $request)
    {
        $deviceId = $request->query('deviceId');
        if ($deviceId) {
            return Horario::where('deviceId', $deviceId)->get();
        }
        return Horario::all();
    }

    /**
     * @throws \Throwable
     */
    public function postHorarios(Request $request)
    {
        DB::transaction(function () use ($request) {

            // Eliminar solo los horarios del dispositivo específicos presentes en la solicitud
            $deviceIds = collect($request->all())
                ->pluck('deviceId')
                ->filter()
                ->unique();
            Log::debug("deviceIds", $deviceIds->all());

            if ($deviceIds->isNotEmpty()) {
                Log::debug("postHorarios Hay algo en deviceIds");
                DB::table('horarios')->whereIn('deviceId', $deviceIds->all())->delete();
                Log::debug("postHorarios DeviceApps borrados");
            }

            foreach ($request->all() as $data) {
                Horario::updateOrCreate(
                    ['deviceId' => $data['deviceId'], 'idHorario' => $data['idHorario'] ?? null],
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

    /**
     * @throws \Throwable
     */
    public function deleteHorarios(Request $request)
    {
        DB::transaction(function () use ($request) {
            $deviceIds = $request->input('deviceId');

            // Si es un string con un solo ID, pasa a ser un array con ese solo elemento
            if (is_string($deviceIds)) $deviceIds = [$deviceIds];

            if ($deviceIds && is_array($deviceIds)) {
                DB::table('horarios')->whereIn('deviceId', $deviceIds)->delete();
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

        //Log::debug('Request recibido en postDevices: ' . json_encode($request->all(), JSON_PRETTY_PRINT));


        /*    Log::info('Datos de entrada:', [
                'method' => $request->method(),
                'url' => $request->url(),
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);*/


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
