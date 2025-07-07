@extends('layouts.app')

@section('title', 'Administrar Dispositivos')

@php
if (!function_exists('tiempoRelativoAbreviado')) {
    function tiempoRelativoAbreviado($date) {
        if (!$date) return 'Nunca';
        if (!$date instanceof \Carbon\Carbon) $date = \Carbon\Carbon::parse($date);
        $now = \Carbon\Carbon::now('UTC');
        $date = $date->copy()->setTimezone('UTC');
        // Debug temporal:
        // echo 'now: ' . $now->toISOString() . ' | updated_at: ' . $date->toISOString();
        $diff = abs($now->diffInSeconds($date));
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

if (!function_exists('svgBateria')) {
    function svgBateria($nivel) {
        $nivel = max(0, min(100, (int)$nivel));
        $color = $nivel <= 20 ? '#ef4444' : ($nivel <= 50 ? '#facc15' : '#22c55e');
        $ancho = round(18 * $nivel / 100); // ancho del relleno
        return '<svg width="28" height="14" viewBox="0 0 28 14" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline align-middle"><rect x="1" y="3" width="22" height="8" rx="2" fill="#fff" stroke="#d1d5db" stroke-width="2"/><rect x="23" y="5" width="3" height="4" rx="1" fill="#d1d5db"/><rect x="3" y="5" width="' . $ancho . '" height="4" rx="1" fill="' . $color . '"/></svg>';
    }
}
@endphp

@section('content')
<div class="max-w-4xl mx-auto px-2 sm:px-4">
    <!-- Título de la sección -->
    <div class="mb-8 text-center">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Tus Dispositivos</h1>
        <p class="mt-2 text-base sm:text-lg leading-7 sm:leading-8 text-gray-600">Vincula nuevos dispositivos y gestiona los existentes.</p>
    </div>

    <!-- Mensajes flash -->
    @if(session('success'))
        <div class="bg-green-50 p-4 rounded-lg mb-6 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 p-4 rounded-lg mb-6 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Formulario para vincular -->
    <div class="bg-white p-4 sm:p-8 rounded-xl shadow-lg mb-12">
        <h2 class="text-lg sm:text-xl font-semibold mb-4">Vincular un nuevo dispositivo</h2>
        <p class="text-sm text-gray-600 mb-6">
            Abre la app Control Parental en el dispositivo del niño y obtén el código de verificación de 6 dígitos.
        </p>
        <form method="POST" action="{{ route('devices.link') }}" class="flex flex-col sm:flex-row items-stretch sm:items-start gap-4">
            @csrf
            <div class="flex-grow">
                <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Código de verificación
                </label>
                <input id="verification_code" 
                       name="verification_code" 
                       type="text" 
                       required 
                       placeholder="123-456"
                       pattern="[0-9]{3}-[0-9]{3}"
                       maxlength="7"
                       class="block w-full rounded-md border-0 py-2.5 text-center text-lg font-mono tracking-wider text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                @error('verification_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">
                    El código expira en 10 minutos
                </p>
            </div>
            <button type="submit"
                    class="w-full sm:w-auto rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Vincular dispositivo
            </button>
        </form>
    </div>

    <!-- Lista de dispositivos -->
    <div id="devicesContainer">
        @if($devices->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
                @foreach($devices as $device)
                    <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">{{ $device->model ?? 'Dispositivo' }}</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">ID: <span class="font-mono">{{ $device->deviceId }}</span></p>
                            <div class="mt-3 flex items-center space-x-2">
                                @php
                                    $batteryLevel = $device->batteryLevel ?? 0;
                                    $batteryColor = $batteryLevel <= 20 ? 'text-red-500' : ($batteryLevel <= 50 ? 'text-yellow-500' : 'text-green-500');
                                @endphp
                                <span class="text-xs font-medium text-gray-600">Batería:</span>
                                {!! svgBateria($device->batteryLevel ?? 0) !!}
                                <span class="text-xs font-semibold {{ $batteryColor }}">{{ $device->batteryLevel ?? 0 }}%</span>
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
                                <span class="text-xs text-gray-500 last-seen" data-last-seen="{{ $device->updated_at ? $device->updated_at->toISOString() : '' }}">
                                    <!-- El texto se llenará por JS -->
                                </span>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <a href="{{ route('devices.show', $device->id) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Ver detalles
                            </a>
                            <button onclick="deleteDevice({{ $device->id }}, '{{ addslashes($device->model ?? 'Dispositivo') }}')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Desvincular">
                                <x-icons.trash class="w-4 h-4" />
                                Desvincular
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Estado vacío -->
            <div class="text-center bg-white p-8 sm:p-12 rounded-xl shadow-lg">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Aún no tienes dispositivos vinculados.</h3>
                <p class="mt-2 text-gray-500">Usa el formulario de arriba para añadir tu primer dispositivo.</p>
            </div>
        @endif
    </div>
</div>

<script>
// Sistema de actualización automática de datos
class AutoRefreshSystem {
    constructor() {
        this.refreshInterval = 2000; // 2 segundos
        this.devices = [];
        this.isLoading = false;
        this.errorCount = 0;
        this.init();
    }
    
    init() {
        this.loadDevices();
        this.startAutoRefresh();
    }
    
    async loadDevices() {
        try {
            // Evitar múltiples peticiones simultáneas
            if (this.isLoading) return;
            this.isLoading = true;
            
            const response = await axios.get('/api/devices', {
                timeout: 2000 // Reducir timeout para actualizaciones más rápidas
            });
            
            if (response.data.success) {
                this.updateDevicesList(response.data.devices);
                this.errorCount = 0; // Resetear contador de errores en éxito
            }
        } catch (error) {
            console.error('Error loading devices:', error);
            this.handleError();
        } finally {
            this.isLoading = false;
        }
    }
    
    handleError() {
        // Si hay errores consecutivos, aumentar el intervalo temporalmente
        this.errorCount = (this.errorCount || 0) + 1;
        if (this.errorCount > 3) {
            this.refreshInterval = 10000; // 10 segundos si hay muchos errores
            setTimeout(() => {
                this.refreshInterval = 2000; // Volver a 2 segundos después de un minuto
                this.errorCount = 0;
            }, 60000);
        }
    }
    
    updateDevicesList(newDevices) {
        const devicesContainer = document.getElementById('devicesContainer');
        if (!devicesContainer) return;
        this.devices = newDevices;
        if (newDevices.length === 0) {
            devicesContainer.innerHTML = `
                <div class="text-center bg-white p-8 sm:p-12 rounded-xl shadow-lg">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Aún no tienes dispositivos vinculados.</h3>
                    <p class="mt-2 text-gray-500">Usa el formulario de arriba para añadir tu primer dispositivo.</p>
                </div>
            `;
            return;
        }
        // Usar la función deviceCardHTML para cada dispositivo
        const devicesHTML = newDevices.map(device => deviceCardHTML(device)).join('');
        devicesContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
                ${devicesHTML}
            </div>
        `;
        this.showUpdateIndicator();
        updateAllLastSeen();
    }
    
    formatLastSeen(timestamp) {
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
    
    showUpdateIndicator() {
        // Crear o actualizar indicador de actualización
        let indicator = document.getElementById('updateIndicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'updateIndicator';
            indicator.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs shadow-lg transform transition-all duration-300';
            indicator.style.transform = 'translateY(100%)';
            document.body.appendChild(indicator);
        }
        
        indicator.innerHTML = `
            <div class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Actualizado
            </div>
        `;
        
        // Mostrar indicador
        setTimeout(() => {
            indicator.style.transform = 'translateY(0)';
        }, 100);
        
        // Ocultar después de 2 segundos
        setTimeout(() => {
            indicator.style.transform = 'translateY(100%)';
        }, 2000);
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.loadDevices();
        }, this.refreshInterval);
    }
}

// Función para eliminar dispositivo
async function deleteDevice(deviceId, deviceName) {
    if (!confirm(`¿Estás seguro de que quieres desvincular el dispositivo "${deviceName}"?`)) {
        return;
    }
    
    try {
        const response = await axios.delete(`/devices/${deviceId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.data.success) {
            showNotification('Dispositivo desvinculado exitosamente', 'success');
            // Recargar la lista de dispositivos
            if (window.autoRefreshSystem) {
                window.autoRefreshSystem.loadDevices();
            }
        } else {
            showNotification(response.data.message || 'Error al desvincular el dispositivo', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al desvincular el dispositivo', 'error');
    }
}

// Función para mostrar notificaciones
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ${
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

// Inicializar sistema de actualización automática
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de actualización automática
    window.autoRefreshSystem = new AutoRefreshSystem();
    updateAllLastSeen();
    setInterval(updateAllLastSeen, 1000);
    
    // Formateo automático del código de verificación
    const codeInput = document.getElementById('verification_code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, ''); // Solo números
            if (value.length > 3) {
                value = value.slice(0, 3) + '-' + value.slice(3, 6);
            }
            e.target.value = value;
        });
        
        // Prevenir caracteres no numéricos
        codeInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9]/.test(char) && e.which !== 8 && e.which !== 46) {
                e.preventDefault();
            }
        });
    }
});

function updateAllLastSeen() {
    document.querySelectorAll('.last-seen').forEach(function(el) {
        const ts = el.getAttribute('data-last-seen');
        el.textContent = ts ? `(${window.autoRefreshSystem.formatLastSeen(ts)})` : '(Nunca)';
    });
}

// Función para renderizar la tarjeta de dispositivo igual que en Blade
function deviceCardHTML(device) {
    const batteryLevel = device.batteryLevel ?? device.battery_level ?? 0;
    let batteryColor = 'text-green-500';
    if (batteryLevel <= 20) batteryColor = 'text-red-500';
    else if (batteryLevel <= 50) batteryColor = 'text-yellow-500';
    // SVG batería
    const color = batteryLevel <= 20 ? '#ef4444' : (batteryLevel <= 50 ? '#facc15' : '#22c55e');
    const width = Math.round(18 * Math.max(0, Math.min(100, batteryLevel)) / 100);
    const svgBateria = `<svg width="28" height="14" viewBox="0 0 28 14" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline align-middle"><rect x="1" y="3" width="22" height="8" rx="2" fill="#fff" stroke="#d1d5db" stroke-width="2"/><rect x="23" y="5" width="3" height="4" rx="1" fill="#d1d5db"/><rect x="3" y="5" width="${width}" height="4" rx="1" fill="${color}"/></svg>`;
    // Estado online/offline
    const online = device.status === 'online';
    // Usar la misma ruta que Blade para el enlace de administrar
    const adminUrl = `/devices/${device.id}`;
    const modelName = device.model ? escapeHtml(device.model) : 'Dispositivo';
    return `
    <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 flex flex-col justify-between">
        <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900">${modelName}</h3>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">ID: <span class="font-mono">${escapeHtml(device.deviceId)}</span></p>
            <div class="mt-3 flex items-center space-x-2">
                <span class="text-xs font-medium text-gray-600">Batería:</span>
                ${svgBateria}
                <span class="text-xs font-semibold ${batteryColor}">${batteryLevel}%</span>
            </div>
            <div class="mt-2 flex items-center space-x-2" id="device-status-${device.id}">
                <span class="text-xs font-medium text-gray-600">Estado:</span>
                ${online
                    ? `<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800\"><span class=\"w-2 h-2 bg-green-400 rounded-full mr-1\"></span>Online</span>`
                    : `<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800\"><span class=\"w-2 h-2 bg-gray-400 rounded-full mr-1\"></span>Offline</span>`}
                <span class="text-xs text-gray-500 last-seen" data-last-seen="${device.last_seen ? device.last_seen : ''}"></span>
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <a href="${adminUrl}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Ver detalles</a>
            <button onclick="deleteDevice(${device.id}, '${modelName.replace(/'/g, "\\'")}')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Desvincular">
                <x-icons.trash class="w-4 h-4" />
                Desvincular
            </button>
        </div>
    </div>
    `;
}

// Función para escapar HTML y evitar XSS
function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function (c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
</script>
@endsection
