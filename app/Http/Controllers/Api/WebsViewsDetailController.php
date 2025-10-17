<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TibaanApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WebsViewsDetailController extends Controller
{
    protected TibaanApiService $tibaanApiService;

    public function __construct(TibaanApiService $tibaanApiService)
    {
        $this->tibaanApiService = $tibaanApiService;
    }

    /**
     * Obtener detalles de una web-view específica
     */
    public function show(Request $request, $webId): JsonResponse
    {
        $externalToken = $request->get('external_token');
        
        if (!$externalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token externo requerido'
            ], 401);
        }

        // Realizar la llamada a la API externa
        $response = $this->makeExternalRequest('GET', "/webs-views-detail/{$webId}", [], $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al obtener detalles de web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ]);
    }

    /**
     * Crear un nuevo detalle de web-view
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'web_id' => 'required|string',
            'det_type' => 'required|string|in:url,video,image',
            'det_url' => 'required_if:det_type,url|string',
            'det_timer' => 'required|integer',
            'det_order' => 'required|integer',
            'det_status' => 'required|integer|in:0,1',
            'det_video' => 'required_if:det_type,video|file',
            'det_video_extension' => 'required_if:det_type,video|string',
            'det_image' => 'required_if:det_type,image|file',
            'det_image_extension' => 'required_if:det_type,image|string'
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

        // Preparar datos para la petición (incluyendo archivos)
        $data = $request->all();
        
        // Realizar la llamada a la API externa con archivos
        $response = $this->makeExternalRequestWithFiles('POST', '/webs-views-detail', $data, $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al crear detalle de web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ], 201);
    }

    /**
     * Actualizar un detalle de web-view
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'web_id' => 'required|string',
            'det_type' => 'required|string|in:url,video,image',
            'det_url' => 'required_if:det_type,url|string',
            'det_timer' => 'required|integer',
            'det_order' => 'required|integer',
            'det_status' => 'required|integer|in:0,1',
            'det_video' => 'sometimes|file',
            'det_video_extension' => 'required_if:det_type,video|string',
            'det_image' => 'sometimes|file',
            'det_image_extension' => 'required_if:det_type,image|string'
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

        // Preparar datos para la petición (incluyendo archivos)
        $data = $request->all();
        
        // Realizar la llamada a la API externa con archivos
        $response = $this->makeExternalRequestWithFiles('PUT', "/webs-views-detail/{$id}", $data, $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al actualizar detalle de web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data']
        ]);
    }

    /**
     * Eliminar un detalle de web-view
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
        $response = $this->makeExternalRequest('DELETE', "/webs-views-detail/{$id}", [], $externalToken);
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al eliminar detalle de web-view'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de web-view eliminado exitosamente'
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

    /**
     * Realizar petición HTTP con archivos a la API externa
     */
    private function makeExternalRequestWithFiles(string $method, string $endpoint, array $data, string $token): array
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

            // Preparar archivos para la petición
            $multipartData = [];
            foreach ($data as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $multipartData[] = [
                        'name' => $key,
                        'contents' => fopen($value->getPathname(), 'r'),
                        'filename' => $value->getClientOriginalName()
                    ];
                } else {
                    $multipartData[] = [
                        'name' => $key,
                        'contents' => $value
                    ];
                }
            }

            $response = $httpClient->$method($baseUrl . $endpoint, [
                'multipart' => $multipartData
            ]);

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
            \Log::error('Error en petición con archivos a API externa', [
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
