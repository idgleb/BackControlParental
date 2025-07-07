<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
// use App\Domain\DeviceAuth\Actions\RegisterDeviceAction;
// use App\Domain\DeviceAuth\Actions\VerifyDeviceAction;
// use App\Domain\DeviceAuth\DTOs\DeviceRegistrationData;
// use App\Domain\DeviceAuth\DTOs\DeviceVerificationData;
// use App\Domain\DeviceAuth\Exceptions\DeviceAuthException;
// use App\Http\Resources\DeviceAuthResource;
use App\Models\Device;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // public function __construct(
    //     private RegisterDeviceAction $registerAction,
    //     private VerifyDeviceAction $verifyAction
    // ) {}
    
    /**
     * Registrar un nuevo dispositivo
     * 
     * POST /api/v1/auth/register
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|uuid',
            'model' => 'required|string|max:255',
            'android_version' => 'required|string|max:50',
            'app_version' => 'required|string|max:50',
            'manufacturer' => 'nullable|string|max:255',
            'fingerprint' => 'nullable|string|max:255',
        ]);
        
        try {
            // Buscar si el dispositivo ya existe
            $device = Device::where('deviceId', $validated['device_id'])->first();
            
            // Si el dispositivo ya está verificado, devolver respuesta especial
            if ($device && $device->is_verified) {
                Log::info('Device already verified, returning special response', [
                    'device_id' => $device->deviceId,
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'device_id' => $device->deviceId,
                        'is_already_verified' => true,
                        'message' => 'Este dispositivo ya está verificado. Use el endpoint check-status para obtener el token.',
                    ],
                    'code' => 'ALREADY_VERIFIED',
                ], 200);
            }
            
            // Generar código de verificación
            $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            if ($device) {
                // Actualizar dispositivo existente
                $device->update([
                    'model' => $validated['model'],
                    'verification_code' => $verificationCode,
                    'verification_expires_at' => now()->addMinutes(10),
                    'is_verified' => false,
                ]);
                
                Log::info('Device registration updated', [
                    'device_id' => $device->deviceId,
                ]);
            } else {
                // Crear nuevo dispositivo
                $device = Device::create([
                    'deviceId' => $validated['device_id'],
                    'model' => $validated['model'],
                    'verification_code' => $verificationCode,
                    'verification_expires_at' => now()->addMinutes(10),
                    'is_verified' => false,
                    'api_token' => null,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $device->deviceId,
                    'verification_code' => $verificationCode,
                    'expires_in_minutes' => 10,
                    'message' => 'Ingrese este código en el panel de control parental',
                ],
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Device registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al registrar dispositivo',
                'code' => 'REGISTRATION_ERROR',
            ], 500);
        }
    }
    
    /**
     * Verificar dispositivo y obtener token API
     * 
     * POST /api/v1/auth/verify
     */
    public function verifyDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|uuid',
            'verification_code' => 'required|string',
            'child_name' => 'nullable|string|max:100',
        ]);
        
        // Limpiar código de verificación (remover espacios o guiones)
        $validated['verification_code'] = str_replace(['-', ' '], '', $validated['verification_code']);
        
        try {
            // Buscar dispositivo
            $device = Device::where('deviceId', $validated['device_id'])
                ->where('verification_code', $validated['verification_code'])
                ->where('verification_expires_at', '>', now())
                ->first();
            
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'error' => 'Código de verificación inválido o expirado',
                    'code' => 'INVALID_CODE',
                ], 400);
            }
            
            // Generar token API
            $apiToken = Str::random(60);
            
            // Actualizar dispositivo
            $device->update([
                'api_token' => hash('sha256', $apiToken),
                'is_verified' => true,
                'verification_code' => null,
                'verification_expires_at' => null,
                'child_name' => $validated['child_name'] ?? null,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $device->deviceId,
                    'api_token' => $apiToken, // Token sin encriptar
                    'message' => 'Dispositivo verificado exitosamente',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Device verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al verificar dispositivo',
                'code' => 'VERIFICATION_ERROR',
            ], 500);
        }
    }
    
    /**
     * Obtener estado del dispositivo
     * 
     * GET /api/v1/auth/status
     */
    public function deviceStatus(Request $request): JsonResponse
    {
        $device = $request->user(); // Ya autenticado por middleware
        
        if (!$device) {
            return response()->json([
                'success' => false,
                'error' => 'Device not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $device->deviceId,
                'model' => $device->model,
                'is_verified' => $device->is_verified,
                'child_name' => $device->child_name,
                'created_at' => $device->created_at->toISOString(),
            ],
        ]);
    }
    
    /**
     * Verificar estado del dispositivo (sin autenticación)
     * Usado para verificar si el dispositivo fue verificado desde la web
     * 
     * GET /api/v1/auth/check-status
     */
    public function checkDeviceStatus(Request $request): JsonResponse
    {
        Log::info('checkDeviceStatus: Request received', [
            'params' => $request->all(),
            'query' => $request->query(),
            'device_id' => $request->get('device_id'),
        ]);
        
        $validated = $request->validate([
            'device_id' => 'required|uuid',
        ]);
        
        try {
            $device = Device::where('deviceId', $validated['device_id'])->first();
            
            Log::info('checkDeviceStatus: Device lookup', [
                'device_id' => $validated['device_id'],
                'found' => $device !== null,
                'is_verified' => $device?->is_verified,
                'has_api_token' => !empty($device?->api_token),
            ]);
            
            if (!$device) {
                Log::info('checkDeviceStatus: Device not found', [
                    'device_id' => $validated['device_id'],
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Device not found',
                    'code' => 'NOT_FOUND',
                ], 404);
            }
            
            // Si el dispositivo está verificado, devolver o generar token
            if ($device->is_verified) {
                // Generar token si no existe o siempre generar uno nuevo
                $newToken = Str::random(60);
                $device->update([
                    'api_token' => hash('sha256', $newToken),
                ]);
                
                Log::info('Device verified from web check - Returning token', [
                    'device_id' => $device->deviceId,
                    'has_token' => !empty($device->api_token),
                    'child_name' => $device->child_name,
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'device_id' => $device->deviceId,
                        'is_verified' => true,
                        'api_token' => $newToken,
                        'child_name' => $device->child_name,
                    ],
                ]);
            }
            
            // Si no está verificado, solo devolver estado
            Log::info('Device not verified yet', [
                'device_id' => $device->deviceId,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $device->deviceId,
                    'is_verified' => false,
                    'api_token' => null,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check device status error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al verificar estado',
                'code' => 'STATUS_ERROR',
            ], 500);
        }
    }
} 