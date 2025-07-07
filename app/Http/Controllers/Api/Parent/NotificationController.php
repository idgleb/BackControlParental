<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Listar notificaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $formattedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'is_read' => (bool)$notification->is_read,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'action_url' => $notification->action_url,
                'icon' => $this->getIconForType($notification->type),
                'color' => $this->getColorForType($notification->type),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedNotifications,
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'has_more' => $notifications->hasMorePages(),
            ],
        ]);
    }
    
    /**
     * Obtener contador de notificaciones no leídas
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }
    
    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Verificar que la notificación pertenece al usuario
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Notificación no encontrada',
            ], 404);
        }
        
        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
            'data' => [
                'id' => $notification->id,
                'is_read' => true,
                'read_at' => $notification->read_at->toISOString(),
            ],
        ]);
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $updated = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        return response()->json([
            'success' => true,
            'message' => "Se marcaron $updated notificaciones como leídas",
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }
    
    /**
     * Obtener icono según el tipo de notificación
     */
    private function getIconForType(string $type): string
    {
        return match ($type) {
            'app_blocked' => 'block',
            'app_unblocked' => 'check_circle',
            'device_offline' => 'wifi_off',
            'device_online' => 'wifi',
            'low_battery' => 'battery_alert',
            'usage_limit_reached' => 'timer_off',
            'blocked_attempt' => 'warning',
            'schedule_activated' => 'schedule',
            'new_app_installed' => 'add_circle',
            'location_update' => 'location_on',
            default => 'notifications',
        };
    }
    
    /**
     * Obtener color según el tipo de notificación
     */
    private function getColorForType(string $type): string
    {
        return match ($type) {
            'app_blocked', 'blocked_attempt' => 'red',
            'app_unblocked', 'device_online' => 'green',
            'device_offline', 'low_battery' => 'orange',
            'usage_limit_reached' => 'yellow',
            'schedule_activated', 'new_app_installed' => 'blue',
            'location_update' => 'purple',
            default => 'gray',
        };
    }
    
    /**
     * Crear notificación de prueba (solo para desarrollo)
     */
    public function createTestNotification(Request $request): JsonResponse
    {
        if (app()->environment('production')) {
            return response()->json(['error' => 'Not available in production'], 403);
        }
        
        $user = $request->user();
        
        $types = [
            'app_blocked' => [
                'title' => 'Aplicación bloqueada',
                'message' => 'YouTube ha sido bloqueada en el dispositivo de Juan',
            ],
            'device_offline' => [
                'title' => 'Dispositivo desconectado',
                'message' => 'El dispositivo de María está fuera de línea',
            ],
            'low_battery' => [
                'title' => 'Batería baja',
                'message' => 'El dispositivo de Pedro tiene 15% de batería',
            ],
            'blocked_attempt' => [
                'title' => 'Intento de acceso bloqueado',
                'message' => 'Juan intentó abrir TikTok (bloqueada) 3 veces',
            ],
        ];
        
        $randomType = array_rand($types);
        
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $randomType,
            'title' => $types[$randomType]['title'],
            'message' => $types[$randomType]['message'],
            'data' => [
                'device_id' => '550e8400-e29b-41d4-a716-446655440000',
                'device_name' => 'Dispositivo de prueba',
                'test' => true,
            ],
            'is_read' => false,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación de prueba creada',
            'data' => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
            ],
        ]);
    }
} 