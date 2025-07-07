<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
// use App\Domain\DeviceAuth\Services\DeviceAuthService;
// use App\Domain\DeviceAuth\Exceptions\DeviceAuthException;
use App\Models\Device;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuthentication
{
    // public function __construct(
    //     private DeviceAuthService $authService
    // ) {}
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener token del header o query parameter
        $token = $request->header('X-Device-Token') 
            ?? $request->bearerToken()
            ?? $request->query('device_token');
            
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Device token required',
                'code' => 'NO_TOKEN'
            ], 401);
        }
        
        try {
            // Buscar dispositivo por token encriptado
            $device = Device::where('api_token', hash('sha256', $token))
                ->where('is_verified', true)
                ->first();
            
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired token',
                    'code' => 'INVALID_TOKEN'
                ], 401);
            }
            
            // Verificar que el deviceId en la request coincide (si existe)
            $requestDeviceId = $request->route('deviceId') 
                ?? $request->input('deviceId') 
                ?? $request->query('deviceId');
                
            if ($requestDeviceId && $requestDeviceId !== $device->deviceId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Device mismatch',
                    'code' => 'DEVICE_MISMATCH'
                ], 403);
            }
            

            
            // Actualizar última actividad
            $device->update(['last_seen' => now()]);
            
            // Agregar device a la request
            $request->merge(['authenticated_device' => $device]);
            $request->setUserResolver(function () use ($device) {
                return $device;
            });
            
            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('Device authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
                'code' => 'AUTH_ERROR'
            ], 500);
        }
    }
} 