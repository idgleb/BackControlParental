@extends('layouts.app')

@section('title', 'Horarios de ' . $device->model)

@section('content')
<div class="max-w-5xl mx-auto px-2 sm:px-4">
    <!-- Título y descripción -->
    <div class="mb-8">
         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="w-full">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Administrar Horarios
                </h1>
                <p class="mt-2 text-base sm:text-lg text-gray-600">
                    Gestionando horarios para <span class="font-semibold">{{ $device->model }}</span>
                </p>
            </div>
            <a href="{{ route('devices.show', $device) }}"
               class="w-full sm:w-auto rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 text-center flex items-center justify-center gap-2">
                <x-icons.arrow-left class="w-4 h-4" />
                Volver al Dispositivo
            </a>
        </div>
    </div>

    <!-- Mensaje de éxito -->
    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Lista de Horarios Existentes -->
    <div class="bg-white shadow-xl rounded-lg mb-12">
        <div class="px-4 sm:px-6 py-4 border-b">
            <h2 class="text-lg sm:text-xl font-semibold">Horarios Activos</h2>
        </div>
        <div class="divide-y divide-gray-200 horarios-list">
            @if($device->horarios->count() > 0)
                @foreach($device->horarios as $horario)
                    <div class="p-4 sm:p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-base sm:text-lg">{{ $horario->nombreDeHorario }}</h3>
                                @if($horario->isActive)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Inactivo
                                    </span>
                                @endif
                            </div>
                            <p class="text-gray-600 mt-1 text-sm">De {{ $horario->horaInicio }} a {{ $horario->horaFin }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Días: {{ implode(', ', array_map(function($dia) { 
                                $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                return $dias[$dia] ?? $dia;
                            }, $horario->diasDeSemana)) }}</p>
                        </div>
                        <div class="flex flex-row space-x-2 ml-0 md:ml-4">
                            <a href="{{ route('horarios.edit', ['device' => $device->id, 'idHorario' => $horario->idHorario]) }}"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium flex items-center gap-1">
                                <x-icons.pencil class="w-4 h-4" />
                                Editar
                            </a>
                            <form method="POST" action="{{ route('horarios.destroy', ['device' => $device->id, 'idHorario' => $horario->idHorario]) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este horario?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium flex items-center gap-1">
                                    <x-icons.trash class="w-4 h-4" />
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="p-6 text-center text-gray-500">
                    <p>No hay horarios definidos para este dispositivo.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Formulario para crear nuevo horario -->
    <div class="bg-white shadow-xl rounded-lg">
        <div class="px-4 sm:px-6 py-4 border-b">
            <h2 class="text-lg sm:text-xl font-semibold">Crear Nuevo Horario</h2>
        </div>
        <form method="POST" action="{{ route('horarios.store', $device) }}" class="p-4 sm:p-6 space-y-6" id="createHorarioForm">
            @csrf
            
            <!-- Nombre del horario -->
            <div>
                <label for="nombreDeHorario" class="block text-xs sm:text-sm font-medium text-gray-700">Nombre del Horario</label>
                <input type="text" name="nombreDeHorario" id="nombreDeHorario" required
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
                                <option value="{{ $tiempo }}">{{ $tiempo }}</option>
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
                                <option value="{{ $tiempo }}">{{ $tiempo }}</option>
                            @endfor
                        @endfor
                    </select>
                    @error('horaFin')
                        <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Días de la semana -->
            <div class="dias-container-create">
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
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-xs sm:text-sm text-gray-700">{{ $nombre }}</span>
                        </label>
                    @endforeach
                </div>
                @error('diasDeSemana')
                    <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('error')
                    <p class="mt-1 text-xs sm:text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estado activo -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="isActive" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-xs sm:text-sm text-gray-700">Horario activo</span>
                </label>
            </div>

            <!-- Botón de guardar -->
            <div class="flex justify-end">
                <button type="submit" id="createHorarioBtn"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 flex items-center justify-center">
                    <svg id="createIcon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span id="createText">Crear Horario</span>
                </button>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de actualización automática de horarios
    class HorariosAutoRefresh {
        constructor() {
            this.refreshInterval = 2000; // 2 segundos
            this.horarios = [];
            this.isLoading = false;
            this.errorCount = 0;
            this.handleDeleteClick = this.handleDeleteClick.bind(this); // Referencia estable
            this.init();
        }
        
        init() {
            this.loadHorarios();
            this.startAutoRefresh();
        }
        
        async loadHorarios() {
            try {
                // Evitar múltiples peticiones simultáneas
                if (this.isLoading) return;
                this.isLoading = true;
                
                const response = await axios.get(`/api/devices/{{ $device->id }}/horarios`, {
                    timeout: 2000
                });
                
                if (response.data.success) {
                    this.updateHorariosList(response.data.horarios);
                    this.errorCount = 0; // Resetear contador de errores en éxito
                }
            } catch (error) {
                console.error('Error loading horarios:', error);
                this.handleError();
            } finally {
                this.isLoading = false;
            }
        }
        
        updateHorariosList(newHorarios) {
            const horariosContainer = document.querySelector('.horarios-list');
            if (!horariosContainer) return;
            
            // Actualizar solo si hay cambios
            if (JSON.stringify(this.horarios) !== JSON.stringify(newHorarios)) {
                this.horarios = newHorarios;
                
                if (newHorarios.length === 0) {
                    horariosContainer.innerHTML = `
                        <div class="p-6 text-center text-gray-500">
                            <p>No hay horarios definidos para este dispositivo.</p>
                        </div>
                    `;
                    return;
                }
                
                const horariosHTML = newHorarios.map(horario => `
                    <div class="p-4 sm:p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-base sm:text-lg">${horario.nombreDeHorario}</h3>
                                ${horario.isActive ? 
                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>' :
                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>'
                                }
                            </div>
                            <p class="text-gray-600 mt-1 text-sm">De ${horario.horaInicio} a ${horario.horaFin}</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Días: ${this.formatDays(horario.diasDeSemana)}</p>
                        </div>
                        <div class="flex flex-row space-x-2 ml-0 md:ml-4">
                            <a href="/devices/{{ $device->id }}/horarios/by-id/${horario.idHorario}/edit"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Editar
                            </a>
                            <button type="button" 
                                    class="delete-horario-btn text-red-600 hover:text-red-900 text-sm font-medium flex items-center gap-1"
                                    data-horario-id="${horario.idHorario}"
                                    data-horario-name="${horario.nombreDeHorario}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Eliminar
                            </button>
                        </div>
                    </div>
                `).join('');
                
                horariosContainer.innerHTML = horariosHTML;
                
                // Reconfigurar botones de eliminar después de actualizar
                this.setupDeleteButtons();
                
                // Mostrar indicador de actualización
                this.showUpdateIndicator();
            }
        }
        
        formatDays(dias) {
            const diasNombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            return dias.map(dia => diasNombres[dia] || dia).join(', ');
        }
        
        showUpdateIndicator() {
            // Crear o actualizar indicador de actualización
            let indicator = document.getElementById('horariosUpdateIndicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'horariosUpdateIndicator';
                indicator.className = 'fixed bottom-4 left-4 bg-blue-500 text-white px-3 py-1 rounded-full text-xs shadow-lg transform transition-all duration-300';
                indicator.style.transform = 'translateY(100%)';
                document.body.appendChild(indicator);
            }
            
            indicator.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Horarios actualizados
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
                this.loadHorarios();
            }, this.refreshInterval);
        }
        
        setupDeleteButtons() {
            const horariosContainer = document.querySelector('.horarios-list');
            if (horariosContainer) {
                horariosContainer.removeEventListener('click', this.handleDeleteClick);
                horariosContainer.addEventListener('click', this.handleDeleteClick);
            }
        }
        
        handleDeleteClick(e) {
            console.log('🖱️ Clic detectado en:', e.target);
            // Verificar si el clic fue en un botón de eliminar
            if (e.target.closest('.delete-horario-btn')) {
                console.log('🎯 Clic en botón de eliminar detectado');
                e.preventDefault();
                
                const button = e.target.closest('.delete-horario-btn');
                const horarioId = button.getAttribute('data-horario-id');
                const horarioName = button.getAttribute('data-horario-name');
                
                console.log('📋 Datos del horario:', { id: horarioId, name: horarioName });
                
                // Confirmar eliminación
                if (!confirm(`¿Estás seguro de que quieres eliminar el horario "${horarioName}"?`)) {
                    console.log('❌ Eliminación cancelada por el usuario');
                    return;
                }
                
                console.log('✅ Confirmación aceptada, procediendo con eliminación');
                this.deleteHorario(button, horarioId, horarioName);
            } else {
                console.log('❌ Clic no fue en un botón de eliminar');
            }
        }
        
        async deleteHorario(button, horarioId, horarioName) {
            try {
                // Cambiar estado del botón
                const originalHTML = button.innerHTML;
                button.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
                button.disabled = true;
                
                // Enviar petición de eliminación
                const response = await axios.delete(`/devices/{{ $device->id }}/horarios/by-id/${horarioId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 10000
                });
                
                if (response.data.success) {
                    // Mostrar notificación de éxito
                    showNotification('Horario eliminado exitosamente', 'success');
                    
                    // La lista se actualizará automáticamente en la próxima actualización
                    // No necesitamos manipular el DOM manualmente
                } else {
                    showNotification(response.data.message || 'Error al eliminar el horario', 'error');
                    // Restaurar botón
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                
                let errorMessage = 'Error al eliminar el horario';
                
                if (error.response) {
                    if (error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.status === 404) {
                        errorMessage = 'Horario no encontrado';
                    } else if (error.response.status === 403) {
                        errorMessage = 'No tienes permisos para eliminar este horario';
                    }
                } else if (error.request) {
                    errorMessage = 'No se pudo conectar con el servidor';
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'La petición tardó demasiado';
                }
                
                showNotification(errorMessage, 'error');
                
                // Restaurar botón
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        }
    }
    
    // Configurar formulario de creación
    const createForm = document.getElementById('createHorarioForm');
    const createBtn = document.getElementById('createHorarioBtn');
    
    if (createForm && createBtn) {
        createForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar formulario antes de enviar
            if (!validateCreateForm()) {
                return;
            }
            
            try {
                // Cambiar estado del botón
                setCreateButtonLoading(true);
                
                // Recopilar datos del formulario
                const formData = new FormData(createForm);
                
                // Enviar petición con Axios
                const response = await axios.post(createForm.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 10000
                });
                
                if (response.data.success) {
                    showNotification('Horario creado exitosamente', 'success');
                    
                    // Limpiar formulario
                    createForm.reset();
                    
                    // La lista se actualizará automáticamente en la próxima actualización
                } else {
                    showNotification(response.data.message || 'Error al crear el horario', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                
                let errorMessage = 'Error al crear el horario';
                
                if (error.response) {
                    if (error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.status === 422) {
                        // Errores de validación
                        const errors = error.response.data.errors;
                        if (errors) {
                            showCreateValidationErrors(errors);
                            errorMessage = 'Por favor, corrige los errores en el formulario';
                        }
                    } else if (error.response.status === 403) {
                        errorMessage = 'No tienes permisos para crear horarios';
                    }
                } else if (error.request) {
                    errorMessage = 'No se pudo conectar con el servidor';
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'La petición tardó demasiado';
                }
                
                showNotification(errorMessage, 'error');
            } finally {
                setCreateButtonLoading(false);
            }
        });
    }
    
    function setCreateButtonLoading(loading) {
        const createIcon = document.getElementById('createIcon');
        const createText = document.getElementById('createText');
        
        if (loading) {
            createBtn.disabled = true;
            createIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            createIcon.classList.add('animate-spin');
            createText.textContent = 'Creando...';
        } else {
            createBtn.disabled = false;
            createIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>';
            createIcon.classList.remove('animate-spin');
            createText.textContent = 'Crear Horario';
        }
    }
    
    function validateCreateForm() {
        let isValid = true;
        
        // Limpiar errores previos
        clearCreateValidationErrors();
        
        // Validar nombre
        const nombre = document.getElementById('nombreDeHorario');
        if (!nombre.value.trim()) {
            showCreateFieldError(nombre, 'El nombre es obligatorio');
            isValid = false;
        }
        
        // Validar hora de inicio
        const horaInicio = document.getElementById('horaInicio');
        if (!horaInicio.value) {
            showCreateFieldError(horaInicio, 'La hora de inicio es obligatoria');
            isValid = false;
        }
        
        // Validar hora de fin
        const horaFin = document.getElementById('horaFin');
        if (!horaFin.value) {
            showCreateFieldError(horaFin, 'La hora de fin es obligatoria');
            isValid = false;
        }
        
        // Validar que hora fin sea después de hora inicio
        if (horaInicio.value && horaFin.value) {
            const inicio = new Date(`2000-01-01T${horaInicio.value}`);
            const fin = new Date(`2000-01-01T${horaFin.value}`);
            
            if (fin <= inicio) {
                showCreateFieldError(horaFin, 'La hora de fin debe ser posterior a la hora de inicio');
                isValid = false;
            }
        }
        
        // Validar días seleccionados
        const diasSeleccionados = document.querySelectorAll('input[name="diasDeSemana[]"]:checked');
        if (diasSeleccionados.length === 0) {
            const diasContainer = document.querySelector('.dias-container-create');
            showCreateFieldError(diasContainer, 'Debes seleccionar al menos un día');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showCreateFieldError(field, message) {
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
        if (field.classList.contains('dias-container-create')) {
            field.appendChild(errorDiv);
        } else {
            field.parentNode.appendChild(errorDiv);
        }
    }
    
    function clearCreateValidationErrors() {
        // Remover mensajes de error del formulario de creación
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
    
    function showCreateValidationErrors(errors) {
        clearCreateValidationErrors();
        
        Object.keys(errors).forEach(field => {
            const fieldElement = document.getElementById(field);
            if (fieldElement) {
                showCreateFieldError(fieldElement, errors[field][0]);
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
    
    // Inicializar sistema de actualización automática de horarios
    console.log('🚀 Inicializando sistema de actualización automática de horarios...');
    window.horariosAutoRefresh = new HorariosAutoRefresh();
    console.log('✅ Sistema de actualización automática inicializado');
});
</script>
@endsection 