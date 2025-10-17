<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\RemoteFileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    protected $fileUploadService;

    public function __construct(RemoteFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Obtener el número de página (por defecto 1)
        $page = $request->input('page', 1);
        $perPage = 10; // 10 noticias por página
        
        // Construir la consulta con ordenamiento
        $query = News::with('user')
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('created_at', 'desc');
        
        // Aplicar paginación
        $news = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
                'from' => $news->firstItem(),
                'to' => $news->lastItem(),
                'has_more_pages' => $news->hasMorePages(),
                'next_page_url' => $news->nextPageUrl(),
                'prev_page_url' => $news->previousPageUrl()
            ]
        ]);
    }

    /**
     * Top 10 visibles (display = true)
     */
    public function top10Public(Request $request): JsonResponse
    {
        try {
            $news = News::with('user')
                ->where('display', true)
                ->orderBy('fecha_hora', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $news
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener Top 10 públicas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'sub_titulo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Asignar automáticamente el user_id del usuario autenticado
        $newsData = $request->all();
        $newsData['user_id'] = $request->user()->id;

        $news = News::create($newsData);
        $news->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Noticia creada exitosamente',
            'data' => $news
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $news = News::with('user')->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|required|string|max:255',
            'sub_titulo' => 'sometimes|required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $news->update($request->all());
        $news->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Noticia actualizada exitosamente',
            'data' => $news
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        // Borrado lógico (SoftDelete)
        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'Noticia eliminada exitosamente (borrado lógico)',
            'data' => [
                'id' => $news->id,
                'titulo' => $news->titulo,
                'deleted_at' => $news->deleted_at
            ]
        ]);
    }

    /**
     * Upload file to remote server
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|mimes:jpg,jpeg,png,gif|max:5120', // 5MB max
            'filename' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $customFilename = $request->input('filename');

            $result = $this->fileUploadService->uploadFile($file, $customFilename);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir archivo: ' . $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente',
                'data' => [
                    'filename' => $result['filename'],
                    'url' => $result['url'],
                    'size' => $result['size'],
                    'mime_type' => $result['mime_type']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file from base64
     */
    public function uploadBase64File(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string', // base64 string
            'filename' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $base64File = $request->input('file');
            $filename = $request->input('filename');

            $result = $this->fileUploadService->uploadFile($base64File, $filename);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir archivo: ' . $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente',
                'data' => [
                    'filename' => $result['filename'],
                    'url' => $result['url'],
                    'size' => $result['size'],
                    'mime_type' => $result['mime_type']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file and save news to database in one step
     */
    public function uploadAndSaveNews(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|mimes:jpg,jpeg,png,gif|max:5120',
            'filename' => 'required|string|max:255',
            'titulo' => 'required|string|max:255',
            'sub_titulo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($request) {
                // 1. Upload file to remote server
                $file = $request->file('file');
                $filename = $request->input('filename');
                
                $uploadResult = $this->fileUploadService->uploadFile($file, $filename);

                if (!$uploadResult['success']) {
                    throw new \Exception('Error al subir archivo: ' . $uploadResult['error']);
                }

                // 2. Generate unique slug first
                $slug = $this->generateUniqueSlug($request->input('titulo'));

                // 3. Save news to database with the file URL
                $newsData = [
                    'titulo' => $request->input('titulo'),
                    'sub_titulo' => $request->input('sub_titulo'),
                    'ruta' => $uploadResult['url'], // URL del archivo subido
                    'user_id' => $request->user()->id, // Asignar automáticamente el user_id del usuario autenticado
                    'slug' => $slug,
                    'fecha_hora' => now(),
                    'link_final' => route('images.show', $slug),
                    'display' => $request->input('display', true) // Por defecto true, pero se puede enviar desde el frontend
                ];

                $news = News::create($newsData);
                $news->load('user');

                // 4. Return success response
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo subido y noticia guardada exitosamente',
                    'data' => $news
                ], 201);
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar errores de integridad de base de datos
            if ($e->getCode() == 23000) { // Integrity constraint violation
                if (strpos($e->getMessage(), 'news_slug_unique') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una noticia con un título similar. Intenta con un título diferente.',
                        'error_type' => 'duplicate_slug'
                    ], 409); // Conflict
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload base64 file and save news to database in one step
     */
    public function uploadBase64AndSaveNews(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string', // base64 string
            'filename' => 'required|string|max:255',
            'titulo' => 'required|string|max:255',
            'sub_titulo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($request) {
                // 1. Upload base64 file to remote server
                $base64File = $request->input('file');
                $filename = $request->input('filename');
                
                $uploadResult = $this->fileUploadService->uploadFile($base64File, $filename);

                if (!$uploadResult['success']) {
                    throw new \Exception('Error al subir archivo: ' . $uploadResult['error']);
                }

                // 2. Generate unique slug first
                $slug = $this->generateUniqueSlug($request->input('titulo'));

                // 3. Save news to database with the file URL
                $newsData = [
                    'titulo' => $request->input('titulo'),
                    'sub_titulo' => $request->input('sub_titulo'),
                    'ruta' => $uploadResult['url'], // URL del archivo subido
                    'user_id' => $request->user()->id, // Asignar automáticamente el user_id del usuario autenticado
                    'slug' => $slug,
                    'fecha_hora' => now(),
                    'link_final' => route('images.show', $slug),
                    'display' => $request->input('display', true) // Por defecto true, pero se puede enviar desde el frontend
                ];

                $news = News::create($newsData);
                $news->load('user');

                // 4. Return success response
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo subido y noticia guardada exitosamente',
                    'data' => $news
                ], 201);
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar errores de integridad de base de datos
            if ($e->getCode() == 23000) { // Integrity constraint violation
                if (strpos($e->getMessage(), 'news_slug_unique') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una noticia con un título similar. Intenta con un título diferente.',
                        'error_type' => 'duplicate_slug'
                    ], 409); // Conflict
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique slug based on title
     */
    private function generateUniqueSlug($title)
    {
        // Limitar longitud del título para el slug
        $title = Str::limit($title, 100);
        $baseSlug = Str::slug($title);
        
        // Si el slug está vacío, usar un valor por defecto
        if (empty($baseSlug)) {
            $baseSlug = 'noticia-' . time();
        }
        
        $slug = $baseSlug;
        $counter = 1;

        // Check if slug exists and make it unique (including soft deleted)
        while (News::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            // Prevenir bucles infinitos
            if ($counter > 1000) {
                $slug = $baseSlug . '-' . time() . '-' . $counter;
                break;
            }
        }

        return $slug;
    }

    /**
     * Restaurar noticia eliminada (borrado lógico)
     */
    public function restore(string $id): JsonResponse
    {
        $news = News::withTrashed()->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        if (!$news->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'La noticia no está eliminada'
            ], 400);
        }

        $news->restore();

        return response()->json([
            'success' => true,
            'message' => 'Noticia restaurada exitosamente',
            'data' => $news->load('user')
        ]);
    }

    /**
     * Eliminar permanentemente (borrado físico)
     */
    public function forceDelete(string $id): JsonResponse
    {
        $news = News::withTrashed()->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        // Eliminar archivo físico si existe
        if ($news->ruta && strpos($news->ruta, '/storage/') !== false) {
            $path = parse_url($news->ruta, PHP_URL_PATH);
            $storagePath = ltrim($path, '/');
            $fullPath = storage_path('app/public/' . str_replace('storage/', '', $storagePath));
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $news->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Noticia eliminada permanentemente'
        ]);
    }

    /**
     * Listar noticias eliminadas (borrado lógico)
     */
    public function trashed(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = News::onlyTrashed()
            ->with('user')
            ->orderBy('deleted_at', 'desc');

        $news = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
                'from' => $news->firstItem(),
                'to' => $news->lastItem(),
                'has_more_pages' => $news->hasMorePages(),
                'next_page_url' => $news->nextPageUrl(),
                'prev_page_url' => $news->previousPageUrl()
            ]
        ]);
    }
}
