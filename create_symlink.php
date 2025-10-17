<?php
/**
 * Script temporal para crear enlace simbólico en Linux
 * Ejecutar una sola vez y luego eliminar este archivo
 */

// Verificar que estamos en el directorio correcto
if (!file_exists('storage/app/public')) {
    die('Error: No se encontró la carpeta storage/app/public');
}

// Crear el enlace simbólico
$target = 'storage/app/public';
$link = 'public/storage';

// Eliminar enlace existente si existe
if (is_link($link)) {
    unlink($link);
}

// Crear el enlace simbólico
if (symlink($target, $link)) {
    echo "✅ Enlace simbólico creado exitosamente!\n";
    echo "Origen: $target\n";
    echo "Destino: $link\n";
    echo "\n⚠️  IMPORTANTE: Elimina este archivo (create_symlink.php) por seguridad\n";
} else {
    echo "❌ Error al crear el enlace simbólico\n";
    echo "Verifica los permisos del servidor\n";
}
?>
