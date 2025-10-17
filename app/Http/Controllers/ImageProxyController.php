<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageProxyController extends Controller
{
    /**
     * Servir imagen remota como proxy manteniendo la URL local
     */
    public function show($slug)
    {
        // Buscar la noticia por slug
        $news = News::where('slug', $slug)->first();
        
        if (!$news) {
            return $this->errorResponse('Noticia no encontrada', 404);
        }
        
        // Verificar que la URL remota existe
        if (!$news->ruta) {
            return $this->errorResponse('URL de imagen no disponible', 404);
        }
        
        try {
            // Verificar si es una URL local (storage) o remota
            if ($this->isLocalStorageUrl($news->ruta)) {
                // Es una imagen local - servir directamente
                return $this->serveLocalImage($news->ruta);
            } else {
                // Es una imagen remota - usar el método anterior
                $imageContent = $this->fetchRemoteImage($news->ruta);
                
                if ($imageContent === false) {
                    return $this->errorResponse('No se pudo cargar la imagen remota', 404);
                }
                
                // Determinar el tipo MIME
                $mimeType = $this->getMimeType($news->ruta);
                
                // Retornar la imagen con los headers apropiados
                return response($imageContent, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Length', strlen($imageContent))
                    ->header('Cache-Control', 'public, max-age=3600') // Cache por 1 hora
                    ->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT')
                    ->header('X-Original-URL', $news->ruta) // Header para debugging
                    ->header('X-Proxy-Status', 'success');
            }
                
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cargar la imagen: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Generar respuesta de error con imagen placeholder
     */
    private function errorResponse($message, $statusCode = 404)
    {
        // Crear una imagen de error simple (1x1 pixel transparente)
        $errorImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        
        return response($errorImage, $statusCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Length', strlen($errorImage))
            ->header('X-Error-Message', $message);
    }
    
    /**
     * Obtener imagen remota de forma más robusta
     */
    private function fetchRemoteImage($url)
    {
        // Intentar con file_get_contents primero (más rápido)
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Laravel Image Proxy/1.0'
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        
        if ($content !== false) {
            return $content;
        }
        
        // Si falla, usar cURL como respaldo
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Laravel Image Proxy/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200) ? $content : false;
    }
    
    /**
     * Obtener tipo MIME basado en la extensión de la URL
     */
    private function getMimeType($url)
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon'
        ];
        
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
    
    /**
     * Verificar si la URL es de storage local
     */
    private function isLocalStorageUrl($url)
    {
        // Verificar si contiene /storage/ (URL local de Laravel)
        return strpos($url, '/storage/') !== false;
    }
    
    /**
     * Servir imagen local desde storage
     */
    private function serveLocalImage($url)
    {
        try {
            // Extraer la ruta del archivo desde la URL
            // URL: http://localhost:8000/storage/images/archivo.jpg
            // Ruta: storage/images/archivo.jpg
            $path = parse_url($url, PHP_URL_PATH);
            $storagePath = ltrim($path, '/'); // Quitar la barra inicial
            
            // Construir la ruta completa en el sistema de archivos
            $fullPath = storage_path('app/public/' . str_replace('storage/', '', $storagePath));
            
            // Verificar que el archivo existe
            if (!file_exists($fullPath)) {
                return $this->errorResponse('Archivo local no encontrado', 404);
            }
            
            // Obtener el tipo MIME
            $mimeType = $this->getMimeType($fullPath);
            
            // Leer el archivo
            $fileContent = file_get_contents($fullPath);
            
            // Retornar la imagen con headers apropiados
            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', filesize($fullPath))
                ->header('Cache-Control', 'public, max-age=3600') // Cache por 1 hora
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT')
                ->header('X-Original-URL', $url) // Header para debugging
                ->header('X-Proxy-Status', 'local');
                
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cargar imagen local: ' . $e->getMessage(), 500);
        }
    }
}
