<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TibaanApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WebsViewsController extends Controller
{
    protected TibaanApiService $tibaanApiService;

    public function __construct(TibaanApiService $tibaanApiService)
    {
        $this->tibaanApiService = $tibaanApiService;
    }

    /**
     * Obtener todas las webs-views
     */
    public function index(Request $request): JsonResponse
    {
        $externalToken = $request->get('external_token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        $response = $this->tibaanApiService->getUserInfo($externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al obtener webs-views'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ]);
    }

    /**
     * Crear una nueva web-view
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'web_url' => 'required|string',
            'web_name' => 'required|string',
            'web_descri' => 'required|string',
            'web_roles' => 'required|array',
            'web_site' => 'required|integer',
            'web_status' => 'required|integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $externalToken = $request->get('external_token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        // Realizar la llamada a la API externa
        $response = $this->makeExternalRequest('POST', '/webs-views', $request->all(), $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al crear web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ], 201);
    }

    /**
     * Actualizar una web-view
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'web_id' => 'required|string',
            'web_url' => 'required|string',
            'web_name' => 'required|string',
            'web_descri' => 'required|string',
            'web_roles' => 'required|array',
            'web_site' => 'required|integer',
            'web_status' => 'required|integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $externalToken = $request->get('external_token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        // Realizar la llamada a la API externa
        $response = $this->makeExternalRequest('PUT', "/webs-views/{$id}", $request->all(), $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al actualizar web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ]);
    }

    /**
     * Eliminar una web-view
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $externalToken = $request->get('external_token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        // Realizar la llamada a la API externa
        $response = $this->makeExternalRequest('DELETE', "/webs-views/{$id}", [], $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al eliminar web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Web-view eliminada exitosamente'
        ]);
    }

    /**
     * Realizar petición HTTP a la API externa
     */
    private function makeExternalRequest(string $method, string $endpoint, array $data = [], string $token = null): array
    {
        try {
            $baseUrl = config('services.tibaan_api.base_url');
            $timeout = config('services.tibaan_api.timeout');
            
            $httpClient = \Illuminate\Support\Facades\Http::timeout($timeout);
            
            // En desarrollo local, deshabilitar verificación SSL
            if (config('app.env') === 'local') {
                $httpClient = $httpClient->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
            }
            
            if ($token) {
                $httpClient = $httpClient->withToken($token);
            }

            $response = $httpClient->$method($baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error en la API externa',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            \Log::error('Error en petición a API externa', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor externo',
                'exception' => $e->getMessage()
            ];
        }
    }
}
