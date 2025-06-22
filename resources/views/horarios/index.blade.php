@extends('layouts.app')

@section('title', 'Horarios de ' . $device->model)

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Título y descripción -->
    <div class="mb-8">
         <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Administrar Horarios
                </h1>
                <p class="mt-2 text-lg text-gray-600">
                    Gestionando horarios para <span class="font-semibold">{{ $device->model }}</span>
                </p>
            </div>
            <a href="{{ route('devices.show', $device) }}"
               class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                &larr; Volver al Dispositivo
            </a>
        </div>
    </div>

    <!-- Mensaje de éxito -->
    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Lista de Horarios Existentes -->
    <div class="bg-white shadow-xl rounded-lg mb-12">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Horarios Activos</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @if($device->horarios->count() > 0)
                @foreach($device->horarios as $horario)
                    <div class="p-6 flex justify-between items-start">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-lg">{{ $horario->nombreDeHorario }}</h3>
                                @if($horario->isActive)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Inactivo
                                    </span>
                                @endif
                            </div>
                            <p class="text-gray-600 mt-1">De {{ $horario->horaInicio }} a {{ $horario->horaFin }}</p>
                            <p class="text-sm text-gray-500 mt-1">Días: {{ implode(', ', array_map(function($dia) { 
                                $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                return $dias[$dia] ?? $dia;
                            }, $horario->diasDeSemana)) }}</p>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <a href="{{ route('horarios.edit', [$device, $horario]) }}"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('horarios.destroy', [$device, $horario]) }}" 
                                  class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este horario?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="p-6 text-center text-gray-500">
                    <p>No hay horarios definidos para este dispositivo.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Formulario para crear nuevo horario -->
    <div class="bg-white shadow-xl rounded-lg">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Crear Nuevo Horario</h2>
        </div>
        <form method="POST" action="{{ route('horarios.store', $device) }}" class="p-6 space-y-6">
            @csrf
            
            <!-- Nombre del horario -->
            <div>
                <label for="nombreDeHorario" class="block text-sm font-medium text-gray-700">Nombre del Horario</label>
                <input type="text" name="nombreDeHorario" id="nombreDeHorario" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                       placeholder="Ej: Horario de estudio">
                @error('nombreDeHorario')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Horas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="horaInicio" class="block text-sm font-medium text-gray-700">Hora de Inicio</label>
                    <select name="horaInicio" id="horaInicio" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Seleccionar hora</option>
                        @for($hora = 0; $hora < 24; $hora++)
                            @for($minuto = 0; $minuto < 60; $minuto += 30)
                                @php
                                    $tiempo = sprintf('%02d:%02d', $hora, $minuto);
                                @endphp
                                <option value="{{ $tiempo }}">{{ $tiempo }}</option>
                            @endfor
                        @endfor
                    </select>
                    @error('horaInicio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="horaFin" class="block text-sm font-medium text-gray-700">Hora de Fin</label>
                    <select name="horaFin" id="horaFin" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Seleccionar hora</option>
                        @for($hora = 0; $hora < 24; $hora++)
                            @for($minuto = 0; $minuto < 60; $minuto += 30)
                                @php
                                    $tiempo = sprintf('%02d:%02d', $hora, $minuto);
                                @endphp
                                <option value="{{ $tiempo }}">{{ $tiempo }}</option>
                            @endfor
                        @endfor
                    </select>
                    @error('horaFin')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Días de la semana -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Días de la Semana</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach([
                        1 => 'Lunes',
                        2 => 'Martes', 
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        0 => 'Domingo'
                    ] as $numero => $nombre)
                        <label class="flex items-center">
                            <input type="checkbox" name="diasDeSemana[]" value="{{ $numero }}"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $nombre }}</span>
                        </label>
                    @endforeach
                </div>
                @error('diasDeSemana')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('error')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estado activo -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="isActive" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Horario activo</span>
                </label>
            </div>

            <!-- Botón de guardar -->
            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Crear Horario
                </button>
            </div>
        </form>
    </div>

</div>
@endsection 