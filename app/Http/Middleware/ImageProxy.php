<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\News;

class ImageProxy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo procesar rutas de imágenes
        if (str_starts_with($request->path(), 'images/')) {
            $slug = $request->route('slug');
            
            if ($slug) {
                $news = News::where('slug', $slug)->first();
                
                if ($news && $news->ruta) {
                    try {
                        // Obtener la imagen remota
                        $imageContent = $this->fetchRemoteImage($news->ruta);
                        
                        if ($imageContent) {
                            return response($imageContent, 200)
                                ->header('Content-Type', $this->getMimeType($news->ruta))
                                ->header('Content-Length', strlen($imageContent))
                                ->header('Cache-Control', 'public, max-age=3600')
                                ->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT')
                                ->header('X-Original-URL', $news->ruta);
                        }
                    } catch (\Exception $e) {
                        // Log error but continue with normal flow
                        \Log::error('Image proxy error: ' . $e->getMessage());
                    }
                }
            }
        }
        
        return $next($request);
    }
    
    /**
     * Fetch remote image content
     */
    private function fetchRemoteImage($url)
    {
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
     * Get MIME type from URL
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
            'svg' => 'image/svg+xml'
        ];
        
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
}
