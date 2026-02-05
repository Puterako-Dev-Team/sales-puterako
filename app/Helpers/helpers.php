<?php

if (!function_exists('toast')) {
    function toast(string $message, string $type = 'success')
    {
        session()->flash($type, $message);
    }
}

if (!function_exists('uploadFile')) {
    /**
     * Upload a single file using FileUploadService
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param string|null $customName
     * @param string $disk
     * @return array
     */
    function uploadFile(
        \Illuminate\Http\UploadedFile $file,
        string $folder = 'uploads',
        ?string $customName = null,
        string $disk = 'public'
    ): array {
        return app(\App\Services\FileUploadService::class)->upload($file, $folder, $customName, $disk);
    }
}

if (!function_exists('uploadFiles')) {
    /**
     * Upload multiple files using FileUploadService
     *
     * @param array $files
     * @param string $folder
     * @param string $disk
     * @return array
     */
    function uploadFiles(
        array $files,
        string $folder = 'uploads',
        string $disk = 'public'
    ): array {
        return app(\App\Services\FileUploadService::class)->uploadMultiple($files, $folder, $disk);
    }
}

if (!function_exists('deleteFile')) {
    /**
     * Delete a file using FileUploadService
     *
     * @param string $path
     * @param string $disk
     * @return array
     */
    function deleteFile(string $path, string $disk = 'public'): array
    {
        return app(\App\Services\FileUploadService::class)->delete($path, $disk);
    }
}

if (!function_exists('getFileUrl')) {
    /**
     * Get file URL using FileUploadService
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    function getFileUrl(string $path, string $disk = 'public'): string
    {
        return app(\App\Services\FileUploadService::class)->getFileUrl($path, $disk);
    }
}
