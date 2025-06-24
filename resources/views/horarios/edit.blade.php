@extends('layouts.app')

@section('title', 'Editar Horario - ' . $device->model)

@section('content')
<div class="max-w-4xl mx-auto px-2 sm:px-4">
    <!-- Título y descripción -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="w-full">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Editar Horario
                </h1>
                <p class="mt-2 text-base sm:text-lg text-gray-600">
                    Editando horario para <span class="font-semibold">{{ $device->model }}</span>
                </p>
            </div>
            <a href="{{ route('horarios.index', $device) }}"
               class="w-full sm:w-auto rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 text-center">
                &larr; Volver a Horarios
            </a>
        </div>
    </div>
    <!-- Formulario de edición -->
    <div class="bg-white shadow-xl rounded-lg">
        <div class="px-4 sm:px-6 py-4 border-b">
            <h2 class="text-lg sm:text-xl font-semibold">Editar Horario: {{ $horario->nombreDeHorario }}</h2>
        </div>
        <form method="POST" action="{{ route('horarios.update', ['device' => $device->id, 'idHorario' => $horario->idHorario]) }}" class="p-4 sm:p-6 space-y-6" id="editHorarioForm">
            @csrf
            @method('PUT')
            <!-- Nombre del horario -->
            <div>
                <label for="nombreDeHorario" class="block text-xs sm:text-sm font-medium text-gray-700">Nombre del Horario</label>
                <input type="text" name="nombreDeHorario" id="nombreDeHorario" required
                       value="{{ old('nombreDeHorario', $horario->nombreDeHorario) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm"
                       placeholder="Ej: Horario de estudio">
                @error('nombreDeHorario')
                    <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <!-- Horas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="horaInicio" class="block text-xs sm:text-sm font-medium text-gray-700">Hora de Inicio</label>
                    <select name="horaInicio" id="horaInicio" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
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
                        <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="horaFin" class="block text-xs sm:text-sm font-medium text-gray-700">Hora de Fin</label>
                    <select name="horaFin" id="horaFin" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
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
                        <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <!-- Días de la semana -->
            <div class="dias-container">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Días de la Semana</label>
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
                            <span class="ml-2 text-xs sm:text-sm text-gray-700">{{ $nombre }}</span>
                        </label>
                    @endforeach
                </div>
                @error('diasDeSemana')
                    <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <!-- Estado activo -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="isActive" value="1"
                           {{ old('isActive', $horario->isActive) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-xs sm:text-sm text-gray-700">Horario activo</span>
                </label>
            </div>
            <!-- Botones -->
            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <a href="{{ route('horarios.index', $device) }}" id="cancelBtn"
                   class="w-full sm:w-auto rounded-md bg-white px-4 py-2 text-xs sm:text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 text-center">
                    Cancelar
                </a>
                <button type="submit" id="saveHorarioBtn"
                        class="w-full sm:w-auto rounded-md bg-indigo-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 flex items-center justify-center">
                    <svg id="saveIcon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span id="saveText">Actualizar Horario</span>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de actualización automática para edición de horarios
    class HorarioEditAutoRefresh {
        constructor() {
            this.refreshInterval = 2000; // Volver a 2 segundos después de un minuto
            this.horarioId = @json($horario->idHorario);
            this.deviceId = {{ $device->id }};
            this.originalUpdatedAt = new Date(@json($horario->updated_at->toISOString())).getTime();
            this.isLoading = false;
            this.errorCount = 0;
            this.init();
        }
        
        init() {
            this.startAutoRefresh();
        }
        
        async checkHorarioChanges() {
            try {
                if (this.isLoading) return;
                this.isLoading = true;
                const response = await axios.get(`/api/devices/${this.deviceId}/horarios/by-id/${this.horarioId}`, { timeout: 2000 });
                if (response.data.success) {
                    const currentData = response.data.horario;
                    if (!currentData) {
                        this.showHorarioDeletedWarning();
                        return;
                    }
                    // Comparar updated_at como timestamp numérico
                    if (currentData.updated_at) {
                        const currentUpdatedAt = new Date(currentData.updated_at).getTime();
                        if (currentUpdatedAt !== this.originalUpdatedAt) {
                            this.showDataChangedWarning(currentData);
                        }
                    }
                    this.errorCount = 0;
                }
            } catch (error) {
                console.error('Error checking horario changes:', error);
                this.handleError();
            } finally {
                this.isLoading = false;
            }
        }
        
        showHorarioDeletedWarning() {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 bg-red-500 text-white';
            notification.style.transform = 'translateX(100%)';
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">¡Atención! Este horario ha sido eliminado</p>
                        <p class="text-xs mt-1">Serás redirigido a la lista de horarios</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Mostrar notificación
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Redirigir después de 3 segundos
            setTimeout(() => {
                window.location.href = `/devices/${this.deviceId}/horarios`;
            }, 3000);
        }
        
        showDataChangedWarning(serverData) {
            // Solo mostrar si no se ha mostrado recientemente
            if (this.lastWarningTime && Date.now() - this.lastWarningTime < 10000) {
                return;
            }
            
            this.lastWarningTime = Date.now();
            
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 bg-yellow-500 text-white';
            notification.style.transform = 'translateX(100%)';
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Datos actualizados</p>
                        <p class="text-xs mt-1">Este horario ha sido modificado desde otra pestaña</p>
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
            
            // Mostrar notificación
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover automáticamente después de 8 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 8000);
        }
        
        handleError() {
            // Si hay errores consecutivos, aumentar el intervalo temporalmente
            this.errorCount = (this.errorCount || 0) + 1;
            if (this.errorCount > 3) {
                this.refreshInterval = 10000; // 10 segundos si hay muchos errores
                setTimeout(() => {
                    this.refreshInterval = 3000; // Volver a 3 segundos después de un minuto
                    this.errorCount = 0;
                }, 60000);
            }
        }
        
        startAutoRefresh() {
            setInterval(() => {
                this.checkHorarioChanges();
            }, this.refreshInterval);
        }
    }
    
    const form = document.getElementById('editHorarioForm');
    const saveBtn = document.getElementById('saveHorarioBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Inicializar sistema de actualización automática
    window.horarioEditAutoRefresh = new HorarioEditAutoRefresh();
    
    if (form && saveBtn) {
        saveBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Validar formulario antes de enviar
            if (!validateForm()) {
                return;
            }
            
            try {
                // Cambiar estado del botón
                setButtonLoading(true);
                
                // Recopilar datos del formulario
                const formData = new FormData(form);
                
                // Enviar petición con Axios
                const response = await axios.post(form.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 10000
                });
                
                if (response.data.success) {
                    showNotification('Horario actualizado exitosamente', 'success');
                    
                    // Redirigir después de un breve delay
                    setTimeout(() => {
                        window.location.href = '/devices/{{ $device->id }}/horarios';
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Error al actualizar el horario', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                
                let errorMessage = 'Error al actualizar el horario';
                
                if (error.response) {
                    if (error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.status === 422) {
                        // Errores de validación
                        const errors = error.response.data.errors;
                        if (errors) {
                            showValidationErrors(errors);
                            errorMessage = 'Por favor, corrige los errores en el formulario';
                        }
                    } else if (error.response.status === 404) {
                        errorMessage = 'Horario no encontrado';
                    } else if (error.response.status === 403) {
                        errorMessage = 'No tienes permisos para editar este horario';
                    }
                } else if (error.request) {
                    errorMessage = 'No se pudo conectar con el servidor';
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'La petición tardó demasiado';
                }
                
                showNotification(errorMessage, 'error');
            } finally {
                setButtonLoading(false);
            }
        });
    }
    
    function validateForm() {
        let isValid = true;
        
        // Limpiar errores previos
        clearValidationErrors();
        
        // Validar nombre
        const nombre = document.getElementById('nombreDeHorario');
        if (!nombre.value.trim()) {
            showFieldError(nombre, 'El nombre es obligatorio');
            isValid = false;
        }
        
        // Validar hora de inicio
        const horaInicio = document.getElementById('horaInicio');
        if (!horaInicio.value) {
            showFieldError(horaInicio, 'La hora de inicio es obligatoria');
            isValid = false;
        }
        
        // Validar hora de fin
        const horaFin = document.getElementById('horaFin');
        if (!horaFin.value) {
            showFieldError(horaFin, 'La hora de fin es obligatoria');
            isValid = false;
        }
        
        // Validar que hora fin sea después de hora inicio
        if (horaInicio.value && horaFin.value) {
            const inicio = new Date(`2000-01-01T${horaInicio.value}`);
            const fin = new Date(`2000-01-01T${horaFin.value}`);
            
            if (fin <= inicio) {
                showFieldError(horaFin, 'La hora de fin debe ser posterior a la hora de inicio');
                isValid = false;
            }
        }
        
        // Validar días seleccionados
        const diasSeleccionados = document.querySelectorAll('input[name="diasDeSemana[]"]:checked');
        if (diasSeleccionados.length === 0) {
            const diasContainer = document.querySelector('.dias-container');
            showFieldError(diasContainer, 'Debes seleccionar al menos un día');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        // Crear elemento de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-sm mt-1 flex items-center';
        errorDiv.innerHTML = `
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            ${message}
        `;
        
        // Agregar clase de error al campo
        field.classList.add('border-red-500');
        
        // Insertar mensaje de error
        if (field.classList.contains('dias-container')) {
            field.appendChild(errorDiv);
        } else {
            field.parentNode.appendChild(errorDiv);
        }
    }
    
    function clearValidationErrors() {
        // Remover mensajes de error
        document.querySelectorAll('.text-red-500.text-sm').forEach(error => {
            if (error.textContent.includes('El nombre es obligatorio') ||
                error.textContent.includes('La hora de inicio es obligatoria') ||
                error.textContent.includes('La hora de fin es obligatoria') ||
                error.textContent.includes('La hora de fin debe ser posterior') ||
                error.textContent.includes('Debes seleccionar al menos un día')) {
                error.remove();
            }
        });
        
        // Remover clases de error
        document.querySelectorAll('.border-red-500').forEach(field => {
            field.classList.remove('border-red-500');
        });
    }
    
    function showValidationErrors(errors) {
        clearValidationErrors();
        
        Object.keys(errors).forEach(field => {
            const fieldElement = document.getElementById(field);
            if (fieldElement) {
                showFieldError(fieldElement, errors[field][0]);
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
            saveText.textContent = 'Actualizando...';
        } else {
            saveBtn.disabled = false;
            saveIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
            saveIcon.classList.remove('animate-spin');
            saveText.textContent = 'Actualizar Horario';
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
});
</script>
@endsection