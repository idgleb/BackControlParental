@extends('layouts.app')

@section('title', 'Administrar ' . $device->model)

@php
if (!function_exists('tiempoRelativoAbreviado')) {
    function tiempoRelativoAbreviado($date) {
        if (!$date) return 'Nunca';
        if (!$date instanceof \Carbon\Carbon) $date = \Carbon\Carbon::parse($date);
        $now = \Carbon\Carbon::now('UTC');
        $date = $date->copy()->setTimezone('UTC');
        $diff = $now->diffInSeconds($date);
        if ($diff < 1) return 'Ahora mismo';
        $days = floor($diff / 86400);
        $diff -= $days * 86400;
        $hours = floor($diff / 3600);
        $diff -= $hours * 3600;
        $minutes = floor($diff / 60);
        $seconds = $diff - $minutes * 60;
        $parts = [];
        if ($days > 0) $parts[] = $days . ' día' . ($days > 1 ? 's' : '');
        if ($hours > 0) $parts[] = $hours . ' hora' . ($hours > 1 ? 's' : '');
        if ($minutes > 0) $parts[] = $minutes . ' min';
        if ($seconds > 0 && count($parts) < 2) $parts[] = $seconds . ' seg';
        $parts = array_slice($parts, 0, 2);
        return 'Hace ' . implode(' y ', $parts);
    }
}
@endphp

@section('content')
<div class="max-w-5xl mx-auto px-2 sm:px-4">
    <!-- Título y descripción -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="w-full">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">
                    {{ $device->model ?? 'Dispositivo sin nombre' }}
                </h1>
                <p class="mt-2 text-base sm:text-lg text-gray-600">
                    ID del Dispositivo: <span class="font-mono text-xs sm:text-sm bg-gray-100 p-1 rounded">{{ $device->deviceId }}</span>
                </p>
                <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
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
                    @php
                        $updatedAt = $device->updated_at instanceof \Illuminate\Support\Carbon
                            ? $device->updated_at
                            : ($device->updated_at ? \Carbon\Carbon::parse($device->updated_at) : null);
                    @endphp
                    <span class="text-sm text-gray-500 last-seen" data-last-seen="{{ $updatedAt ? $updatedAt->toISOString() : '' }}">
                        <!-- El texto se llenará por JS -->
                    </span>
                </div>
                
                <!-- Ubicación -->
                @if($device->hasLocation())
                <div class="mt-3 flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700">Ubicación:</span>
                    <a href="{{ route('devices.location', $device) }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                        📍 {{ number_format($device->latitude, 6) }}, {{ number_format($device->longitude, 6) }}
                    </a>
                    @if($device->location_updated_at)
                        <span class="text-xs text-gray-500">
                            ({{ tiempoRelativoAbreviado($device->location_updated_at) }})
                        </span>
                    @endif
                </div>
                @endif
            </div>
            <a href="{{ route('horarios.index', $device) }}"
               class="w-full sm:w-auto rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 text-center">
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
    <form id="updateAppsForm" method="POST" action="{{ route('ajax.devices.apps.update', $device) }}">
        @csrf
        @method('PUT')
        <div class="bg-white px-2 sm:px-6 py-8 sm:py-12 shadow-xl rounded-lg">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">Aplicaciones Instaladas</h2>
            
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
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-sm sm:text-base"
                           placeholder="Buscar aplicaciones por nombre...">
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($device->deviceApps as $deviceApp)
                    <div class="p-4 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:bg-gray-50 app-item" data-app-name="{{ strtolower($deviceApp->appName) }}">
                        <!-- Icono y Nombre de la App -->
                        <div class="w-full md:w-1/3 mb-2 md:mb-0 flex items-center gap-3">
                            @if($deviceApp->app_icon_base64)
                                <img src="data:image/png;base64,{{ $deviceApp->app_icon_base64 }}" alt="Icono de {{ $deviceApp->appName }}" class="w-5 h-5 rounded-md object-cover border border-gray-200 bg-white" loading="lazy">
                            @else
                                <div class="w-5 h-5 rounded-md bg-gray-200 flex items-center justify-center text-gray-400 text-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h2a4 4 0 014 4v2M9 7h.01M15 7h.01" /></svg>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">{{ $deviceApp->appName }}</h3>
                                <p class="text-xs sm:text-sm text-gray-500 break-all">{{ $deviceApp->packageName }}</p>
                            </div>
                        </div>
                        <!-- Estado de la App -->
                        <div class="w-full md:w-1/4 mb-2 md:mb-0">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="apps[{{ $deviceApp->id }}][appStatus]" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                @foreach($appStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $deviceApp->appStatus->value === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Límite diario -->
                        <div class="w-full md:w-1/4">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Límite diario (minutos)</label>
                            <input type="number" name="apps[{{ $deviceApp->id }}][dailyUsageLimitMinutes]" 
                                   value="{{ $deviceApp->dailyUsageLimitMinutes }}" 
                                   min="0" max="1440"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
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
            const appItems = document.querySelectorAll('.app-item');

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
    
    // Funcionalidad de guardado asíncrono con Axios
    if (saveBtn && updateForm) {
        saveBtn.addEventListener('click', async function() {
            // Prevenir múltiples clicks
            if (saveBtn.disabled) return;
            
            try {
                // Cambiar estado del botón a "guardando"
                setButtonLoading(true);
                
                // Recopilar datos del formulario
                const formData = new FormData(updateForm);
                
                // Enviar petición con Axios
                const response = await axios.post(updateForm.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 10000 // 10 segundos de timeout
                });
                
                if (response.data.success) {
                    showNotification('Cambios guardados exitosamente', 'success');
                    if (response.data.device && response.data.device.apps) {
                        response.data.device.apps.forEach(app => {
                            const appItem = document.querySelector(`.app-item input[name='apps[${app.id}][dailyUsageLimitMinutes]']`)?.closest('.app-item');
                            if (appItem) {
                                // Estado
                                const select = appItem.querySelector(`select[name='apps[${app.id}][appStatus]']`);
                                if (select && select.value !== app.app_status) {
                                    select.value = app.app_status;
                                }
                                // Límite diario
                                const input = appItem.querySelector(`input[name='apps[${app.id}][dailyUsageLimitMinutes]']`);
                                if (input && input.value != app.daily_usage_limit_minutes) {
                                    input.value = app.daily_usage_limit_minutes;
                                }
                            }
                        });
                    } else {
                        fetchAndRenderApps();
                    }
                } else {
                    showNotification(response.data.message || 'Error al guardar los cambios', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                
                let errorMessage = 'Error al guardar los cambios';
                
                if (error.response) {
                    // El servidor respondió con un código de error
                    if (error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.status === 422) {
                        errorMessage = 'Datos inválidos. Por favor, revisa la información.';
                    } else if (error.response.status === 500) {
                        errorMessage = 'Error interno del servidor. Inténtalo de nuevo.';
                    }
                } else if (error.request) {
                    // La petición fue hecha pero no se recibió respuesta
                    errorMessage = 'No se pudo conectar con el servidor. Verifica tu conexión.';
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'La petición tardó demasiado. Inténtalo de nuevo.';
                }
                
                showNotification(errorMessage, 'error');
            } finally {
                setButtonLoading(false);
            }
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
        // Remover notificaciones existentes
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ${
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
                    <button onclick="this.closest('.notification').remove()" class="text-white hover:text-gray-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animación de entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover automáticamente después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    document.querySelectorAll('.last-seen').forEach(function(el) {
        const ts = el.getAttribute('data-last-seen');
        el.textContent = ts ? `(${formatLastSeen(ts)})` : '(Nunca)';
    });
});
</script>

<script>
// Función para actualizar el estado del dispositivo con Axios
async function updateDeviceStatus() {
    const deviceId = {{ $device->id }};
    const statusElement = document.getElementById(`device-status-${deviceId}`);
    
    // Evitar múltiples peticiones simultáneas
    if (window.isUpdatingDeviceStatus) return;
    window.isUpdatingDeviceStatus = true;
    
    try {
        const response = await axios.get(`/ajax/devices/${deviceId}/status`, {
            timeout: 1500 // Reducir timeout para actualizaciones más rápidas
        });
        
        // Buscar específicamente el span del estado (el segundo span, no el label)
        const statusSpans = statusElement.querySelectorAll('span');
        const statusSpan = statusSpans[1]; // El segundo span es el del estado
        const timeSpan = statusElement.querySelector('.text-sm.text-gray-500');
        
        if (statusSpan) {
            if (response.data.status === 'online') {
                statusSpan.innerHTML = '<span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>Online';
                statusSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            } else {
                statusSpan.innerHTML = '<span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>Offline';
                statusSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
            }
        }
        
        if (timeSpan) {
            timeSpan.textContent = `(${formatLastSeen(response.data.last_seen)})`;
        }
    } catch (error) {
        console.error('Error updating device status:', error);
        // No mostrar error al usuario para actualizaciones automáticas
    } finally {
        window.isUpdatingDeviceStatus = false;
    }
}

// Actualizar estado cada 2 segundos
setInterval(updateDeviceStatus, 2000);

// Actualizar inmediatamente al cargar la página
document.addEventListener('DOMContentLoaded', updateDeviceStatus);

function formatLastSeen(timestamp) {
    if (!timestamp) return 'Nunca';
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return 'Nunca';

    const now = new Date();
    let diff = Math.floor((now - date) / 1000); // en segundos

    if (diff < 1) return 'Ahora mismo';

    const days = Math.floor(diff / 86400);
    diff -= days * 86400;
    const hours = Math.floor(diff / 3600);
    diff -= hours * 3600;
    const minutes = Math.floor(diff / 60);
    const seconds = diff - minutes * 60;

    let parts = [];
    if (days > 0) parts.push(`${days} día${days > 1 ? 's' : ''}`);
    if (hours > 0) parts.push(`${hours} h`);
    if (minutes > 0) parts.push(`${minutes} min`);
    if (seconds > 0 && parts.length < 2) parts.push(`${seconds} seg`);

    parts = parts.slice(0, 2);

    return `Hace ${parts.join(' y ')}`;
}
</script>

<script>
// --- ACTUALIZACIÓN AUTOMÁTICA DE APPS ---
const deviceId = {{ $device->id }};
const appStatusOptions = @json($appStatusOptions);

function renderAppItem(app, appStatusOptions) {
    // Icono
    let iconHtml = '';
    if (app.app_icon_base64) {
        iconHtml = `<img src="data:image/png;base64,${app.app_icon_base64}" alt="Icono de ${app.app_name}" class="w-5 h-5 rounded-md object-cover border border-gray-200 bg-white" loading="lazy">`;
    } else {
        iconHtml = `<div class="w-5 h-5 rounded-md bg-gray-200 flex items-center justify-center text-gray-400 text-base">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h2a4 4 0 014 4v2M9 7h.01M15 7h.01" /></svg>
        </div>`;
    }
    // Opciones de estado
    let statusOptions = '';
    for (const [value, label] of Object.entries(appStatusOptions)) {
        statusOptions += `<option value="${value}"${app.app_status === value ? ' selected' : ''}>${label}</option>`;
    }
    return `
    <div class="p-4 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:bg-gray-50 app-item" data-app-name="${app.app_name.toLowerCase()}">
        <div class="w-full md:w-1/3 mb-2 md:mb-0 flex items-center gap-3">
            ${iconHtml}
            <div>
                <h3 class="text-base sm:text-lg font-medium text-gray-900">${app.app_name}</h3>
                <p class="text-xs sm:text-sm text-gray-500 break-all">${app.package_name}</p>
            </div>
        </div>
        <div class="w-full md:w-1/4 mb-2 md:mb-0">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select name="apps[${app.id}][appStatus]" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                ${statusOptions}
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Límite diario (minutos)</label>
            <input type="number" name="apps[${app.id}][dailyUsageLimitMinutes]" value="${app.daily_usage_limit_minutes ?? 0}" min="0" max="1440" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
        </div>
    </div>`;
}

async function fetchAndRenderApps() {
    try {
        const response = await axios.get(`/ajax/devices/${deviceId}`);
        const device = response.data.device || response.data;
        const apps = device.apps || device.deviceApps || [];
        const appsContainer = document.querySelector('.divide-y.divide-gray-200');
        if (!appsContainer) return;

        // Crear un mapa de apps actuales en el DOM por ID
        const domAppItems = Array.from(appsContainer.querySelectorAll('.app-item'));
        const domAppsMap = {};
        domAppItems.forEach(item => {
            // Buscar el input para extraer el ID
            const input = item.querySelector("input[name^='apps['][name$='[dailyUsageLimitMinutes]']");
            if (input) {
                const match = input.name.match(/apps\[(\d+)\]/);
                if (match) {
                    domAppsMap[match[1]] = item;
                }
            }
        });

        // IDs de apps recibidas
        const receivedIds = apps.map(app => String(app.id));

        // Actualizar o agregar apps
        apps.forEach(app => {
            const appId = String(app.id);
            let appItem = domAppsMap[appId];
            if (appItem) {
                // Ya existe en el DOM, solo actualizar campos si cambiaron
                // Estado
                const select = appItem.querySelector(`select[name='apps[${appId}][appStatus]']`);
                if (select && select.value !== app.app_status && document.activeElement !== select) {
                    select.value = app.app_status;
                }
                // Límite diario
                const input = appItem.querySelector(`input[name='apps[${appId}][dailyUsageLimitMinutes]']`);
                if (input && input.value != app.daily_usage_limit_minutes && document.activeElement !== input) {
                    input.value = app.daily_usage_limit_minutes;
                }
                // Nombre y paquete (por si acaso)
                const nameEl = appItem.querySelector('h3');
                if (nameEl && nameEl.textContent !== app.app_name) {
                    nameEl.textContent = app.app_name;
                }
                const pkgEl = appItem.querySelector('p');
                if (pkgEl && pkgEl.textContent !== app.package_name) {
                    pkgEl.textContent = app.package_name;
                }
                // Icono (opcional, solo si cambia)
                const iconImg = appItem.querySelector('img');
                if (iconImg && app.app_icon_base64 && iconImg.src !== `data:image/png;base64,${app.app_icon_base64}`) {
                    iconImg.src = `data:image/png;base64,${app.app_icon_base64}`;
                }
            } else {
                // No existe, agregar al final
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = renderAppItem(app, appStatusOptions);
                appsContainer.appendChild(tempDiv.firstElementChild);
            }
        });

        // Eliminar apps que ya no existen
        domAppItems.forEach(item => {
            const input = item.querySelector("input[name^='apps['][name$='[dailyUsageLimitMinutes]']");
            if (input) {
                const match = input.name.match(/apps\[(\d+)\]/);
                if (match && !receivedIds.includes(match[1])) {
                    item.remove();
                }
            }
        });

        // Aplicar filtro de búsqueda activo tras actualizar el DOM
        const searchInput = document.getElementById('searchApps');
        if (searchInput) {
            searchInput.dispatchEvent(new Event('input'));
        }
        // Volver a asociar eventos de guardado instantáneo
        bindInstantSaveEvents();
    } catch (error) {
        console.error('Error actualizando apps:', error);
    }
}

// Actualizar apps cada 5 segundos
setInterval(fetchAndRenderApps, 5000);
// Actualizar al cargar la página
fetchAndRenderApps();
</script>

<script>
// Guardado instantáneo de campos de app
function showFieldSpinner(field, show) {
    let spinner = field.parentElement.querySelector('.inline-spinner');
    if (!spinner) {
        spinner = document.createElement('span');
        spinner.className = 'inline-spinner ml-2 align-middle';
        spinner.innerHTML = `<svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>`;
        field.parentElement.appendChild(spinner);
    }
    spinner.style.display = show ? 'inline-block' : 'none';
}

function instantSaveAppField(appId, fieldName, value, fieldEl) {
    showFieldSpinner(fieldEl, true);
    // Construir payload mínimo
    const payload = { app_id: appId };
    payload[fieldName] = value;
    axios.post(`/ajax/devices/${deviceId}/apps/${appId}/update-field`, payload, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => {
        showNotification('Guardado', 'success');
    })
    .catch(err => {
        showNotification('Error al guardar', 'error');
    })
    .finally(() => {
        showFieldSpinner(fieldEl, false);
    });
}

// Delegar eventos al cargar y tras cada actualización de apps
function bindInstantSaveEvents() {
    document.querySelectorAll('.app-item').forEach(appItem => {
        // Estado
        const select = appItem.querySelector("select[name^='apps['][name$='[appStatus]']");
        if (select && !select.dataset.instantSaveBound) {
            select.addEventListener('change', function() {
                const appId = select.name.match(/apps\[(\d+)\]/)[1];
                instantSaveAppField(appId, 'app_status', select.value, select);
            });
            select.dataset.instantSaveBound = '1';
        }
        // Límite diario
        const input = appItem.querySelector("input[name^='apps['][name$='[dailyUsageLimitMinutes]']");
        if (input && !input.dataset.instantSaveBound) {
            input.addEventListener('change', function() {
                const appId = input.name.match(/apps\[(\d+)\]/)[1];
                instantSaveAppField(appId, 'daily_usage_limit_minutes', input.value, input);
            });
            input.dataset.instantSaveBound = '1';
        }
    });
}

// Llamar al cargar y tras cada actualización de apps
bindInstantSaveEvents();
</script>

@endsection
