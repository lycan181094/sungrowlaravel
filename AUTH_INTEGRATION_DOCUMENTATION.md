# Documentación de Integración de Autenticación con API Externa (Tibaan)

## Descripción General

Este sistema implementa un middleware de autenticación que actúa como intermediario entre tu frontend Angular y la API externa de Tibaan. Laravel consume los endpoints de autenticación externos y proporciona tokens locales para las rutas protegidas.

## Arquitectura

```
Frontend Angular → Laravel API → API Externa Tibaan
```

### Flujo de Autenticación

1. **Login**: El frontend envía credenciales a Laravel
2. **Validación Externa**: Laravel valida las credenciales con la API de Tibaan
3. **Token Local**: Si la validación es exitosa, Laravel crea un token local (Sanctum)
4. **Respuesta Dual**: Laravel devuelve tanto el token local como el externo

## Configuración

### Variables de Entorno

Agrega estas variables a tu archivo `.env`:

```env
# API Externa de Tibaan
TIBAAN_API_URL=https://intergrow.site/api
TIBAAN_API_TIMEOUT=30
```

### Instalación de Dependencias

Asegúrate de tener Guzzle HTTP instalado (ya viene con Laravel):

```bash
composer require guzzlehttp/guzzle
```

## Endpoints Implementados

### Autenticación

#### POST `/api/auth/login`
- **Descripción**: Autentica al usuario con la API externa de Tibaan
- **Headers**: `Content-Type: application/json`
- **Body**:
```json
{
    "email": "gt360@inergrow.site",
    "password": "Gt2025."
}
```
- **Respuesta**:
```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "user": {...},
        "token": "token_local_sanctum",
        "external_token": "token_externo_tibaan",
        "token_type": "Bearer"
    }
}
```

#### POST `/api/auth/logout`
- **Descripción**: Cierra sesión tanto local como externamente
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}` (opcional)
- **Respuesta**:
```json
{
    "success": true,
    "message": "Logout exitoso"
}
```

### Webs Views (Requieren Token Externo)

#### GET `/api/webs-views`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

#### POST `/api/webs-views`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`
- **Body**:
```json
{
    "web_url": "test1",
    "web_name": "test1",
    "web_descri": "Prueba",
    "web_roles": [9],
    "web_site": 3,
    "web_status": 1
}
```

#### PUT `/api/webs-views/{id}`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

#### DELETE `/api/webs-views/{id}`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

### Webs Views Detail (Requieren Token Externo)

#### GET `/api/webs-views-detail/{web_id}`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

#### POST `/api/webs-views-detail`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`
- **Body**: FormData con archivos
  - `web_id`: string
  - `det_type`: "url" | "video" | "image"
  - `det_url`: string (si det_type = url)
  - `det_timer`: number
  - `det_order`: number
  - `det_status`: 1 | 0
  - `det_video`: file (si det_type = video)
  - `det_video_extension`: string (si det_type = video)
  - `det_image`: file (si det_type = image)
  - `det_image_extension`: string (si det_type = image)

#### PUT `/api/webs-views-detail/{id}`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

#### DELETE `/api/webs-views-detail/{id}`
- **Headers**: 
  - `Authorization: Bearer {token_local}`
  - `X-External-Token: {token_externo}`

## Uso desde Angular

### Servicio de Autenticación

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://tu-laravel-api.com/api';
  private tokenSubject = new BehaviorSubject<string | null>(null);
  private externalTokenSubject = new BehaviorSubject<string | null>(null);

  constructor(private http: HttpClient) {
    // Recuperar tokens del localStorage
    const token = localStorage.getItem('token');
    const externalToken = localStorage.getItem('external_token');
    if (token) this.tokenSubject.next(token);
    if (externalToken) this.externalTokenSubject.next(externalToken);
  }

  login(email: string, password: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/login`, {
      email,
      password
    }).pipe(
      tap((response: any) => {
        if (response.success) {
          const { token, external_token } = response.data;
          localStorage.setItem('token', token);
          localStorage.setItem('external_token', external_token);
          this.tokenSubject.next(token);
          this.externalTokenSubject.next(external_token);
        }
      })
    );
  }

  logout(): Observable<any> {
    const headers = this.getAuthHeaders();
    return this.http.post(`${this.apiUrl}/auth/logout`, {}, { headers }).pipe(
      tap(() => {
        localStorage.removeItem('token');
        localStorage.removeItem('external_token');
        this.tokenSubject.next(null);
        this.externalTokenSubject.next(null);
      })
    );
  }

  private getAuthHeaders(): HttpHeaders {
    const token = this.tokenSubject.value;
    const externalToken = this.externalTokenSubject.value;
    
    let headers = new HttpHeaders();
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }
    if (externalToken) {
      headers = headers.set('X-External-Token', externalToken);
    }
    
    return headers;
  }

  getToken(): string | null {
    return this.tokenSubject.value;
  }

  getExternalToken(): string | null {
    return this.externalTokenSubject.value;
  }

  isAuthenticated(): boolean {
    return !!this.tokenSubject.value;
  }
}
```

### Servicio para Webs Views

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class WebsViewsService {
  private apiUrl = 'http://tu-laravel-api.com/api';

  constructor(private http: HttpClient, private authService: AuthService) {}

  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken();
    const externalToken = this.authService.getExternalToken();
    
    let headers = new HttpHeaders();
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }
    if (externalToken) {
      headers = headers.set('X-External-Token', externalToken);
    }
    
    return headers;
  }

  getWebsViews(): Observable<any> {
    return this.http.get(`${this.apiUrl}/webs-views`, {
      headers: this.getHeaders()
    });
  }

  createWebView(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/webs-views`, data, {
      headers: this.getHeaders()
    });
  }

  updateWebView(id: string, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/webs-views/${id}`, data, {
      headers: this.getHeaders()
    });
  }

  deleteWebView(id: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/webs-views/${id}`, {
      headers: this.getHeaders()
    });
  }
}
```

## Middleware y Validación

### ValidateExternalToken Middleware

Este middleware valida que el token externo sea válido antes de permitir el acceso a las rutas protegidas:

- Verifica la presencia del header `X-External-Token`
- Valida el token con la API externa de Tibaan
- Permite o deniega el acceso según la respuesta

### Logs y Monitoreo

El sistema registra logs detallados para:
- Intentos de login exitosos y fallidos
- Errores de conexión con la API externa
- Validaciones de tokens
- Errores en peticiones a endpoints externos

Los logs se encuentran en `storage/logs/laravel.log`.

## Manejo de Errores

### Códigos de Estado HTTP

- **200**: Operación exitosa
- **401**: No autenticado o token inválido
- **422**: Error de validación
- **500**: Error interno del servidor
- **503**: Error de conexión con API externa

### Respuestas de Error

```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {...} // Solo en errores de validación
}
```

## Consideraciones de Seguridad

1. **Tokens Locales**: Se usan para autenticación en Laravel
2. **Tokens Externos**: Se usan para validación con la API de Tibaan
3. **Timeouts**: Configurables para evitar bloqueos
4. **Logs**: Registro detallado de todas las operaciones
5. **Validación**: Validación tanto local como externa

## Testing

### Ejemplo de Prueba con Postman

1. **Login**:
   - POST `{{url}}/api/auth/login`
   - Body: `{"email": "gt360@inergrow.site", "password": "Gt2025."}`

2. **Usar Token**:
   - Header: `Authorization: Bearer {token_local}`
   - Header: `X-External-Token: {token_externo}`

3. **Acceder a Webs Views**:
   - GET `{{url}}/api/webs-views`

## Troubleshooting

### Problemas Comunes

1. **Error 503**: Verificar conectividad con la API externa
2. **Error 401**: Verificar que los tokens sean válidos
3. **Timeout**: Aumentar el valor de `TIBAAN_API_TIMEOUT`

### Verificación de Configuración

```bash
# Verificar configuración
php artisan config:cache
php artisan route:list
```

## Mantenimiento

- Monitorear logs regularmente
- Verificar conectividad con la API externa
- Actualizar tokens cuando sea necesario
- Mantener las dependencias actualizadas
