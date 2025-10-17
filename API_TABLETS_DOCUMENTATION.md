# API de News - Documentación

## Descripción
API REST para el manejo de noticias con autenticación mediante Laravel Sanctum.

## Estructura de Base de Datos

### Tabla `users`
- `id` (Primary Key)
- `name`
- `email`
- `password`
- `email_verified_at`
- `remember_token`
- `created_at`
- `updated_at`

### Tabla `news`
- `id` (Primary Key)
- `titulo` (string, required)
- `sub_titulo` (string, required)
- `ruta` (string, required)
- `link_final` (string, required)
- `fecha_hora` (timestamp, required)
- `user_id` (Foreign Key to users table)
- `created_at` (timestamp, required)
- `updated_at` (timestamp)

## Endpoints de la API

### Autenticación
Todas las rutas requieren autenticación mediante Laravel Sanctum.

### Rutas disponibles:

#### 1. Listar todas las noticias
```
GET /api/news
```
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "titulo": "Tablet 1",
            "sub_titulo": "Subtítulo 1",
            "ruta": "https://example.com/ruta1",
            "link_final": "https://example.com/final1",
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
    ]
}
```

#### 2. Crear una nueva noticia
```
POST /api/news
```
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "titulo": "Nueva Noticia",
    "sub_titulo": "Subtítulo de la nueva noticia",
    "ruta": "https://example.com/nueva-ruta",
    "link_final": "https://example.com/nuevo-final",
    "fecha_hora": "2024-01-01T10:00:00",
    "user_id": 1
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "message": "Noticia creada exitosamente",
    "data": {
        "id": 2,
        "titulo": "Nueva Noticia",
        "sub_titulo": "Subtítulo de la nueva noticia",
        "ruta": "https://example.com/nueva-ruta",
        "link_final": "https://example.com/nuevo-final",
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

#### 3. Obtener una noticia específica
```
GET /api/news/{id}
```
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "titulo": "Noticia 1",
        "sub_titulo": "Subtítulo 1",
        "ruta": "https://example.com/ruta1",
        "link_final": "https://example.com/final1",
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

#### 4. Actualizar una noticia
```
PUT /api/news/{id}
PATCH /api/news/{id}
```
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (todos los campos son opcionales):**
```json
{
    "titulo": "Noticia Actualizada",
    "sub_titulo": "Nuevo subtítulo",
    "ruta": "https://example.com/nueva-ruta-actualizada",
    "link_final": "https://example.com/nuevo-final-actualizado",
    "fecha_hora": "2024-01-02T15:30:00",
    "user_id": 1
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Noticia actualizada exitosamente",
    "data": {
        "id": 1,
        "titulo": "Noticia Actualizada",
        "sub_titulo": "Nuevo subtítulo",
        "ruta": "https://example.com/nueva-ruta-actualizada",
        "link_final": "https://example.com/nuevo-final-actualizado",
        "fecha_hora": "2024-01-02T15:30:00.000000Z",
        "user_id": 1,
        "created_at": "2024-01-01T10:00:00.000000Z",
        "updated_at": "2024-01-02T15:30:00.000000Z",
        "user": {
            "id": 1,
            "name": "Usuario",
            "email": "usuario@example.com"
        }
    }
}
```

#### 5. Eliminar una noticia
```
DELETE /api/news/{id}
```
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Noticia eliminada exitosamente"
}
```

## Códigos de Respuesta

- **200**: Éxito
- **201**: Creado exitosamente
- **404**: Recurso no encontrado
- **422**: Error de validación
- **401**: No autorizado (token inválido o faltante)

## Errores de Validación

Cuando hay errores de validación, la API devuelve:

```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "titulo": ["El campo titulo es obligatorio."],
        "user_id": ["El campo user_id debe existir en la tabla users."]
    }
}
```

## Campos de Auditoría

La tabla `news` incluye automáticamente:
- `created_at`: Fecha y hora de creación
- `updated_at`: Fecha y hora de última actualización

Estos campos se manejan automáticamente por Laravel y no requieren ser enviados en las peticiones.

## Configuración de Base de Datos

Para ejecutar las migraciones:

```bash
php artisan migrate
```

## Autenticación

Para usar la API, necesitas:
1. Registrar un usuario
2. Obtener un token de autenticación
3. Incluir el token en el header `Authorization: Bearer {token}`

Ejemplo de obtención de token:
```bash
POST /api/login
{
    "email": "usuario@example.com",
    "password": "password"
}
```
