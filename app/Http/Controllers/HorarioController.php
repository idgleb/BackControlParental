<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Horario;
use App\Models\SyncEvent;
use App\Enums\DayOfWeek;
use Illuminate\Http\Request;
use App\Models\Notification;

class HorarioController extends Controller
{
    public function index(Request $request, Device $device)
    {
        if ($request->user()->cannot('view', $device)) {
            abort(403);
        }

        $device->load('horarios');

        return view('horarios.index', [
            'device' => $device,
            'dayOptions' => DayOfWeek::getOptions()
        ]);
    }

    public function create(Request $request, Device $device)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        return view('horarios.create', [
            'device' => $device,
            'dayOptions' => DayOfWeek::getOptions()
        ]);
    }

    public function store(Request $request, Device $device)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        $validated = $request->validate([
            'nombreDeHorario' => 'required|string|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'diasDeSemana' => 'required|array|min:1',
            'diasDeSemana.*' => 'integer|in:0,1,2,3,4,5,6',
            'isActive' => 'boolean',
        ]);

        // Generar un ID único para el horario
        $maxId = $device->horarios()->max('idHorario') ?? 0;
        $validated['idHorario'] = $maxId + 1;
        $validated['deviceId'] = $device->deviceId;
        $validated['isActive'] = $request->has('isActive');

        // Asegurar que los días se guarden como números enteros
        $validated['diasDeSemana'] = array_map('intval', $validated['diasDeSemana']);

        try {
            $horario = Horario::create($validated);
            
            \Log::debug('Intentando registrar evento de CREACIÓN de horario', ['horario_id' => $horario->idHorario]);
            // Registrar evento de sincronización
            SyncEvent::recordCreate(
                $device->deviceId,
                'horario',
                $horario->idHorario,
                $horario->toArray()
            );
            \Log::info('Evento de CREACIÓN de horario registrado con éxito');
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario creado exitosamente.',
                    'horario' => [
                        'id' => $horario->id,
                        'idHorario' => $horario->idHorario,
                        'nombreDeHorario' => $horario->nombreDeHorario,
                        'horaInicio' => $horario->horaInicio,
                        'horaFin' => $horario->horaFin,
                        'diasDeSemana' => $horario->diasDeSemana,
                        'isActive' => (bool) $horario->isActive
                    ]
                ]);
            }

            // Si es una petición normal, redirigir con mensaje
            return back()->with('success', 'Horario creado exitosamente.');
            
        } catch (\Exception $e) {
            \Log::error('Error registrando evento de horario o creando horario', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el horario: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Error al crear el horario: ' . $e->getMessage()]);
        }
    }

    public function edit(Request $request, Device $device, Horario $horario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        return view('horarios.edit', [
            'device' => $device, 
            'horario' => $horario,
            'dayOptions' => DayOfWeek::getOptions()
        ]);
    }

    public function update(Request $request, Device $device, Horario $horario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        $validated = $request->validate([
            'nombreDeHorario' => 'required|string|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'diasDeSemana' => 'required|array|min:1',
            'diasDeSemana.*' => 'integer|in:0,1,2,3,4,5,6',
            'isActive' => 'boolean',
        ]);

        $validated['isActive'] = $request->has('isActive');

        // Asegurar que los días se guarden como números enteros
        $validated['diasDeSemana'] = array_map('intval', $validated['diasDeSemana']);

        try {
            // Guardar datos anteriores para el evento
            $oldData = $horario->toArray();
            
            $horario->update($validated);
            
            \Log::debug('Intentando registrar evento de ACTUALIZACIÓN de horario', ['horario_id' => $horario->idHorario]);
            // Registrar evento de sincronización
            SyncEvent::recordUpdate(
                $device->deviceId,
                'horario',
                $horario->idHorario,
                $horario->toArray(),
                $oldData
            );
            \Log::info('Evento de ACTUALIZACIÓN de horario registrado con éxito');
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario actualizado exitosamente.',
                    'horario' => [
                        'id' => $horario->id,
                        'idHorario' => $horario->idHorario,
                        'nombreDeHorario' => $horario->nombreDeHorario,
                        'horaInicio' => $horario->horaInicio,
                        'horaFin' => $horario->horaFin,
                        'diasDeSemana' => $horario->diasDeSemana,
                        'isActive' => (bool) $horario->isActive
                    ]
                ]);
            }

            // Si es una petición normal, redirigir con mensaje
            return redirect()->route('horarios.index', $device)->with('success', 'Horario actualizado exitosamente.');
            
        } catch (\Exception $e) {
            \Log::error('Error registrando evento de horario o actualizando horario', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el horario: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Error al actualizar el horario: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Device $device, Horario $horario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        try {
            // Guardar datos antes de eliminar
            $oldData = $horario->toArray();
            
            $horario->delete();
            
            \Log::debug('Intentando registrar evento de ELIMINACIÓN de horario', ['horario_id' => $oldData['idHorario']]);
            // Registrar evento de sincronización
            SyncEvent::recordDelete(
                $device->deviceId,
                'horario',
                $oldData['idHorario'],
                $oldData
            );
            \Log::info('Evento de ELIMINACIÓN de horario registrado con éxito');
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario eliminado exitosamente.'
                ]);
            }

            // Si es una petición normal, redirigir con mensaje
            return back()->with('success', 'Horario eliminado exitosamente.');
            
        } catch (\Exception $e) {
            \Log::error('Error registrando evento de horario o eliminando horario', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el horario: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Error al eliminar el horario: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener horarios de un dispositivo para la API
     */
    public function getHorariosForDevice(Request $request, Device $device)
    {
        $user = $request->user();
        if (!$user || !$user->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este dispositivo'
            ], 403);
        }

        try {
            $horarios = $device->horarios()
                ->select('id', 'idHorario', 'nombreDeHorario', 'horaInicio', 'horaFin', 'diasDeSemana', 'isActive')
                ->orderBy('nombreDeHorario')
                ->get()
                ->map(function ($horario) {
                    return [
                        'id' => $horario->id,
                        'idHorario' => $horario->idHorario,
                        'nombreDeHorario' => $horario->nombreDeHorario,
                        'horaInicio' => $horario->horaInicio,
                        'horaFin' => $horario->horaFin,
                        'diasDeSemana' => $horario->diasDeSemana,
                        'isActive' => (bool) $horario->isActive
                    ];
                });

            return response()->json([
                'success' => true,
                'horarios' => $horarios,
                'count' => $horarios->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los horarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un horario específico de un dispositivo para la API
     */
    public function getHorarioForDevice(Request $request, Device $device, Horario $horario)
    {
        $user = $request->user();
        if (!$user || !$user->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este dispositivo'
            ], 403);
        }

        // Verificar que el horario pertenece al dispositivo
        if ($horario->deviceId !== $device->deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'El horario no pertenece a este dispositivo'
            ], 404);
        }

        try {
            $horarioData = [
                'id' => $horario->id,
                'idHorario' => $horario->idHorario,
                'nombreDeHorario' => $horario->nombreDeHorario,
                'horaInicio' => $horario->horaInicio,
                'horaFin' => $horario->horaFin,
                'diasDeSemana' => $horario->diasDeSemana,
                'isActive' => (bool) $horario->isActive
            ];

            return response()->json([
                'success' => true,
                'horario' => $horarioData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un horario específico de un dispositivo por deviceId e idHorario (clave compuesta)
     */
    public function getHorarioByDeviceAndIdHorario(Request $request, Device $device, $idHorario)
    {
        $user = $request->user();
        if (!$user || !$user->devices()->where('devices.deviceId', $device->deviceId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este dispositivo'
            ], 403);
        }

        $horario = $device->horarios()->where('idHorario', $idHorario)->first();
        if (!$horario) {
            return response()->json([
                'success' => false,
                'message' => 'Horario no encontrado'
            ], 404);
        }

        try {
            $horarioData = [
                'id' => $horario->id,
                'idHorario' => $horario->idHorario,
                'nombreDeHorario' => $horario->nombreDeHorario,
                'horaInicio' => $horario->horaInicio,
                'horaFin' => $horario->horaFin,
                'diasDeSemana' => $horario->diasDeSemana,
                'isActive' => (bool) $horario->isActive,
                'updated_at' => $horario->updated_at ? $horario->updated_at->toISOString() : null
            ];

            return response()->json([
                'success' => true,
                'horario' => $horarioData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Editar un horario por deviceId + idHorario
     */
    public function editByIdHorario(Request $request, Device $device, $idHorario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }
        \Log::debug('Buscando horario (editByIdHorario)', [
            'deviceId' => $device->deviceId,
            'idHorario' => $idHorario,
            'query' => $device->horarios()->where('idHorario', $idHorario)->toSql(),
            'bindings' => $device->horarios()->where('idHorario', $idHorario)->getBindings(),
        ]);
        $horario = $device->horarios()->where('idHorario', $idHorario)->firstOrFail();
        return view('horarios.edit', [
            'device' => $device,
            'horario' => $horario,
            'dayOptions' => DayOfWeek::getOptions()
        ]);
    }

    /**
     * Actualizar un horario por deviceId + idHorario
     */
    public function updateByIdHorario(Request $request, Device $device, $idHorario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }
        
        $horario = $device->horarios()->where('idHorario', $idHorario)->firstOrFail();
        
        $validated = $request->validate([
            'nombreDeHorario' => 'required|string|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'diasDeSemana' => 'required|array|min:1',
            'diasDeSemana.*' => 'integer|in:0,1,2,3,4,5,6',
            'isActive' => 'boolean',
        ]);
        $validated['isActive'] = $request->has('isActive');
        $validated['diasDeSemana'] = array_map('intval', $validated['diasDeSemana']);
        
        try {
            // Guardar datos anteriores para el evento
            $oldData = $horario->toArray();

            $horario->update($validated);
            
            \Log::debug('Intentando registrar evento de ACTUALIZACIÓN de horario', ['horario_id' => $horario->idHorario]);
            // Registrar evento de sincronización
            SyncEvent::recordUpdate(
                $device->deviceId,
                'horario',
                $horario->idHorario,
                $horario->toArray(),
                $oldData
            );
            \Log::info('Evento de ACTUALIZACIÓN de horario registrado con éxito');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario actualizado exitosamente.',
                    'horario' => [
                        'id' => $horario->id,
                        'idHorario' => $horario->idHorario,
                        'nombreDeHorario' => $horario->nombreDeHorario,
                        'horaInicio' => $horario->horaInicio,
                        'horaFin' => $horario->horaFin,
                        'diasDeSemana' => $horario->diasDeSemana,
                        'isActive' => (bool) $horario->isActive,
                        'updated_at' => $horario->updated_at ? $horario->updated_at->toISOString() : null
                    ]
                ]);
            }
            return redirect()->route('horarios.index', $device)->with('success', 'Horario actualizado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error registrando evento de horario o actualizando horario', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el horario: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Error al actualizar el horario: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar un horario por deviceId + idHorario
     */
    public function destroyByIdHorario(Request $request, Device $device, $idHorario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }
        
        $horario = $device->horarios()->where('idHorario', $idHorario)->firstOrFail();
        
        try {
            // Guardar datos antes de eliminar para el evento
            $oldData = $horario->toArray();

            $horario->delete();
            
            \Log::debug('Intentando registrar evento de ELIMINACIÓN de horario', ['horario_id' => $oldData['idHorario']]);
            // Registrar evento de sincronización
            SyncEvent::recordDelete(
                $device->deviceId,
                'horario',
                $oldData['idHorario'],
                $oldData
            );
            \Log::info('Evento de ELIMINACIÓN de horario registrado con éxito');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario eliminado exitosamente.'
                ]);
            }
            return back()->with('success', 'Horario eliminado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error registrando evento de horario o eliminando horario', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el horario: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Error al eliminar el horario: ' . $e->getMessage()]);
        }
    }

    /**
     * API para App de Padres
     * Obtener todos los horarios de un dispositivo
     */
    public function getDeviceHorarios(Request $request, Device $device)
    {
        $this->authorize('view', $device);
        
        $horarios = $device->horarios()
            ->orderBy('horaInicio')
            ->get()
            ->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'idHorario' => $horario->idHorario,
                    'nombreDeHorario' => $horario->nombreDeHorario,
                    'diasDeSemana' => $horario->diasDeSemana,
                    'diasDeSemanaNombres' => $this->getDayNames($horario->diasDeSemana),
                    'horaInicio' => $horario->horaInicio,
                    'horaFin' => $horario->horaFin,
                    'isActive' => $horario->isActive,
                    'isCurrentlyActive' => $this->isHorarioCurrentlyActive($horario),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $horarios,
            'summary' => [
                'totalHorarios' => $horarios->count(),
                'activeHorarios' => $horarios->where('isActive', true)->count(),
                'currentlyActive' => $horarios->where('isCurrentlyActive', true)->count(),
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Crear un nuevo horario
     */
    public function createHorario(Request $request, Device $device)
    {
        $this->authorize('update', $device);
        
        $validated = $request->validate([
            'nombreDeHorario' => 'required|string|max:255',
            'diasDeSemana' => 'required|array|min:1',
            'diasDeSemana.*' => 'integer|min:0|max:6',
            'horaInicio' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'horaFin' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'isActive' => 'boolean',
        ]);
        
        // Generar nuevo ID único para el horario
        $maxId = $device->horarios()->max('idHorario') ?? 0;
        $newIdHorario = $maxId + 1;
        
        $horario = $device->horarios()->create([
            'deviceId' => $device->deviceId,
            'idHorario' => $newIdHorario,
            'nombreDeHorario' => $validated['nombreDeHorario'],
            'diasDeSemana' => $validated['diasDeSemana'],
            'horaInicio' => $validated['horaInicio'],
            'horaFin' => $validated['horaFin'],
            'isActive' => $validated['isActive'] ?? true,
        ]);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'horario',
            'entity_id' => $horario->idHorario,
            'action' => 'create',
            'data' => $horario->toArray(),
            'created_at' => now(),
        ]);
        
        // Crear notificación
        Notification::create([
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'type' => 'horario_created',
            'title' => 'Nuevo horario creado',
            'message' => "Se ha creado el horario '{$horario->nombreDeHorario}' en {$device->model}",
            'data' => [
                'idHorario' => $horario->idHorario,
                'nombreDeHorario' => $horario->nombreDeHorario,
            ],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Horario creado exitosamente',
            'data' => [
                'id' => $horario->id,
                'idHorario' => $horario->idHorario,
                'nombreDeHorario' => $horario->nombreDeHorario,
                'diasDeSemana' => $horario->diasDeSemana,
                'horaInicio' => $horario->horaInicio,
                'horaFin' => $horario->horaFin,
                'isActive' => $horario->isActive,
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Actualizar un horario existente
     */
    public function updateHorario(Request $request, Device $device, $idHorario)
    {
        $this->authorize('update', $device);
        
        $validated = $request->validate([
            'nombreDeHorario' => 'sometimes|string|max:255',
            'diasDeSemana' => 'sometimes|array|min:1',
            'diasDeSemana.*' => 'integer|min:0|max:6',
            'horaInicio' => 'sometimes|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'horaFin' => 'sometimes|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'isActive' => 'sometimes|boolean',
        ]);
        
        $horario = $device->horarios()
            ->where('idHorario', $idHorario)
            ->firstOrFail();
            
        $horario->update($validated);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'horario',
            'entity_id' => $horario->idHorario,
            'action' => 'update',
            'data' => $horario->toArray(),
            'created_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Horario actualizado exitosamente',
            'data' => [
                'id' => $horario->id,
                'idHorario' => $horario->idHorario,
                'nombreDeHorario' => $horario->nombreDeHorario,
                'diasDeSemana' => $horario->diasDeSemana,
                'horaInicio' => $horario->horaInicio,
                'horaFin' => $horario->horaFin,
                'isActive' => $horario->isActive,
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Eliminar un horario
     */
    public function deleteHorario(Request $request, Device $device, $idHorario)
    {
        $this->authorize('update', $device);
        
        $horario = $device->horarios()
            ->where('idHorario', $idHorario)
            ->firstOrFail();
            
        $horarioName = $horario->nombreDeHorario;
        $horario->delete();
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'horario',
            'entity_id' => $idHorario,
            'action' => 'delete',
            'created_at' => now(),
        ]);
        
        // Crear notificación
        Notification::create([
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'type' => 'horario_deleted',
            'title' => 'Horario eliminado',
            'message' => "Se ha eliminado el horario '{$horarioName}' de {$device->model}",
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Horario eliminado exitosamente',
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Activar/Desactivar un horario
     */
    public function toggleHorario(Request $request, Device $device, $idHorario)
    {
        $this->authorize('update', $device);
        
        $horario = $device->horarios()
            ->where('idHorario', $idHorario)
            ->firstOrFail();
            
        $horario->update(['isActive' => !$horario->isActive]);
        
        // Crear evento de sincronización
        SyncEvent::create([
            'deviceId' => $device->deviceId,
            'entity_type' => 'horario',
            'entity_id' => $horario->idHorario,
            'action' => 'update',
            'data' => ['isActive' => $horario->isActive],
            'created_at' => now(),
        ]);
        
        $action = $horario->isActive ? 'activado' : 'desactivado';
        
        return response()->json([
            'success' => true,
            'message' => "Horario {$action} exitosamente",
            'data' => [
                'idHorario' => $horario->idHorario,
                'isActive' => $horario->isActive,
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Helper: Obtener nombres de días
     */
    private function getDayNames($diasDeSemana)
    {
        $nombres = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];
        
        return collect($diasDeSemana)->map(function ($dia) use ($nombres) {
            return $nombres[$dia] ?? '';
        })->toArray();
    }
    
    /**
     * Helper: Verificar si un horario está activo en este momento
     */
    private function isHorarioCurrentlyActive($horario)
    {
        if (!$horario->isActive) {
            return false;
        }
        
        $now = now();
        $currentDay = $now->dayOfWeek;
        $currentTime = $now->format('H:i');
        
        // Verificar si hoy está en los días del horario
        if (!in_array($currentDay, $horario->diasDeSemana)) {
            return false;
        }
        
        // Verificar si la hora actual está en el rango
        return $currentTime >= $horario->horaInicio && $currentTime <= $horario->horaFin;
    }
} 