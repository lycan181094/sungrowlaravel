<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TibaanApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected TibaanApiService $tibaanApiService;

    public function __construct(TibaanApiService $tibaanApiService)
    {
        $this->tibaanApiService = $tibaanApiService;
    }

    /**
     * Login user through external Tibaan API
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Autenticar con la API externa de Tibaan
        $tibaanResponse = $this->tibaanApiService->login(
            $request->email,
            $request->password
        );

        if (!$tibaanResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => $tibaanResponse['error'] ?? 'Error de autenticación'
            ], 401);
        }

        // Obtener datos del usuario de la API de Tibaan
        $tibaanData = $tibaanResponse['data']['data'];
        $tibaanUser = $tibaanData['user'];

        // Buscar o crear usuario local basado en los datos de Tibaan
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            // Crear usuario local con datos de Tibaan
            $user = User::create([
                'name' => $tibaanUser['name'] ?? $tibaanUser['firstname'] . ' ' . $tibaanUser['lastname'],
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hash local para seguridad
            ]);
        } else {
            // Actualizar nombre si es necesario
            $user->update([
                'name' => $tibaanUser['name'] ?? $tibaanUser['firstname'] . ' ' . $tibaanUser['lastname']
            ]);
        }

        // Crear token local de Sanctum
        $localToken = $user->createToken('auth-token')->plainTextToken;

        // Respuesta compatible con Angular (manteniendo la estructura original)
        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => $user,
                'token' => $localToken, // Token local para las rutas protegidas
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Logout user (revoke token) - both local and external
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Obtener el token externo del usuario (podrías almacenarlo en la sesión o en la base de datos)
        $externalToken = $request->header('X-External-Token');
        
        // Logout en la API externa si tenemos el token
        if ($externalToken) {
            $tibaanResponse = $this->tibaanApiService->logout($externalToken);
            
            if (!$tibaanResponse['success']) {
                // Log del error pero continuamos con el logout local
                \Log::warning('Error en logout externo', [
                    'user_id' => $user->id,
                    'error' => $tibaanResponse['error'] ?? 'Error desconocido'
                ]);
            }
        }

        // Logout local (revocar token de Sanctum)
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout de todos los dispositivos exitoso'
        ]);
    }
}
