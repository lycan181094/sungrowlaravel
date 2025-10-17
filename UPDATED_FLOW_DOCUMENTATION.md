# Flujo Actualizado - Subida de Archivos y Guardado en BD

## Flujo Implementado

### 1. **Frontend envía archivo + datos de noticia**
### 2. **API sube archivo al servidor remoto**
### 3. **API construye URL final usando nombre de archivo + URL base**
### 4. **API guarda noticia en base de datos con la URL**
### 5. **API devuelve status OK al frontend**

## Configuración Requerida

### Variables de Entorno (.env)
```env
# URL del servidor remoto para subir archivos
REMOTE_SERVER_URL=https://tu-servidor-remoto.com

# API Key para autenticación
REMOTE_SERVER_API_KEY=tu-api-key-secreta

# URL base donde estarán disponibles los archivos
REMOTE_SERVER_BASE_URL=https://tu-servidor-remoto.com/uploads
```

## Endpoints Disponibles

### 1. Subir Archivo + Guardar Noticia (Multipart)
```
POST /api/news/upload-and-save
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data):**
- `file`: Archivo de imagen (jpg, jpeg, png, gif)
- `filename`: Nombre del archivo (requerido)
- `titulo`: Título de la noticia (requerido)
- `sub_titulo`: Subtítulo de la noticia (requerido)
- `link_final`: Link final (requerido)
- `fecha_hora`: Fecha y hora (requerido)
- `user_id`: ID del usuario (requerido)

**Ejemplo con cURL:**
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "file=@/ruta/a/imagen.jpg" \
  -F "filename=mi-imagen.jpg" \
  -F "titulo=Mi Noticia" \
  -F "sub_titulo=Subtítulo de la noticia" \
  -F "link_final=https://example.com/final" \
  -F "fecha_hora=2024-01-01T10:00:00" \
  -F "user_id=1" \
  https://tu-api.com/api/news/upload-and-save
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "message": "Archivo subido y noticia guardada exitosamente",
    "data": {
        "id": 1,
        "titulo": "Mi Noticia",
        "sub_titulo": "Subtítulo de la noticia",
        "ruta": "https://tu-servidor-remoto.com/uploads/mi-imagen.jpg",
        "link_final": "https://example.com/final",
        "fecha_hora": "2024-01-01T10:00:00.000000Z",
        "user_id": 1,
        "created_at": "2024-01-01T10:00:00.000000Z",
        "updated_at": "2024-01-01T10:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "Usuario",
            "email": "usuario@example.com"
        }
    }
}
```

### 2. Subir Archivo + Guardar Noticia (Base64)
```
POST /api/news/upload-base64-and-save
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
    "filename": "mi-imagen.jpg",
    "titulo": "Mi Noticia",
    "sub_titulo": "Subtítulo de la noticia",
    "link_final": "https://example.com/final",
    "fecha_hora": "2024-01-01T10:00:00",
    "user_id": 1
}
```

## Flujo Técnico Detallado

### 1. **Frontend → API Laravel**
```javascript
const formData = new FormData();
formData.append('file', file);
formData.append('filename', 'mi-imagen.jpg');
formData.append('titulo', 'Mi Noticia');
formData.append('sub_titulo', 'Subtítulo');
formData.append('link_final', 'https://example.com/final');
formData.append('fecha_hora', '2024-01-01T10:00:00');
formData.append('user_id', '1');

fetch('/api/news/upload-and-save', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token },
    body: formData
});
```

### 2. **API Laravel → Servidor Remoto**
```php
// El servicio sube el archivo al servidor remoto
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $this->remoteApiKey
])->attach('file', $file->getContent(), $filename)
  ->post($this->remoteServerUrl . '/api/upload');
```

### 3. **Construcción de URL Final**
```php
// La API construye la URL final usando:
// REMOTE_SERVER_BASE_URL + filename
$finalUrl = rtrim($this->remoteBaseUrl, '/') . '/' . $filename;
// Resultado: https://tu-servidor-remoto.com/uploads/mi-imagen.jpg
```

### 4. **Guardado en Base de Datos**
```php
$newsData = [
    'titulo' => $request->input('titulo'),
    'sub_titulo' => $request->input('sub_titulo'),
    'ruta' => $finalUrl, // URL construida
    'link_final' => $request->input('link_final'),
    'fecha_hora' => $request->input('fecha_hora'),
    'user_id' => $request->input('user_id')
];

$news = News::create($newsData);
```

### 5. **Respuesta al Frontend**
```json
{
    "success": true,
    "message": "Archivo subido y noticia guardada exitosamente",
    "data": { /* datos de la noticia guardada */ }
}
```

## Servidor Remoto Requerido

Tu servidor remoto debe tener un endpoint que:

### 1. **Acepte archivos via POST**
```
POST /api/upload
```

### 2. **Requiera autenticación**
```
Authorization: Bearer {REMOTE_SERVER_API_KEY}
```

### 3. **Devuelva solo éxito**
```json
{
    "success": true
}
```

### Ejemplo de Servidor Remoto (PHP)
```php
<?php
header('Content-Type: application/json');

// Verificar autenticación
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if ($token !== 'tu-api-key-secreta') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Verificar archivo
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];
$filename = $file['name'];

// Crear directorio si no existe
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$targetPath = $uploadDir . $filename;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
}
?>
```

## Ventajas del Flujo Implementado

1. **✅ Un solo endpoint**: Sube archivo y guarda noticia en una sola petición
2. **✅ URL predecible**: Sabes exactamente dónde estará el archivo
3. **✅ Transaccional**: Si falla la subida, no se guarda en BD
4. **✅ Eficiente**: Menos peticiones HTTP
5. **✅ Seguro**: Validaciones completas en un solo lugar

## Ejemplo de Uso Completo

### Frontend (JavaScript)
```javascript
async function createNewsWithImage(file, newsData) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('filename', 'noticia-' + Date.now() + '.jpg');
    formData.append('titulo', newsData.titulo);
    formData.append('sub_titulo', newsData.sub_titulo);
    formData.append('link_final', newsData.link_final);
    formData.append('fecha_hora', newsData.fecha_hora);
    formData.append('user_id', newsData.user_id);

    try {
        const response = await fetch('/api/news/upload-and-save', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token')
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            console.log('Noticia creada:', result.data);
            return result.data;
        } else {
            console.error('Error:', result.message);
            return null;
        }
    } catch (error) {
        console.error('Error de red:', error);
        return null;
    }
}

// Uso
const file = document.getElementById('fileInput').files[0];
const newsData = {
    titulo: 'Mi Noticia',
    sub_titulo: 'Subtítulo',
    link_final: 'https://example.com/final',
    fecha_hora: new Date().toISOString(),
    user_id: 1
};

createNewsWithImage(file, newsData);
```

**¡El flujo está completamente implementado y listo para usar!** 🎉
