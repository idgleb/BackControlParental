@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-3">
            <a href="{{ route('devices.show', $device) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Ubicación en Tiempo Real</h1>
        </div>
        <p class="mt-2 text-gray-600">{{ $device->model }} - {{ $device->deviceId }}</p>
    </div>

    <!-- Estado del dispositivo -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-sm font-medium text-gray-700">Estado:</span>
                <span id="device-status" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $device->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($device->status) }}
                </span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-700">Batería:</span>
                <span id="battery-level" class="ml-2 text-sm text-gray-900">{{ $device->batteryLevel }}%</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-700">Última actualización:</span>
                <span id="last-update" class="ml-2 text-sm text-gray-900">
                    @if($device->location_updated_at)
                        {{ $device->location_updated_at->diffForHumans() }}
                    @else
                        Sin datos
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Mapa -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div id="map" style="height: 500px;"></div>
    </div>

    <!-- Información de ubicación -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Detalles de Ubicación</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-700">Latitud:</span>
                <span id="latitude" class="ml-2 text-sm text-gray-900">
                    {{ $device->latitude ?? 'Sin datos' }}
                </span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-700">Longitud:</span>
                <span id="longitude" class="ml-2 text-sm text-gray-900">
                    {{ $device->longitude ?? 'Sin datos' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Inicializar el mapa
    let map;
    let marker;
    
    // Coordenadas iniciales
    const initialLat = {{ $device->latitude ?? 0 }};
    const initialLng = {{ $device->longitude ?? 0 }};
    const hasInitialLocation = {{ ($device->latitude && $device->longitude) ? 'true' : 'false' }};
    
    // Inicializar mapa
    function initMap() {
        // Centro del mapa (Colombia si no hay ubicación)
        const centerLat = hasInitialLocation ? initialLat : 4.5709;
        const centerLng = hasInitialLocation ? initialLng : -74.2973;
        const zoomLevel = hasInitialLocation ? 16 : 6;
        
        map = L.map('map').setView([centerLat, centerLng], zoomLevel);
        
        // Agregar capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Agregar marcador si hay ubicación inicial
        if (hasInitialLocation) {
            marker = L.marker([initialLat, initialLng]).addTo(map);
            marker.bindPopup('<b>{{ $device->model }}</b><br>Ubicación actual').openPopup();
        }
    }
    
    // Actualizar ubicación en el mapa
    function updateMapLocation(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup('<b>{{ $device->model }}</b><br>Ubicación actual').openPopup();
        }
        
        // Centrar mapa en la nueva ubicación
        map.setView([lat, lng], 16);
    }
    
    // Función para actualizar los datos
    function updateDeviceData() {
        axios.get(`{{ route('devices.show', $device) }}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.data.success) {
                const device = response.data.device;
                
                // Actualizar estado
                const statusElement = document.getElementById('device-status');
                const isOnline = device.status === 'online';
                statusElement.textContent = device.status.charAt(0).toUpperCase() + device.status.slice(1);
                statusElement.className = `ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                }`;
                
                // Actualizar batería
                document.getElementById('battery-level').textContent = device.batteryLevel + '%';
                
                // Actualizar ubicación
                if (device.latitude && device.longitude) {
                    document.getElementById('latitude').textContent = parseFloat(device.latitude).toFixed(6);
                    document.getElementById('longitude').textContent = parseFloat(device.longitude).toFixed(6);
                    
                    // Actualizar mapa
                    updateMapLocation(device.latitude, device.longitude);
                    
                    // Actualizar tiempo de última actualización
                    if (device.location_updated_at) {
                        const locationDate = new Date(device.location_updated_at);
                        const now = new Date();
                        const diffMs = now - locationDate;
                        const diffMins = Math.floor(diffMs / 60000);
                        
                        let timeText;
                        if (diffMins < 1) {
                            timeText = 'Hace menos de un minuto';
                        } else if (diffMins < 60) {
                            timeText = `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
                        } else {
                            const diffHours = Math.floor(diffMins / 60);
                            timeText = `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
                        }
                        
                        document.getElementById('last-update').textContent = timeText;
                    }
                } else {
                    document.getElementById('latitude').textContent = 'Sin datos';
                    document.getElementById('longitude').textContent = 'Sin datos';
                    document.getElementById('last-update').textContent = 'Sin datos';
                }
            }
        })
        .catch(error => {
            console.error('Error al actualizar datos:', error);
        });
    }
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        
        // Actualizar datos inmediatamente
        updateDeviceData();
        
        // Actualizar cada 10 segundos
        setInterval(updateDeviceData, 10000);
    });
</script>
@endsection 