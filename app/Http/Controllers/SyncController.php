<?php

namespace App\Http\Controllers;

use App\Models\DeviceApp;
use App\Models\Horario;
use App\Models\Device;
use App\Models\SyncEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JsonException;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    public function getApps(Request $request)
    {
        $deviceId = $request->query('deviceId');
        $includeIcons = $request->query('includeIcons', 'false') === 'true';

        // Límite dinámico basado en si incluimos íconos o no
        $defaultLimit = $includeIcons ? 5 : 25;   // 25 sin iconos, 5 con iconos
        $maxLimit = $includeIcons ? 8 : 30;       // 30 sin iconos, 8 con iconos

        $limit = min((int)$request->query('limit', $defaultLimit), $maxLimit);
        $offset = max((int)$request->query('offset', 0), 0); // Asegurar que offset no sea negativo

        $query = DeviceApp::query();

        if ($deviceId) {
            $query->where('deviceId', $deviceId);
        }

        // Contar total antes de aplicar límites
        $total = $query->count();

        // Aplicar paginación
        $apps = $query->skip($offset)
            ->take($limit)
            ->get()
            ->map(function (DeviceApp $app) use ($includeIcons) {
                // Solo incluir íconos si se solicita explícitamente
                if ($includeIcons && $app->appIcon !== null) {
                    $app->appIcon = array_values(unpack('C*', $app->appIcon));
                } else {
                    // No incluir el ícono para reducir el tamaño de la respuesta
                    $app->appIcon = null;
                }

                if ($app->isSystemApp === 1) {
                    $app->isSystemApp = true;
                } else {
                    $app->isSystemApp = false;
                }

                return $app;
            });

        // Log para debug
        \Log::debug("getApps", [
            'deviceId' => $deviceId,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'returned' => count($apps),
            'hasMore' => ($offset + $limit) < $total
        ]);

        // Incluir metadatos de paginación
        $response = [
            'data' => $apps,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $total
            ]
        ];

        return response()->stream(function () use ($response) {
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            flush();
        }, 200, ['Content-Type' => 'application/json; charset=utf-8']);
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
                    $icon = $binaryData;
                    //$icon = base64_encode(pack('C*', ...$icon));
                }

                // Validar que appName no sea null
                $appName = $data['appName'] ?? 'Sin nombre'; // Valor por defecto si es null
                if (empty($appName)) {
                    Log::warning("appName is empty or null for deviceId: {$data['deviceId']}, packageName: {$data['packageName']}");
                }

                // Fix temporal para el error tipográfico
                $appStatus = $data['appStatus'] ?? 'DISPONIBLE';
                if ($appStatus === 'DISPONIBE') {
                    $appStatus = 'DISPONIBLE';
                    Log::warning("Corrigiendo appStatus de DISPONIBE a DISPONIBLE para {$data['packageName']}");
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
                        'appStatus' => $appStatus,
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
        $lastSync = $request->query('lastSync');
        $knownIds = $request->query('knownIds');

        Log::debug("getHorarios", [
            'deviceId' => $deviceId,
            'lastSync' => $lastSync,
            'knownIds' => $knownIds
        ]);

        try {
            $query = $deviceId
                ? Horario::where('deviceId', $deviceId)
                : Horario::query();

            if ($lastSync) {
                $lastSyncDate = \Carbon\Carbon::parse($lastSync);
                $query->where('updated_at', '>', $lastSyncDate);
            }

            $horarios = $query->get();
            $deletedIds = [];

            if ($knownIds && $deviceId) {
                $knownIdsArray = is_array($knownIds) ? $knownIds : explode(',', $knownIds);
                $currentIds = Horario::where('deviceId', $deviceId)
                    ->pluck('idHorario')
                    ->toArray();
                $deletedIds = array_diff($knownIdsArray, $currentIds);
            }

            $rawData = $horarios->toArray();

            if ($lastSync && $horarios->isEmpty() && empty($deletedIds)) {
                Log::debug("getHorarios sin cambios", ['deviceId' => $deviceId]);
                $response = [
                    'status' => 'no_changes',
                    'message' => 'No hay cambios en los horarios',
                    'deviceId' => $deviceId,
                    'timestamp' => now()->toIso8601String(),
                    'data' => [],
                    'changes' => [
                        'added' => [],
                        'updated' => [],
                        'deleted' => []
                    ],
                    'total_changes' => 0
                ];

                return response()->stream(function () use ($response) {
                    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                }, 200, ['Content-Type' => 'application/json; charset=utf-8']);
            }

            $response = [
                'status' => 'success',
                'data' => $rawData,
                'changes' => [
                    'added' => [],
                    'updated' => [],
                    'deleted' => array_values($deletedIds)
                ],
                'timestamp' => now()->toIso8601String(),
                'total_changes' => count($rawData) + count($deletedIds)
            ];

            if ($lastSync) {
                $lastSyncDate = \Carbon\Carbon::parse($lastSync);
                foreach ($horarios as $horario) {
                    if ($horario->created_at > $lastSyncDate) {
                        $response['changes']['added'][] = $horario->idHorario;
                    } else {
                        $response['changes']['updated'][] = $horario->idHorario;
                    }
                }
            }

            Log::debug("getHorarios con cambios", [
                'deviceId' => $deviceId,
                'total_changes' => $response['total_changes'],
                'added' => count($response['changes']['added']),
                'updated' => count($response['changes']['updated']),
                'deleted' => count($response['changes']['deleted'])
            ]);

            return response()->stream(function () use ($response) {
                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 200, ['Content-Type' => 'application/json; charset=utf-8']);

        } catch (JsonException $e) {
            Log::error("JSON Encoding Error in getHorarios: " . $e->getMessage());
            $errorResp = [
                'status' => 'error',
                'message' => 'Failed to fetch horarios',
                'details' => $e->getMessage()
            ];

            return response()->stream(function () use ($errorResp) {
                echo json_encode($errorResp, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 500, ['Content-Type' => 'application/json; charset=utf-8']);
        } catch (\Exception $e) {
            Log::error("getHorarios error", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $errorResp = [
                'status' => 'error',
                'message' => 'Failed to fetch horarios',
                'details' => $e->getMessage()
            ];

            return response()->stream(function () use ($errorResp) {
                echo json_encode($errorResp, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 500, ['Content-Type' => 'application/json; charset=utf-8']);
        }
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


    public function postDevices(Request $request)
    {
        $data = $request->all();
        \Log::info('postDevices data', $data);

        // Buscar el dispositivo existente
        $device = Device::where('deviceId', $data['deviceId'] ?? null)->first();

        if ($device) {
            // Si el dispositivo existe, actualizar los datos y forzar la actualización del timestamp
            $device->update([
                'model' => $data['model'] ?? null,
                'batteryLevel' => $data['batteryLevel'] ?? null,
            ]);

            // Forzar la actualización del timestamp updated_at
            $device->touch();
        } else {
            // Si no existe, crear uno nuevo
            Device::create([
                'deviceId' => $data['deviceId'] ?? null,
                'model' => $data['model'] ?? null,
                'batteryLevel' => $data['batteryLevel'] ?? null,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function getDevices(Request $request)
    {
        $deviceId = $request->query('deviceId');
        Log::debug("getDevices", ['deviceId' => $deviceId]);

        try {
            $devices = $deviceId
                ? Device::where('deviceId', $deviceId)->get()
                : Device::all();

            return response()->stream(function () use ($devices) {
                echo json_encode($devices, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 200, ['Content-Type' => 'application/json; charset=utf-8']);

        } catch (\Exception $e) {
            Log::error("getDevices error", ['message' => $e->getMessage()]);
            $errorResp = [
                'status' => 'error',
                'message' => 'Failed to fetch devices',
                'details' => $e->getMessage()
            ];

            return response()->stream(function () use ($errorResp) {
                echo json_encode($errorResp, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 500, ['Content-Type' => 'application/json; charset=utf-8']);
        }
    }

    /**
     * Sincronización moderna basada en eventos
     * GET /api/sync/events
     */
    public function getEvents(Request $request)
    {
        // Validar parámetros
        $validator = Validator::make($request->query(), [
            'deviceId' => 'required|uuid',
            'lastEventId' => 'required|integer|min:0',
            'types' => 'nullable|string',
            'per_page' => 'integer|min:1|max:100',
        ], [
            'types.string' => 'The types parameter must be a string (e.g., "horario,app").',
        ]);

        // Manejar el parámetro types
        $typesInput = isset($request->all()['types']) ? $request->all()['types'] : $request->query('types', '');
        $entityTypes = [];

        if (is_array($typesInput)) {
            $entityTypes = $typesInput; // Maneja types=horario&types=app o types[]=horario&types[]=app
        } elseif (is_string($typesInput) && !empty($typesInput)) {
            $entityTypes = explode(',', $typesInput); // Maneja types=horario,app
        } else {
            $entityTypes = ['horario', 'app']; // Valor por defecto
        }

        // Validar que todos los tipos sean válidos
        foreach ($entityTypes as $type) {
            if (!in_array($type, ['horario', 'app'])) {
                return $this->streamJsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid type. Only "horario" and "app" are allowed.',
                    'events' => [],
                    'lastEventId' => (int) $request->query('lastEventId', 0),
                    'hasMore' => false,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }
        }

        if ($validator->fails()) {
            return $this->streamJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'events' => [],
                'lastEventId' => (int) $request->query('lastEventId', 0),
                'hasMore' => false,
                'timestamp' => now()->toIso8601String(),
            ], 400);
        }

        $deviceId = $request->query('deviceId');
        $lastEventId = (int) $request->query('lastEventId', 0);
        $perPage = (int) $request->query('per_page', 25);

        try {
            // Verificar si lastEventId está desfasado
            $maxServerEventId = SyncEvent::max('id') ?? 0;
            if ($lastEventId > $maxServerEventId) {
                Log::warning("Client's lastEventId ($lastEventId) is ahead of server's max ($maxServerEventId). Resetting sync.", [
                    'deviceId' => $deviceId,
                ]);
                $lastEventId = 0;
            }

            // Consultar eventos con paginación
            $eventsQuery = SyncEvent::forDevice($deviceId)
                ->whereIn('entity_type', $entityTypes)
                ->where('id', '>', $lastEventId)
                ->orderBy('id');

            $events = $eventsQuery->paginate($perPage);

            // Mapear eventos a la estructura deseada
            $mappedEvents = collect($events->items())->map(function ($event) {
                return [
                    'id' => $event->id,
                    'deviceId' => $event->device_id,
                    'entity_type' => $event->entity_type,
                    'entity_id' => $event->entity_id,
                    'action' => $event->action,
                    'data' => $event->data ? (object) $event->data : null,
                    'created_at' => $event->created_at->toIso8601String(),
                ];
            })->toArray();

            // Logging para depuración
            Log::debug('getEvents query', [
                'deviceId' => $deviceId,
                'lastEventId_in' => $lastEventId,
                'lastEventId_out' => $events->isNotEmpty() ? $events->last()->id : $lastEventId,
                'eventsCount' => count($mappedEvents),
                'totalEventsAvailable' => $events->total(),
                'hasMore' => $events->hasMorePages(),
                'types' => $entityTypes,
            ]);

            // Respuesta
            $response = [
                'status' => 'success',
                'events' => $mappedEvents,
                'lastEventId' => $events->isNotEmpty() ? $events->last()->id : $lastEventId,
                'hasMore' => $events->hasMorePages(),
                'timestamp' => now()->toIso8601String(),
            ];

            return $this->streamJsonResponse($response);
        } catch (\Exception $e) {
            Log::error('getEvents error', [
                'deviceId' => $deviceId,
                'lastEventId' => $lastEventId,
                'types' => $entityTypes,
                'error' => $e->getMessage(),
            ]);

            return $this->streamJsonResponse([
                'status' => 'error',
                'message' => 'Failed to get events: ' . $e->getMessage(),
                'events' => [],
                'lastEventId' => $lastEventId,
                'hasMore' => false,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    private function streamJsonResponse(array $data, int $status = 200)
    {
        return response()->stream(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            flush();
        }, $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }


    /**
     * Aplicar eventos desde el cliente
     * POST /api/sync/events
     */
    public function postEvents(Request $request)
    {
        $validated = $request->validate([
            'deviceId' => 'required|string',
            'events' => 'required|array',
            'events.*.entity_type' => 'required|in:horario,app,device',
            'events.*.entity_id' => 'required|string',
            'events.*.action' => 'required|in:create,update,delete',
            'events.*.data' => 'nullable|array',
            'events.*.timestamp' => 'required|date',
        ]);

        // Asegurarse de que el dispositivo exista
        $device = Device::firstOrCreate(
            ['deviceId' => $validated['deviceId']],
            ['model' => 'Unknown'] // Un valor por defecto para el modelo
        );

        DB::transaction(function () use ($validated) {
            foreach ($validated['events'] as $event) {
                // Aplicar cada evento según su tipo y acción
                $this->applyEvent($validated['deviceId'], $event);

                // Registrar el evento para otros clientes
                SyncEvent::create([
                    'deviceId' => $validated['deviceId'],
                    'entity_type' => $event['entity_type'],
                    'entity_id' => $event['entity_id'],
                    'action' => $event['action'],
                    'data' => $event['data'] ?? null,
                    'created_at' => Carbon::parse($event['timestamp'])
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'processed' => count($validated['events'])
        ])->header('Connection', 'close');
    }

    /**
     * Aplicar un evento individual
     */
    private function applyEvent(string $deviceId, array $event)
    {
        switch ($event['entity_type']) {
            case 'horario':
                $this->applyHorarioEvent($deviceId, $event);
                break;
            case 'app':
                $this->applyAppEvent($deviceId, $event);
                break;
            case 'device':
                $this->applyDeviceEvent($deviceId, $event);
                break;
        }
    }

    /**
     * Aplicar evento de horario
     */
    private function applyHorarioEvent(string $deviceId, array $event)
    {
        $data = $event['data'] ?? [];
        // Asegurarse de que el idHorario esté en los datos para el updateOrCreate
        if (!isset($data['idHorario'])) {
            $data['idHorario'] = $event['entity_id'];
        }

        switch ($event['action']) {
            case 'create':
            case 'update':
                // Usamos updateOrCreate para manejar ambos casos de forma segura.
                Horario::updateOrCreate(
                    [
                        'deviceId' => $deviceId,
                        'idHorario' => $event['entity_id']
                    ],
                    $data
                );
                break;

            case 'delete':
                Horario::where('deviceId', $deviceId)
                    ->where('idHorario', $event['entity_id'])
                    ->delete();
                break;
        }
    }

    /**
     * Aplicar evento de app
     */
    private function applyAppEvent(string $deviceId, array $event)
    {
        switch ($event['action']) {
            case 'create':
            case 'update':
                // Para apps usamos updateOrCreate porque el packageName es la clave
                DeviceApp::updateOrCreate(
                    [
                        'deviceId' => $deviceId,
                        'packageName' => $event['entity_id']
                    ],
                    $event['data'] ?? []
                );
                break;

            case 'delete':
                DeviceApp::where('deviceId', $deviceId)
                    ->where('packageName', $event['entity_id'])
                    ->delete();
                break;
        }
    }

    /**
     * Aplicar evento de dispositivo
     */
    private function applyDeviceEvent(string $deviceId, array $event)
    {
        switch ($event['action']) {
            case 'update':
                Device::where('deviceId', $deviceId)->update($event['data']);
                break;
        }
    }

    /**
     * Obtener estado de sincronización
     * GET /api/sync/status
     */
    public function getSyncStatus(Request $request)
    {
        $deviceId = $request->query('deviceId');

        if (!$deviceId) {
            return response()->json(['error' => 'deviceId is required'], 400);
        }

        try {
            // Contar eventos pendientes por tipo
            $pendingEvents = SyncEvent::forDevice($deviceId)
                ->unsynced()
                ->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray(); // Convertir a array para evitar problemas de serialización

            // Obtener último evento
            $lastEvent = SyncEvent::forDevice($deviceId)
                ->latest('id')
                ->first();

            $response = [
                'status' => 'success',
                'deviceId' => $deviceId,
                'pendingEvents' => $pendingEvents ?: new \stdClass(), // Usar objeto vacío si no hay eventos
                'lastEventId' => $lastEvent?->id ?? 0,
                'lastEventTime' => $lastEvent?->created_at?->toIso8601String(),
                'serverTime' => now()->toIso8601String()
            ];

            return response()->stream(function () use ($response) {
                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 200, ['Content-Type' => 'application/json; charset=utf-8']);

        } catch (JsonException $e) {
            Log::error("JSON Encoding Error in getSyncStatus: " . $e->getMessage());
            $errorResp = [
                'status' => 'error',
                'message' => 'Failed to get sync status',
                'deviceId' => $deviceId,
                'serverTime' => now()->toIso8601String()
            ];
            return response()->stream(function () use ($errorResp) {
                echo json_encode($errorResp, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 500, ['Content-Type' => 'application/json; charset=utf-8']);
        } catch (\Exception $e) {
            Log::error("getSyncStatus error", [
                'deviceId' => $deviceId,
                'error' => $e->getMessage()
            ]);

            $errorResp = [
                'status' => 'error',
                'message' => 'Failed to get sync status',
                'deviceId' => $deviceId,
                'serverTime' => now()->toIso8601String()
            ];
            return response()->stream(function () use ($errorResp) {
                echo json_encode($errorResp, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }, 500, ['Content-Type' => 'application/json; charset=utf-8']);
        }
    }

}
