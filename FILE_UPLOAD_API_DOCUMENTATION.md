# API de Subida de Archivos - Documentación

## Descripción
API para subir archivos (imágenes) a un servidor remoto y obtener URLs públicas.

## Configuración Requerida

### Variables de Entorno (.env)
```env
REMOTE_SERVER_URL=https://tu-servidor-remoto.com
REMOTE_SERVER_API_KEY=tu-api-key-aqui
```

### Servidor Remoto
Tu servidor remoto debe tener un endpoint que acepte archivos:
```
POST /api/upload
```

**Headers requeridos:**
```
Authorization: Bearer {REMOTE_SERVER_API_KEY}
Content-Type: multipart/form-data
```

**Respuesta esperada del servidor remoto:**
```json
{
    "success": true,
    "url": "https://tu-servidor-remoto.com/uploads/archivo.jpg"
}
```

## Endpoints Disponibles

### 1. Subir Archivo (Multipart Form Data)
```
POST /api/news/upload-file
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data):**
- `file`: Archivo de imagen (jpg, jpeg, png, gif)
- `filename`: Nombre personalizado (opcional)

**Ejemplo con cURL:**
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "file=@/ruta/a/tu/imagen.jpg" \
  -F "filename=mi-imagen-personalizada.jpg" \
  https://tu-api.com/api/news/upload-file
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Archivo subido exitosamente",
    "data": {
        "filename": "2024-01-01_10-30-45_abc123.jpg",
        "url": "https://tu-servidor-remoto.com/uploads/2024-01-01_10-30-45_abc123.jpg",
        "size": 1024000,
        "mime_type": "image/jpeg"
    }
}
```

### 2. Subir Archivo (Base64)
```
POST /api/news/upload-base64
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "file": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD...",
    "filename": "mi-imagen.jpg"
}
```

**Ejemplo con JavaScript:**
```javascript
// Convertir archivo a base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

// Subir archivo
async function uploadFile(file) {
    const base64 = await fileToBase64(file);
    
    const response = await fetch('/api/news/upload-base64', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            file: base64,
            filename: file.name
        })
    });
    
    return response.json();
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Archivo subido exitosamente",
    "data": {
        "filename": "mi-imagen.jpg",
        "url": "https://tu-servidor-remoto.com/uploads/mi-imagen.jpg",
        "size": 1024000,
        "mime_type": "image/jpeg"
    }
}
```

## Validaciones

### Archivos Permitidos
- **Tipos**: jpg, jpeg, png, gif
- **Tamaño máximo**: 5MB
- **MIME types**: image/jpeg, image/png, image/gif

### Errores Comunes

#### Error de Validación (422)
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "file": ["El archivo debe ser una imagen válida"],
        "filename": ["El nombre del archivo es requerido"]
    }
}
```

#### Error de Servidor Remoto (500)
```json
{
    "success": false,
    "message": "Error al subir archivo: Failed to upload to remote server: Connection timeout"
}
```

#### Error de Tamaño (500)
```json
{
    "success": false,
    "message": "Error interno: File size exceeds maximum allowed size of 5MB"
}
```

## Flujo de Trabajo Recomendado

### 1. Frontend → API → Servidor Remoto
```
1. Usuario selecciona archivo en frontend
2. Frontend convierte archivo a base64 (opcional)
3. Frontend envía archivo a tu API Laravel
4. API Laravel valida el archivo
5. API Laravel sube archivo al servidor remoto
6. API Laravel devuelve URL pública
7. Frontend guarda URL en base de datos
```

### 2. Ejemplo de Implementación Completa

**Frontend (JavaScript):**
```javascript
// 1. Subir archivo
const uploadResponse = await fetch('/api/news/upload-base64', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        file: base64String,
        filename: 'imagen-noticia.jpg'
    })
});

const uploadData = await uploadResponse.json();

// 2. Crear noticia con la URL del archivo
const newsResponse = await fetch('/api/news', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        titulo: 'Mi Noticia',
        sub_titulo: 'Subtítulo de la noticia',
        ruta: uploadData.data.url, // URL del archivo subido
        link_final: 'https://example.com/final',
        fecha_hora: '2024-01-01T10:00:00',
        user_id: 1
    })
});
```

## Configuración del Servidor Remoto

### Endpoint Requerido
Tu servidor remoto debe implementar:

```php
// Ejemplo en PHP (servidor remoto)
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = '/uploads/';
    $filename = $_FILES['file']['name'];
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'url' => 'https://tu-servidor.com/uploads/' . $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
    }
}
```

### Autenticación
El servidor remoto debe validar el token de autorización:
```php
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if ($token !== 'tu-api-key-aqui') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
```

## Seguridad

1. **Validación de archivos**: Solo imágenes permitidas
2. **Límite de tamaño**: Máximo 5MB
3. **Nombres únicos**: Evita conflictos de archivos
4. **Autenticación**: Token requerido para todas las operaciones
5. **Sanitización**: Nombres de archivo seguros

## Monitoreo y Logs

El servicio registra automáticamente:
- Intentos de subida exitosos
- Errores de validación
- Fallos de conexión con servidor remoto
- Tamaños de archivos procesados

Revisa los logs en `storage/logs/laravel.log` para debugging.
