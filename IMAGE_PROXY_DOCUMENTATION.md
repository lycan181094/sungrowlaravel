# Sistema de Proxy de Imágenes

## Descripción

Este sistema permite servir imágenes remotas a través de URLs locales, manteniendo la URL del navegador sin cambios. Es útil para:

- Mantener URLs consistentes y amigables
- Evitar problemas de CORS
- Controlar el acceso a imágenes
- Aplicar cache y optimizaciones

## Funcionamiento

### URL Local vs URL Remota

**URL Local (lo que ve el usuario):**
```
http://localhost:8000/images/noticia-de-prueba
```

**URL Remota (donde está realmente la imagen):**
```
https://myimagesexample.solucionesgt360.com/myimages/test/artworks-000131373782-zdklbd-t500x500.jpg
```

### Flujo del Sistema

1. **Usuario accede** a `http://localhost:8000/images/noticia-de-prueba`
2. **Laravel busca** la noticia por slug `noticia-de-prueba`
3. **Obtiene la URL remota** de la noticia (`ruta` field)
4. **Descarga la imagen** del servidor remoto
5. **Sirve la imagen** con headers apropiados
6. **URL del navegador** permanece como `localhost:8000/images/noticia-de-prueba`

## Componentes

### 1. Ruta Web (`routes/web.php`)
```php
Route::get('/images/{slug}', [ImageProxyController::class, 'show'])->name('images.show');
```

### 2. Controlador (`ImageProxyController.php`)
- Busca la noticia por slug
- Descarga la imagen remota
- Sirve la imagen con headers correctos
- Maneja errores gracefully

### 3. Headers de Respuesta
```
Content-Type: image/jpeg
Content-Length: 12345
Cache-Control: public, max-age=3600
Last-Modified: Wed, 06 Oct 2024 10:30:00 GMT
X-Original-URL: https://example.com/image.jpg
X-Proxy-Status: success
```

## Ventajas

### ✅ **URLs Consistentes**
- El usuario siempre ve `localhost:8000/images/slug`
- No hay redirecciones que cambien la URL

### ✅ **Control de Acceso**
- Puedes agregar autenticación si es necesario
- Logs de acceso a imágenes
- Control de rate limiting

### ✅ **Cache y Performance**
- Cache headers para optimización
- Compresión automática
- Timeout configurable

### ✅ **Manejo de Errores**
- Imagen placeholder en caso de error
- Logs detallados de problemas
- Fallback graceful

## Configuración

### Timeout
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 segundos
```

### Cache
```php
->header('Cache-Control', 'public, max-age=3600') // 1 hora
```

### User Agent
```php
curl_setopt($ch, CURLOPT_USERAGENT, 'Laravel Image Proxy/1.0');
```

## Uso en el Frontend

### En Angular
```typescript
// La URL se mantiene local
const imageUrl = `http://localhost:8000/images/${news.slug}`;

// En el template
<img [src]="imageUrl" [alt]="news.titulo">
```

### En HTML Directo
```html
<img src="http://localhost:8000/images/noticia-de-prueba" alt="Noticia">
```

## Debugging

### Headers de Debug
- `X-Original-URL`: URL remota original
- `X-Proxy-Status`: Estado del proxy (success/error)
- `X-Error-Message`: Mensaje de error si falla

### Logs
```bash
tail -f storage/logs/laravel.log
```

## Alternativas

### 1. Redirect Simple (NO recomendado)
```php
return redirect($news->ruta); // Cambia la URL del navegador
```

### 2. Iframe (NO recomendado)
```html
<iframe src="url-remota"></iframe> <!-- Problemas de CORS -->
```

### 3. Proxy con Cache (Recomendado)
```php
// Cache local de imágenes
$cachedImage = Cache::get("image_{$slug}");
if (!$cachedImage) {
    $cachedImage = $this->fetchRemoteImage($news->ruta);
    Cache::put("image_{$slug}", $cachedImage, 3600);
}
```

## Consideraciones de Seguridad

### 1. Validación de URLs
```php
// Validar que la URL sea de un dominio permitido
$allowedDomains = ['example.com', 'images.example.com'];
$urlHost = parse_url($news->ruta, PHP_URL_HOST);
if (!in_array($urlHost, $allowedDomains)) {
    abort(403, 'Dominio no permitido');
}
```

### 2. Rate Limiting
```php
// En routes/web.php
Route::get('/images/{slug}', [ImageProxyController::class, 'show'])
    ->middleware('throttle:60,1'); // 60 requests por minuto
```

### 3. Autenticación
```php
// Si necesitas autenticación
Route::get('/images/{slug}', [ImageProxyController::class, 'show'])
    ->middleware('auth:sanctum');
```

## Monitoreo

### Métricas Importantes
- Tiempo de respuesta del proxy
- Tasa de éxito de descarga
- Uso de cache
- Errores de timeout

### Alertas
- Imágenes que fallan consistentemente
- Timeouts frecuentes
- Uso excesivo de ancho de banda

## Optimizaciones Futuras

1. **Cache Local**: Guardar imágenes en storage local
2. **Compresión**: Redimensionar imágenes automáticamente
3. **CDN**: Usar un CDN para distribución
4. **WebP**: Convertir automáticamente a WebP
5. **Lazy Loading**: Cargar imágenes bajo demanda
