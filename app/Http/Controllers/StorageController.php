<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StorageController extends Controller
{
    /**
     * Servir archivos desde storage/app/public
     */
    public function serve($path)
    {
        try {
            // Construir la ruta completa
            $fullPath = storage_path('app/public/' . $path);
            
            // Verificar que el archivo existe
            if (!File::exists($fullPath)) {
                abort(404, 'Archivo no encontrado');
            }
            
            // Obtener el tipo MIME
            $mimeType = File::mimeType($fullPath);
            
            // Leer el archivo
            $file = File::get($fullPath);
            
            // Retornar el archivo con headers apropiados
            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', File::size($fullPath))
                ->header('Cache-Control', 'public, max-age=3600');
                
        } catch (\Exception $e) {
            abort(404, 'Error al cargar el archivo');
        }
    }
}
