<?php
/**
 * Script específico para Cloudways
 * Crear enlace simbólico sin terminal
 */

// Verificar que estamos en Cloudways
$cloudwaysPath = '/home/master/applications/' . $_SERVER['HTTP_HOST'] . '/public_html';
$currentPath = getcwd();

echo "<h2>🔧 Cloudways Symlink Creator</h2>";
echo "<p><strong>Directorio actual:</strong> $currentPath</p>";

// Verificar estructura de Cloudways
if (file_exists('storage/app/public')) {
    echo "<p>✅ Carpeta storage/app/public encontrada</p>";
    
    // Crear enlace simbólico
    $target = 'storage/app/public';
    $link = 'public/storage';
    
    // Eliminar enlace existente si existe
    if (is_link($link)) {
        unlink($link);
        echo "<p>🗑️ Enlace anterior eliminado</p>";
    }
    
    // Crear el enlace simbólico
    if (symlink($target, $link)) {
        echo "<p>✅ <strong>Enlace simbólico creado exitosamente!</strong></p>";
        echo "<p><strong>Origen:</strong> $target</p>";
        echo "<p><strong>Destino:</strong> $link</p>";
        
        // Verificar que funciona
        if (is_link($link)) {
            echo "<p>✅ Enlace verificado correctamente</p>";
            echo "<p><strong>URL de prueba:</strong> <a href='/storage/images/' target='_blank'>/storage/images/</a></p>";
        }
        
    } else {
        echo "<p>❌ <strong>Error al crear el enlace simbólico</strong></p>";
        echo "<p>Posibles causas:</p>";
        echo "<ul>";
        echo "<li>Permisos insuficientes</li>";
        echo "<li>La función symlink() está deshabilitada</li>";
        echo "<li>El servidor no soporta enlaces simbólicos</li>";
        echo "</ul>";
        
        echo "<h3>🔄 Alternativa: Usar Controlador</h3>";
        echo "<p>Si no puedes crear enlaces simbólicos, usa el controlador personalizado que ya está implementado.</p>";
        echo "<p><strong>URL alternativa:</strong> <a href='/storage/images/' target='_blank'>/storage/images/</a></p>";
    }
    
} else {
    echo "<p>❌ No se encontró la carpeta storage/app/public</p>";
    echo "<p>Verifica que estés en el directorio correcto del proyecto Laravel</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad</p>";
echo "<p><strong>Archivo:</strong> cloudways_symlink.php</p>";
?>
