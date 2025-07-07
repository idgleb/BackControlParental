<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Horario;
use App\Models\SyncEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Listar horarios de un dispositivo
     */
    public function index(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $horarios = Horario::where('deviceId', $device->deviceId)
            ->orderBy('horaInicio')
            ->get()
            ->map(function ($horario) {
                return [
                    'id' => $horario->idHorario,
                    'name' => $horario->nombreDeHorario,
                    'days' => $horario->diasDeSemana,
                    'start_time' => $horario->horaInicio,
                    'end_time' => $horario->horaFin,
                    'is_active' => (bool)$horario->isActive,
                    'created_at' => $horario->created_at->toISOString(),
                    'updated_at' => $horario->updated_at->toISOString(),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $horarios,
        ]);
    }
    
    /**
     * Crear un nuevo horario
     */
    public function store(Request $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'days' => 'required|array|min:1',
            'days.*' => 'integer|min:0|max:6',
            'start_time' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'end_time' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/|after:start_time',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        return DB::transaction(function () use ($request, $device) {
            // Generar ID único para el horario
            $lastId = Horario::where('deviceId', $device->deviceId)
                ->max('idHorario');
            $newId = ($lastId ?? 0) + 1;
            
            // Crear horario
            $horario = Horario::create([
                'deviceId' => $device->deviceId,
                'idHorario' => $newId,
                'nombreDeHorario' => $request->input('name'),
                'diasDeSemana' => $request->input('days'),
                'horaInicio' => $request->input('start_time'),
                'horaFin' => $request->input('end_time'),
                'isActive' => $request->input('is_active', true),
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
            
            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => [
                    'id' => $horario->idHorario,
                    'name' => $horario->nombreDeHorario,
                    'days' => $horario->diasDeSemana,
                    'start_time' => $horario->horaInicio,
                    'end_time' => $horario->horaFin,
                    'is_active' => (bool)$horario->isActive,
                ],
            ], 201);
        });
    }
    
    /**
     * Actualizar un horario
     */
    public function update(Request $request, Device $device, int $schedule): JsonResponse
    {
        $this->authorize('update', $device);
        
        $horario = Horario::where('deviceId', $device->deviceId)
            ->where('idHorario', $schedule)
            ->firstOrFail();
            
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:100',
            'days' => 'array|min:1',
            'days.*' => 'integer|min:0|max:6',
            'start_time' => 'string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'end_time' => 'string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Validar que end_time > start_time si ambos se proporcionan
        $startTime = $request->input('start_time', $horario->horaInicio);
        $endTime = $request->input('end_time', $horario->horaFin);
        if ($endTime <= $startTime) {
            return response()->json([
                'success' => false,
                'error' => 'La hora de fin debe ser mayor que la hora de inicio',
            ], 422);
        }
        
        return DB::transaction(function () use ($request, $horario) {
            // Actualizar horario
            $horario->update([
                'nombreDeHorario' => $request->input('name', $horario->nombreDeHorario),
                'diasDeSemana' => $request->input('days', $horario->diasDeSemana),
                'horaInicio' => $request->input('start_time', $horario->horaInicio),
                'horaFin' => $request->input('end_time', $horario->horaFin),
                'isActive' => $request->input('is_active', $horario->isActive),
            ]);
            
            // Crear evento de sincronización
            SyncEvent::create([
                'deviceId' => $horario->deviceId,
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
                    'id' => $horario->idHorario,
                    'name' => $horario->nombreDeHorario,
                    'days' => $horario->diasDeSemana,
                    'start_time' => $horario->horaInicio,
                    'end_time' => $horario->horaFin,
                    'is_active' => (bool)$horario->isActive,
                ],
            ]);
        });
    }
    
    /**
     * Activar/desactivar un horario
     */
    public function toggle(Request $request, Device $device, int $schedule): JsonResponse
    {
        $this->authorize('update', $device);
        
        $horario = Horario::where('deviceId', $device->deviceId)
            ->where('idHorario', $schedule)
            ->firstOrFail();
        
        return DB::transaction(function () use ($horario) {
            $horario->update(['isActive' => !$horario->isActive]);
            
            // Crear evento de sincronización
            SyncEvent::create([
                'deviceId' => $horario->deviceId,
                'entity_type' => 'horario',
                'entity_id' => $horario->idHorario,
                'action' => 'update',
                'data' => ['isActive' => $horario->isActive],
                'created_at' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $horario->isActive ? 'Horario activado' : 'Horario desactivado',
                'data' => [
                    'id' => $horario->idHorario,
                    'is_active' => (bool)$horario->isActive,
                ],
            ]);
        });
    }
    
    /**
     * Eliminar un horario
     */
    public function destroy(Request $request, Device $device, int $schedule): JsonResponse
    {
        $this->authorize('update', $device);
        
        $horario = Horario::where('deviceId', $device->deviceId)
            ->where('idHorario', $schedule)
            ->firstOrFail();
        
        return DB::transaction(function () use ($horario) {
            // Crear evento de sincronización antes de eliminar
            SyncEvent::create([
                'deviceId' => $horario->deviceId,
                'entity_type' => 'horario',
                'entity_id' => $horario->idHorario,
                'action' => 'delete',
                'data' => ['idHorario' => $horario->idHorario],
                'created_at' => now(),
            ]);
            
            $horario->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Horario eliminado exitosamente',
            ]);
        });
    }
} 