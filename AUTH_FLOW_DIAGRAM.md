# Diagrama de Flujo de Autenticación

## Flujo de Login

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Laravel API   │    │   API Tibaan    │
│   Angular       │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │ 1. POST /auth/login   │                       │
         │ {email, password}     │                       │
         ├──────────────────────►│                       │
         │                       │                       │
         │                       │ 2. POST /auth/login   │
         │                       │ {email, password}     │
         │                       ├──────────────────────►│
         │                       │                       │
         │                       │ 3. Response           │
         │                       │ {success, token}      │
         │                       │◄──────────────────────┤
         │                       │                       │
         │                       │ 4. Create/Find User   │
         │                       │ 5. Generate Local     │
         │                       │    Token (Sanctum)    │
         │                       │                       │
         │ 6. Response           │                       │
         │ {success, data: {     │                       │
         │   user,              │                       │
         │   token (local),     │                       │
         │   external_token     │                       │
         │ }}                   │                       │
         │◄──────────────────────┤                       │
         │                       │                       │
```

## Flujo de Acceso a Rutas Protegidas

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Laravel API   │    │   API Tibaan    │
│   Angular       │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │ 1. GET /webs-views    │                       │
         │ Headers:              │                       │
         │ - Authorization:      │                       │
         │   Bearer {local}      │                       │
         │ - X-External-Token:   │                       │
         │   {external}          │                       │
         ├──────────────────────►│                       │
         │                       │                       │
         │                       │ 2. Validate Local     │
         │                       │    Token (Sanctum)    │
         │                       │                       │
         │                       │ 3. Validate External  │
         │                       │    Token              │
         │                       │    GET /webs-views    │
         │                       ├──────────────────────►│
         │                       │                       │
         │                       │ 4. Response           │
         │                       │ {success, data}       │
         │                       │◄──────────────────────┤
         │                       │                       │
         │                       │ 5. Forward Response   │
         │                       │                       │
         │ 6. Response           │                       │
         │ {success, data}       │                       │
         │◄──────────────────────┤                       │
         │                       │                       │
```

## Flujo de Logout

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Laravel API   │    │   API Tibaan    │
│   Angular       │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │ 1. POST /auth/logout  │                       │
         │ Headers:              │                       │
         │ - Authorization:      │                       │
         │   Bearer {local}      │                       │
         │ - X-External-Token:   │                       │
         │   {external}          │                       │
         ├──────────────────────►│                       │
         │                       │                       │
         │                       │ 2. POST /auth/logout  │
         │                       │ Bearer {external}     │
         │                       ├──────────────────────►│
         │                       │                       │
         │                       │ 3. Response           │
         │                       │ {success}             │
         │                       │◄──────────────────────┤
         │                       │                       │
         │                       │ 4. Revoke Local       │
         │                       │    Token (Sanctum)    │
         │                       │                       │
         │ 5. Response           │                       │
         │ {success, message}    │                       │
         │◄──────────────────────┤                       │
         │                       │                       │
```

## Componentes del Sistema

### 1. TibaanApiService
- Maneja todas las comunicaciones con la API externa
- Métodos: login(), logout(), validateToken(), getUserInfo()
- Manejo de errores y timeouts

### 2. AuthController
- Endpoints de autenticación local
- Integración con TibaanApiService
- Creación de usuarios locales
- Generación de tokens Sanctum

### 3. ValidateExternalToken Middleware
- Valida tokens externos antes de acceder a rutas protegidas
- Verifica conectividad con API externa
- Manejo de errores de validación

### 4. WebsViewsController & WebsViewsDetailController
- Proxies para endpoints de webs-views
- Validación de tokens externos
- Manejo de archivos y form-data

## Estados de Autenticación

### Usuario No Autenticado
- No tiene tokens locales ni externos
- Solo puede acceder a rutas públicas
- Debe hacer login para obtener tokens

### Usuario Autenticado Localmente
- Tiene token local (Sanctum)
- Puede acceder a rutas protegidas básicas
- No puede acceder a webs-views sin token externo

### Usuario Completamente Autenticado
- Tiene token local (Sanctum)
- Tiene token externo (Tibaan)
- Puede acceder a todas las rutas protegidas
- Puede usar endpoints de webs-views

## Manejo de Errores

### Error de Conectividad
- API externa no disponible
- Timeout en peticiones
- Respuesta: 503 Service Unavailable

### Token Inválido
- Token externo expirado o inválido
- Token local revocado
- Respuesta: 401 Unauthorized

### Error de Validación
- Datos de entrada incorrectos
- Archivos faltantes o inválidos
- Respuesta: 422 Unprocessable Entity

## Seguridad

### Tokens Locales (Sanctum)
- Almacenados en base de datos
- Revocables individualmente
- Usados para autenticación en Laravel

### Tokens Externos (Tibaan)
- Validados en tiempo real
- No almacenados localmente
- Usados para comunicación con API externa

### Headers de Seguridad
- Authorization: Bearer {local_token}
- X-External-Token: {external_token}
- Validación en cada petición
