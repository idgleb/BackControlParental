@extends('layouts.app')

@section('title', 'Editar Horario - ' . $device->model)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Título y descripción -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Editar Horario
                </h1>
                <p class="mt-2 text-lg text-gray-600">
                    Editando horario para <span class="font-semibold">{{ $device->model }}</span>
                </p>
            </div>
            <a href="{{ route('horarios.index', $device) }}"
               class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                &larr; Volver a Horarios
            </a>
        </div>
    </div>

    <!-- Formulario de edición -->
    <div class="bg-white shadow-xl rounded-lg">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Editar Horario: {{ $horario->nombreDeHorario }}</h2>
        </div>
        <form method="POST" action="{{ route('horarios.update', [$device, $horario]) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Nombre del horario -->
            <div>
                <label for="nombreDeHorario" class="block text-sm font-medium text-gray-700">Nombre del Horario</label>
                <input type="text" name="nombreDeHorario" id="nombreDeHorario" required
                       value="{{ old('nombreDeHorario', $horario->nombreDeHorario) }}"
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
                                <option value="{{ $tiempo }}" {{ old('horaInicio', $horario->horaInicio) == $tiempo ? 'selected' : '' }}>
                                    {{ $tiempo }}
                                </option>
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
                                <option value="{{ $tiempo }}" {{ old('horaFin', $horario->horaFin) == $tiempo ? 'selected' : '' }}>
                                    {{ $tiempo }}
                                </option>
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
                                   {{ in_array($numero, old('diasDeSemana', $horario->diasDeSemana)) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $nombre }}</span>
                        </label>
                    @endforeach
                </div>
                @error('diasDeSemana')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estado activo -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="isActive" value="1"
                           {{ old('isActive', $horario->isActive) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Horario activo</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('horarios.index', $device) }}"
                   class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Actualizar Horario
                </button>
            </div>
        </form>
    </div>

</div>
@endsection