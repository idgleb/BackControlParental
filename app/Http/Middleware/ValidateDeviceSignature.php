<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeviceSignature
{
    /**
     * Validar firma HMAC de las peticiones para mayor seguridad
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Device-Signature');
        
        if (!$signature) {
            return response()->json([
                'success' => false,
                'error' => 'Missing signature',
            ], 401);
        }
        
        // Obtener el dispositivo autenticado (ya validado por DeviceAuthentication)
        $device = $request->user();
        
        // Construir el payload para la firma
        $payload = $this->buildPayload($request);
        
        // Calcular la firma esperada usando el token del dispositivo como secreto
        $expectedSignature = hash_hmac(
            'sha256', 
            $payload, 
            $device->api_token // Token sin hashear, guardado de forma segura
        );
        
        // Comparación segura contra timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid signature',
            ], 401);
        }
        
        return $next($request);
    }
    
    /**
     * Construir payload para la firma
     */
    private function buildPayload(Request $request): string
    {
        $parts = [
            $request->method(),
            $request->path(),
            $request->header('X-Device-Timestamp', ''),
            $request->header('X-Device-Nonce', ''),
            json_encode($request->all()),
        ];
        
        return implode('|', $parts);
    }
} 