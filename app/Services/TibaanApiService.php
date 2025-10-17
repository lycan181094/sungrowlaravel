<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TibaanApiService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.tibaan_api.base_url');
        $this->timeout = config('services.tibaan_api.timeout');
    }

    /**
     * Configurar cliente HTTP con opciones para desarrollo local
     */
    private function getHttpClient()
    {
        $client = Http::timeout($this->timeout);
        
        // En desarrollo local, deshabilitar verificación SSL
        if (config('app.env') === 'local') {
            $client = $client->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);
        }
        
        return $client;
    }

    /**
     * Realizar login en la API externa de Tibaan
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/auth/login', [
                    'email' => $email,
                    'password' => $password
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Login exitoso en API Tibaan', [
                    'email' => $email,
                    'response_status' => $response->status()
                ]);

                // Verificar si el login fue exitoso según la estructura de Tibaan
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'data' => $data
                    ];
                } else {
                    // Login fallido según la API de Tibaan
                    return [
                        'success' => false,
                        'error' => $data['msg'] ?? 'Credenciales incorrectas',
                        'status' => $data['code'] ?? 500
                    ];
                }
            }

            Log::warning('Error en login API Tibaan', [
                'email' => $email,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor de autenticación',
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('Excepción en login API Tibaan', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor de autenticación',
                'exception' => $e->getMessage()
            ];
        }
    }

    /**
     * Realizar logout en la API externa de Tibaan
     */
    public function logout(string $token): array
    {
        try {
            $response = $this->getHttpClient()
                ->withToken($token)
                ->post($this->baseUrl . '/auth/logout');

            if ($response->successful()) {
                Log::info('Logout exitoso en API Tibaan', [
                    'response_status' => $response->status()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::warning('Error en logout API Tibaan', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error al cerrar sesión',
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('Excepción en logout API Tibaan', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor de autenticación',
                'exception' => $e->getMessage()
            ];
        }
    }

    /**
     * Validar token con la API externa
     */
    public function validateToken(string $token): array
    {
        try {
            // Usamos el endpoint de webs-views para validar el token
            $response = $this->getHttpClient()
                ->withToken($token)
                ->get($this->baseUrl . '/webs-views');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'valid' => true
                ];
            }

            return [
                'success' => true,
                'valid' => false,
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('Excepción al validar token en API Tibaan', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor de autenticación',
                'exception' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información del usuario desde la API externa
     */
    public function getUserInfo(string $token): array
    {
        try {
            $response = $this->getHttpClient()
                ->withToken($token)
                ->get($this->baseUrl . '/webs-views');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo obtener información del usuario',
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('Excepción al obtener información del usuario', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor',
                'exception' => $e->getMessage()
            ];
        }
    }
}
