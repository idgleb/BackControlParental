@extends('layouts.app')

@section('title', 'Administrar ' . $device->model)

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Título y descripción -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    {{ $device->model ?? 'Dispositivo sin nombre' }}
                </h1>
                <p class="mt-2 text-lg text-gray-600">
                    ID del Dispositivo: <span class="font-mono text-sm bg-gray-100 p-1 rounded">{{ $device->deviceId }}</span>
                </p>
                <div class="mt-4 flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700">Batería:</span>
                    <div class="flex items-center space-x-2">
                        @php
                            $batteryLevel = $device->batteryLevel ?? 0;
                            $batteryColor = $batteryLevel <= 20 ? 'text-red-500' : ($batteryLevel <= 50 ? 'text-yellow-500' : 'text-green-500');
                            $batteryIcon = $batteryLevel <= 20 ? '🔴' : ($batteryLevel <= 50 ? '🟡' : '🟢');
                        @endphp
                        <span class="text-lg">{{ $batteryIcon }}</span>
                        <span class="text-sm font-semibold {{ $batteryColor }}">{{ $batteryLevel }}%</span>
                        <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full {{ $batteryLevel <= 20 ? 'bg-red-500' : ($batteryLevel <= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                 style="width: {{ $batteryLevel }}%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado Online/Offline -->
                <div class="mt-3 flex items-center space-x-2" id="device-status-{{ $device->id }}">
                    <span class="text-sm font-medium text-gray-700">Estado:</span>
                    @if($device->isOnline())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                            Online
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                            Offline
                        </span>
                    @endif
                    <span class="text-sm text-gray-500">({{ $device->last_seen }})</span>
                </div>
            </div>
            <a href="{{ route('horarios.index', $device) }}"
               class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Administrar Horarios
            </a>
        </div>
    </div>

    <!-- Mensaje de éxito -->
    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Formulario de actualización de Apps -->
    <form id="updateAppsForm" method="POST" action="{{ route('devices.apps.update', $device) }}">
        @csrf
        <div class="bg-white px-6 py-12 shadow-xl rounded-lg sm:px-12">
            <h2 class="text-xl font-semibold mb-4">Aplicaciones Instaladas</h2>
            
            <!-- Campo de búsqueda -->
            <div class="mb-6">
                <label for="searchApps" class="sr-only">Buscar aplicaciones</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="searchApps" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="Buscar aplicaciones por nombre...">
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($device->deviceApps as $deviceApp)
                    <div class="p-6 flex items-center justify-between hover:bg-gray-50 app-item" data-app-name="{{ strtolower($deviceApp->appName) }}">
                        <!-- Nombre de la App -->
                        <div class="w-1/3">
                            <h3 class="text-lg font-medium text-gray-900">{{ $deviceApp->appName }}</h3>
                            <p class="text-sm text-gray-500">{{ $deviceApp->packageName }}</p>
                        </div>

                        <!-- Estado de la App -->
                        <div class="w-1/4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="apps[{{ $deviceApp->id }}][appStatus]" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @foreach($appStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $deviceApp->appStatus->value === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Límite diario -->
                        <div class="w-1/4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Límite diario (minutos)</label>
                            <input type="number" name="apps[{{ $deviceApp->id }}][dailyUsageLimitMinutes]" 
                                   value="{{ $deviceApp->dailyUsageLimitMinutes }}" 
                                   min="0" max="1440"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <p>No hay aplicaciones instaladas en este dispositivo.</p>
                    </div>
                @endforelse
            </div>

            <!-- Botón de guardar -->
            <div class="mt-6 flex justify-end">
                <button type="button" id="saveAppsBtn" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg id="saveIcon" class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span id="saveText">Guardar Cambios</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchApps');
    const appItems = document.querySelectorAll('.app-item');
    const saveBtn = document.getElementById('saveAppsBtn');
    const updateForm = document.getElementById('updateAppsForm');
    
    // Funcionalidad de búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            appItems.forEach(function(item) {
                const appName = item.getAttribute('data-app-name');
                
                if (searchTerm === '' || appName.includes(searchTerm)) {
                    item.style.display = 'flex';
                    item.classList.remove('opacity-50');
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            const visibleItems = Array.from(appItems).filter(item => 
                item.style.display !== 'none'
            );
            
            // Buscar si ya existe un mensaje de "no resultados"
            let noResultsMessage = document.getElementById('no-results-message');
            
            if (visibleItems.length === 0 && searchTerm !== '') {
                if (!noResultsMessage) {
                    noResultsMessage = document.createElement('div');
                    noResultsMessage.id = 'no-results-message';
                    noResultsMessage.className = 'p-6 text-center text-gray-500';
                    noResultsMessage.innerHTML = `
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33"></path>
                        </svg>
                        <p class="mt-2 text-sm">No se encontraron aplicaciones que coincidan con "${searchTerm}"</p>
                    `;
                    
                    // Insertar después del campo de búsqueda
                    const searchContainer = searchInput.closest('.mb-6');
                    searchContainer.parentNode.insertBefore(noResultsMessage, searchContainer.nextSibling);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        });
        
        // Limpiar búsqueda con Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
                this.blur();
            }
        });
    }
    
    // Funcionalidad de guardado asíncrono
    if (saveBtn && updateForm) {
        saveBtn.addEventListener('click', function() {
            // Prevenir múltiples clicks
            if (saveBtn.disabled) return;
            
            // Cambiar estado del botón a "guardando"
            setButtonLoading(true);
            
            // Recopilar datos del formulario
            const formData = new FormData(updateForm);
            
            // Enviar petición AJAX
            fetch(updateForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Cambios guardados exitosamente', 'success');
                } else {
                    showNotification('Error al guardar los cambios', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al guardar los cambios', 'error');
            })
            .finally(() => {
                setButtonLoading(false);
            });
        });
    }
    
    function setButtonLoading(loading) {
        const saveIcon = document.getElementById('saveIcon');
        const saveText = document.getElementById('saveText');
        
        if (loading) {
            saveBtn.disabled = true;
            saveIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            saveIcon.classList.add('animate-spin');
            saveText.textContent = 'Guardando...';
        } else {
            saveBtn.disabled = false;
            saveIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
            saveIcon.classList.remove('animate-spin');
            saveText.textContent = 'Guardar Cambios';
        }
    }
    
    function showNotification(message, type) {
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${type === 'success' ? 
                        '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
                        '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'
                    }
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remover automáticamente después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
});
</script>

<script>
// Función para actualizar el estado del dispositivo
function updateDeviceStatus() {
    const deviceId = {{ $device->id }};
    const statusElement = document.getElementById(`device-status-${deviceId}`);
    
    fetch(`/api/devices/${deviceId}/status`)
        .then(response => response.json())
        .then(data => {
            // Buscar específicamente el span del estado (el segundo span, no el label)
            const statusSpans = statusElement.querySelectorAll('span');
            const statusSpan = statusSpans[1]; // El segundo span es el del estado
            const timeSpan = statusElement.querySelector('.text-sm.text-gray-500');
            
            if (statusSpan) {
                if (data.status === 'online') {
                    statusSpan.innerHTML = '<span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>Online';
                    statusSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                } else {
                    statusSpan.innerHTML = '<span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>Offline';
                    statusSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                }
            }
            
            if (timeSpan) {
                timeSpan.textContent = `(${data.last_seen})`;
            }
        })
        .catch(error => {
            console.error('Error updating device status:', error);
        });
}

// Actualizar estado cada 30 segundos
setInterval(updateDeviceStatus, 30000);

// Actualizar inmediatamente al cargar la página
document.addEventListener('DOMContentLoaded', updateDeviceStatus);
</script>
@endsection
