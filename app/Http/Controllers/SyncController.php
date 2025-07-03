<?php

namespace App\Http\Controllers;

use App\Models\DeviceApp;
use App\Models\Horario;
use App\Models\Device;
use App\Models\SyncEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SyncController extends Controller
{
    /**
     * GET /api/sync/apps
     */
    public function getApps(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'deviceId' => 'required|uuid',
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0',
            'includeIcons' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        }

        $deviceId = $request->query('deviceId');
        $includeIcons = $request->query('includeIcons', 'false') === 'true';
        $defaultLimit = $includeIcons ? 5 : 25;
        $maxLimit = $includeIcons ? 8 : 30;
        $limit = min((int)$request->query('limit', $defaultLimit), $maxLimit);
        $offset = max((int)$request->query('offset', 0), 0);

        try {
            $query = DeviceApp::where('deviceId', $deviceId);
        $total = $query->count();
        $apps = $query->skip($offset)
            ->take($limit)
            ->get()
            ->map(function (DeviceApp $app) use ($includeIcons) {
                if ($includeIcons && $app->appIcon !== null) {
                $app->appIcon = array_values(unpack('C*', $app->appIcon));
                } else {
                    $app->appIcon = null;
            }
                    $app->isSystemApp = (bool) $app->isSystemApp;
                    return [
                        'deviceId' => $app->deviceId,
                        'packageName' => $app->packageName,
                        'appName' => $app->appName,
                        'appIcon' => $app->appIcon,
                        'appCategory' => $app->appCategory,
                        'contentRating' => $app->contentRating,
                        'isSystemApp' => $app->isSystemApp,
                        'usageTimeToday' => $app->usageTimeToday,
                        'timeStempUsageTimeToday' => $app->timeStempUsageTimeToday,
                        'appStatus' => $app->appStatus,
                        'dailyUsageLimitMinutes' => $app->dailyUsageLimitMinutes,
                    ];
                });

            Log::debug('getApps', [
            'deviceId' => $deviceId,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'returned' => count($apps),
                'hasMore' => ($offset + $limit) < $total,
        ]);

            return $this->streamedJsonResponse([
                'status' => 'success',
            'data' => $apps,
                'hasMore' => ($offset + $limit) < $total,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getApps', [
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch apps: ' . $e->getMessage(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/sync/apps
     */
    public function postApps(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*.deviceId' => 'required|uuid',
            '*.packageName' => 'required|string',
            '*.appName' => 'nullable|string',
            '*.appIcon' => 'nullable|array',
            '*.appCategory' => 'nullable|string',
            '*.contentRating' => 'nullable|string',
            '*.isSystemApp' => 'boolean',
            '*.usageTimeToday' => 'integer|min:0',
            '*.timeStempUsageTimeToday' => 'integer|min:0',
            '*.appStatus' => 'string|in:DISPONIBLE,BLOQUEADA,LIMITADA',
            '*.dailyUsageLimitMinutes' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
            }

        try {
            DB::transaction(function () use ($request) {
            foreach ($request->all() as $data) {
                $icon = $data['appIcon'] ?? null;
                if (is_array($icon)) {
                        $icon = pack('C*', ...$icon);
                }

                    $appName = $data['appName'] ?? 'Sin nombre';
                $appStatus = $data['appStatus'] ?? 'DISPONIBLE';

                DeviceApp::updateOrCreate(
                        [
                            'deviceId' => $data['deviceId'],
                            'packageName' => $data['packageName'],
                        ],
                    [
                        'appName' => $appName,
                        'appIcon' => $icon,
                            'appCategory' => $data['appCategory'],
                            'contentRating' => $data['contentRating'],
                            'isSystemApp' => $data['isSystemApp'] ?? false,
                            'usageTimeToday' => $data['usageTimeToday'] ?? 0,
                            'timeStempUsageTimeToday' => $data['timeStempUsageTimeToday'] ?? 0,
                        'appStatus' => $appStatus,
                            'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'] ?? 0,
                    ]
                );

                    SyncEvent::create([
                        'deviceId' => $data['deviceId'],
                        'entity_type' => 'app',
                        'entity_id' => $data['packageName'],
                        'action' => 'create',
                        'data' => $data,
                        'created_at' => now(),
                    ]);
            }
        });

            return $this->streamedJsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in postApps', [
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to save apps: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/sync/apps/delete
     */
    public function deleteApps(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deviceIds' => 'required|array',
            'deviceIds.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            DB::transaction(function () use ($request) {
                $deviceIds = $request->input('deviceIds');
                $deleted = DeviceApp::whereIn('deviceId', $deviceIds)->delete();

                foreach ($deviceIds as $deviceId) {
                    SyncEvent::create([
                        'deviceId' => $deviceId,
                        'entity_type' => 'app',
                        'entity_id' => 'all',
                        'action' => 'delete',
                        'created_at' => now(),
                    ]);
                }

                Log::debug('deleteApps', [
                    'deviceIds' => $deviceIds,
                    'deleted_count' => $deleted,
                ]);
        });

            return $this->streamedJsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in deleteApps', [
                'deviceIds' => $request->input('deviceIds'),
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete apps: ' . $e->getMessage(),
            ], 500);
    }
    }

    /**
     * GET /api/sync/horarios
     */
    public function getHorarios(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'deviceId' => 'required|uuid',
            'lastSync' => 'nullable|date',
            'knownIds' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        }

        $deviceId = $request->query('deviceId');
        $lastSync = $request->query('lastSync');
        $knownIds = $request->query('knownIds');

        try {
            $query = Horario::where('deviceId', $deviceId);
            if ($lastSync) {
                $lastSyncDate = Carbon::parse($lastSync);
                $query->where('updated_at', '>', $lastSyncDate);
            }

            $horarios = $query->get()->map(function ($horario) {
                return [
                    'deviceId' => $horario->deviceId,
                    'idHorario' => $horario->idHorario,
                    'nombreDeHorario' => $horario->nombreDeHorario,
                    'diasDeSemana' => $horario->diasDeSemana,
                    'horaInicio' => $horario->horaInicio,
                    'horaFin' => $horario->horaFin,
                    'isActive' => (bool) $horario->isActive,
                ];
            });

            $deletedIds = [];
            if ($knownIds) {
                $knownIdsArray = explode(',', $knownIds);
                $currentIds = Horario::where('deviceId', $deviceId)
                    ->pluck('idHorario')
                    ->toArray();
                $deletedIds = array_diff($knownIdsArray, $currentIds);
            }

            Log::debug('getHorarios', [
                    'deviceId' => $deviceId,
                'lastSync' => $lastSync,
                'knownIds' => $knownIds,
                'total' => count($horarios),
                'deletedIds' => $deletedIds,
            ]);

            return $this->streamedJsonResponse([
                'status' => count($horarios) || count($deletedIds) ? 'success' : 'no_changes',
                'data' => $horarios,
                'hasMore' => false, // Horarios no usa paginación
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getHorarios', [
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch horarios: ' . $e->getMessage(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/sync/horarios
     */
    public function postHorarios(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*.deviceId' => 'required|uuid',
            '*.idHorario' => 'required|integer',
            '*.nombreDeHorario' => 'required|string',
            '*.diasDeSemana' => 'array',
            '*.diasDeSemana.*' => 'integer|min:0|max:6',
            '*.horaInicio' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            '*.horaFin' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            '*.isActive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            DB::transaction(function () use ($request) {
            foreach ($request->all() as $data) {
                Horario::updateOrCreate(
                        [
                            'deviceId' => $data['deviceId'],
                            'idHorario' => $data['idHorario'],
                        ],
                    [
                        'nombreDeHorario' => $data['nombreDeHorario'],
                        'diasDeSemana' => $data['diasDeSemana'],
                        'horaInicio' => $data['horaInicio'],
                        'horaFin' => $data['horaFin'],
                            'isActive' => $data['isActive'] ?? false,
                    ]
                );

                    SyncEvent::create([
                        'deviceId' => $data['deviceId'],
                        'entity_type' => 'horario',
                        'entity_id' => $data['idHorario'],
                        'action' => 'create',
                        'data' => $data,
                        'created_at' => now(),
                    ]);
            }
        });

            return $this->streamedJsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in postHorarios', [
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to save horarios: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/sync/horarios/delete
     */
    public function deleteHorarios(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deviceIds' => 'required|array',
            'deviceIds.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
        DB::transaction(function () use ($request) {
                $deviceIds = $request->input('deviceIds');
                $deleted = Horario::whereIn('deviceId', $deviceIds)->delete();

                foreach ($deviceIds as $deviceId) {
                    SyncEvent::create([
                        'deviceId' => $deviceId,
                        'entity_type' => 'horario',
                        'entity_id' => 'all',
                        'action' => 'delete',
                        'created_at' => now(),
                    ]);
                }

                Log::debug('deleteHorarios', [
                    'deviceIds' => $deviceIds,
                    'deleted_count' => $deleted,
                ]);
            });

            return $this->streamedJsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in deleteHorarios', [
                'deviceIds' => $request->input('deviceIds'),
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete horarios: ' . $e->getMessage(),
            ], 500);
    }
    }

    /**
     * GET /api/sync/devices
     */
    public function getDevices(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'deviceId' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        }

        $deviceId = $request->query('deviceId');

        try {
            $devices = Device::where('deviceId', $deviceId)->get()->map(function ($device) {
                return [
                    'deviceId' => $device->deviceId,
                    'model' => $device->model,
                    'batteryLevel' => $device->batteryLevel,
                    'updated_at' => $device->updated_at->toIso8601String(),
                ];
            });

            return $this->streamedJsonResponse([
                'status' => 'success',
                'data' => $devices,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getDevices', [
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch devices: ' . $e->getMessage(),
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ], 500);
    }
    }

    /**
     * POST /api/sync/devices
     */
    public function postDevices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deviceId' => 'required|uuid',
            'model' => 'nullable|string',
            'batteryLevel' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $data = $request->all();
            Device::updateOrCreate(
                ['deviceId' => $data['deviceId']],
                [
                    'model' => $data['model'] ?? 'Unknown',
                    'batteryLevel' => $data['batteryLevel'] ?? null,
                ]
            );

            SyncEvent::create([
                'deviceId' => $data['deviceId'],
                'entity_type' => 'device',
                'entity_id' => $data['deviceId'],
                'action' => 'update',
                'data' => $data,
                'created_at' => now(),
            ]);

            return $this->streamedJsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in postDevices', [
                'deviceId' => $request->input('deviceId'),
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to save device: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/sync/events
     */
    public function getEvents(Request $request)
    {
        $deviceId = $request->query('deviceId');
        $lastEventId = $request->query('lastEventId', 0);
        $typesParam = $request->query('types', 'horario,app');
        
        // Procesar el parámetro types: puede venir como string "horario,app" o como array
        if (is_string($typesParam)) {
            $entityTypes = explode(',', $typesParam);
        } else {
            $entityTypes = (array) $typesParam;
        }

        if (!$deviceId) {
            return $this->streamedJsonResponse(['error' => 'deviceId is required'], 400);
        }

        try {
            // Log para debugging
            Log::debug('getEvents called', [
                'deviceId' => $deviceId,
                'lastEventId' => $lastEventId,
                'entityTypes' => $entityTypes,
                'typesParam' => $typesParam
            ]);

            // Usar la misma lógica de consulta que getSyncStatus para consistencia
            $eventsQuery = SyncEvent::forDevice($deviceId)
                ->whereIn('entity_type', $entityTypes)
                ->where('id', '>', $lastEventId)
                ->orderBy('id');

            $totalEventsAvailable = (clone $eventsQuery)->count();
            
            // Test directo sin el scope forDevice
            $testCount = SyncEvent::where('deviceId', $deviceId)
                ->whereIn('entity_type', $entityTypes)
                ->where('id', '>', $lastEventId)
                ->count();
            
            // Log para debugging
            Log::debug('getEvents query results', [
                'totalEventsAvailable' => $totalEventsAvailable,
                'testCountWithoutScope' => $testCount,
                'deviceId' => $deviceId,
                'entityTypes' => $entityTypes,
                'lastEventId' => $lastEventId,
                'query' => $eventsQuery->toSql(),
                'bindings' => $eventsQuery->getBindings()
            ]);
            
            $events = $eventsQuery
                ->limit(30)
                ->get()
                ->map(function ($event) use ($deviceId) {
                    $eventData = $event->data;
                    
                    // Asegurar que data sea null o un objeto, nunca un array vacío
                    if (is_array($eventData) && empty($eventData)) {
                        $eventData = null;
                    }
                    
                    // Corregir el deviceId para que nunca sea nulo
                    $finalDeviceId = $event->deviceId ?? $deviceId;

                    return [
                        'id' => $event->id,
                        'deviceId' => $finalDeviceId,
                        'entity_type' => $event->entity_type,
                        'entity_id' => $event->entity_id,
                        'action' => $event->action,
                        'data' => $eventData,
                        'created_at' => $event->created_at->toIso8601String(),
                    ];
                });

            $newLastEventId = $events->isNotEmpty() ? $events->last()['id'] : $lastEventId;

            $response = [
                'status' => 'success',
                'events' => $events->toArray(),
                'lastEventId' => (int) $newLastEventId,
                'hasMore' => $events->count() < $totalEventsAvailable,
                'timestamp' => now()->toIso8601String()
            ];

            return $this->streamedJsonResponse($response);

        } catch (\Exception $e) {
            Log::error("getEvents error", [
                'deviceId' => $deviceId,
                'lastEventId' => $lastEventId,
                'error' => $e->getMessage()
            ]);

            return $this->streamedJsonResponse(['error' => 'Failed to get events', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper para enviar respuestas JSON por streaming de forma consistente.
     */
    private function streamedJsonResponse(array $data, int $status = 200)
    {
        return response()->stream(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if (ob_get_level() > 0) {
                ob_end_flush();
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
        $validator = Validator::make($request->all(), [
            'deviceId' => 'required|uuid',
            'events' => 'required|array',
            'events.*.entity_type' => 'required|in:horario,app,device',
            'events.*.entity_id' => 'required|string',
            'events.*.action' => 'required|in:create,update,delete',
            'events.*.data' => 'nullable|array',
            'events.*.timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $validated = $request->all();
        DB::transaction(function () use ($validated) {
                Device::firstOrCreate(
                    ['deviceId' => $validated['deviceId']],
                    ['model' => 'Unknown']
                );

            foreach ($validated['events'] as $event) {
                    // Solo aplicar el evento, NO crear un nuevo SyncEvent
                    // porque el cliente ya tiene este cambio
                $this->applyEvent($validated['deviceId'], $event);

                    // Log para tracking
                    Log::debug('Applied event from client', [
                    'deviceId' => $validated['deviceId'],
                    'entity_type' => $event['entity_type'],
                    'entity_id' => $event['entity_id'],
                        'action' => $event['action']
                ]);
            }
        });

            return $this->streamedJsonResponse([
            'status' => 'success',
                'processed' => count($validated['events']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in postEvents', [
                'deviceId' => $request->input('deviceId'),
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to process events: ' . $e->getMessage(),
            ], 500);
        }
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
        if (!isset($data['idHorario'])) {
            $data['idHorario'] = $event['entity_id'];
        }

        switch ($event['action']) {
            case 'create':
            case 'update':
                Horario::updateOrCreate(
                    [
                    'deviceId' => $deviceId,
                        'idHorario' => $event['entity_id'],
                    ],
                    [
                        'nombreDeHorario' => $data['nombreDeHorario'] ?? 'Sin nombre',
                        'diasDeSemana' => $data['diasDeSemana'] ?? [],
                        'horaInicio' => $data['horaInicio'] ?? '00:00',
                        'horaFin' => $data['horaFin'] ?? '23:59',
                        'isActive' => $data['isActive'] ?? false,
                    ]
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
        $data = $event['data'] ?? [];
        switch ($event['action']) {
            case 'create':
            case 'update':
                $icon = $data['appIcon'] ?? null;
                if (is_array($icon)) {
                    $icon = pack('C*', ...$icon);
                }
                DeviceApp::updateOrCreate(
                    [
                        'deviceId' => $deviceId,
                        'packageName' => $event['entity_id'],
                    ],
                    [
                        'appName' => $data['appName'] ?? 'Sin nombre',
                        'appIcon' => $icon,
                        'appCategory' => $data['appCategory'] ?? null,
                        'contentRating' => $data['contentRating'] ?? null,
                        'isSystemApp' => $data['isSystemApp'] ?? false,
                        'usageTimeToday' => $data['usageTimeToday'] ?? 0,
                        'timeStempUsageTimeToday' => $data['timeStempUsageTimeToday'] ?? 0,
                        'appStatus' => $data['appStatus'] ?? 'DISPONIBLE',
                        'dailyUsageLimitMinutes' => $data['dailyUsageLimitMinutes'] ?? 0,
                    ]
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
                Device::where('deviceId', $deviceId)->update($event['data'] ?? []);
                break;
        }
    }

    /**
     * POST /api/sync/events/confirm
     */
    public function confirmEvents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deviceId' => 'required|uuid',
            'eventIds' => 'required|array',
            'eventIds.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $deviceId = $request->input('deviceId');
        $eventIds = $request->input('eventIds');

        try {
            $updated = SyncEvent::where('deviceId', $deviceId)
                ->whereIn('id', $eventIds)
                ->update(['synced_at' => now()]);

            Log::debug('Events confirmed as synced', [
                'deviceId' => $deviceId,
                'eventIds' => $eventIds,
                'updated_count' => $updated,
            ]);

            return $this->streamedJsonResponse([
                'status' => 'success',
                'message' => "$updated events marked as synced",
            ]);
        } catch (\Exception $e) {
            Log::error('Error in confirmEvents', [
                'deviceId' => $deviceId,
                'eventIds' => $eventIds,
                'error' => $e->getMessage(),
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to confirm events: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/sync/test-events - Método de prueba directa
     */
    public function testEvents(Request $request)
    {
        $deviceId = $request->query('deviceId');
        $lastEventId = $request->query('lastEventId', 0);

        if (!$deviceId) {
            return $this->streamedJsonResponse(['error' => 'deviceId is required'], 400);
        }
        
        // Consulta directa sin scopes ni nada complejo
        $events = \DB::table('sync_events')
            ->where('deviceId', $deviceId)
            ->where('id', '>', $lastEventId)
            ->whereIn('entity_type', ['horario', 'app'])
            ->orderBy('id')
            ->limit(5)
            ->get();
            
        return $this->streamedJsonResponse([
            'deviceId' => $deviceId,
            'lastEventId' => $lastEventId,
            'eventsFound' => $events->count(),
            'events' => $events,
        ]);
    }

    /**
     * GET /api/sync/debug - Método temporal para debugging
     */
    public function debugEvents(Request $request)
    {
        $deviceId = $request->query('deviceId');
        $lastEventId = $request->query('lastEventId', 0);
        
        if (!$deviceId) {
            return $this->streamedJsonResponse(['error' => 'deviceId is required'], 400);
        }

        // Contar todos los eventos para este dispositivo
        $totalEvents = SyncEvent::where('deviceId', $deviceId)->count();
        
        // Contar eventos después del lastEventId
        $eventsAfterLastId = SyncEvent::where('deviceId', $deviceId)
            ->where('id', '>', $lastEventId)
            ->count();
            
        // Obtener algunos eventos de muestra
        $sampleEvents = SyncEvent::where('deviceId', $deviceId)
            ->where('id', '>', $lastEventId)
            ->limit(5)
            ->get(['id', 'entity_type', 'entity_id', 'action', 'created_at']);
            
        // Verificar tipos únicos de entidades
        $uniqueEntityTypes = SyncEvent::where('deviceId', $deviceId)
            ->distinct()
            ->pluck('entity_type');
            
        return $this->streamedJsonResponse([
            'deviceId' => $deviceId,
            'totalEventsForDevice' => $totalEvents,
            'eventsAfterLastId' => $eventsAfterLastId,
            'lastEventId' => $lastEventId,
            'uniqueEntityTypes' => $uniqueEntityTypes,
            'sampleEvents' => $sampleEvents,
        ]);
    }

    /**
     * GET /api/sync/status
     */
    public function getSyncStatus(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'deviceId' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'pendingEvents' => [],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        }

        $deviceId = $request->query('deviceId');
        $clientLastEventId = $request->query('lastEventId', 0);

        try {
            // Contar eventos pendientes basándose en el lastEventId del cliente
            $pendingEvents = SyncEvent::where('deviceId', $deviceId)
                ->where('id', '>', $clientLastEventId)
                ->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray();

            $lastEvent = SyncEvent::where('deviceId', $deviceId)
                ->latest('id')
                ->first();

            $response = [
                'status' => 'success',
                'deviceId' => $deviceId,
                'pendingEvents' => $pendingEvents ?: new \stdClass(),
                'lastEventId' => $lastEvent?->id ?? 0,
                'lastEventTime' => $lastEvent?->created_at?->toIso8601String(),
                'serverTime' => now()->toIso8601String()
            ];

            return $this->streamedJsonResponse($response);

        } catch (\Exception $e) {
            Log::error("getSyncStatus error", [
                'deviceId' => $deviceId,
                'error' => $e->getMessage()
            ]);

            return $this->streamedJsonResponse([
                'status' => 'error',
                'message' => 'Failed to get sync status',
                'deviceId' => $deviceId,
                'serverTime' => now()->toIso8601String()
            ], 500);
        }
    }
}
