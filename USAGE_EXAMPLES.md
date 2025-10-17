# Ejemplos de Uso - API de Subida de Archivos

## 1. Subir Archivo con Multipart Form Data

### JavaScript (Frontend)
```javascript
// Función para subir archivo
async function uploadFile(file, customFilename = null) {
    const formData = new FormData();
    formData.append('file', file);
    
    if (customFilename) {
        formData.append('filename', customFilename);
    }

    try {
        const response = await fetch('/api/news/upload-file', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token')
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            console.log('Archivo subido:', result.data.url);
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
const fileInput = document.getElementById('fileInput');
fileInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (file) {
        const result = await uploadFile(file, 'mi-imagen-personalizada.jpg');
        if (result) {
            // Usar la URL para crear una noticia
            createNewsWithImage(result.url);
        }
    }
});
```

### cURL
```bash
curl -X POST \
  -H "Authorization: Bearer tu-token-aqui" \
  -F "file=@/ruta/a/tu/imagen.jpg" \
  -F "filename=imagen-noticia.jpg" \
  https://tu-api.com/api/news/upload-file
```

## 2. Subir Archivo con Base64

### JavaScript (Frontend)
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

// Subir archivo base64
async function uploadBase64File(file) {
    try {
        const base64 = await fileToBase64(file);
        
        const response = await fetch('/api/news/upload-base64', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                file: base64,
                filename: file.name
            })
        });

        const result = await response.json();
        
        if (result.success) {
            console.log('Archivo subido:', result.data.url);
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
```

## 3. Flujo Completo: Subir Archivo + Crear Noticia

### JavaScript (Frontend Completo)
```javascript
class NewsManager {
    constructor(apiBaseUrl, token) {
        this.apiBaseUrl = apiBaseUrl;
        this.token = token;
    }

    // Subir archivo
    async uploadFile(file, customFilename = null) {
        const formData = new FormData();
        formData.append('file', file);
        
        if (customFilename) {
            formData.append('filename', customFilename);
        }

        const response = await fetch(`${this.apiBaseUrl}/api/news/upload-file`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`
            },
            body: formData
        });

        return response.json();
    }

    // Crear noticia
    async createNews(newsData) {
        const response = await fetch(`${this.apiBaseUrl}/api/news`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newsData)
        });

        return response.json();
    }

    // Flujo completo: subir archivo y crear noticia
    async createNewsWithImage(file, newsData, customFilename = null) {
        try {
            // 1. Subir archivo
            console.log('Subiendo archivo...');
            const uploadResult = await this.uploadFile(file, customFilename);
            
            if (!uploadResult.success) {
                throw new Error(uploadResult.message);
            }

            // 2. Crear noticia con la URL del archivo
            console.log('Creando noticia...');
            const newsWithImage = {
                ...newsData,
                ruta: uploadResult.data.url // URL del archivo subido
            };

            const newsResult = await this.createNews(newsWithImage);
            
            if (!newsResult.success) {
                throw new Error(newsResult.message);
            }

            console.log('Noticia creada exitosamente:', newsResult.data);
            return newsResult.data;

        } catch (error) {
            console.error('Error en el proceso:', error.message);
            throw error;
        }
    }
}

// Uso
const newsManager = new NewsManager('https://tu-api.com', 'tu-token-aqui');

// Ejemplo de uso
document.getElementById('submitNews').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const file = document.getElementById('imageFile').files[0];
    const title = document.getElementById('title').value;
    const subtitle = document.getElementById('subtitle').value;
    const finalLink = document.getElementById('finalLink').value;
    const userId = document.getElementById('userId').value;

    if (!file) {
        alert('Por favor selecciona una imagen');
        return;
    }

    try {
        const result = await newsManager.createNewsWithImage(
            file,
            {
                titulo: title,
                sub_titulo: subtitle,
                link_final: finalLink,
                fecha_hora: new Date().toISOString(),
                user_id: parseInt(userId)
            },
            `noticia-${Date.now()}.jpg`
        );

        alert('Noticia creada exitosamente!');
        console.log('Noticia creada:', result);
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
```

## 4. HTML de Ejemplo

```html
<!DOCTYPE html>
<html>
<head>
    <title>Crear Noticia con Imagen</title>
</head>
<body>
    <form id="newsForm">
        <div>
            <label for="title">Título:</label>
            <input type="text" id="title" required>
        </div>
        
        <div>
            <label for="subtitle">Subtítulo:</label>
            <input type="text" id="subtitle" required>
        </div>
        
        <div>
            <label for="finalLink">Link Final:</label>
            <input type="url" id="finalLink" required>
        </div>
        
        <div>
            <label for="imageFile">Imagen:</label>
            <input type="file" id="imageFile" accept="image/*" required>
        </div>
        
        <div>
            <label for="userId">User ID:</label>
            <input type="number" id="userId" required>
        </div>
        
        <button type="submit" id="submitNews">Crear Noticia</button>
    </form>

    <script>
        // Aquí va el código JavaScript de arriba
    </script>
</body>
</html>
```

## 5. React Hook de Ejemplo

```javascript
import { useState } from 'react';

export const useFileUpload = (apiBaseUrl, token) => {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);

    const uploadFile = async (file, customFilename = null) => {
        setUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('file', file);
            
            if (customFilename) {
                formData.append('filename', customFilename);
            }

            const response = await fetch(`${apiBaseUrl}/api/news/upload-file`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }

            return result.data;
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setUploading(false);
        }
    };

    return { uploadFile, uploading, error };
};
```

## 6. Vue.js Composable

```javascript
import { ref } from 'vue';

export const useFileUpload = (apiBaseUrl, token) => {
    const uploading = ref(false);
    const error = ref(null);

    const uploadFile = async (file, customFilename = null) => {
        uploading.value = true;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('file', file);
            
            if (customFilename) {
                formData.append('filename', customFilename);
            }

            const response = await fetch(`${apiBaseUrl}/api/news/upload-file`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }

            return result.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            uploading.value = false;
        }
    };

    return { uploadFile, uploading, error };
};
```

## 7. Python (Requests)

```python
import requests
import base64

class NewsAPI:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {token}'
        }

    def upload_file(self, file_path, custom_filename=None):
        """Subir archivo usando multipart form data"""
        url = f"{self.base_url}/api/news/upload-file"
        
        with open(file_path, 'rb') as file:
            files = {'file': file}
            data = {}
            
            if custom_filename:
                data['filename'] = custom_filename
            
            response = requests.post(url, headers=self.headers, files=files, data=data)
            return response.json()

    def upload_base64(self, file_path, custom_filename):
        """Subir archivo usando base64"""
        url = f"{self.base_url}/api/news/upload-base64"
        
        with open(file_path, 'rb') as file:
            file_data = file.read()
            base64_data = base64.b64encode(file_data).decode('utf-8')
            
            payload = {
                'file': f'data:image/jpeg;base64,{base64_data}',
                'filename': custom_filename
            }
            
            response = requests.post(url, headers=self.headers, json=payload)
            return response.json()

# Uso
api = NewsAPI('https://tu-api.com', 'tu-token-aqui')

# Subir archivo
result = api.upload_file('/ruta/a/imagen.jpg', 'mi-imagen.jpg')
print(result)
```
