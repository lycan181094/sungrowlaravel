<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebsViewsController;
use App\Http\Controllers\Api\WebsViewsDetailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas de autenticación (públicas)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Ruta pública para envío de correos
Route::post('/mail', [MailController::class, 'send']);

// Noticias públicas (listar y ver) - Throttling más permisivo
Route::get('news', [NewsController::class, 'index'])->middleware('throttle:300,1');
Route::get('news/{id}', [NewsController::class, 'show'])->middleware('throttle:300,1');

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Información del usuario autenticado
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    
    // CRUD protegido (crear, actualizar, eliminar)
    Route::post('news', [NewsController::class, 'store']);
    Route::put('news/{id}', [NewsController::class, 'update']);
    Route::delete('news/{id}', [NewsController::class, 'destroy']);
    
    // Gestión de borrado lógico
    Route::get('news/trashed', [NewsController::class, 'trashed']); // Listar eliminadas
    Route::post('news/{id}/restore', [NewsController::class, 'restore']); // Restaurar
    Route::delete('news/{id}/force', [NewsController::class, 'forceDelete']); // Eliminar permanentemente
    
    // Flujo principal: subir archivo + guardar noticia en un solo paso
    Route::post('news/upload-and-save', [NewsController::class, 'uploadAndSaveNews']);
    Route::post('news/upload-base64-and-save', [NewsController::class, 'uploadBase64AndSaveNews']);
    
    // Rutas para webs-views (requieren validación de token externo)
    Route::middleware('validate.external.token')->group(function () {
        Route::get('webs-views', [WebsViewsController::class, 'index']);
        Route::post('webs-views', [WebsViewsController::class, 'store']);
        Route::put('webs-views/{id}', [WebsViewsController::class, 'update']);
        Route::delete('webs-views/{id}', [WebsViewsController::class, 'destroy']);
        
        // Rutas para webs-views-detail
        Route::get('webs-views-detail/{web_id}', [WebsViewsDetailController::class, 'show']);
        Route::post('webs-views-detail', [WebsViewsDetailController::class, 'store']);
        Route::put('webs-views-detail/{id}', [WebsViewsDetailController::class, 'update']);
        Route::delete('webs-views-detail/{id}', [WebsViewsDetailController::class, 'destroy']);
    });
});
