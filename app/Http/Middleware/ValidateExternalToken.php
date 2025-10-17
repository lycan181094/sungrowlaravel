<?php

namespace App\Http\Middleware;

use App\Services\TibaanApiService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateExternalToken
{
    protected TibaanApiService $tibaanApiService;

    public function __construct(TibaanApiService $tibaanApiService)
    {
        $this->tibaanApiService = $tibaanApiService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el token externo del header
        $externalToken = $request->header('X-External-Token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        // Validar el token con la API externa
        $validationResult = $this->tibaanApiService->validateToken($externalToken);
        
        if (!$validationResult['success']) {
            Log::warning('Error al validar token externo', [
                'error' => $validationResult['error'] ?? 'Error desconocido',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con el servidor de autenticación'
            ], 503);
        }

        if (!$validationResult['valid']) {
            Log::info('Token externo inválido', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        }

        // Agregar el token externo a la request para uso posterior
        $request->merge(['external_token' => $externalToken]);

        return $next($request);
    }
}
