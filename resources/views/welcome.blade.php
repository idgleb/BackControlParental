@extends('layouts.app')

@section('title', '! Control Parental v5.0')

@section('content')
<div class="max-w-4xl mx-auto px-2 sm:px-4 text-center">
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
        Control Parental v2.0
    </h1>
    <p class="mt-4 sm:mt-6 text-base sm:text-lg leading-7 sm:leading-8 text-gray-600">
        Hola, Gestiona el uso de dispositivos y aplicaciones de tus hijos de manera inteligente y segura.
    </p>

    @auth
        <!-- Estadísticas en tiempo real -->
        <div class="mt-8 sm:mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
            <!-- Dispositivos -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg border border-gray-200">
                <div class="flex items-center justify-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Dispositivos</p>
                        <p class="text-2xl font-bold text-gray-900" id="devices-count">-</p>
                    </div>
                </div>
            </div>

            <!-- Dispositivos Online -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg border border-gray-200">
                <div class="flex items-center justify-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Online</p>
                        <p class="text-2xl font-bold text-gray-900" id="devices-online">-</p>
                    </div>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg border border-gray-200">
                <div class="flex items-center justify-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.19 4.19A2 2 0 004 6v10a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-1.81 1.19z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Notificaciones</p>
                        <p class="text-2xl font-bold text-gray-900" id="notifications-count">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicador de actualización -->
        <div class="mt-4 text-xs text-gray-500" id="last-update">
            Última actualización: <span id="update-time">-</span>
        </div>
    @endauth

    <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-x-6">
        @auth
            <a href="{{ route('devices.index') }}"
               class="w-full sm:w-auto rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 text-center">
                Ir a Mis Dispositivos
            </a>
        @else
            <a href="{{ route('login') }}"
               class="w-full sm:w-auto rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 text-center">
                Iniciar Sesión
            </a>
            <a href="{{ route('register') }}"
               class="w-full sm:w-auto text-sm font-semibold leading-6 text-gray-900 text-center">
                Registrarse <span aria-hidden="true">→</span>
            </a>
        @endauth
    </div>
</div>

@auth
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de actualización automática para la página de inicio
    class WelcomeAutoRefresh {
        constructor() {
            this.refreshInterval = 2000; // 2 segundos
            this.isLoading = false;
            this.errorCount = 0;
            this.init();
        }

        init() {
            this.loadStats();
            this.startAutoRefresh();
        }

        async loadStats() {
            try {
                // Evitar múltiples peticiones simultáneas
                if (this.isLoading) return;
                this.isLoading = true;

                const [devicesResponse, notificationsResponse] = await Promise.all([
                    axios.get('/api/devices', { timeout: 2000 }),
                    axios.get('/api/notifications/count', { timeout: 2000 })
                ]);

                if (devicesResponse.data.success) {
                    this.updateDevicesStats(devicesResponse.data.devices);
                }

                if (notificationsResponse.data.success) {
                    this.updateNotificationsStats(notificationsResponse.data.count);
                }

                this.updateLastUpdateTime();
                this.errorCount = 0; // Resetear contador de errores en éxito
            } catch (error) {
                console.error('Error loading stats:', error);
                this.handleError();
            } finally {
                this.isLoading = false;
            }
        }

        updateDevicesStats(devices) {
            const devicesCount = document.getElementById('devices-count');
            const devicesOnline = document.getElementById('devices-online');

            if (devicesCount && devicesOnline) {
                const totalDevices = devices.length;
                const onlineDevices = devices.filter(device => device.status === 'online').length;

                devicesCount.textContent = totalDevices;
                devicesOnline.textContent = onlineDevices;

                // Animación de actualización
                this.animateUpdate(devicesCount);
                this.animateUpdate(devicesOnline);
            }
        }

        updateNotificationsStats(count) {
            const notificationsCount = document.getElementById('notifications-count');

            if (notificationsCount) {
                notificationsCount.textContent = count;

                // Animación de actualización
                this.animateUpdate(notificationsCount);
            }
        }

        updateLastUpdateTime() {
            const updateTime = document.getElementById('update-time');

            if (updateTime) {
                const now = new Date();
                updateTime.textContent = now.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }
        }

        animateUpdate(element) {
            // Agregar clase de animación
            element.classList.add('text-indigo-600');

            // Remover clase después de 500ms
            setTimeout(() => {
                element.classList.remove('text-indigo-600');
            }, 500);
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

        startAutoRefresh() {
            setInterval(() => {
                this.loadStats();
            }, this.refreshInterval);
        }
    }

    // Inicializar sistema de actualización automática
    window.welcomeAutoRefresh = new WelcomeAutoRefresh();
});
</script>
@endauth
@endsection
