<?php

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected FileUploadService $uploadService;

    public function __construct(FileUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Upload a single file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'folder' => 'nullable|string|max:100',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'uploads');

        $result = $this->uploadService->upload($file, $folder);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Upload multiple files
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file',
            'folder' => 'nullable|string|max:100',
        ]);

        $files = $request->file('files');
        $folder = $request->input('folder', 'uploads');

        $result = $this->uploadService->uploadMultiple($files, $folder);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Delete a file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        $result = $this->uploadService->delete($path);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Delete multiple files
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
        ]);

        $paths = $request->input('paths');
        $result = $this->uploadService->deleteMultiple($paths);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get file info
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        $info = $this->uploadService->getFileInfo($path);

        if ($info) {
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found',
        ], 404);
    }

    /**
     * Download a file
     *
     * @param Request $request
     * @return mixed
     */
    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        
        if (!$this->uploadService->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return Storage::disk('public')->download($path);
    }

    /**
     * Get allowed file extensions
     *
     * @return JsonResponse
     */
    public function allowedExtensions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->uploadService->getAllowedExtensions(),
        ]);
    }
}
