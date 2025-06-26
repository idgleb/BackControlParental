<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - Control Parental V2</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
</head>
<body class="h-full">
<div class="flex min-h-full flex-col justify-center px-2 sm:px-6 py-8 sm:py-12 lg:px-8 bg-gradient-to-br from-blue-100 to-purple-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-8 sm:mt-10 text-center text-2xl sm:text-3xl font-bold leading-8 sm:leading-9 tracking-tight text-gray-900">
            Crea una nueva cuenta
        </h2>
    </div>
    <div class="mt-8 sm:mt-10 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white px-4 sm:px-6 py-8 sm:py-12 shadow-xl rounded-lg">
            <form method="POST" action="{{ route('register.post') }}" class="space-y-6" id="registerForm">
                @csrf
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-medium leading-6 text-gray-900">Nombre</label>
                    <div class="mt-2">
                        <input id="name" name="name" type="text" autocomplete="name" required
                               class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-xs sm:text-sm sm:leading-6"
                               value="{{ old('name') }}">
                    </div>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-medium leading-6 text-gray-900">Correo electrónico</label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-xs sm:text-sm sm:leading-6"
                               value="{{ old('email') }}">
                    </div>
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-xs sm:text-sm font-medium leading-6 text-gray-900">Contraseña</label>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                               class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-xs sm:text-sm sm:leading-6">
                    </div>
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs sm:text-sm font-medium leading-6 text-gray-900">Confirmar contraseña</label>
                    <div class="mt-2">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-xs sm:text-sm sm:leading-6">
                    </div>
                </div>
                <div>
                    <button type="submit" id="registerBtn"
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs sm:text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 items-center">
                        <svg id="registerIcon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span id="registerText">Registrarse</span>
                    </button>
                </div>
            </form>
            <p class="mt-8 sm:mt-10 text-center text-xs sm:text-sm text-gray-500">
                ¿Ya tienes una cuenta?
                <a href="{{ route('login') }}" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">
                    Inicia sesión
                </a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de actualización automática para la página de registro
    class RegisterAutoRefresh {
        constructor() {
            this.refreshInterval = 5000; // 5 segundos (menos frecuente para registro)
            this.isLoading = false;
            this.errorCount = 0;
            this.serverStatus = 'unknown';
            this.init();
        }

        init() {
            this.checkServerStatus();
            this.startAutoRefresh();
        }

        async checkServerStatus() {
            try {
                // Evitar múltiples peticiones simultáneas
                if (this.isLoading) return;
                this.isLoading = true;

                const response = await axios.get('/api/health', {
                    timeout: 3000
                });

                if (response.data.success) {
                    this.updateServerStatus('online');
                    this.errorCount = 0;
                }
            } catch (error) {
                console.error('Error checking server status:', error);
                this.updateServerStatus('offline');
                this.handleError();
            } finally {
                this.isLoading = false;
            }
        }

        updateServerStatus(status) {
            if (this.serverStatus === status) return;

            this.serverStatus = status;

            // Crear o actualizar indicador de estado
            let statusIndicator = document.getElementById('server-status-indicator');
            if (!statusIndicator) {
                statusIndicator = document.createElement('div');
                statusIndicator.id = 'server-status-indicator';
                statusIndicator.className = 'fixed bottom-4 right-4 z-50 p-2 rounded-full text-xs shadow-lg transform transition-all duration-300';
                document.body.appendChild(statusIndicator);
            }

            if (status === 'online') {
                statusIndicator.className = 'fixed bottom-4 right-4 z-50 p-2 rounded-full text-xs shadow-lg transform transition-all duration-300 bg-green-500 text-white';
                statusIndicator.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Servidor Online
                    </div>
                `;
            } else {
                statusIndicator.className = 'fixed bottom-4 right-4 z-50 p-2 rounded-full text-xs shadow-lg transform transition-all duration-300 bg-red-500 text-white';
                statusIndicator.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        Servidor Offline
                    </div>
                `;
            }

            // Mostrar indicador
            setTimeout(() => {
                statusIndicator.style.transform = 'translateY(0)';
            }, 100);
        }

        handleError() {
            // Si hay errores consecutivos, aumentar el intervalo temporalmente
            this.errorCount = (this.errorCount || 0) + 1;
            if (this.errorCount > 3) {
                this.refreshInterval = 15000; // 15 segundos si hay muchos errores
                setTimeout(() => {
                    this.refreshInterval = 5000; // Volver a 5 segundos después de un minuto
                    this.errorCount = 0;
                }, 60000);
            }
        }

        startAutoRefresh() {
            setInterval(() => {
                this.checkServerStatus();
            }, this.refreshInterval);
        }
    }

    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');

    // Inicializar sistema de actualización automática
    window.registerAutoRefresh = new RegisterAutoRefresh();

    if (registerForm && registerBtn) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validar formulario antes de enviar
            if (!validateRegisterForm()) {
                return;
            }

            try {
                // Cambiar estado del botón
                setRegisterButtonLoading(true);

                // Recopilar datos del formulario
                const formData = new FormData(registerForm);

                // Enviar petición con Axios
                const response = await axios.post(registerForm.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 10000
                });

                if (response.data.success) {
                    showNotification('Registro exitoso', 'success');

                    // Redirigir después de un breve delay
                    setTimeout(() => {
                        window.location.href = response.data.redirect || '/devices';
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Error en el registro', 'error');
                }
            } catch (error) {
                console.error('Error:', error);

                let errorMessage = 'Error en el registro';

                if (error.response) {
                    if (error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.status === 422) {
                        // Errores de validación
                        const errors = error.response.data.errors;
                        if (errors) {
                            showRegisterValidationErrors(errors);
                            errorMessage = 'Por favor, corrige los errores en el formulario';
                        }
                    } else if (error.response.status === 409) {
                        errorMessage = 'El correo electrónico ya está registrado';
                    }
                } else if (error.request) {
                    errorMessage = 'No se pudo conectar con el servidor';
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'La petición tardó demasiado';
                }

                showNotification(errorMessage, 'error');
            } finally {
                setRegisterButtonLoading(false);
            }
        });
    }

    function setRegisterButtonLoading(loading) {
        const registerIcon = document.getElementById('registerIcon');
        const registerText = document.getElementById('registerText');

        if (loading) {
            registerBtn.disabled = true;
            registerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            registerIcon.classList.add('animate-spin');
            registerText.textContent = 'Registrando...';
        } else {
            registerBtn.disabled = false;
            registerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>';
            registerIcon.classList.remove('animate-spin');
            registerText.textContent = 'Registrarse';
        }
    }

    function validateRegisterForm() {
        let isValid = true;

        // Limpiar errores previos
        clearRegisterValidationErrors();

        // Validar nombre
        const name = document.getElementById('name');
        if (!name.value.trim()) {
            showRegisterFieldError(name, 'El nombre es obligatorio');
            isValid = false;
        }

        // Validar email
        const email = document.getElementById('email');
        if (!email.value.trim()) {
            showRegisterFieldError(email, 'El correo electrónico es obligatorio');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showRegisterFieldError(email, 'Ingresa un correo electrónico válido');
            isValid = false;
        }

        // Validar contraseña
        const password = document.getElementById('password');
        if (!password.value) {
            showRegisterFieldError(password, 'La contraseña es obligatoria');
            isValid = false;
        } else if (password.value.length < 8) {
            showRegisterFieldError(password, 'La contraseña debe tener al menos 8 caracteres');
            isValid = false;
        }

        // Validar confirmación de contraseña
        const passwordConfirmation = document.getElementById('password_confirmation');
        if (!passwordConfirmation.value) {
            showRegisterFieldError(passwordConfirmation, 'Debes confirmar la contraseña');
            isValid = false;
        } else if (password.value !== passwordConfirmation.value) {
            showRegisterFieldError(passwordConfirmation, 'Las contraseñas no coinciden');
            isValid = false;
        }

        return isValid;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showRegisterFieldError(field, message) {
        // Crear elemento de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-xs mt-1 flex items-center';
        errorDiv.innerHTML = `
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            ${message}
        `;

        // Agregar clase de error al campo
        field.classList.add('ring-red-500');

        // Insertar mensaje de error
        field.parentNode.appendChild(errorDiv);
    }

    function clearRegisterValidationErrors() {
        // Remover mensajes de error
        document.querySelectorAll('.text-red-500.text-xs').forEach(error => {
            if (error.textContent.includes('El nombre es obligatorio') ||
                error.textContent.includes('El correo electrónico es obligatorio') ||
                error.textContent.includes('Ingresa un correo electrónico válido') ||
                error.textContent.includes('La contraseña es obligatoria') ||
                error.textContent.includes('La contraseña debe tener al menos 8 caracteres') ||
                error.textContent.includes('Debes confirmar la contraseña') ||
                error.textContent.includes('Las contraseñas no coinciden')) {
                error.remove();
            }
        });

        // Remover clases de error
        document.querySelectorAll('.ring-red-500').forEach(field => {
            field.classList.remove('ring-red-500');
        });
    }

    function showRegisterValidationErrors(errors) {
        clearRegisterValidationErrors();

        Object.keys(errors).forEach(field => {
            const fieldElement = document.getElementById(field);
            if (fieldElement) {
                showRegisterFieldError(fieldElement, errors[field][0]);
            }
        });
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
});
</script>
</body>
</html>
