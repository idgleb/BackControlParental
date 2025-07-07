<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceApp;
use App\Models\SyncEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Reporte de uso del día actual
     */
    public function todayUsage(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $apps = DeviceApp::where('deviceId', $device->deviceId)
            ->where('usageTimeToday', '>', 0)
            ->orderBy('usageTimeToday', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'package_name' => $app->packageName,
                    'app_name' => $app->appName,
                    'app_category' => $app->appCategory,
                    'usage_minutes' => intval($app->usageTimeToday / 60),
                    'usage_seconds' => $app->usageTimeToday,
                    'status' => $app->appStatus,
                    'daily_limit_minutes' => $app->dailyUsageLimitMinutes,
                    'percentage_used' => $app->dailyUsageLimitMinutes > 0 
                        ? round(($app->usageTimeToday / 60) / $app->dailyUsageLimitMinutes * 100, 2)
                        : null,
                ];
            });
        
        $totalUsageSeconds = $apps->sum('usage_seconds');
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => now()->toDateString(),
                'total_usage_minutes' => intval($totalUsageSeconds / 60),
                'total_usage_formatted' => $this->formatTime($totalUsageSeconds),
                'apps_count' => $apps->count(),
                'apps' => $apps->take(20), // Top 20 apps
            ],
        ]);
    }
    
    /**
     * Reporte de uso semanal
     */
    public function weekUsage(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();
        
        // Obtener eventos de uso de la semana
        $usageEvents = SyncEvent::where('deviceId', $device->deviceId)
            ->where('entity_type', 'usage')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        // Agrupar por día
        $dailyUsage = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayEvents = $usageEvents->filter(function ($event) use ($date) {
                return Carbon::parse($event->created_at)->toDateString() === $date->toDateString();
            });
            
            $totalSeconds = $dayEvents->sum(function ($event) {
                return $event->data['total_usage_seconds'] ?? 0;
            });
            
            $dailyUsage[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->locale('es')->dayName,
                'usage_minutes' => intval($totalSeconds / 60),
                'usage_formatted' => $this->formatTime($totalSeconds),
            ];
        }
        
        $totalWeekSeconds = collect($dailyUsage)->sum(function ($day) {
            return $day['usage_minutes'] * 60;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_usage_minutes' => intval($totalWeekSeconds / 60),
                'total_usage_formatted' => $this->formatTime($totalWeekSeconds),
                'daily_average_minutes' => intval($totalWeekSeconds / 60 / 7),
                'daily_usage' => $dailyUsage,
            ],
        ]);
    }
    
    /**
     * Reporte de uso mensual
     */
    public function monthUsage(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        // Obtener eventos de uso del mes
        $usageEvents = SyncEvent::where('deviceId', $device->deviceId)
            ->where('entity_type', 'usage')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        // Agrupar por semana
        $weeklyUsage = [];
        $currentWeekStart = $startDate->copy()->startOfWeek();
        $weekNumber = 1;
        
        while ($currentWeekStart < $endDate) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();
            
            $weekEvents = $usageEvents->filter(function ($event) use ($currentWeekStart, $weekEnd) {
                $eventDate = Carbon::parse($event->created_at);
                return $eventDate >= $currentWeekStart && $eventDate <= $weekEnd;
            });
            
            $totalSeconds = $weekEvents->sum(function ($event) {
                return $event->data['total_usage_seconds'] ?? 0;
            });
            
            $weeklyUsage[] = [
                'week_number' => $weekNumber,
                'start_date' => $currentWeekStart->toDateString(),
                'end_date' => min($weekEnd, $endDate)->toDateString(),
                'usage_minutes' => intval($totalSeconds / 60),
                'usage_formatted' => $this->formatTime($totalSeconds),
            ];
            
            $currentWeekStart->addWeek();
            $weekNumber++;
        }
        
        $totalMonthSeconds = collect($weeklyUsage)->sum(function ($week) {
            return $week['usage_minutes'] * 60;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'month' => now()->format('Y-m'),
                'month_name' => now()->locale('es')->monthName,
                'total_usage_minutes' => intval($totalMonthSeconds / 60),
                'total_usage_formatted' => $this->formatTime($totalMonthSeconds),
                'daily_average_minutes' => intval($totalMonthSeconds / 60 / now()->daysInMonth),
                'weekly_usage' => $weeklyUsage,
            ],
        ]);
    }
    
    /**
     * Reporte de uso por aplicaciones
     */
    public function appsUsage(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $period = $request->query('period', 'week'); // week, month, all
        $category = $request->query('category'); // filtrar por categoría
        
        $query = DeviceApp::where('deviceId', $device->deviceId);
        
        if ($category) {
            $query->where('appCategory', $category);
        }
        
        $apps = $query->orderBy('usageTimeToday', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'package_name' => $app->packageName,
                    'app_name' => $app->appName,
                    'app_category' => $app->appCategory,
                    'is_system_app' => (bool)$app->isSystemApp,
                    'usage_today_minutes' => intval($app->usageTimeToday / 60),
                    'status' => $app->appStatus,
                    'daily_limit_minutes' => $app->dailyUsageLimitMinutes,
                ];
            });
        
        // Categorías más usadas
        $categories = $apps->groupBy('app_category')
            ->map(function ($categoryApps, $categoryName) {
                $totalMinutes = $categoryApps->sum('usage_today_minutes');
                return [
                    'category' => $categoryName ?: 'Sin categoría',
                    'apps_count' => $categoryApps->count(),
                    'total_usage_minutes' => $totalMinutes,
                ];
            })
            ->sortByDesc('total_usage_minutes')
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_apps' => $apps->count(),
                'blocked_apps' => $apps->where('status', 'BLOQUEADA')->count(),
                'limited_apps' => $apps->where('status', 'LIMITADA')->count(),
                'top_apps' => $apps->take(10),
                'categories' => $categories,
            ],
        ]);
    }
    
    /**
     * Reporte de intentos bloqueados
     */
    public function blockedAttempts(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        
        $days = $request->query('days', 7);
        $startDate = now()->subDays($days);
        
        // Obtener eventos de bloqueo
        $blockedEvents = SyncEvent::where('deviceId', $device->deviceId)
            ->where('entity_type', 'blocked_attempt')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'timestamp' => $event->created_at->toISOString(),
                    'app_name' => $event->data['app_name'] ?? 'Desconocida',
                    'package_name' => $event->data['package_name'] ?? null,
                    'reason' => $event->data['reason'] ?? 'App bloqueada',
                ];
            });
        
        // Agrupar por app
        $appsSummary = $blockedEvents->groupBy('package_name')
            ->map(function ($attempts, $packageName) {
                return [
                    'package_name' => $packageName,
                    'app_name' => $attempts->first()['app_name'],
                    'attempts_count' => $attempts->count(),
                    'last_attempt' => $attempts->first()['timestamp'],
                ];
            })
            ->sortByDesc('attempts_count')
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'total_attempts' => $blockedEvents->count(),
                'unique_apps' => $appsSummary->count(),
                'apps_summary' => $appsSummary->take(10),
                'recent_attempts' => $blockedEvents->take(20),
            ],
        ]);
    }
    
    /**
     * Formatear tiempo en formato legible
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }
        
        return sprintf('%dm', $minutes);
    }
} 