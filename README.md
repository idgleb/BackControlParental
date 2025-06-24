# 🛡️ Control Parental - Sistema de Monitoreo Inteligente

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC.svg)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-77C1D5.svg)](https://alpinejs.dev)
[![Axios](https://img.shields.io/badge/Axios-1.x-5A29E4.svg)](https://axios-http.com)

Un sistema completo de control parental desarrollado con Laravel que permite a los padres monitorear y gestionar el uso de dispositivos móviles de sus hijos en **tiempo real** con actualización automática en todas las vistas.

## ✨ Características Principales

### 🔄 **Sistema de Actualización Automática Completo**
- **Actualización en tiempo real** cada 3 segundos en todas las vistas principales
- **Detección inteligente de cambios** - Solo actualiza cuando hay modificaciones reales
- **Optimización de rendimiento** - Manejo inteligente de errores y timeouts
- **Indicadores visuales** de actualización sutiles y no intrusivos
- **Adaptación automática** a problemas de conectividad

#### **Vistas con Actualización Automática:**
| **Vista** | **Intervalo** | **Funcionalidad** |
|-----------|---------------|-------------------|
| **Lista de Dispositivos** | 3 segundos | Estado online/offline, contador |
| **Vista de Dispositivo** | 3 segundos | Estado del dispositivo, apps |
| **Lista de Horarios** | 3 segundos | Lista completa, crear/eliminar |
| **Editar Horario** | 3 segundos | Detectar cambios/eliminación |
| **Página de Inicio** | 3 segundos | Estadísticas en tiempo real |
| **Login/Registro** | 5 segundos | Estado del servidor |

### 📱 **Gestión de Dispositivos**
- **Vinculación de dispositivos** Android/iOS
- **Monitoreo de estado** online/offline en tiempo real
- **Información de batería** actualizada automáticamente
- **Historial de actividad** detallado
- **Sincronización automática** de datos

### 📱 **Control de Aplicaciones**
- **Lista de aplicaciones** instaladas con actualización automática
- **Iconos de aplicaciones** desde la base de datos (formato base64)
- **Configuración de permisos** por aplicación
- **Estadísticas de uso** detalladas
- **Guardado asíncrono** con AJAX

### ⏰ **Sistema de Horarios Inteligente**
- **Horarios personalizables** por dispositivo
- **Días de la semana** configurables
- **Horas de inicio y fin** flexibles
- **Activación/desactivación** automática
- **Detección de cambios** desde otras pestañas
- **Eliminación automática** cuando se borra desde la BD

### 🔔 **Sistema de Notificaciones Avanzado**
- **Notificaciones push** en tiempo real
- **Diferentes tipos**: dispositivo online/offline, uso de apps, alertas de horarios
- **Marcado como leído** individual y masivo
- **Historial de notificaciones** completo
- **Contador en tiempo real** en el header
- **Panel desplegable** con notificaciones

### 🎯 **Experiencia de Usuario Mejorada**
- **Interfaz responsive** adaptada a móviles, tablets y desktop
- **Menú hamburguesa** para dispositivos móviles
- **Validación en tiempo real** en formularios
- **Notificaciones toast** para feedback inmediato
- **Animaciones suaves** y transiciones
- **Indicadores de carga** en botones

## 🛠️ Tecnologías Utilizadas

### **Backend**
- **Laravel 10.x** - Framework PHP robusto y elegante
- **PHP 8.1+** - Lenguaje de programación moderno
- **MySQL/PostgreSQL** - Base de datos relacional
- **Eloquent ORM** - Mapeo objeto-relacional
- **Laravel Sanctum** - Autenticación API
- **Laravel Policies** - Control de acceso granular

### **Frontend**
- **Tailwind CSS 3.x** - Framework CSS utility-first
- **Alpine.js 3.x** - Framework JavaScript ligero
- **Axios 1.x** - Cliente HTTP para peticiones AJAX
- **Vite** - Build tool moderno y rápido
- **Blade Components** - Componentes reutilizables

### **Características Técnicas**
- **Responsive Design** - Adaptable a todos los dispositivos
- **PWA Ready** - Preparado para Progressive Web App
- **AJAX/SPA** - Experiencia de usuario fluida
- **Real-time Updates** - Actualizaciones automáticas
- **Error Handling** - Manejo robusto de errores
- **Performance Optimization** - Optimización de rendimiento

## 📋 Requisitos del Sistema

- **PHP** >= 8.1
- **Composer** >= 2.0
- **Node.js** >= 16.0
- **MySQL** >= 8.0 o **PostgreSQL** >= 13
- **Laravel Sail** (recomendado para desarrollo)

## 🚀 Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/control-parental.git
cd control-parental
```

### 2. Instalar dependencias
```bash
# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node.js
npm install
```

### 3. Configurar el entorno
```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar la base de datos
```bash
# Editar .env con tus credenciales de base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=control_parental
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Ejecutar migraciones
```bash
php artisan migrate
```

### 6. Compilar assets
```bash
npm run build
```

### 7. Iniciar el servidor
```bash
# Con Laravel Sail (recomendado)
./vendor/bin/sail up

# O con el servidor de desarrollo de PHP
php artisan serve
```

## 🐳 Usando Docker (Laravel Sail)

```bash
# Iniciar todos los servicios
./vendor/bin/sail up -d

# Ejecutar migraciones
./vendor/bin/sail artisan migrate

# Compilar assets
./vendor/bin/sail npm run build

# Acceder a la aplicación
http://localhost
```

## 🔌 APIs Disponibles

### **Dispositivos**
```http
GET /api/devices                    # Lista de dispositivos
GET /api/devices/{device}/status    # Estado de dispositivo específico
```

### **Horarios**
```http
GET /api/devices/{device}/horarios           # Lista de horarios de un dispositivo
GET /api/devices/{device}/horarios/{horario} # Horario específico
```

### **Notificaciones**
```http
GET /api/notifications              # Lista de notificaciones
GET /api/notifications/count        # Conteo de notificaciones no leídas
POST /api/notifications/{id}/read   # Marcar como leído
POST /api/notifications/read-all    # Marcar todas como leídas
```

### **Sincronización**
```http
GET /api/sync/apps                  # Obtener apps
POST /api/sync/apps                 # Sincronizar apps
DELETE /api/sync/apps               # Eliminar apps
GET /api/sync/horarios              # Obtener horarios
POST /api/sync/horarios             # Sincronizar horarios
DELETE /api/sync/horarios           # Eliminar horarios
GET /api/sync/devices               # Obtener dispositivos
POST /api/sync/devices              # Sincronizar dispositivos
```

### **Autenticación**
```http
POST /api/register                  # Registro de usuario
POST /api/login                     # Inicio de sesión
```

### **Health Check**
```http
GET /api/health                     # Estado del servidor
```

## 📱 Configuración de la App Móvil

### Para Android
1. Descargar la app desde Google Play Store
2. Configurar la URL del servidor en la app
3. Vincular dispositivo con el código QR
4. Conceder permisos necesarios

### Para iOS
1. Descargar la app desde App Store
2. Configurar la URL del servidor
3. Vincular dispositivo con el código QR
4. Configurar restricciones de pantalla

## 🔧 Configuración Avanzada

### Variables de Entorno Importantes
```env
# Configuración de base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=control_parental
DB_USERNAME=root
DB_PASSWORD=

# Configuración de notificaciones
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# Configuración de la app
APP_NAME="Control Parental"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

### Configuración de WebSockets (Opcional)
```bash
# Instalar dependencias de WebSockets
composer require pusher/pusher-php-server

# Configurar broadcasting en config/broadcasting.php
```

## 📊 Estructura del Proyecto

```
controlParental/
├── app/
│   ├── Http/Controllers/     # Controladores de la aplicación
│   │   ├── AuthController.php
│   │   ├── DeviceController.php
│   │   ├── HorarioController.php
│   │   ├── NotificationController.php
│   │   └── SyncController.php
│   ├── Models/              # Modelos Eloquent
│   │   ├── Device.php
│   │   ├── DeviceApp.php
│   │   ├── Horario.php
│   │   ├── Notification.php
│   │   └── User.php
│   ├── Enums/              # Enumeraciones
│   │   ├── AppStatus.php
│   │   └── DayOfWeek.php
│   └── Policies/           # Políticas de autorización
│       └── DevicePolicy.php
├── resources/
│   ├── views/              # Vistas Blade
│   │   ├── components/     # Componentes reutilizables
│   │   ├── devices/        # Vistas de dispositivos
│   │   ├── horarios/       # Vistas de horarios
│   │   └── layouts/        # Layouts principales
│   ├── css/               # Estilos CSS
│   └── js/                # JavaScript
├── routes/
│   ├── web.php            # Rutas web
│   └── api.php            # Rutas API
├── database/
│   ├── migrations/        # Migraciones de BD
│   └── seeders/          # Seeders
└── tests/                # Tests automatizados
```

## 🧪 Testing

### Ejecutar Tests
```bash
# Tests unitarios
php artisan test --testsuite=Unit

# Tests de integración
php artisan test --testsuite=Feature

# Todos los tests
php artisan test
```

### Cobertura de Tests
- **AuthTest.php** - Tests de autenticación
- **SyncEndpointsTest.php** - Tests de endpoints de sincronización
- **DeviceRelationshipTest.php** - Tests de relaciones de dispositivos
- **UserDeviceRelationshipTest.php** - Tests de relaciones usuario-dispositivo

## 🚀 Despliegue

### Producción
```bash
# Optimizar para producción
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Variables de Entorno de Producción
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
LOG_LEVEL=error
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🆘 Soporte

Si tienes problemas o preguntas:

1. Revisar la [documentación](https://github.com/tu-usuario/control-parental/wiki)
2. Buscar en [issues existentes](https://github.com/tu-usuario/control-parental/issues)
3. Crear un nuevo issue con detalles del problema

## 🔄 Changelog

### v2.0.0 - Actualización Automática Completa
- ✅ **Sistema de actualización automática** en todas las vistas principales
- ✅ **APIs RESTful** para sincronización en tiempo real
- ✅ **Detección inteligente de cambios** con optimización de rendimiento
- ✅ **Indicadores visuales** de actualización no intrusivos
- ✅ **Manejo robusto de errores** y adaptación a problemas de conectividad
- ✅ **Experiencia de usuario mejorada** con validación en tiempo real

### v1.0.0 - Lanzamiento Inicial
- ✅ Sistema básico de control parental
- ✅ Gestión de dispositivos y aplicaciones
- ✅ Sistema de horarios
- ✅ Notificaciones básicas

---

**Desarrollado con ❤️ usando Laravel, Tailwind CSS y Alpine.js**
