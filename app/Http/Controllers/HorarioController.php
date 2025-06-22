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

        Horario::create($validated);

        return back()->with('success', 'Horario creado exitosamente.');
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

        $horario->update($validated);

        return redirect()->route('horarios.index', $device)->with('success', 'Horario actualizado exitosamente.');
    }

    public function destroy(Request $request, Device $device, Horario $horario)
    {
        if ($request->user()->cannot('update', $device)) {
            abort(403);
        }

        $horario->delete();

        return back()->with('success', 'Horario eliminado exitosamente.');
    }
} 