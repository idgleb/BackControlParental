<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Horario;
use App\Enums\DayOfWeek;
use Illuminate\Http\Request;

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
            $horario->update($validated);
            
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
            $horario->delete();
            
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
        \Log::debug('Buscando horario (updateByIdHorario)', [
            'deviceId' => $device->deviceId,
            'idHorario' => $idHorario,
            'query' => $device->horarios()->where('idHorario', $idHorario)->toSql(),
            'bindings' => $device->horarios()->where('idHorario', $idHorario)->getBindings(),
        ]);
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
            $horario->update($validated);
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
        \Log::debug('Buscando horario (destroyByIdHorario)', [
            'deviceId' => $device->deviceId,
            'idHorario' => $idHorario,
            'query' => $device->horarios()->where('idHorario', $idHorario)->toSql(),
            'bindings' => $device->horarios()->where('idHorario', $idHorario)->getBindings(),
        ]);
        $horario = $device->horarios()->where('idHorario', $idHorario)->firstOrFail();
        try {
            $horario->delete();
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Horario eliminado exitosamente.'
                ]);
            }
            return back()->with('success', 'Horario eliminado exitosamente.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el horario: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Error al eliminar el horario: ' . $e->getMessage()]);
        }
    }
} 