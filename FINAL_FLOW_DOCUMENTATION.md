# Flujo Final - Subida de Archivos y Generación de Links

## Flujo Implementado

### 1. **Frontend envía archivo + nombre + datos de noticia (SIN link_final)**
### 2. **API sube archivo al servidor remoto**
### 3. **API guarda noticia en BD con ruta del servidor remoto**
### 4. **API genera link_final automáticamente usando ruta Laravel**
### 5. **API devuelve noticia completa con ambos links**

## Estructura de URLs

- **`ruta`**: URL directa al servidor remoto
  - Ejemplo: `https://miservidorremoto.com/myimage.jpg`
- **`link_final`**: URL de tu API Laravel que redirige al servidor remoto
  - Ejemplo: `https://tu-api-laravel.com/images/1`

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
        "link_final": "https://tu-api-laravel.com/images/1",
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
formData.append('fecha_hora', '2024-01-01T10:00:00');
formData.append('user_id', '1');
// NO se envía link_final

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

### 3. **Construcción de URL del Servidor Remoto**
```php
// La API construye la URL del servidor remoto:
$remoteUrl = rtrim($this->remoteBaseUrl, '/') . '/' . $filename;
// Resultado: https://tu-servidor-remoto.com/uploads/mi-imagen.jpg
```

### 4. **Guardado en Base de Datos**
```php
$newsData = [
    'titulo' => $request->input('titulo'),
    'sub_titulo' => $request->input('sub_titulo'),
    'ruta' => $remoteUrl, // URL del servidor remoto
    'fecha_hora' => $request->input('fecha_hora'),
    'user_id' => $request->input('user_id')
];

$news = News::create($newsData);
```

### 5. **Generación de Link Final**
```php
// Generar link_final usando ruta Laravel
$news->link_final = route('images.show', $news->id);
$news->save();
// Resultado: https://tu-api-laravel.com/images/1
```

### 6. **Ruta Laravel para Servir Imágenes**
```php
// En routes/web.php
Route::get('/images/{id}', function ($id) {
    $news = \App\Models\News::find($id);
    
    if (!$news) {
        abort(404, 'Imagen no encontrada');
    }
    
    // Redirigir a la URL del servidor remoto
    return redirect($news->ruta);
})->name('images.show');
```

## Ejemplo de Uso Completo

### Frontend (JavaScript)
```javascript
async function createNewsWithImage(file, newsData) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('filename', 'noticia-' + Date.now() + '.jpg');
    formData.append('titulo', newsData.titulo);
    formData.append('sub_titulo', newsData.sub_titulo);
    formData.append('fecha_hora', newsData.fecha_hora);
    formData.append('user_id', newsData.user_id);
    // NO se envía link_final

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
            console.log('URL directa:', result.data.ruta);
            console.log('URL Laravel:', result.data.link_final);
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
    fecha_hora: new Date().toISOString(),
    user_id: 1
};

createNewsWithImage(file, newsData);
```

## Ventajas del Flujo Final

1. **✅ Frontend simplificado**: No necesita enviar link_final
2. **✅ URLs predecibles**: Sabes exactamente dónde estará cada archivo
3. **✅ Control total**: Puedes cambiar el servidor remoto sin afectar el frontend
4. **✅ URLs amigables**: `/images/1` es más limpio que URLs largas
5. **✅ Flexibilidad**: Puedes cambiar la lógica de redirección
6. **✅ Transaccional**: Todo o nada
7. **✅ Eficiente**: Un solo endpoint para todo

## Estructura de Base de Datos Final

```sql
news table:
- id (Primary Key)
- titulo (string)
- sub_titulo (string)
- ruta (string) - URL del servidor remoto
- link_final (string) - URL de Laravel que redirige
- fecha_hora (timestamp)
- user_id (Foreign Key)
- created_at (timestamp)
- updated_at (timestamp)
```

## Ejemplo de Respuesta Completa

```json
{
    "success": true,
    "message": "Archivo subido y noticia guardada exitosamente",
    "data": {
        "id": 1,
        "titulo": "Mi Noticia",
        "sub_titulo": "Subtítulo de la noticia",
        "ruta": "https://tu-servidor-remoto.com/uploads/mi-imagen.jpg",
        "link_final": "https://tu-api-laravel.com/images/1",
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

**¡El flujo está completamente implementado según tus especificaciones!** 🎉

- **Frontend**: Solo envía archivo + datos (sin link_final)
- **API**: Sube archivo + guarda en BD + genera link_final automáticamente
- **Resultado**: Dos URLs disponibles (directa y Laravel)
