<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $lastCheck = $request->get('last_check');
        
        $query = Notification::where('user_id', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->limit(20);
        
        // Si se proporciona last_check, solo obtener notificaciones más recientes
        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }
        
        $notifications = $query->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'read' => (bool) $notification->read_at,
                'created_at' => $notification->created_at->toISOString(),
                'data' => $notification->data
            ];
        });
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $user->notifications()->whereNull('read_at')->count()
        ]);
    }
    
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }
    
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        
        $user->notifications()->whereNull('read_at')->update([
            'read_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }
    
    public static function createNotification($userId, $type, $title, $message, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read_at' => null
        ]);
    }
    
    public static function notifyDeviceStatus($device, $status)
    {
        $user = $device->user;
        $type = $status === 'online' ? 'device_online' : 'device_offline';
        $title = $status === 'online' ? 'Dispositivo Conectado' : 'Dispositivo Desconectado';
        $message = $status === 'online' 
            ? "El dispositivo {$device->name} se ha conectado"
            : "El dispositivo {$device->name} se ha desconectado";
        
        return self::createNotification($user->id, $type, $title, $message, [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'status' => $status
        ]);
    }
    
    public static function notifyAppUsage($device, $appName, $duration)
    {
        $user = $device->user;
        $title = 'Uso de Aplicación';
        $message = "La aplicación {$appName} ha sido usada por {$duration} minutos en {$device->name}";
        
        return self::createNotification($user->id, 'app_usage', $title, $message, [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'app_name' => $appName,
            'duration' => $duration
        ]);
    }
    
    public static function notifyScheduleAlert($device, $schedule)
    {
        $user = $device->user;
        $title = 'Alerta de Horario';
        $message = "El horario '{$schedule->nombreDeHorario}' está activo en {$device->name}";
        
        return self::createNotification($user->id, 'schedule_alert', $title, $message, [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'schedule_id' => $schedule->id,
            'schedule_name' => $schedule->nombreDeHorario
        ]);
    }
    
    /**
     * Obtener el conteo de notificaciones no leídas para la API
     */
    public function getCount(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $unreadCount = $user->notifications()->whereNull('read_at')->count();
            
            return response()->json([
                'success' => true,
                'count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el conteo de notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 