<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RemoteFileUploadService
{
    protected $remoteServerUrl;
    protected $remoteApiKey;
    protected $remoteBaseUrl; // URL base del servidor remoto para archivos
    protected $ftpHost;
    protected $ftpUsername;
    protected $ftpPassword;
    protected $ftpPort;
    protected $ftpDirectory;
    protected $uploadMethod; // 'http' o 'ftp'
    protected $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    protected $maxFileSize = 5 * 1024 * 1024; // 5MB

    public function __construct()
    {
        $this->remoteServerUrl = config('filesystems.disks.remote_server.url');
        $this->remoteApiKey = config('filesystems.disks.remote_server.api_key');
        $this->remoteBaseUrl = config('filesystems.disks.remote_server.base_url');
        
        // Configuración FTP
        $this->ftpHost = config('filesystems.disks.remote_server.ftp_host');
        $this->ftpUsername = config('filesystems.disks.remote_server.ftp_username');
        $this->ftpPassword = config('filesystems.disks.remote_server.ftp_password');
        $this->ftpPort = config('filesystems.disks.remote_server.ftp_port', 21);
        $this->ftpDirectory = config('filesystems.disks.remote_server.ftp_directory', '/home/snowbyte/public_html/myimagesexample.solucionesgt360.com/myimages');
        $this->uploadMethod = config('filesystems.disks.remote_server.upload_method', 'local');
        
        // Validar configuraciones requeridas solo cuando se use
        // $this->validateConfiguration();
    }
    
    /**
     * Validate required configuration
     */
    protected function validateConfiguration()
    {
        // Debug: mostrar el método de subida
        \Log::info('Upload method: ' . $this->uploadMethod);
        
        if ($this->uploadMethod === 'http') {
            if (empty($this->remoteServerUrl)) {
                throw new \Exception('REMOTE_SERVER_URL is required for HTTP upload method');
            }
            if (empty($this->remoteApiKey)) {
                throw new \Exception('REMOTE_SERVER_API_KEY is required for HTTP upload method');
            }
            if (empty($this->remoteBaseUrl)) {
                throw new \Exception('REMOTE_SERVER_BASE_URL is required for HTTP upload method');
            }
        } elseif ($this->uploadMethod === 'ftp') {
            if (empty($this->ftpHost)) {
                throw new \Exception('REMOTE_SERVER_FTP_HOST is required for FTP upload method');
            }
            if (empty($this->ftpUsername)) {
                throw new \Exception('REMOTE_SERVER_FTP_USERNAME is required for FTP upload method');
            }
            if (empty($this->ftpPassword)) {
                throw new \Exception('REMOTE_SERVER_FTP_PASSWORD is required for FTP upload method');
            }
            if (empty($this->remoteBaseUrl)) {
                throw new \Exception('REMOTE_SERVER_BASE_URL is required for FTP upload method');
            }
        } elseif ($this->uploadMethod === 'local') {
            // Para método local no necesitamos validaciones adicionales
            // Solo verificar que la carpeta storage existe
            if (!is_dir(storage_path('app/public'))) {
                throw new \Exception('Storage directory not found. Run: php artisan storage:link');
            }
        } else {
            throw new \Exception('Invalid upload method. Must be "http", "ftp" or "local"');
        }
    }

    /**
     * Upload file to remote server
     *
     * @param UploadedFile|string $file - File or base64 string
     * @param string|null $filename - Custom filename (optional)
     * @return array
     */
    public function uploadFile($file, $filename = null)
    {
        try {
            // Validar configuraciones requeridas
            $this->validateConfiguration();
            
            // Handle base64 input
            if (is_string($file)) {
                $file = $this->handleBase64File($file, $filename);
            }

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $filename ?: $this->generateUniqueFilename($file);

            // Upload to storage
            if ($this->uploadMethod === 'local') {
                $uploadSuccess = $this->uploadToLocalStorage($file, $filename);
                $finalUrl = $uploadSuccess['url'];
            } elseif ($this->uploadMethod === 'ftp') {
                $uploadSuccess = $this->uploadViaFTP($file, $filename);
                if (!$uploadSuccess) {
                    throw new \Exception('Failed to upload file to remote server');
                }
                $finalUrl = rtrim($this->remoteBaseUrl, '/') . '/' . $filename;
            } else {
                $uploadSuccess = $this->uploadToRemoteServer($file, $filename);
                if (!$uploadSuccess) {
                    throw new \Exception('Failed to upload file to remote server');
                }
                $finalUrl = rtrim($this->remoteBaseUrl, '/') . '/' . $filename;
            }

            return [
                'success' => true,
                'filename' => $filename,
                'url' => $finalUrl,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle base64 file input
     */
    protected function handleBase64File($base64String, $filename = null)
    {
        // Remove data URL prefix if present
        if (strpos($base64String, 'data:') === 0) {
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
        }

        // Decode base64
        $fileData = base64_decode($base64String);
        
        if ($fileData === false) {
            throw new \Exception('Invalid base64 data');
        }

        // Create temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempPath, $fileData);

        // Get file extension from filename or detect from content
        $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : $this->detectFileExtension($fileData);
        
        if (!$extension) {
            throw new \Exception('Could not determine file extension');
        }

        // Create UploadedFile instance
        $uploadedFile = new UploadedFile(
            $tempPath,
            $filename ?: 'file.' . $extension,
            'image/' . $extension,
            null,
            true
        );

        return $uploadedFile;
    }

    /**
     * Detect file extension from content
     */
    protected function detectFileExtension($fileData)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $fileData);
        finfo_close($finfo);

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        return $mimeToExt[$mimeType] ?? null;
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile($file)
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $this->allowedExtensions));
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            throw new \Exception('File must be an image');
        }

        // Validar que el archivo sea realmente una imagen
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \Exception('File is not a valid image');
        }

        // Validar dimensiones máximas (opcional)
        $maxWidth = 4000;
        $maxHeight = 4000;
        if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
            throw new \Exception("Image dimensions exceed maximum allowed size of {$maxWidth}x{$maxHeight}px");
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);
        
        return "{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Upload file to remote server via HTTP API
     */
    protected function uploadToRemoteServer($file, $filename)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->remoteApiKey,
            'Accept' => 'application/json'
        ])->attach('file', $file->getContent(), $filename)
          ->post($this->remoteServerUrl . '/upload.php');

        if (!$response->successful()) {
            throw new \Exception('Failed to upload to remote server: ' . $response->body());
        }

        // Solo verificamos que la subida fue exitosa
        // No necesitamos la URL del servidor remoto ya que la construimos nosotros
        $data = $response->json();
        
        if (!isset($data['success']) || !$data['success']) {
            throw new \Exception('Remote server upload failed');
        }

        return true; // Solo retornamos éxito
    }

    /**
     * Upload file via FTP
     */
    protected function uploadViaFTP($file, $filename)
    {
        // Verificar si la extensión FTP está disponible
        if (!function_exists('ftp_connect')) {
            // Usar cURL como alternativa
            return $this->uploadViaFTPCurl($file, $filename);
        }
        
        $ftpConnection = null;
        $tempFile = null;
        
        try {
            // Create FTP connection
            $ftpConnection = ftp_connect($this->ftpHost, $this->ftpPort);
            
            if (!$ftpConnection) {
                throw new \Exception('Could not connect to FTP server');
            }

            // Login to FTP
            $login = ftp_login($ftpConnection, $this->ftpUsername, $this->ftpPassword);
            
            if (!$login) {
                ftp_close($ftpConnection);
                throw new \Exception('FTP login failed');
            }

            // Set passive mode (recommended for most hosting providers)
            ftp_pasv($ftpConnection, true);

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'ftp_upload_');
            file_put_contents($tempFile, $file->getContent());

            // Change to target directory
            if (!ftp_chdir($ftpConnection, $this->ftpDirectory)) {
                // Try to create directory if it doesn't exist
                $this->createFTPDirectory($ftpConnection, $this->ftpDirectory);
                ftp_chdir($ftpConnection, $this->ftpDirectory);
            }

            // Upload file
            $uploadResult = ftp_put($ftpConnection, $filename, $tempFile, FTP_BINARY);

            if (!$uploadResult) {
                throw new \Exception('FTP upload failed');
            }

            return true;

        } catch (\Exception $e) {
            throw new \Exception('FTP upload error: ' . $e->getMessage());
        } finally {
            // Clean up resources
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
            if ($ftpConnection) {
                ftp_close($ftpConnection);
            }
        }
    }
    
    /**
     * Upload file via FTP using cURL (alternative method)
     */
    protected function uploadViaFTPCurl($file, $filename)
    {
        $tempFile = null;
        
        try {
            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'ftp_upload_');
            file_put_contents($tempFile, $file->getContent());
            
            // Construir URL FTP
            $ftpUrl = "ftp://{$this->ftpHost}:{$this->ftpPort}{$this->ftpDirectory}/{$filename}";
            
            // Inicializar cURL
            $ch = curl_init();
            
            // Abrir archivo para lectura
            $fp = fopen($tempFile, 'r');
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $ftpUrl);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->ftpUsername}:{$this->ftpPassword}");
            curl_setopt($ch, CURLOPT_UPLOAD, 1);
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($tempFile));
            curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Ejecutar
            $result = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Cerrar recursos
            fclose($fp);
            curl_close($ch);
            
            if (!$result) {
                throw new \Exception('FTP upload via cURL failed: ' . $error);
            }
            
            return true;
            
        } catch (\Exception $e) {
            throw new \Exception('FTP cURL upload error: ' . $e->getMessage());
        } finally {
            // Clean up resources
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Create FTP directory recursively
     */
    protected function createFTPDirectory($ftpConnection, $directory)
    {
        $parts = explode('/', trim($directory, '/'));
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;
            
            if (!@ftp_chdir($ftpConnection, $currentPath)) {
                if (!@ftp_mkdir($ftpConnection, $currentPath)) {
                    throw new \Exception("Could not create directory: $currentPath");
                }
            }
        }
    }

    /**
     * Upload file to local storage
     */
    protected function uploadToLocalStorage($file, $filename)
    {
        try {
            // Store file in storage/app/public/images/
            $path = $file->storeAs('public/images', $filename);
            
            // Generate public URL
            $url = Storage::url($path);
            
            return [
                'success' => true,
                'path' => $path,
                'url' => $url
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Alternative method: Upload via SFTP (if available)
     */
    public function uploadViaSFTP($file, $filename)
    {
        // This would require additional configuration and SFTP library
        // For now, we'll use the HTTP method above
        return $this->uploadFile($file, $filename);
    }
}
