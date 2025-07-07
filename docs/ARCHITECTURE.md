# Arquitectura del Proyecto Control Parental

## Principios de Diseño

### 1. Domain Driven Design (DDD)
Organizamos el código por dominios de negocio, no por tipo de archivo.

```
app/Domain/Device/     # Todo lo relacionado con dispositivos
app/Domain/Schedule/   # Todo lo relacionado con horarios
app/Domain/User/       # Todo lo relacionado con usuarios
```

### 2. Separación de Responsabilidades

#### Controllers (Controladores)
- Solo manejan HTTP: request/response
- Delegan lógica a Actions/Services
- No contienen lógica de negocio

```php
// ❌ MAL - Lógica en el controlador
public function blockApp(Request $request, Device $device, $packageName)
{
    $app = $device->apps()->where('packageName', $packageName)->first();
    if (!$app) throw new NotFoundHttpException();
    
    if (in_array($packageName, ['com.android.settings'])) {
        throw new \Exception("Cannot block system app");
    }
    
    $app->update(['status' => 'BLOCKED']);
    SyncEvent::create([...]);
    // más lógica...
}

// ✅ BIEN - Delegar a una Action
public function blockApp(
    BlockAppRequest $request, 
    Device $device, 
    string $packageName,
    BlockAppAction $action
) {
    $this->authorize('update', $device);
    
    $blockData = AppBlockData::fromRequest(
        $request->validated(),
        $request->user()->id
    );
    
    $app = $action->execute($device, $packageName, $blockData);
    
    return new AppResource($app);
}
```

#### Actions (Casos de Uso)
- Una clase por caso de uso
- Contienen la lógica de negocio
- Coordinan Services y Repositories
- Son reutilizables

#### Services (Servicios de Dominio)
- Lógica compartida entre Actions
- Operaciones complejas del dominio
- No dependen de HTTP

#### DTOs (Data Transfer Objects)
- Estructuras de datos inmutables
- Validación de datos
- Conversión entre capas

```php
class AppBlockData
{
    public function __construct(
        public readonly ?string $reason,
        public readonly ?Carbon $blockedUntil,
        public readonly int $blockedBy,
    ) {}
    
    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            reason: $data['reason'] ?? null,
            blockedUntil: isset($data['blocked_until']) 
                ? Carbon::parse($data['blocked_until']) 
                : null,
            blockedBy: $userId,
        );
    }
}
```

### 3. API Resources
Formateo consistente de respuestas API:

```php
class DeviceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => [
                'is_online' => $this->isOnline(),
                'last_seen' => $this->last_heartbeat?->toISOString(),
            ],
            'apps' => AppResource::collection($this->whenLoaded('apps')),
        ];
    }
}
```

### 4. Repositorios
Abstracción de acceso a datos:

```php
interface DeviceRepositoryInterface
{
    public function find(int $id): ?Device;
    public function getAllActive(): Collection;
    public function getByUser(User $user): Collection;
}

class EloquentDeviceRepository implements DeviceRepositoryInterface
{
    public function find(int $id): ?Device
    {
        return Device::find($id);
    }
}
```

### 5. Eventos
Desacoplar efectos secundarios:

```php
// En la Action
event(new AppBlocked($device, $app, $blockData));

// Listeners separados
class SendAppBlockedNotification
{
    public function handle(AppBlocked $event): void
    {
        // Enviar notificación push
    }
}

class LogAppBlockedActivity
{
    public function handle(AppBlocked $event): void
    {
        // Registrar en logs
    }
}
```

## Estructura de Carpetas

### Por Dominio (Domain)
```
app/Domain/Device/
├── Actions/           # Casos de uso
├── DTOs/             # Objetos de transferencia
├── Events/           # Eventos del dominio
├── Models/           # Modelos Eloquent
├── Repositories/     # Interfaces de repositorio
└── Services/         # Servicios del dominio
```

### Por Capa (Http)
```
app/Http/
├── Controllers/
│   ├── Api/         # API móvil
│   ├── Ajax/        # AJAX web
│   └── Web/         # Web tradicional
├── Requests/        # Validación
├── Resources/       # Transformación
└── Middleware/      # Middleware HTTP
```

### Infraestructura
```
app/Infrastructure/
├── Repositories/    # Implementaciones
├── External/        # Servicios externos
└── Cache/          # Estrategias de cache
```

## Testing

### Estructura de Tests
```
tests/
├── Unit/           # Tests unitarios
│   └── Domain/
│       └── Device/
│           ├── Actions/
│           └── Services/
├── Feature/        # Tests de integración
│   ├── Api/
│   └── Web/
└── Integration/    # Tests E2E
```

### Ejemplo de Test
```php
class BlockAppActionTest extends TestCase
{
    public function test_can_block_app(): void
    {
        // Arrange
        $device = Device::factory()->create();
        $app = DeviceApp::factory()->create([
            'deviceId' => $device->deviceId,
            'appStatus' => 'DISPONIBLE',
        ]);
        
        $action = app(BlockAppAction::class);
        $blockData = new AppBlockData(
            reason: 'Test block',
            blockedUntil: now()->addHour(),
            blockedBy: 1
        );
        
        // Act
        $result = $action->execute($device, $app->packageName, $blockData);
        
        // Assert
        $this->assertEquals('BLOQUEADA', $result->appStatus);
        $this->assertDatabaseHas('sync_events', [
            'entity_type' => 'app',
            'action' => 'update',
        ]);
    }
}
```

## Configuración y Variables de Entorno

### Grupos de configuración
```env
# App
APP_NAME="Control Parental"
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=control_parental

# Cache
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# External Services
FIREBASE_PROJECT_ID=
GOOGLE_MAPS_API_KEY=

# Monitoring
SENTRY_LARAVEL_DSN=
LOG_CHANNEL=stack
```

## Seguridad

### Principios
1. **Autenticación por capas**: Diferentes métodos para web/móvil
2. **Autorización granular**: Policies para cada recurso
3. **Validación estricta**: Form Requests para toda entrada
4. **Rate limiting**: Diferentes límites por tipo de cliente
5. **Logging completo**: Auditoría de acciones críticas

### Implementación
```php
// Policy
class DevicePolicy
{
    public function view(User $user, Device $device): bool
    {
        return $user->devices->contains($device);
    }
}

// Middleware
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->group(function () {
        // rutas protegidas
    });
```

## Performance

### Estrategias
1. **Eager Loading**: Prevenir N+1 queries
2. **Caching**: Redis para datos frecuentes
3. **Queues**: Procesos asíncronos
4. **Pagination**: Limitar resultados
5. **API Resources**: Solo datos necesarios

### Implementación
```php
// Eager loading
$devices = Device::with(['apps', 'horarios'])->get();

// Caching
Cache::remember('user.devices.'.$userId, 300, function () {
    return $this->user->devices()->get();
});

// Queues
dispatch(new ProcessDeviceMetrics($device));
```

## Mantenibilidad

### Principios SOLID
- **S**ingle Responsibility
- **O**pen/Closed
- **L**iskov Substitution
- **I**nterface Segregation
- **D**ependency Inversion

### Convenciones
1. PSR-12 para estilo de código
2. Nombres descriptivos en inglés
3. Comentarios en puntos críticos
4. Type hints estrictos
5. Documentación actualizada

## Despliegue

### Ambientes
1. **Local**: Docker para desarrollo
2. **Staging**: Réplica de producción
3. **Production**: Alta disponibilidad

### CI/CD
```yaml
# .github/workflows/deploy.yml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: |
          composer install
          php artisan test
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        run: |
          ssh deploy@server 'cd /app && git pull && composer install'
``` 