# Configuración del Servidor Remoto

## Variables de Entorno Requeridas

Agrega estas variables a tu archivo `.env`:

```env
# URL del servidor remoto donde se subirán los archivos
REMOTE_SERVER_URL=https://tu-servidor-remoto.com

# API Key para autenticación con el servidor remoto
REMOTE_SERVER_API_KEY=tu-api-key-secreta-aqui
```

## Ejemplo de Servidor Remoto (PHP)

Crea un archivo `upload.php` en tu servidor remoto:

```php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Verificar autenticación
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if ($token !== 'tu-api-key-secreta-aqui') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verificar archivo
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];

// Validar archivo
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Crear directorio si no existe
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único
$filename = $file['name'];
$extension = pathinfo($filename, PATHINFO_EXTENSION);
$uniqueName = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
$targetPath = $uploadDir . $uniqueName;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $publicUrl = 'https://tu-servidor-remoto.com/uploads/' . $uniqueName;
    
    echo json_encode([
        'success' => true,
        'url' => $publicUrl,
        'filename' => $uniqueName,
        'size' => $file['size']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
}
?>
```

## Configuración de Nginx (opcional)

Para servir archivos estáticos:

```nginx
location /uploads/ {
    alias /ruta/a/tu/servidor/uploads/;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Configuración de Apache (opcional)

En tu `.htaccess`:

```apache
RewriteEngine On
RewriteRule ^uploads/(.*)$ /ruta/a/tu/servidor/uploads/$1 [L]

<Files "*.jpg">
    Header set Cache-Control "max-age=31536000, public"
</Files>
<Files "*.png">
    Header set Cache-Control "max-age=31536000, public"
</Files>
<Files "*.gif">
    Header set Cache-Control "max-age=31536000, public"
</Files>
```
