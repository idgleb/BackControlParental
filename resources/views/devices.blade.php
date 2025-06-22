@extends('layouts.app')

@section('title', 'Administrar Dispositivos')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Título de la sección -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Tus Dispositivos</h1>
        <p class="mt-2 text-lg leading-8 text-gray-600">Vincula nuevos dispositivos y gestiona los existentes.</p>
    </div>

    <!-- Formulario para vincular -->
    <div class="bg-white p-8 rounded-xl shadow-lg mb-12">
        <h2 class="text-xl font-semibold mb-4">Vincular un nuevo dispositivo</h2>
        <form method="POST" action="{{ route('devices.link') }}" class="flex items-start space-x-4">
            @csrf
            <div class="flex-grow">
                <label for="deviceId" class="sr-only">ID del Dispositivo</label>
                <input id="deviceId" name="deviceId" type="text" required placeholder="Introduce el ID del dispositivo"
                       class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                @error('deviceId')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit"
                    class="rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Vincular
            </button>
        </form>
    </div>

    <!-- Lista de dispositivos -->
    <div>
        @if($devices->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($devices as $device)
                    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $device->model ?? 'Dispositivo' }}</h3>
                            <p class="text-sm text-gray-500 mt-1">ID: <span class="font-mono">{{ $device->deviceId }}</span></p>
                            <div class="mt-3 flex items-center space-x-2">
                                @php
                                    $batteryLevel = $device->batteryLevel ?? 0;
                                    $batteryColor = $batteryLevel <= 20 ? 'text-red-500' : ($batteryLevel <= 50 ? 'text-yellow-500' : 'text-green-500');
                                @endphp
                                <span class="text-xs font-medium text-gray-600">Batería:</span>
                                <span class="text-xs font-semibold {{ $batteryColor }}">{{ $batteryLevel }}%</span>
                                <div class="flex-1 w-12 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full {{ $batteryLevel <= 20 ? 'bg-red-500' : ($batteryLevel <= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                         style="width: {{ $batteryLevel }}%"></div>
                                </div>
                            </div>
                            
                            <!-- Estado Online/Offline -->
                            <div class="mt-2 flex items-center space-x-2" id="device-status-{{ $device->id }}">
                                <span class="text-xs font-medium text-gray-600">Estado:</span>
                                @if($device->isOnline())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                                        Offline
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500">({{ $device->last_seen }})</span>
                            </div>
                        </div>
                        <div class="mt-6">
                            <a href="{{ route('devices.show', $device->id) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Administrar &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Estado vacío -->
            <div class="text-center bg-white p-12 rounded-xl shadow-lg">
                <h3 class="text-xl font-semibold text-gray-900">Aún no tienes dispositivos vinculados.</h3>
                <p class="mt-2 text-gray-500">Usa el formulario de arriba para añadir tu primer dispositivo.</p>
            </div>
        @endif
    </div>
</div>

<script>
// Función para actualizar el estado de los dispositivos
function updateDeviceStatuses() {
    const deviceStatuses = document.querySelectorAll('[id^="device-status-"]');
    
    deviceStatuses.forEach(statusElement => {
        const deviceId = statusElement.id.replace('device-status-', '');
        
        // Hacer petición AJAX para obtener el estado actualizado
        fetch(`/api/devices/${deviceId}/status`)
            .then(response => response.json())
            .then(data => {
                // Buscar específicamente el span del estado (el segundo span, no el label)
                const statusSpans = statusElement.querySelectorAll('span');
                const statusSpan = statusSpans[1]; // El segundo span es el del estado
                const timeSpan = statusElement.querySelector('.text-xs.text-gray-500');
                
                if (statusSpan) {
                    if (data.status === 'online') {
                        statusSpan.innerHTML = '<span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>Online';
                        statusSpan.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                    } else {
                        statusSpan.innerHTML = '<span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>Offline';
                        statusSpan.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                    }
                }
                
                if (timeSpan) {
                    timeSpan.textContent = `(${data.last_seen})`;
                }
            })
            .catch(error => {
                console.error('Error updating device status:', error);
            });
    });
}

// Actualizar estado cada 30 segundos
setInterval(updateDeviceStatuses, 30000);

// Actualizar inmediatamente al cargar la página
document.addEventListener('DOMContentLoaded', updateDeviceStatuses);
</script>
@endsection
