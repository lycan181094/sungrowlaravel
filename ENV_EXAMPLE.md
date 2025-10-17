# Configuración de Variables de Entorno

Crea un archivo `.env` en la raíz del proyecto con las siguientes variables:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Configuración del servidor remoto
REMOTE_SERVER_UPLOAD_METHOD=http
REMOTE_SERVER_URL=https://tu-servidor-api.com
REMOTE_SERVER_API_KEY=tu-api-key-secreta
REMOTE_SERVER_BASE_URL=https://tu-servidor-api.com/uploads

# Configuración de la API Externa de Tibaan
TIBAAN_API_URL=https://intergrow.site/api
TIBAAN_API_TIMEOUT=30

# Configuración FTP (para hosting compartido)
# REMOTE_SERVER_UPLOAD_METHOD=ftp
# REMOTE_SERVER_FTP_HOST=midominio.com
# REMOTE_SERVER_FTP_USERNAME=usuario123
# REMOTE_SERVER_FTP_PASSWORD=password123
# REMOTE_SERVER_FTP_PORT=21
# REMOTE_SERVER_FTP_DIRECTORY=/public_html/uploads
# REMOTE_SERVER_BASE_URL=https://midominio.com/uploads
```

## Configuración para Desarrollo

Para desarrollo local, puedes usar valores de ejemplo:

```env
REMOTE_SERVER_UPLOAD_METHOD=http
REMOTE_SERVER_URL=https://httpbin.org
REMOTE_SERVER_API_KEY=test-key
REMOTE_SERVER_BASE_URL=https://httpbin.org/uploads
```

## Configuración para Producción

Para producción, configura las variables reales:

```env
REMOTE_SERVER_UPLOAD_METHOD=ftp
REMOTE_SERVER_FTP_HOST=tu-dominio.com
REMOTE_SERVER_FTP_USERNAME=tu-usuario
REMOTE_SERVER_FTP_PASSWORD=tu-password
REMOTE_SERVER_FTP_PORT=21
REMOTE_SERVER_FTP_DIRECTORY=/public_html/uploads
REMOTE_SERVER_BASE_URL=https://tu-dominio.com/uploads
```
