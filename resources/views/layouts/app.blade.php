<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Control Parental')</title>
    @if (app()->environment('local'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $cssFile = $manifest['resources/css/app.css']['file'] ?? 'assets/app.css';
            $jsFile = $manifest['resources/js/app.js']['file'] ?? 'assets/app.js';
        @endphp
        <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
        <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
    @endif
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full">

<div class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-600">ControlParental</a>
            <!-- Menú Desktop -->
            <div class="hidden sm:flex items-center space-x-4">
                @auth
                    <span class="font-semibold text-gray-800">{{ Auth::user()->name }}</span>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('devices.index') }}" class="text-gray-600 hover:text-indigo-600">Mis Dispositivos</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-indigo-600">Cerrar sesión</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-500">Registrarse</a>
                @endauth
            </div>
            <!-- Botón hamburguesa (solo móvil) -->
            <div class="sm:hidden" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" :aria-expanded="open">
                    <svg x-show="!open" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <!-- Menú móvil -->
                <div x-show="open" x-transition class="absolute top-16 left-0 w-full bg-white shadow-lg z-50 border-t border-gray-100">
                    <div class="flex flex-col px-4 py-4 space-y-2">
                        @auth
                            <span class="font-semibold text-gray-800">{{ Auth::user()->name }}</span>
                            <a href="{{ route('devices.index') }}" class="text-gray-600 hover:text-indigo-600">Mis Dispositivos</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-indigo-600 w-full text-left">Cerrar sesión</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600">Iniciar sesión</a>
                            <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-500 text-center">Registrarse</a>
                        @endauth
                    </div>
                </div>
            </div>
            <!-- Notificaciones -->
            <div class="relative" x-data="{ notificationsOpen: false }">
                <button @click="notificationsOpen = !notificationsOpen" 
                        class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <span class="sr-only">Ver notificaciones</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 00-6 6v3.75a6 6 0 006 6h3.75a6 6 0 006-6V9.75a6 6 0 00-6-6H10.5z" />
                    </svg>
                    <!-- Contador de notificaciones -->
                    <span id="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">
                        <span id="notificationCountText">0</span>
                    </span>
                </button>
                
                <!-- Panel de notificaciones -->
                <div x-show="notificationsOpen" 
                     @click.away="notificationsOpen = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                    <div class="py-2">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                        </div>
                        <div id="notificationsList" class="max-h-64 overflow-y-auto">
                            <!-- Las notificaciones se cargarán aquí dinámicamente -->
                            <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                No hay notificaciones nuevas
                            </div>
                        </div>
                        <div class="px-4 py-2 border-t border-gray-200">
                            <button id="markAllRead" 
                                    class="text-xs text-indigo-600 hover:text-indigo-500">
                                Marcar todas como leídas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 py-8 sm:py-12">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-6">
        <div class="container mx-auto px-4 sm:px-6 text-center text-gray-500">
            &copy; {{ date('Y') }} Control Parental. Todos los derechos reservados.
        </div>
    </footer>

</div>

@stack('scripts')

<script>
// Sistema de notificaciones en tiempo real
class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.lastCheck = new Date();
        this.checkInterval = 3000; // 3 segundos
        this.isLoading = false;
        this.errorCount = 0;
        this.init();
    }
    
    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.startAutoRefresh();
    }
    
    setupEventListeners() {
        // Marcar todas como leídas
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
    }
    
    async loadNotifications() {
        try {
            // Evitar múltiples peticiones simultáneas
            if (this.isLoading) return;
            this.isLoading = true;
            
            const response = await axios.get('{{ route('ajax.notifications.recent') }}', {
                params: {
                    last_check: this.lastCheck.toISOString()
                },
                timeout: 2000 // Reducir timeout para actualizaciones más rápidas
            });
            
            if (response.data.success) {
                this.updateNotifications(response.data.notifications);
                this.lastCheck = new Date();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            // En caso de error, aumentar el intervalo temporalmente
            this.handleError();
        } finally {
            this.isLoading = false;
        }
    }
    
    handleError() {
        // Si hay errores consecutivos, aumentar el intervalo temporalmente
        this.errorCount = (this.errorCount || 0) + 1;
        if (this.errorCount > 3) {
            this.checkInterval = 10000; // 10 segundos si hay muchos errores
            setTimeout(() => {
                this.checkInterval = 3000; // Volver a 3 segundos después de un minuto
                this.errorCount = 0;
            }, 60000);
        }
    }
    
    updateNotifications(newNotifications) {
        // Agregar nuevas notificaciones
        newNotifications.forEach(notification => {
            if (!this.notifications.find(n => n.id === notification.id)) {
                this.notifications.unshift(notification);
                this.unreadCount++;
                
                // Mostrar notificación toast si no está en el panel
                if (!document.querySelector('.notifications-open')) {
                    this.showToastNotification(notification);
                }
            }
        });
        
        this.updateUI();
    }
    
    updateUI() {
        this.updateNotificationCount();
        this.updateNotificationsList();
    }
    
    updateNotificationCount() {
        const countElement = document.getElementById('notificationCount');
        const countTextElement = document.getElementById('notificationCountText');
        
        if (this.unreadCount > 0) {
            countElement.classList.remove('hidden');
            countTextElement.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
        } else {
            countElement.classList.add('hidden');
        }
    }
    
    updateNotificationsList() {
        const listElement = document.getElementById('notificationsList');
        if (!listElement) return;
        
        if (this.notifications.length === 0) {
            listElement.innerHTML = `
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    No hay notificaciones nuevas
                </div>
            `;
            return;
        }
        
        const notificationsHTML = this.notifications.map(notification => `
            <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 ${!notification.read ? 'bg-blue-50' : ''}" 
                 data-notification-id="${notification.id}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center">
                            ${this.getNotificationIcon(notification.type)}
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                <p class="text-sm text-gray-500">${notification.message}</p>
                                <p class="text-xs text-gray-400 mt-1">${this.formatTime(notification.created_at)}</p>
                            </div>
                        </div>
                    </div>
                    ${!notification.read ? `
                        <button onclick="notificationSystem.markAsRead(${notification.id})" 
                                class="ml-2 text-xs text-indigo-600 hover:text-indigo-500">
                            Marcar como leída
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        listElement.innerHTML = notificationsHTML;
    }
    
    getNotificationIcon(type) {
        const icons = {
            'device_online': '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            'device_offline': '<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            'app_usage': '<svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'schedule_alert': '<svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            'default': '<svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        
        return icons[type] || icons.default;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Ahora mismo';
        if (minutes < 60) return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
        if (hours < 24) return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
        if (days < 7) return `Hace ${days} día${days > 1 ? 's' : ''}`;
        
        return date.toLocaleDateString('es-ES');
    }
    
    showToastNotification(notification) {
        const toast = document.createElement('div');
        toast.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 bg-white border-l-4 border-blue-500`;
        toast.style.transform = 'translateX(100%)';
        
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${this.getNotificationIcon(notification.type)}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                    <p class="text-sm text-gray-500">${notification.message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.closest('.notification-toast').remove()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animación de entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover automáticamente después de 5 segundos
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await axios.post(`/ajax/notifications/${notificationId}/mark-read`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.data.success) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateUI();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await axios.post('{{ route('ajax.notifications.markAllRead') }}', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.data.success) {
                this.notifications.forEach(notification => notification.read = true);
                this.unreadCount = 0;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.loadNotifications();
        }, this.checkInterval);
    }
}

// Inicializar sistema de notificaciones
let notificationSystem;
document.addEventListener('DOMContentLoaded', function() {
    notificationSystem = new NotificationSystem();
});
</script>

</body>
</html> 