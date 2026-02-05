<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Allowed file types and their extensions
     */
    protected array $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
        'spreadsheet' => ['xls', 'xlsx', 'csv'],
        'archive' => ['zip', 'rar', '7z'],
        'presentation' => ['ppt', 'pptx'],
    ];

    /**
     * Maximum file size in KB for each type
     */
    protected array $maxSizes = [
        'image' => 5120,       // 5MB
        'document' => 10240,   // 10MB
        'spreadsheet' => 10240, // 10MB
        'archive' => 51200,    // 50MB
        'presentation' => 20480, // 20MB
        'default' => 10240,    // 10MB
    ];

    /**
     * Upload a single file
     *
     * @param UploadedFile $file
     * @param string $folder Folder name inside storage/app/public
     * @param string|null $customName Custom filename without extension
     * @param string $disk Storage disk to use
     * @return array
     */
    public function upload(
        UploadedFile $file,
        string $folder = 'uploads',
        ?string $customName = null,
        string $disk = 'public'
    ): array {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
            ];
        }

        // Generate filename
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        
        if ($customName) {
            $filename = Str::slug($customName) . '_' . time() . '.' . $extension;
        } else {
            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        }

        // Create folder path with date organization
        $datePath = date('Y/m');
        $fullPath = trim($folder, '/') . '/' . $datePath;

        try {
            // Store the file
            $path = $file->storeAs($fullPath, $filename, $disk);

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'path' => $path,
                    'url' => $this->getFileUrl($path, $disk),
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'extension' => $extension,
                    'size' => $file->getSize(),
                    'size_formatted' => $this->formatFileSize($file->getSize()),
                    'mime_type' => $file->getMimeType(),
                    'disk' => $disk,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload multiple files
     *
     * @param array $files Array of UploadedFile
     * @param string $folder
     * @param string $disk
     * @return array
     */
    public function uploadMultiple(
        array $files,
        string $folder = 'uploads',
        string $disk = 'public'
    ): array {
        $results = [
            'success' => true,
            'uploaded' => [],
            'failed' => [],
        ];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $result = $this->upload($file, $folder, null, $disk);
                
                if ($result['success']) {
                    $results['uploaded'][] = $result['data'];
                } else {
                    $results['failed'][] = [
                        'filename' => $file->getClientOriginalName(),
                        'message' => $result['message'],
                    ];
                }
            }
        }

        $results['success'] = count($results['failed']) === 0;
        $results['message'] = count($results['uploaded']) . ' file(s) uploaded successfully';
        
        if (count($results['failed']) > 0) {
            $results['message'] .= ', ' . count($results['failed']) . ' file(s) failed';
        }

        return $results;
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @param string $disk
     * @return array
     */
    public function delete(string $path, string $disk = 'public'): array
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                return [
                    'success' => true,
                    'message' => 'File deleted successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'File not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete multiple files
     *
     * @param array $paths
     * @param string $disk
     * @return array
     */
    public function deleteMultiple(array $paths, string $disk = 'public'): array
    {
        $deleted = 0;
        $failed = 0;

        foreach ($paths as $path) {
            $result = $this->delete($path, $disk);
            if ($result['success']) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "$deleted file(s) deleted" . ($failed > 0 ? ", $failed failed" : ''),
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    /**
     * Get file URL
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    public function getFileUrl(string $path, string $disk = 'public'): string
    {
        if ($disk === 'public') {
            return asset('storage/' . $path);
        }
        
        return Storage::disk($disk)->url($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file info
     *
     * @param string $path
     * @param string $disk
     * @return array|null
     */
    public function getFileInfo(string $path, string $disk = 'public'): ?array
    {
        if (!$this->exists($path, $disk)) {
            return null;
        }

        $size = Storage::disk($disk)->size($path);
        $lastModified = Storage::disk($disk)->lastModified($path);

        return [
            'path' => $path,
            'url' => $this->getFileUrl($path, $disk),
            'size' => $size,
            'size_formatted' => $this->formatFileSize($size),
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
        ];
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function validateFile(UploadedFile $file): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'message' => 'Invalid file upload',
            ];
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = $this->getFileType($extension);

        // Check if extension is allowed
        if (!$fileType) {
            return [
                'valid' => false,
                'message' => "File type '$extension' is not allowed",
            ];
        }

        // Check file size
        $maxSize = $this->maxSizes[$fileType] ?? $this->maxSizes['default'];
        $fileSizeKB = $file->getSize() / 1024;

        if ($fileSizeKB > $maxSize) {
            return [
                'valid' => false,
                'message' => "File size exceeds maximum allowed size of " . $this->formatFileSize($maxSize * 1024),
            ];
        }

        return [
            'valid' => true,
            'type' => $fileType,
        ];
    }

    /**
     * Get file type based on extension
     *
     * @param string $extension
     * @return string|null
     */
    protected function getFileType(string $extension): ?string
    {
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Format file size to human readable
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }
        
        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Get all allowed extensions as flat array
     *
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return array_merge(...array_values($this->allowedTypes));
    }

    /**
     * Get allowed extensions by type
     *
     * @param string $type
     * @return array
     */
    public function getAllowedExtensionsByType(string $type): array
    {
        return $this->allowedTypes[$type] ?? [];
    }

    /**
     * Set custom allowed types (override defaults)
     *
     * @param array $types
     * @return self
     */
    public function setAllowedTypes(array $types): self
    {
        $this->allowedTypes = $types;
        return $this;
    }

    /**
     * Add allowed extensions to a type
     *
     * @param string $type
     * @param array $extensions
     * @return self
     */
    public function addAllowedExtensions(string $type, array $extensions): self
    {
        if (!isset($this->allowedTypes[$type])) {
            $this->allowedTypes[$type] = [];
        }
        
        $this->allowedTypes[$type] = array_merge($this->allowedTypes[$type], $extensions);
        return $this;
    }

    /**
     * Set max file size for a type
     *
     * @param string $type
     * @param int $sizeInKB
     * @return self
     */
    public function setMaxSize(string $type, int $sizeInKB): self
    {
        $this->maxSizes[$type] = $sizeInKB;
        return $this;
    }
}
