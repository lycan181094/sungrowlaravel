# Configuración FTP para Hosting Compartido

## ✅ **Sistema Compatible con FTP**

El servicio ahora soporta **FTP** para hosting compartido. Puedes usar tanto HTTP API como FTP.

## 🔧 **Configuración Requerida**

### Variables de Entorno (.env)

#### **Para FTP (Hosting Compartido)**
```env
# Método de subida
REMOTE_SERVER_UPLOAD_METHOD=ftp

# Configuración FTP
REMOTE_SERVER_FTP_HOST=tu-servidor.com
REMOTE_SERVER_FTP_USERNAME=tu-usuario-ftp
REMOTE_SERVER_FTP_PASSWORD=tu-password-ftp
REMOTE_SERVER_FTP_PORT=21
REMOTE_SERVER_FTP_DIRECTORY=/public_html/uploads

# URL base donde estarán disponibles los archivos
REMOTE_SERVER_BASE_URL=https://tu-servidor.com/uploads
```

#### **Para HTTP API (Servidor con API)**
```env
# Método de subida
REMOTE_SERVER_UPLOAD_METHOD=http

# Configuración HTTP
REMOTE_SERVER_URL=https://tu-servidor.com
REMOTE_SERVER_API_KEY=tu-api-key
REMOTE_SERVER_BASE_URL=https://tu-servidor.com/uploads
```

## 🏠 **Configuración para Hosting Compartido**

### **1. Crear Carpeta de Subidas**
En tu hosting compartido:
```
/public_html/uploads/
```

### **2. Permisos de Carpeta**
```bash
chmod 755 /public_html/uploads/
```

### **3. Configuración .env**
```env
REMOTE_SERVER_UPLOAD_METHOD=ftp
REMOTE_SERVER_FTP_HOST=tu-dominio.com
REMOTE_SERVER_FTP_USERNAME=tu-usuario-cpanel
REMOTE_SERVER_FTP_PASSWORD=tu-password-cpanel
REMOTE_SERVER_FTP_PORT=21
REMOTE_SERVER_FTP_DIRECTORY=/public_html/uploads
REMOTE_SERVER_BASE_URL=https://tu-dominio.com/uploads
```

## 🔄 **Flujo de Funcionamiento**

### **1. Frontend → API Laravel**
```javascript
const formData = new FormData();
formData.append('file', file);
formData.append('filename', 'mi-imagen.jpg');
formData.append('titulo', 'Mi Noticia');
formData.append('sub_titulo', 'Subtítulo');
formData.append('user_id', '1');

fetch('/api/news/upload-and-save', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token },
    body: formData
});
```

### **2. API Laravel → FTP Server**
```php
// El servicio se conecta via FTP
$ftpConnection = ftp_connect($this->ftpHost, $this->ftpPort);
ftp_login($ftpConnection, $this->ftpUsername, $this->ftpPassword);
ftp_pasv($ftpConnection, true); // Modo pasivo
ftp_chdir($ftpConnection, '/public_html/uploads');
ftp_put($ftpConnection, $filename, $tempFile, FTP_BINARY);
```

### **3. URLs Resultantes**
- **`ruta`**: `https://tu-dominio.com/uploads/mi-imagen.jpg`
- **`link_final`**: `https://tu-api-laravel.com/images/mi-noticia-importante`

## 🎯 **Ventajas del Sistema FTP**

1. **✅ Compatible con hosting compartido**: No necesitas servidor dedicado
2. **✅ Fácil configuración**: Solo credenciales FTP
3. **✅ Costo bajo**: Usa hosting compartido existente
4. **✅ Acceso directo**: Archivos disponibles inmediatamente
5. **✅ Sin API adicional**: No necesitas programar servidor remoto

## 🔧 **Configuración Avanzada**

### **Modo Pasivo FTP**
```php
ftp_pasv($ftpConnection, true); // Recomendado para hosting compartido
```

### **Creación Automática de Directorios**
```php
// El sistema crea automáticamente las carpetas si no existen
$this->createFTPDirectory($ftpConnection, '/public_html/uploads');
```

### **Limpieza de Archivos Temporales**
```php
// Los archivos temporales se eliminan automáticamente
unlink($tempFile);
ftp_close($ftpConnection);
```

## 📋 **Ejemplo de Uso Completo**

### **Frontend (JavaScript)**
```javascript
async function uploadToHosting(file, newsData) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('filename', 'noticia-' + Date.now() + '.jpg');
    formData.append('titulo', newsData.titulo);
    formData.append('sub_titulo', newsData.sub_titulo);
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
            console.log('Archivo subido a hosting:', result.data.ruta);
            console.log('Link Laravel:', result.data.link_final);
            return result.data;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
```

### **Respuesta de la API**
```json
{
    "success": true,
    "message": "Archivo subido y noticia guardada exitosamente",
    "data": {
        "id": 1,
        "titulo": "Mi Noticia",
        "sub_titulo": "Subtítulo",
        "ruta": "https://tu-dominio.com/uploads/noticia-1234567890.jpg",
        "link_final": "https://tu-api-laravel.com/images/mi-noticia",
        "slug": "mi-noticia",
        "fecha_hora": "2024-01-01T10:00:00.000000Z",
        "user_id": 1
    }
}
```

## ⚠️ **Consideraciones de Seguridad**

1. **Credenciales FTP**: Mantén las credenciales seguras
2. **Permisos de carpeta**: Solo lectura pública (755)
3. **Validación de archivos**: El sistema valida tipos y tamaños
4. **Nombres únicos**: Evita conflictos de archivos

## 🎉 **¡Sistema Listo para Hosting Compartido!**

- **✅ Compatible con cPanel**: Usa credenciales FTP estándar
- **✅ Sin servidor dedicado**: Funciona en hosting compartido
- **✅ URLs públicas**: Archivos accesibles directamente
- **✅ Sistema robusto**: Manejo de errores y limpieza automática

**¡Perfecto para proyectos con hosting compartido!** 🎉
